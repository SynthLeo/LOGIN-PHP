-- ============================================================
--  MIGRACIÓN: tabla solicitudes (staging de atletas)
--  Ejecutar en la misma BD que las tablas principales
-- ============================================================

CREATE TABLE IF NOT EXISTS solicitudes (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  -- Identificación
  folio_unico         VARCHAR(20)  NOT NULL UNIQUE,
  estatus             ENUM('pendiente','revision','aprobada','rechazada')
                      NOT NULL DEFAULT 'pendiente',
  observaciones       TEXT         NULL,           -- nota del admin al rechazar/revisar

  -- Datos personales
  nombre              VARCHAR(80)  NOT NULL,
  apellido_paterno    VARCHAR(60)  NOT NULL,
  apellido_materno    VARCHAR(60)  NULL,
  curp                CHAR(18)     NOT NULL UNIQUE,
  genero              VARCHAR(20)  NOT NULL,
  fecha_nacimiento    DATE         NOT NULL,
  edad                TINYINT UNSIGNED NOT NULL,
  categoria           VARCHAR(20)  NOT NULL,
  domicilio           VARCHAR(200) NOT NULL,
  celular             VARCHAR(15)  NOT NULL,
  telefono_fijo       VARCHAR(15)  NULL,
  correo              VARCHAR(120) NULL,
  id_delegacion       TINYINT UNSIGNED NOT NULL,

  -- Menor de edad
  menor_edad          TINYINT(1)   NOT NULL DEFAULT 0,
  datos_tutor         JSON         NULL,           -- {nombre, telefono, correo}

  -- Perfil deportivo
  disciplina          VARCHAR(40)  NOT NULL,
  nivel               VARCHAR(20)  NULL,
  modalidad           VARCHAR(20)  NULL,

  -- Información médica
  tipo_sangre         VARCHAR(3)   NULL,
  factor_rh           VARCHAR(10)  NULL,
  alergias            TEXT         NULL,
  padecimientos       TEXT         NULL,
  seguro_tipo         VARCHAR(30)  NULL,

  -- Contacto de emergencia
  em_nombre_completo  VARCHAR(120) NOT NULL,
  em_parentesco       VARCHAR(40)  NOT NULL,
  em_tel_principal    VARCHAR(15)  NOT NULL,

  -- Auditoría
  creado_en           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                      ON UPDATE CURRENT_TIMESTAMP,
  aprobado_en         DATETIME     NULL,
  aprobado_por        INT          NULL    -- id del admin que aprobó

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices útiles para el dashboard
CREATE INDEX idx_sol_estatus   ON solicitudes (estatus);
CREATE INDEX idx_sol_curp      ON solicitudes (curp);
CREATE INDEX idx_sol_creado    ON solicitudes (creado_en DESC);
