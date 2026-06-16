-- ══════════════════════════════════════════════════════════════
--  schema.sql  —  Sistema Deportivo
--  Motor: MariaDB 10.6+
--  Ejecutar: mariadb -u root -p deporte < schema.sql
-- ══════════════════════════════════════════════════════════════

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ── USUARIOS ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS USUARIOS (
    id_usuario        INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    email             VARCHAR(180)     NOT NULL,
    password_hash     VARCHAR(255)     NOT NULL,
    rol               ENUM('admin','entrenador','atleta','visitante')
                                       NOT NULL DEFAULT 'atleta',
    activo            TINYINT(1)       NOT NULL DEFAULT 1,
    intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_hasta   DATETIME                  DEFAULT NULL,
    created_at        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── SESIONES ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS SESIONES (
    id_sesion     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario    INT UNSIGNED NOT NULL,
    token_sesion  TEXT         NOT NULL,
    inicio_sesion DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expira_en     DATETIME     NOT NULL,
    activa        TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id_sesion),
    KEY idx_sesiones_usuario (id_usuario),
    CONSTRAINT fk_sesiones_usuario
        FOREIGN KEY (id_usuario) REFERENCES USUARIOS (id_usuario)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── delegaciones  ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS delegaciones  (
    id_delegacion INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nombre        VARCHAR(100)  NOT NULL,
    tipo          VARCHAR(60)            DEFAULT NULL,
    PRIMARY KEY (id_delegacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Datos iniciales
INSERT IGNORE INTO delegaciones  (id_delegacion, nombre, tipo) VALUES
    (1, 'Cabecera',    'urbana'),
    (2, 'San Antonio', 'rural'),
    (3, 'Zolotepec',   'rural'),
    (4, 'Zapata',      'rural'),
    (5, 'Otra',        NULL);

-- ── informacion_medica ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS informacion_medica (
    id_informacion_medica INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    tipo_sangre           ENUM('A','B','AB','O') DEFAULT NULL,
    factor_rh             ENUM('Positivo','Negativo')  DEFAULT NULL,
    alergias              TEXT                   DEFAULT NULL,
    padecimientos         TEXT                   DEFAULT NULL,
    PRIMARY KEY (id_informacion_medica)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── seguro ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS seguro (
    id_seguro      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo           VARCHAR(60)  NOT NULL,
    estado         VARCHAR(30)           DEFAULT 'activo',
    no_afiliacion  VARCHAR(30)           DEFAULT NULL,
    PRIMARY KEY (id_seguro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── contactos_emerg. ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS contactos_emerg (
    id_contacto     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre_completo VARCHAR(150) NOT NULL,
    parentesco      VARCHAR(60)  NOT NULL,
    tel_principal   VARCHAR(20)  NOT NULL,
    PRIMARY KEY (id_contacto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── datos_personales ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS datos_personales (
    id_datos_personales   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    folio_unico           VARCHAR(20)   NOT NULL,
    curp                  CHAR(18)      NOT NULL,
    nombre                VARCHAR(100)  NOT NULL,
    apellido_paterno      VARCHAR(80)   NOT NULL,
    apellido_materno      VARCHAR(80)            DEFAULT NULL,
    genero                ENUM('Masculino','Femenino','Prefiero no decir') NOT NULL,
    fecha_nacimiento      DATE          NOT NULL,
    edad                  TINYINT UNSIGNED       DEFAULT NULL,
    menor_edad            TINYINT(1)    NOT NULL DEFAULT 0,
    datos_tutor           JSON                   DEFAULT NULL,  -- nombre, telefono, correo
    domicilio             VARCHAR(200)  NOT NULL,
    celular               VARCHAR(20)   NOT NULL,
    correo                VARCHAR(180)           DEFAULT NULL,
    -- FKs
    id_usuario            INT UNSIGNED  NOT NULL,
    id_delegacion         INT UNSIGNED  NOT NULL,
    id_informacion_medica INT UNSIGNED           DEFAULT NULL,
    id_contacto           INT UNSIGNED           DEFAULT NULL,
    id_seguro             INT UNSIGNED           DEFAULT NULL,
    created_at            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_datos_personales),
    UNIQUE KEY uq_curp (curp),
    UNIQUE KEY uq_folio (folio_unico),
    KEY idx_dp_usuario    (id_usuario),
    KEY idx_dp_delegacion (id_delegacion),
    CONSTRAINT fk_dp_usuario
        FOREIGN KEY (id_usuario)  REFERENCES USUARIOS (id_usuario),
    CONSTRAINT fk_dp_delegacion
        FOREIGN KEY (id_delegacion) REFERENCES delegaciones  (id_delegacion),
    CONSTRAINT fk_dp_medica
        FOREIGN KEY (id_informacion_medica) REFERENCES informacion_medica (id_informacion_medica),
    CONSTRAINT fk_dp_contacto
        FOREIGN KEY (id_contacto)  REFERENCES contactos_emerg (id_contacto),
    CONSTRAINT fk_dp_seguro
        FOREIGN KEY (id_seguro)    REFERENCES seguro (id_seguro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── perfil_deportivo ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS perfil_deportivo (
    id_perfil           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    disciplina          VARCHAR(60)  NOT NULL,
    nivel               VARCHAR(30)           DEFAULT NULL,
    modalidad           VARCHAR(30)           DEFAULT NULL,
    id_datos_personales INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_perfil),
    KEY idx_perfil_datos (id_datos_personales),
    CONSTRAINT fk_perfil_datos
        FOREIGN KEY (id_datos_personales) REFERENCES datos_personales (id_datos_personales)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── CATEGORIA ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS CATEGORIA (
    id_categoria INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo         VARCHAR(30)  NOT NULL,
    grupos       VARCHAR(60)           DEFAULT NULL,
    PRIMARY KEY (id_categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO CATEGORIA (tipo, grupos) VALUES
    ('Infantil',  'menores de 12'),
    ('Juvenil',   '12 a 17'),
    ('Libre',     '18 a 49'),
    ('Veteranos', '50 en adelante');

-- ── ENTRENADOR ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ENTRENADOR (
    id_entrenador    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nombre           VARCHAR(150)  NOT NULL,
    perfil           VARCHAR(100)           DEFAULT NULL,
    anios_experiencia TINYINT UNSIGNED      DEFAULT NULL,
    num_telefonico   VARCHAR(20)            DEFAULT NULL,
    direccion        VARCHAR(200)           DEFAULT NULL,
    id_delegacion    INT UNSIGNED           DEFAULT NULL,
    PRIMARY KEY (id_entrenador),
    CONSTRAINT fk_entr_delegacion
        FOREIGN KEY (id_delegacion) REFERENCES delegaciones  (id_delegacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── TALLERES ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS TALLERES (
    id_taller     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre        VARCHAR(100) NOT NULL,
    horario       VARCHAR(100)          DEFAULT NULL,
    tipo          VARCHAR(60)           DEFAULT NULL,
    id_entrenador INT UNSIGNED          DEFAULT NULL,
    id_delegacion INT UNSIGNED          DEFAULT NULL,
    PRIMARY KEY (id_taller),
    CONSTRAINT fk_taller_entr
        FOREIGN KEY (id_entrenador) REFERENCES ENTRENADOR (id_entrenador),
    CONSTRAINT fk_taller_del
        FOREIGN KEY (id_delegacion) REFERENCES delegaciones  (id_delegacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de unión USUARIOS ↔ TALLERES (Participa)
CREATE TABLE IF NOT EXISTS USUARIO_TALLER (
    id_usuario INT UNSIGNED NOT NULL,
    id_taller  INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_usuario, id_taller),
    CONSTRAINT fk_ut_usuario FOREIGN KEY (id_usuario) REFERENCES USUARIOS  (id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_ut_taller  FOREIGN KEY (id_taller)  REFERENCES TALLERES  (id_taller)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── LIGAS ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS LIGAS (
    id_liga       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre        VARCHAR(100) NOT NULL,
    cancha        VARCHAR(100)          DEFAULT NULL,
    disciplina    VARCHAR(60)           DEFAULT NULL,
    id_delegacion INT UNSIGNED          DEFAULT NULL,
    PRIMARY KEY (id_liga),
    CONSTRAINT fk_liga_del
        FOREIGN KEY (id_delegacion) REFERENCES delegaciones  (id_delegacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de unión USUARIOS ↔ LIGAS (Participa)
CREATE TABLE IF NOT EXISTS USUARIO_LIGA (
    id_usuario  INT UNSIGNED NOT NULL,
    id_liga     INT UNSIGNED NOT NULL,
    id_categoria INT UNSIGNED          DEFAULT NULL,
    PRIMARY KEY (id_usuario, id_liga),
    CONSTRAINT fk_ul_usuario  FOREIGN KEY (id_usuario)  REFERENCES USUARIOS  (id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_ul_liga     FOREIGN KEY (id_liga)     REFERENCES LIGAS     (id_liga)    ON DELETE CASCADE,
    CONSTRAINT fk_ul_categoria FOREIGN KEY (id_categoria) REFERENCES CATEGORIA (id_categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── APOYOS ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS APOYOS (
    id_apoyo   INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    tipo       VARCHAR(60)    NOT NULL,
    monto      DECIMAL(10,2)           DEFAULT NULL,
    promedio   DECIMAL(5,2)            DEFAULT NULL,
    fecha      DATE                    DEFAULT NULL,
    id_usuario INT UNSIGNED   NOT NULL,
    PRIMARY KEY (id_apoyo),
    KEY idx_apoyos_usuario (id_usuario),
    CONSTRAINT fk_apoyos_usuario
        FOREIGN KEY (id_usuario) REFERENCES USUARIOS (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
