USE sistema_academico;

CREATE TABLE IF NOT EXISTS solicitudes_estudiante (
    solicitud_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    estudiante_id BIGINT UNSIGNED NOT NULL,
    proyecto_id BIGINT UNSIGNED NULL,
    asunto VARCHAR(180) NOT NULL,
    detalle TEXT NOT NULL,
    estado ENUM('Pendiente', 'Aprobada', 'Rechazada') NOT NULL DEFAULT 'Pendiente',
    respuesta TEXT NULL,
    revisada_por BIGINT UNSIGNED NULL,
    fecha_solicitud TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_revision TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (solicitud_id),
    KEY ix_solicitudes_estudiante_estado (estudiante_id, estado, fecha_solicitud),
    KEY ix_solicitudes_estado_fecha (estado, fecha_solicitud),
    CONSTRAINT fk_solicitud_estudiante
        FOREIGN KEY (estudiante_id) REFERENCES estudiantes (estudiante_id),
    CONSTRAINT fk_solicitud_proyecto
        FOREIGN KEY (proyecto_id) REFERENCES proyectos (proyecto_id) ON DELETE SET NULL,
    CONSTRAINT fk_solicitud_revisor
        FOREIGN KEY (revisada_por) REFERENCES usuarios (usuario_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plantillas_notificacion (
    plantilla_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    tipo VARCHAR(40) NOT NULL,
    asunto VARCHAR(180) NOT NULL,
    mensaje TEXT NOT NULL,
    creada_por BIGINT UNSIGNED NOT NULL,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (plantilla_id),
    KEY ix_plantillas_tipo_activa (tipo, activa),
    CONSTRAINT fk_plantilla_usuario
        FOREIGN KEY (creada_por) REFERENCES usuarios (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notificaciones (
    notificacion_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
    plantilla_id BIGINT UNSIGNED NULL,
    asunto VARCHAR(180) NOT NULL,
    mensaje TEXT NOT NULL,
    fecha_lectura TIMESTAMP NULL DEFAULT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (notificacion_id),
    KEY ix_notificaciones_usuario_fecha (usuario_id, fecha_creacion),
    CONSTRAINT fk_notificacion_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (usuario_id) ON DELETE CASCADE,
    CONSTRAINT fk_notificacion_plantilla
        FOREIGN KEY (plantilla_id) REFERENCES plantillas_notificacion (plantilla_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS respuestas_cuestionario (
    respuesta_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo_cuestionario VARCHAR(180) NOT NULL,
    estudiante_id BIGINT UNSIGNED NOT NULL,
    proyecto_id BIGINT UNSIGNED NULL,
    actividad_id BIGINT UNSIGNED NULL,
    calificacion_general VARCHAR(80) NULL,
    fecha_respuesta TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (respuesta_id),
    KEY ix_respuestas_proyecto_fecha (proyecto_id, fecha_respuesta),
    KEY ix_respuestas_actividad_fecha (actividad_id, fecha_respuesta),
    KEY ix_respuestas_estudiante_fecha (estudiante_id, fecha_respuesta),
    CONSTRAINT fk_respuesta_estudiante
        FOREIGN KEY (estudiante_id) REFERENCES estudiantes (estudiante_id),
    CONSTRAINT fk_respuesta_proyecto
        FOREIGN KEY (proyecto_id) REFERENCES proyectos (proyecto_id) ON DELETE SET NULL,
    CONSTRAINT fk_respuesta_actividad
        FOREIGN KEY (actividad_id) REFERENCES actividades_proyecto (actividad_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS respuestas_cuestionario_detalle (
    detalle_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    respuesta_id BIGINT UNSIGNED NOT NULL,
    pregunta VARCHAR(500) NOT NULL,
    respuesta TEXT NOT NULL,
    PRIMARY KEY (detalle_id),
    KEY ix_respuestas_detalle_respuesta (respuesta_id),
    CONSTRAINT fk_respuesta_detalle
        FOREIGN KEY (respuesta_id) REFERENCES respuestas_cuestionario (respuesta_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documentos_emitidos (
    documento_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo ENUM('diploma', 'carta') NOT NULL,
    estudiante_id BIGINT UNSIGNED NOT NULL,
    proyecto_id BIGINT UNSIGNED NOT NULL,
    actividad_id BIGINT UNSIGNED NOT NULL,
    fecha_emision DATE NOT NULL,
    contenido JSON NOT NULL,
    generado_por BIGINT UNSIGNED NOT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (documento_id),
    UNIQUE KEY uq_documento_emitido (tipo, estudiante_id, actividad_id),
    KEY ix_documentos_emision (tipo, fecha_emision),
    CONSTRAINT fk_documento_emitido_estudiante
        FOREIGN KEY (estudiante_id) REFERENCES estudiantes (estudiante_id),
    CONSTRAINT fk_documento_emitido_proyecto
        FOREIGN KEY (proyecto_id) REFERENCES proyectos (proyecto_id),
    CONSTRAINT fk_documento_emitido_actividad
        FOREIGN KEY (actividad_id) REFERENCES actividades_proyecto (actividad_id),
    CONSTRAINT fk_documento_emitido_usuario
        FOREIGN KEY (generado_por) REFERENCES usuarios (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permisos (nombre_permiso, descripcion) VALUES
    ('portal.dashboard', 'Consultar el panel de proyección social'),
    ('actividades.calendario', 'Consultar el calendario de actividades'),
    ('respuestas.consultar', 'Consultar respuestas de cuestionarios'),
    ('solicitudes.crear', 'Crear solicitudes propias de proyección social'),
    ('solicitudes.consultar_propias', 'Consultar solicitudes propias'),
    ('solicitudes.revisar', 'Revisar solicitudes de estudiantes'),
    ('notificaciones.gestionar', 'Crear plantillas de notificación'),
    ('documentos.generar', 'Generar diplomas y cartas de participación');

INSERT IGNORE INTO roles_permisos (rol_id, permiso_id)
SELECT r.rol_id, p.permiso_id
FROM roles r
CROSS JOIN permisos p
WHERE r.nombre_rol = 'administrador'
  AND p.nombre_permiso IN (
      'portal.dashboard', 'actividades.calendario', 'respuestas.consultar',
      'solicitudes.crear', 'solicitudes.consultar_propias', 'solicitudes.revisar',
      'notificaciones.gestionar', 'documentos.generar'
  );

INSERT IGNORE INTO roles_permisos (rol_id, permiso_id)
SELECT r.rol_id, p.permiso_id
FROM roles r
CROSS JOIN permisos p
WHERE r.nombre_rol = 'coordinador'
  AND p.nombre_permiso IN (
      'portal.dashboard', 'actividades.calendario', 'respuestas.consultar',
      'solicitudes.revisar', 'notificaciones.gestionar', 'documentos.generar'
  );

INSERT IGNORE INTO roles_permisos (rol_id, permiso_id)
SELECT r.rol_id, p.permiso_id
FROM roles r
CROSS JOIN permisos p
WHERE r.nombre_rol = 'docente'
  AND p.nombre_permiso IN ('actividades.calendario', 'respuestas.consultar');

INSERT IGNORE INTO roles_permisos (rol_id, permiso_id)
SELECT r.rol_id, p.permiso_id
FROM roles r
CROSS JOIN permisos p
WHERE r.nombre_rol = 'estudiante'
  AND p.nombre_permiso IN ('solicitudes.crear', 'solicitudes.consultar_propias');

INSERT IGNORE INTO roles_permisos (rol_id, permiso_id)
SELECT r.rol_id, p.permiso_id
FROM roles r
CROSS JOIN permisos p
WHERE r.nombre_rol = 'estudiante'
  AND p.nombre_permiso = 'actividades.calendario';
