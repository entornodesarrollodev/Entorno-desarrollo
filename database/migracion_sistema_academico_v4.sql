USE sistema_academico;

SET @columna_existe = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'actividades_proyecto'
      AND column_name = 'fecha_limite_confirmacion'
);
SET @migracion_sql = IF(
    @columna_existe = 0,
    'ALTER TABLE actividades_proyecto ADD COLUMN fecha_limite_confirmacion DATETIME NULL AFTER fecha_fin',
    'DO 0'
);
PREPARE migracion_stmt FROM @migracion_sql;
EXECUTE migracion_stmt;
DEALLOCATE PREPARE migracion_stmt;

ALTER TABLE solicitudes_estudiante
    MODIFY COLUMN estado ENUM('Pendiente', 'Aprobada', 'Rechazada', 'Devuelta') NOT NULL DEFAULT 'Pendiente';

SET @columna_existe = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'notificaciones' AND column_name = 'estado_envio'
);
SET @migracion_sql = IF(
    @columna_existe = 0,
    'ALTER TABLE notificaciones ADD COLUMN estado_envio ENUM(''Pendiente'', ''Enviado'', ''Error'') NOT NULL DEFAULT ''Enviado''',
    'DO 0'
);
PREPARE migracion_stmt FROM @migracion_sql;
EXECUTE migracion_stmt;
DEALLOCATE PREPARE migracion_stmt;

SET @columna_existe = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'notificaciones' AND column_name = 'fecha_envio'
);
SET @migracion_sql = IF(
    @columna_existe = 0,
    'ALTER TABLE notificaciones ADD COLUMN fecha_envio TIMESTAMP NULL DEFAULT NULL',
    'DO 0'
);
PREPARE migracion_stmt FROM @migracion_sql;
EXECUTE migracion_stmt;
DEALLOCATE PREPARE migracion_stmt;

SET @columna_existe = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'notificaciones' AND column_name = 'error_envio'
);
SET @migracion_sql = IF(
    @columna_existe = 0,
    'ALTER TABLE notificaciones ADD COLUMN error_envio VARCHAR(500) NULL',
    'DO 0'
);
PREPARE migracion_stmt FROM @migracion_sql;
EXECUTE migracion_stmt;
DEALLOCATE PREPARE migracion_stmt;

UPDATE notificaciones SET fecha_envio = fecha_creacion WHERE estado_envio = 'Enviado' AND fecha_envio IS NULL;

CREATE TABLE IF NOT EXISTS evaluacion_criterios (
    criterio_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(160) NOT NULL,
    descripcion VARCHAR(1000) NULL,
    escala_min TINYINT UNSIGNED NOT NULL DEFAULT 1,
    escala_max TINYINT UNSIGNED NOT NULL DEFAULT 5,
    obligatorio TINYINT(1) NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_por BIGINT UNSIGNED NOT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (criterio_id),
    UNIQUE KEY uq_evaluacion_criterio_nombre (nombre),
    CONSTRAINT chk_evaluacion_criterio_escala CHECK (escala_max > escala_min),
    CONSTRAINT fk_evaluacion_criterio_creador FOREIGN KEY (creado_por) REFERENCES usuarios (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @columna_existe = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'evaluaciones_estudiante' AND column_name = 'periodo'
);
SET @migracion_sql = IF(
    @columna_existe = 0,
    'ALTER TABLE evaluaciones_estudiante ADD COLUMN periodo VARCHAR(80) NOT NULL DEFAULT ''General''',
    'DO 0'
);
PREPARE migracion_stmt FROM @migracion_sql;
EXECUTE migracion_stmt;
DEALLOCATE PREPARE migracion_stmt;

SET @columna_existe = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'evaluaciones_estudiante' AND column_name = 'estado_evaluacion'
);
SET @migracion_sql = IF(
    @columna_existe = 0,
    'ALTER TABLE evaluaciones_estudiante ADD COLUMN estado_evaluacion ENUM(''Borrador'', ''Cerrada'') NOT NULL DEFAULT ''Cerrada''',
    'DO 0'
);
PREPARE migracion_stmt FROM @migracion_sql;
EXECUTE migracion_stmt;
DEALLOCATE PREPARE migracion_stmt;

SET @columna_existe = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'evaluaciones_estudiante' AND column_name = 'cerrada_por'
);
SET @migracion_sql = IF(
    @columna_existe = 0,
    'ALTER TABLE evaluaciones_estudiante ADD COLUMN cerrada_por BIGINT UNSIGNED NULL',
    'DO 0'
);
PREPARE migracion_stmt FROM @migracion_sql;
EXECUTE migracion_stmt;
DEALLOCATE PREPARE migracion_stmt;

SET @columna_existe = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'evaluaciones_estudiante' AND column_name = 'fecha_cierre'
);
SET @migracion_sql = IF(
    @columna_existe = 0,
    'ALTER TABLE evaluaciones_estudiante ADD COLUMN fecha_cierre TIMESTAMP NULL DEFAULT NULL',
    'DO 0'
);
PREPARE migracion_stmt FROM @migracion_sql;
EXECUTE migracion_stmt;
DEALLOCATE PREPARE migracion_stmt;

CREATE TABLE IF NOT EXISTS evaluacion_criterios_resultados (
    resultado_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    evaluacion_id BIGINT UNSIGNED NOT NULL,
    criterio_id BIGINT UNSIGNED NULL,
    criterio_nombre VARCHAR(160) NOT NULL,
    escala_min TINYINT UNSIGNED NOT NULL,
    escala_max TINYINT UNSIGNED NOT NULL,
    obligatorio TINYINT(1) NOT NULL DEFAULT 1,
    puntuacion TINYINT UNSIGNED NULL,
    comentario VARCHAR(1000) NULL,
    PRIMARY KEY (resultado_id),
    UNIQUE KEY uq_evaluacion_resultado_criterio (evaluacion_id, criterio_nombre),
    CONSTRAINT chk_evaluacion_resultado_escala CHECK (escala_max > escala_min),
    CONSTRAINT fk_evaluacion_resultado_evaluacion FOREIGN KEY (evaluacion_id) REFERENCES evaluaciones_estudiante (evaluacion_id) ON DELETE CASCADE,
    CONSTRAINT fk_evaluacion_resultado_criterio FOREIGN KEY (criterio_id) REFERENCES evaluacion_criterios (criterio_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS respuestas_asistencia (
    respuesta_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actividad_id BIGINT UNSIGNED NOT NULL,
    estudiante_id BIGINT UNSIGNED NOT NULL,
    respuesta ENUM('Asistiré', 'No asistiré') NOT NULL,
    fecha_respuesta TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (respuesta_id),
    UNIQUE KEY uq_asistencia_actividad_estudiante (actividad_id, estudiante_id),
    KEY ix_asistencia_estudiante_fecha (estudiante_id, fecha_respuesta),
    CONSTRAINT fk_asistencia_actividad FOREIGN KEY (actividad_id) REFERENCES actividades_proyecto (actividad_id) ON DELETE CASCADE,
    CONSTRAINT fk_asistencia_estudiante FOREIGN KEY (estudiante_id) REFERENCES estudiantes (estudiante_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS solicitud_estudiante_historial (
    historial_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    solicitud_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NULL,
    estado_anterior VARCHAR(20) NULL,
    estado_nuevo VARCHAR(20) NULL,
    comentario TEXT NULL,
    accion VARCHAR(40) NOT NULL,
    fecha_cambio TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (historial_id),
    KEY ix_solicitud_historial_fecha (solicitud_id, fecha_cambio),
    CONSTRAINT fk_solicitud_historial_solicitud FOREIGN KEY (solicitud_id) REFERENCES solicitudes_estudiante (solicitud_id) ON DELETE CASCADE,
    CONSTRAINT fk_solicitud_historial_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (usuario_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS solicitud_estudiante_adjuntos (
    adjunto_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    solicitud_id BIGINT UNSIGNED NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    clave_almacenamiento CHAR(64) NOT NULL,
    tipo_mime VARCHAR(100) NOT NULL,
    tamano_bytes INT UNSIGNED NOT NULL,
    subido_por BIGINT UNSIGNED NOT NULL,
    fecha_subida TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (adjunto_id),
    UNIQUE KEY uq_adjunto_clave (clave_almacenamiento),
    KEY ix_adjunto_solicitud (solicitud_id, fecha_subida),
    CONSTRAINT fk_solicitud_adjunto_solicitud FOREIGN KEY (solicitud_id) REFERENCES solicitudes_estudiante (solicitud_id) ON DELETE CASCADE,
    CONSTRAINT fk_solicitud_adjunto_usuario FOREIGN KEY (subido_por) REFERENCES usuarios (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS avisos_privacidad (
    aviso_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    version VARCHAR(40) NOT NULL,
    contenido MEDIUMTEXT NOT NULL,
    vigente TINYINT(1) NOT NULL DEFAULT 0,
    requiere_aceptacion TINYINT(1) NOT NULL DEFAULT 1,
    creado_por BIGINT UNSIGNED NOT NULL,
    fecha_publicacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (aviso_id),
    UNIQUE KEY uq_aviso_privacidad_version (version),
    CONSTRAINT fk_aviso_privacidad_creador FOREIGN KEY (creado_por) REFERENCES usuarios (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS aceptaciones_privacidad (
    aceptacion_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    aviso_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    fecha_aceptacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (aceptacion_id),
    UNIQUE KEY uq_aceptacion_usuario_aviso (usuario_id, aviso_id),
    KEY ix_aceptacion_usuario_fecha (usuario_id, fecha_aceptacion),
    CONSTRAINT fk_aceptacion_aviso FOREIGN KEY (aviso_id) REFERENCES avisos_privacidad (aviso_id),
    CONSTRAINT fk_aceptacion_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (usuario_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS actividad_notificaciones_envio (
    envio_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actividad_id BIGINT UNSIGNED NOT NULL,
    plantilla_id BIGINT UNSIGNED NULL,
    canal ENUM('Interno') NOT NULL DEFAULT 'Interno',
    estado ENUM('Enviado', 'Sin destinatarios', 'Sin plantilla', 'Error') NOT NULL,
    destinatarios INT UNSIGNED NOT NULL DEFAULT 0,
    detalle_error VARCHAR(500) NULL,
    fecha_intento TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (envio_id),
    KEY ix_envio_actividad_fecha (actividad_id, fecha_intento),
    CONSTRAINT fk_envio_actividad FOREIGN KEY (actividad_id) REFERENCES actividades_proyecto (actividad_id) ON DELETE CASCADE,
    CONSTRAINT fk_envio_plantilla FOREIGN KEY (plantilla_id) REFERENCES plantillas_notificacion (plantilla_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permisos (nombre_permiso, descripcion) VALUES
    ('evaluaciones.crear', 'Crear y cerrar evaluaciones de participación'),
    ('evaluaciones.criterios', 'Administrar criterios de evaluación'),
    ('asistencia.responder', 'Confirmar o declinar asistencia propia'),
    ('asistencia.consultar', 'Consultar las respuestas de asistencia'),
    ('privacidad.gestionar', 'Administrar avisos de privacidad'),
    ('privacidad.consultar', 'Consultar el aviso de privacidad vigente');

INSERT IGNORE INTO roles_permisos (rol_id, permiso_id)
SELECT r.rol_id, p.permiso_id
FROM roles r CROSS JOIN permisos p
WHERE (r.nombre_rol = 'administrador' AND p.nombre_permiso IN (
    'evaluaciones.crear', 'evaluaciones.criterios', 'asistencia.consultar',
    'privacidad.gestionar', 'privacidad.consultar'
)) OR (r.nombre_rol = 'coordinador' AND p.nombre_permiso IN (
    'evaluaciones.crear', 'evaluaciones.criterios', 'asistencia.consultar',
    'privacidad.consultar'
)) OR (r.nombre_rol = 'docente' AND p.nombre_permiso IN (
    'evaluaciones.crear', 'asistencia.consultar', 'privacidad.consultar'
)) OR (r.nombre_rol = 'estudiante' AND p.nombre_permiso IN (
    'asistencia.responder', 'privacidad.consultar'
));
