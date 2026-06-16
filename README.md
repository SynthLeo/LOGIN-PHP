# Sistema Deportivo — API Backend (PHP + MariaDB)

API REST en PHP puro (sin framework) para la gestión de atletas, solicitudes de registro, autenticación y estadísticas de un programa deportivo municipal. Usa **MariaDB/MySQL** como base de datos, autenticación por **JWT** y arquitectura por **namespaces PHP** (`Configuracion`, `Seguridad`).

---

## Tabla de contenido

- [Stack tecnológico](#stack-tecnológico)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Modelo de datos](#modelo-de-datos)
- [Instalación](#instalación)
- [Variables de entorno (`.env`)](#variables-de-entorno-env)
- [Flujo de autenticación (JWT)](#flujo-de-autenticación-jwt)
- [Endpoints de la API](#endpoints-de-la-api)
  - [Auth](#auth)
  - [Atletas](#atletas)
  - [Solicitudes](#solicitudes)
  - [Dashboard](#dashboard)
- [Flujo de aprobación de solicitudes](#flujo-de-aprobación-de-solicitudes)
- [Seguridad implementada](#seguridad-implementada)
- [Notas y pendientes conocidos](#notas-y-pendientes-conocidos)

---

## Stack tecnológico

| Componente   | Tecnología                                  |
|--------------|----------------------------------------------|
| Lenguaje     | PHP 8+ (`declare(strict_types=1)`, namespaces) |
| Base de datos| MariaDB 10.6+ / MySQL (PDO)                  |
| Auth         | JWT propio (HMAC-SHA256, sin librerías externas) |
| Servidor     | Apache (usa `.htaccess` para reenviar el header `Authorization`) |
| Formato      | JSON puro (`application/json`) en cada request/response |

No usa Composer ni dependencias externas: todo el JWT, CORS y conexión a base de datos están implementados a mano dentro de `Seguridad/` y `Configuracion/`.

---

## Estructura del proyecto

```
.
├── .htaccess                          # Reenvía el header Authorization a PHP
├── Configuracion/
│   ├── Database.php                   # Singleton PDO (getDB())
│   └── Entorno.php                    # Carga .env y expone Entorno::get()
├── Seguridad/
│   ├── Autenticacion.php              # Middleware: exige JWT válido
│   ├── Cors.php                       # Cabeceras CORS según ALLOWED_ORIGIN
│   └── JWT.php                        # Generación/verificación de JWT (HS256)
├── auth/
│   └── login.php                      # POST — login con bloqueo por intentos fallidos
├── atletas/
│   ├── registro.php                   # POST — alta directa de atleta (requiere JWT)
│   ├── listar.php                     # GET  — listado con filtros (requiere JWT)
│   └── eliminar.php                   # DELETE — borrado en cascada (requiere JWT)
├── solicitudes/
│   ├── crear.php                      # POST — formulario público de inscripción
│   ├── estatus.php                    # GET  — consulta pública de estatus (semáforo)
│   ├── listar.php                     # GET  — listado paginado (requiere JWT admin)
│   ├── actualizar_estatus.php         # PATCH — revisión/rechazo (requiere JWT admin)
│   ├── aprobar.php                    # PATCH — aprueba y migra a tablas reales (requiere JWT admin)
│   └── 001_crear_solicitudes.sql      # Migración de la tabla `solicitudes`
├── dashboard/
│   └── stats.php                      # GET — estadísticas agregadas (requiere JWT)
├── schema.sql                         # Esquema limpio recomendado para crear la BD desde cero
├── seed.sql                           # Usuario admin de prueba
├── deporte.sql                        # Dump completo de la BD (estructura + datos)
├── NEW.sql                            # Migración de `solicitudes` (idéntica a 001_crear_solicitudes.sql)
└── respaldo.sql                       # Backup/dump alterno de la BD (misma estructura que deporte.sql)
```

> **Nota:** `deporte.sql`, `respaldo.sql`, `schema.sql`, `NEW.sql` y `solicitudes/001_crear_solicitudes.sql` se superponen. Para un entorno **nuevo**, usa `schema.sql` + `001_crear_solicitudes.sql` + `seed.sql`. Los archivos `deporte.sql` / `respaldo.sql` son *dumps* completos (con datos de prueba reales) útiles solo como referencia o respaldo.

---

## Modelo de datos

Tablas principales y relaciones (FK):

```
usuarios ─┬─< sesiones
          ├─< apoyos
          └─< datos_personales >── delegaciones
                    │  │  │
                    │  │  └──> seguro (nullable)
                    │  └─────> contactos_emerg
                    └────────> informacion_medica
                    │
                    └─< perfil_deportivo (ON DELETE CASCADE)

entrenador ─< talleres ─< usuario_taller >─ usuarios
delegaciones ─< ligas ─< usuario_liga >─ usuarios / categoria

solicitudes  (tabla independiente, "staging" — sin FKs hacia las tablas anteriores)
```

### Tablas clave

- **`usuarios`**: credenciales, rol (`admin`, `entrenador`, `atleta`, `visitante`), control de bloqueo por intentos fallidos.
- **`sesiones`**: histórico de tokens JWT emitidos por login (auditoría, no se usa para invalidar tokens activamente).
- **`datos_personales`**: tabla central del atleta. Incluye columna **virtual generada** `categoria` calculada automáticamente a partir de `edad`:
  - `< 12` → `Infantil`
  - `< 18` → `Juvenil`
  - `>= 50` → `Veteranos`
  - resto → `Libre`
- **`perfil_deportivo`**: disciplina/nivel/modalidad del atleta (`ON DELETE CASCADE` con `datos_personales`).
- **`informacion_medica`**, **`contactos_emerg`**, **`seguro`**: entidades relacionadas 1-a-1 con el atleta (la última es opcional/nullable).
- **`solicitudes`**: tabla de *staging* pública donde se reciben inscripciones antes de ser revisadas/aprobadas por un admin. No tiene relación FK con las tablas reales; al aprobarse, sus datos se **copian** mediante `solicitudes/aprobar.php`.
- **`delegaciones`**, **`categoria`**, **`entrenador`**, **`talleres`**, **`ligas`**, **`apoyos`**: catálogos y tablas de apoyo no cubiertas aún por endpoints en este repo.

---

## Instalación

### 1. Requisitos

- PHP 8.0+ con extensión `pdo_mysql`
- MariaDB 10.6+ o MySQL compatible
- Apache con `mod_rewrite` habilitado (para que `.htaccess` reenvíe `Authorization`)

### 2. Clonar y configurar

```bash
git clone <url-del-repo>
cd <carpeta-del-repo>
```

### 3. Crear la base de datos

**Opción A — desde cero (recomendado):**

```bash
mysql -u root -p -e "CREATE DATABASE deporte CHARACTER SET utf8mb4;"
mysql -u root -p deporte < schema.sql
mysql -u root -p deporte < solicitudes/001_crear_solicitudes.sql
mysql -u root -p deporte < seed.sql
```

**Opción B — restaurar un dump existente:**

```bash
mysql -u root -p deporte < deporte.sql
```

### 4. Crear el archivo `.env`

En la raíz del proyecto (mismo nivel que `Configuracion/`):

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=deporte
DB_USER=root
DB_PASS=

JWT_SECRET=cambia_esto_por_una_cadena_larga_y_aleatoria
JWT_EXPIRY=3600

ALLOWED_ORIGIN=http://localhost:5173

LOGIN_MAX_INTENTOS=5
LOGIN_BLOQUEO_SEGUNDOS=900
```

### 5. Levantar el servidor

```bash
php -S localhost:8000
```

o configúralo como `DocumentRoot` en Apache/XAMPP/Laragon.

### 6. Usuario administrador de prueba

Definido en `seed.sql` / `deporte.sql`:

```
email:    admin@deporte.mx
password: Admin2026!
```

---

## Variables de entorno (`.env`)

`Configuracion/Entorno.php` carga el `.env` manualmente línea por línea (sin librerías), ignorando comentarios (`#`) y líneas vacías.

| Variable                  | Descripción                                              | Default            |
|----------------------------|-----------------------------------------------------------|---------------------|
| `DB_HOST`                 | Host de la base de datos                                  | `127.0.0.1`         |
| `DB_PORT`                 | Puerto                                                     | `3306`               |
| `DB_NAME`                 | Nombre de la base de datos                                | `deporte`            |
| `DB_USER`                 | Usuario de la BD                                          | `root`               |
| `DB_PASS`                 | Contraseña de la BD                                       | *(vacío)*            |
| `JWT_SECRET`               | Secreto para firmar los JWT (HMAC-SHA256)                  | *(vacío — debe configurarse)* |
| `JWT_EXPIRY`               | Expiración del token en segundos                           | `3600`               |
| `ALLOWED_ORIGIN`           | Origen permitido para CORS (frontend)                      | `http://localhost:5173` |
| `LOGIN_MAX_INTENTOS`       | Intentos fallidos antes de bloquear la cuenta               | `5`                   |
| `LOGIN_BLOQUEO_SEGUNDOS`   | Duración del bloqueo de cuenta en segundos                  | `900` (15 min)        |

---

## Flujo de autenticación (JWT)

1. El cliente hace `POST /auth/login.php` con `{ "email", "password" }`.
2. El servidor valida credenciales contra `usuarios`. Si falla, incrementa `intentos_fallidos`; al llegar a `LOGIN_MAX_INTENTOS` bloquea la cuenta `LOGIN_BLOQUEO_SEGUNDOS` segundos (`bloqueado_hasta`).
3. Si las credenciales son correctas, se genera un JWT (`Seguridad/JWT.php`, HS256 manual) con payload `{ sub: id_usuario, rol, iat, exp }`.
4. El token se registra en `sesiones` (auditoría) y se devuelve al cliente como `{ "token", "rol" }`.
5. El cliente debe enviar el token en cada request protegido:

   ```
   Authorization: Bearer <token>
   ```

6. Cada endpoint protegido llama a `Autenticacion::requerir()`, que valida el JWT (firma + expiración) y expone el id del usuario autenticado en `$GLOBALS['userId']`.

> El `.htaccess` reenvía explícitamente el header `Authorization` a PHP vía `RewriteRule` con `E=HTTP_AUTHORIZATION`, ya que algunos servidores Apache/CGI lo eliminan por defecto.

---

## Endpoints de la API

Todas las respuestas son JSON. Los endpoints protegidos requieren `Authorization: Bearer <token>` y devuelven `401` si el token es inválido/expirado.

### Auth

#### `POST /auth/login.php`
Público.

```json
// Request
{ "email": "admin@deporte.mx", "password": "Admin2026!" }

// Response 200
{ "token": "eyJ...", "rol": "admin" }
```

Respuestas de error: `400` (faltan campos / email inválido), `401` (credenciales incorrectas / cuenta inactiva), `429` (cuenta bloqueada temporalmente).

---

### Atletas

#### `GET /atletas/listar.php`
Protegido. Devuelve todos los atletas con sus datos relacionados (perfil deportivo, info médica, contacto de emergencia, seguro, delegación).

Query params opcionales:
- `disciplina` — filtra por disciplina exacta
- `categoria` — filtra por categoría exacta
- `busqueda` — busca por nombre, apellido paterno, CURP o folio (`LIKE`)

```json
// Response 200
{
  "total": 1,
  "atletas": [
    {
      "id_datos_personales": 1,
      "folio_unico": "ATL-2026-8047",
      "nombre": "LEONARDO",
      "apellido_paterno": "Manuel",
      "curp": "MAML090130HMCNRNA1",
      "edad": 26,
      "menor_edad": false,
      "datos_tutor": null,
      "disciplina": "Voleibol",
      "delegacion": "Cabecera",
      "...": "..."
    }
  ]
}
```

#### `POST /atletas/registro.php`
Protegido. Alta directa de un atleta (uso administrativo, sin pasar por `solicitudes`).

Campos requeridos en el body: `nombre`, `apellido_paterno`, `curp` (18 caracteres), `genero`, `fecha_nacimiento` (`YYYY-MM-DD`), `domicilio`, `id_delegacion`, `celular`, `disciplina`, `em_nombre_completo`, `em_parentesco`, `em_tel_principal`. Si `menor_edad` es verdadero, además requiere `datos_tutor.{nombre,telefono,correo}`.

```json
// Response 201
{ "message": "Atleta registrado correctamente.", "folio": "ATL-2026-XXXX", "id_atleta": 12 }
```

Crea en una sola transacción: `informacion_medica` → `seguro` (si aplica) → `contactos_emerg` → `datos_personales` → `perfil_deportivo`. Responde `409` si la CURP ya existe.

#### `DELETE /atletas/eliminar.php?id=123`
Protegido. Elimina al atleta y sus registros relacionados en cascada dentro de una transacción: `perfil_deportivo` → `datos_personales` → `informacion_medica` → `contactos_emerg` → `seguro`.

```json
// Response 200
{ "message": "Atleta eliminado correctamente.", "id": 123 }
```

---

### Solicitudes

Sistema de *staging* para que el público se inscriba sin crear directamente un atleta; un administrador revisa y aprueba/rechaza.

#### `POST /solicitudes/crear.php`
**Público.** Recibe el formulario de inscripción y lo guarda con `estatus = 'pendiente'`.

Campos requeridos: `folio_unico`, `nombre`, `apellido_paterno`, `curp`, `genero`, `fecha_nacimiento`, `edad`, `categoria`, `domicilio`, `celular`, `id_delegacion`, `disciplina`, `em_nombre_completo`, `em_parentesco`, `em_tel_principal`. Devuelve `409` si ya existe una solicitud con esa CURP o folio.

```json
// Response 201
{ "message": "Solicitud enviada correctamente.", "folio_unico": "ATL-2026-XXXX", "estatus": "pendiente" }
```

#### `GET /solicitudes/estatus.php?folio=ATL-2026-1234`
**Público.** También acepta `?curp=...`. Devuelve un "semáforo" de estatus para que el solicitante consulte su avance.

```json
// Response 200
{
  "folio_unico": "ATL-2026-1234",
  "nombre": "Juan Pérez",
  "estatus": "revision",
  "semaforo": { "color": "amarillo", "icono": "🟡", "etiqueta": "En revisión por el equipo IMDET" },
  "observaciones": null,
  "creado_en": "2026-06-01 10:00:00",
  "aprobado_en": null
}
```

#### `GET /solicitudes/listar.php`
Protegido (admin). Lista paginada con conteos por estatus para tabs del dashboard.

Query params: `estatus` (`pendiente`/`revision`/`aprobada`/`rechazada`), `pagina`, `q` (búsqueda libre).

```json
// Response 200
{
  "solicitudes": [ /* ... */ ],
  "total": 42,
  "pagina": 1,
  "total_paginas": 3,
  "conteos": { "pendiente": 10, "revision": 5, "aprobada": 20, "rechazada": 7, "todos": 42 }
}
```

#### `PATCH /solicitudes/actualizar_estatus.php`
Protegido (admin). Cambia el estatus a `pendiente`, `revision` o `rechazada` (**no** se usa para aprobar). Si el nuevo estatus es `rechazada`, `observaciones` es obligatorio.

```json
// Request
{ "id": 42, "estatus": "rechazada", "observaciones": "CURP ilegible, favor de reenviar." }
```

Devuelve `409` si la solicitud ya está `aprobada`.

#### `PATCH /solicitudes/aprobar.php`
Protegido (admin). Migra la solicitud a las tablas reales dentro de una transacción completa:

1. Crea cuenta en `usuarios` (rol `atleta`) con contraseña temporal aleatoria. Si no hay correo, genera uno con el CURP (`@sinregistro.local`).
2. Inserta en `informacion_medica`, `seguro` (si aplica), `contactos_emerg`.
3. Inserta en `datos_personales` y `perfil_deportivo`.
4. Marca la solicitud como `aprobada`, con `aprobado_en` y `aprobado_por` (id del admin).

```json
// Request
{ "id": 42 }

// Response 200
{
  "message": "Solicitud aprobada y atleta registrado correctamente.",
  "folio_unico": "ATL-2026-1234",
  "id_datos_personales": 15,
  "id_usuario": 22,
  "email_generado": "maml090130hmcnrna1@sinregistro.local",
  "password_temporal": "a1b2c3d4e5f6"
}
```

> ⚠️ La `password_temporal` se devuelve **en texto plano** en la respuesta; debe comunicarse al atleta por un canal seguro (no queda almacenada en texto plano en la BD, solo su hash).

---

### Dashboard

#### `GET /dashboard/stats.php`
Protegido. Estadísticas generales del sistema.

```json
// Response 200
{
  "total_atletas": 120,
  "menores": 35,
  "disciplinas": 8,
  "delegaciones ": 5,
  "por_disciplina": [ { "disciplina": "Voleibol", "total": 30 } ],
  "por_categoria": [ { "categoria": "Libre", "total": 60 } ],
  "recientes": [ { "folio_unico": "ATL-2026-8047", "nombre": "LEONARDO", "...": "..." } ]
}
```

> Nota: la clave `"delegaciones "` se devuelve con un espacio final tal como está en el código fuente actual (ver [Notas y pendientes](#notas-y-pendientes-conocidos)).

---

## Flujo de aprobación de solicitudes

```
Público                Admin
   │                      │
   ├─ POST crear.php ────►│  (estatus = pendiente)
   │                      │
   │       GET estatus.php (consulta pública con folio/CURP)
   │                      │
   │                      ├─ GET listar.php (revisa solicitudes)
   │                      ├─ PATCH actualizar_estatus.php → revision / rechazada
   │                      └─ PATCH aprobar.php → crea usuario + atleta real
   │                                 │
   │                                 ▼
   │                    usuarios / datos_personales / perfil_deportivo / etc.
```

---

## Seguridad implementada

- **Contraseñas**: `password_hash()` / `password_verify()` (bcrypt).
- **JWT propio**: HMAC-SHA256, payload con `sub`, `rol`, `iat`, `exp`; verificación de firma con `hash_equals()` (comparación segura contra *timing attacks*).
- **Bloqueo de cuenta** tras múltiples intentos fallidos de login (configurable vía `.env`).
- **CORS restringido** a un único origen (`ALLOWED_ORIGIN`), no a `*`, en los endpoints que usan `Seguridad/Cors.php`.
- **Prepared statements (PDO)** en todas las queries con parámetros de usuario — sin concatenación de SQL.
- **Transacciones** en todas las operaciones multi-tabla (registro, eliminación, aprobación de solicitudes).
- **CURP/folio únicos** a nivel de base de datos (`UNIQUE KEY`) para evitar duplicados, manejado explícitamente como error `409`.

---

## Notas y pendientes conocidos

Estos puntos se documentan tal como están en el código fuente actual, para que cualquier colaborador los tenga en cuenta:

- **CORS inconsistente**: `Seguridad/Cors.php` valida `ALLOWED_ORIGIN` correctamente, pero `solicitudes/*.php` y `auth/login.php` (los archivos sin `Cors::aplicar()`) usan `Access-Control-Allow-Origin: *` directamente — revisar antes de producción si se requiere el mismo nivel de restricción.
- **Mensajes de error detallados en producción**: varios endpoints (`atletas/listar.php`, `dashboard/stats.php`, `solicitudes/aprobar.php`) tienen `error_reporting(E_ALL)` y `display_errors=1` activos, y algunos devuelven `$e->getMessage()` directamente en la respuesta JSON. Esto es útil en desarrollo pero **debe desactivarse en producción** para no filtrar detalles internos.
- **Espacios en nombres de columnas/claves**: en varias partes del código (`datos_personales `, `delegaciones `, claves del JSON de `dashboard/stats.php`) hay espacios en blanco accidentales al final de nombres. Funciona porque es consistente en todo el código, pero conviene limpiarlo en una refactorización.
- **Archivos SQL redundantes**: `deporte.sql`, `respaldo.sql`, `NEW.sql` y `schema.sql` se solapan en contenido. Se recomienda consolidar en un solo `schema.sql` + migraciones versionadas (`001_...`, `002_...`) y dejar de mantener dumps completos en el repositorio.
- **Sin invalidación activa de sesiones**: la tabla `sesiones` registra tokens emitidos pero no se usa para revocar tokens antes de su expiración natural (no hay endpoint de logout que marque `activa = 0`).
- **`solicitudes` no referencia el catálogo de `delegaciones`** con una FK real (se guarda como `TINYINT` suelto), a diferencia de `datos_personales`.
