CREATE DATABASE IF NOT EXISTS sistema_academico
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sistema_academico;

-- Correos y carnets unicos, asi evitamos cuentas repetidas.
CREATE TABLE IF NOT EXISTS usuarios (
    usuario_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre_completo VARCHAR(150) NOT NULL,
    correo VARCHAR(254) NOT NULL,
    carnet VARCHAR(32) NULL,
    clave_hash VARCHAR(255) NOT NULL,
    estado_activo TINYINT(1) NOT NULL DEFAULT 1,
    carrera VARCHAR(100) NULL,
    anio_academico TINYINT UNSIGNED NULL,
    modalidad_participacion ENUM('Voluntariado', 'Horas sociales', 'Práctica profesional') NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id),
    UNIQUE KEY uq_usuarios_correo (correo),
    UNIQUE KEY uq_usuarios_carnet (carnet),
    CONSTRAINT chk_usuarios_estado CHECK (estado_activo IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles (
    rol_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre_rol VARCHAR(50) NOT NULL,
    etiqueta VARCHAR(100) NOT NULL,
    PRIMARY KEY (rol_id),
    UNIQUE KEY uq_roles_nombre (nombre_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permisos (
    permiso_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre_permiso VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    PRIMARY KEY (permiso_id),
    UNIQUE KEY uq_permisos_nombre (nombre_permiso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios_roles (
    usuario_id BIGINT UNSIGNED NOT NULL,
    rol_id BIGINT UNSIGNED NOT NULL,
    fecha_asignacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id),
    KEY ix_usuarios_roles_rol (rol_id),
    CONSTRAINT fk_usuarios_roles_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (usuario_id) ON DELETE CASCADE,
    CONSTRAINT fk_usuarios_roles_rol
        FOREIGN KEY (rol_id) REFERENCES roles (rol_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles_permisos (
    rol_id BIGINT UNSIGNED NOT NULL,
    permiso_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (rol_id, permiso_id),
    KEY ix_roles_permisos_permiso (permiso_id),
    CONSTRAINT fk_roles_permisos_rol
        FOREIGN KEY (rol_id) REFERENCES roles (rol_id) ON DELETE CASCADE,
    CONSTRAINT fk_roles_permisos_permiso
        FOREIGN KEY (permiso_id) REFERENCES permisos (permiso_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS estudiantes (
    estudiante_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    correo VARCHAR(254) NOT NULL,
    carnet VARCHAR(32) NULL,
    telefono VARCHAR(32) NULL,
    carrera VARCHAR(100) NOT NULL,
    anio_academico TINYINT UNSIGNED NOT NULL,
    modalidad_participacion ENUM('Voluntariado', 'Horas sociales', 'Práctica profesional') NOT NULL DEFAULT 'Horas sociales',
    nombres VARCHAR(100) NULL,
    apellidos VARCHAR(100) NULL,
    dui VARCHAR(20) NULL,
    fecha_nacimiento DATE NULL,
    genero ENUM('M', 'F') NULL,
    direccion VARCHAR(255) NULL,
    estado_registro ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (estudiante_id),
    UNIQUE KEY uq_estudiantes_usuario (usuario_id),
    UNIQUE KEY uq_estudiantes_correo (correo),
    UNIQUE KEY uq_estudiantes_carnet (carnet),
    CONSTRAINT chk_estudiantes_anio CHECK (anio_academico IN (1, 2)),
    CONSTRAINT fk_estudiantes_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (usuario_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proyectos (
    proyecto_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    codigo VARCHAR(40) NOT NULL,
    descripcion TEXT NOT NULL,
    objetivo TEXT NOT NULL,
    requisitos TEXT NULL,
    carrera VARCHAR(100) NOT NULL,
    anio_academico TINYINT UNSIGNED NOT NULL,
    modalidad ENUM('Presencial', 'Virtual', 'Mixta') NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NULL,
    estado ENUM('Activo', 'Inactivo', 'Finalizado', 'En Pausa') NOT NULL DEFAULT 'Activo',
    creado_por BIGINT UNSIGNED NOT NULL,
    responsable_id BIGINT UNSIGNED NULL,
    modificado_por BIGINT UNSIGNED NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (proyecto_id),
    UNIQUE KEY uq_proyectos_codigo (codigo),
    KEY ix_proyectos_filtros (carrera, anio_academico, modalidad, estado),
    CONSTRAINT fk_proyectos_creador FOREIGN KEY (creado_por) REFERENCES usuarios (usuario_id),
    CONSTRAINT fk_proyectos_responsable FOREIGN KEY (responsable_id) REFERENCES usuarios (usuario_id) ON DELETE SET NULL,
    CONSTRAINT fk_proyectos_modificador FOREIGN KEY (modificado_por) REFERENCES usuarios (usuario_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS actividades_proyecto (
    actividad_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    proyecto_id BIGINT UNSIGNED NOT NULL,
    titulo VARCHAR(180) NOT NULL,
    descripcion TEXT NOT NULL,
    modalidad ENUM('Presencial', 'Virtual', 'Mixta') NOT NULL DEFAULT 'Presencial',
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    lugar VARCHAR(255) NOT NULL,
    horas_estimadas DECIMAL(5,2) NOT NULL,
    cupo INT UNSIGNED NULL,
    responsable_id BIGINT UNSIGNED NOT NULL,
    recurso VARCHAR(150) NULL,
    creado_por BIGINT UNSIGNED NOT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (actividad_id),
    KEY ix_actividades_horario (responsable_id, fecha_inicio, fecha_fin),
    KEY ix_actividades_recurso (recurso, fecha_inicio, fecha_fin),
    CONSTRAINT fk_actividad_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos (proyecto_id),
    CONSTRAINT fk_actividad_creador FOREIGN KEY (creado_por) REFERENCES usuarios (usuario_id),
    CONSTRAINT fk_actividad_responsable FOREIGN KEY (responsable_id) REFERENCES usuarios (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proyecto_estudiantes (
    asignacion_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    proyecto_id BIGINT UNSIGNED NOT NULL,
    estudiante_id BIGINT UNSIGNED NOT NULL,
    asignado_por BIGINT UNSIGNED NOT NULL,
    funcion VARCHAR(120) NOT NULL DEFAULT 'Participante',
    estado_participacion ENUM('Activa', 'Retirada') NOT NULL DEFAULT 'Activa',
    fecha_asignacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_desasignacion TIMESTAMP NULL,
    PRIMARY KEY (asignacion_id),
    KEY ix_proyecto_estudiante (proyecto_id, estudiante_id),
    CONSTRAINT fk_pe_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos (proyecto_id),
    CONSTRAINT fk_pe_estudiante FOREIGN KEY (estudiante_id) REFERENCES estudiantes (estudiante_id),
    CONSTRAINT fk_pe_usuario FOREIGN KEY (asignado_por) REFERENCES usuarios (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS registros_horas (
    registro_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actividad_id BIGINT UNSIGNED NOT NULL,
    estudiante_id BIGINT UNSIGNED NOT NULL,
    reportado_por BIGINT UNSIGNED NOT NULL,
    horas DECIMAL(5,2) NOT NULL,
    fecha_actividad DATE NOT NULL,
    descripcion TEXT NOT NULL,
    estado ENUM('Pendiente', 'Aprobado', 'Rechazado') NOT NULL DEFAULT 'Pendiente',
    revisado_por BIGINT UNSIGNED NULL,
    motivo_revision VARCHAR(500) NULL,
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_revision TIMESTAMP NULL,
    PRIMARY KEY (registro_id),
    CONSTRAINT fk_horas_actividad FOREIGN KEY (actividad_id) REFERENCES actividades_proyecto (actividad_id),
    CONSTRAINT fk_horas_estudiante FOREIGN KEY (estudiante_id) REFERENCES estudiantes (estudiante_id),
    CONSTRAINT fk_horas_reportador FOREIGN KEY (reportado_por) REFERENCES usuarios (usuario_id),
    CONSTRAINT fk_horas_revisor FOREIGN KEY (revisado_por) REFERENCES usuarios (usuario_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evaluaciones_estudiante (
    evaluacion_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    estudiante_id BIGINT UNSIGNED NOT NULL,
    proyecto_id BIGINT UNSIGNED NULL,
    evaluador_id BIGINT UNSIGNED NULL,
    calificacion DECIMAL(5,2) NULL,
    comentarios TEXT NOT NULL,
    fecha_evaluacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (evaluacion_id),
    CONSTRAINT fk_evaluacion_estudiante FOREIGN KEY (estudiante_id) REFERENCES estudiantes (estudiante_id) ON DELETE CASCADE,
    CONSTRAINT fk_evaluacion_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos (proyecto_id) ON DELETE SET NULL,
    CONSTRAINT fk_evaluacion_evaluador FOREIGN KEY (evaluador_id) REFERENCES usuarios (usuario_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documentos_estudiante (
    documento_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    estudiante_id BIGINT UNSIGNED NOT NULL,
    nombre VARCHAR(180) NOT NULL,
    ruta VARCHAR(500) NOT NULL,
    tipo VARCHAR(100) NULL,
    subido_por BIGINT UNSIGNED NULL,
    fecha_subida TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (documento_id),
    CONSTRAINT fk_documento_estudiante FOREIGN KEY (estudiante_id) REFERENCES estudiantes (estudiante_id) ON DELETE CASCADE,
    CONSTRAINT fk_documento_usuario FOREIGN KEY (subido_por) REFERENCES usuarios (usuario_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auditoria_cambios (
    auditoria_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NULL,
    entidad VARCHAR(60) NOT NULL,
    entidad_id BIGINT UNSIGNED NOT NULL,
    accion ENUM('CREAR', 'ACTUALIZAR', 'CAMBIAR_ESTADO', 'ASIGNAR') NOT NULL,
    datos_anteriores JSON NULL,
    datos_nuevos JSON NULL,
    fecha_cambio TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (auditoria_id),
    KEY ix_auditoria_entidad (entidad, entidad_id),
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (usuario_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS solicitudes_registro (
    solicitud_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
    estado_solicitud ENUM('pendiente', 'aceptada', 'rechazada') NOT NULL DEFAULT 'pendiente',
    fecha_solicitud TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revisada_por BIGINT UNSIGNED NULL,
    fecha_revision TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (solicitud_id),
    UNIQUE KEY uq_solicitudes_usuario (usuario_id),
    KEY ix_solicitudes_estado_fecha (estado_solicitud, fecha_solicitud),
    CONSTRAINT fk_solicitudes_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (usuario_id) ON DELETE CASCADE,
    CONSTRAINT fk_solicitudes_revisor
        FOREIGN KEY (revisada_por) REFERENCES usuarios (usuario_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dejamos los roles y permisos listos para que el sistema arranque.
INSERT IGNORE INTO roles (nombre_rol, etiqueta) VALUES
    ('administrador', 'Administrador'),
    ('coordinador', 'Coordinador de proyeccion social'),
    ('docente', 'Docente'),
    ('asistente', 'Asistente'),
    ('estudiante', 'Estudiante');

INSERT IGNORE INTO permisos (nombre_permiso, descripcion) VALUES
    ('usuarios.consultar', 'Consultar usuarios y roles'),
    ('usuarios.crear', 'Crear cuentas internas'),
    ('usuarios.gestionar_roles', 'Asignar y cambiar roles'),
    ('usuarios.gestionar_estado', 'Activar y desactivar cuentas'),
    ('estudiantes.crear', 'Registrar estudiantes'),
    ('panel.administracion', 'Acceder al panel administrativo'),
    ('solicitudes.revisar', 'Revisar solicitudes de registro'),
    ('estudiantes.consultar', 'Consultar expedientes de estudiantes'),
    ('estudiantes.actualizar', 'Actualizar estudiantes y sus estados'),
    ('estudiantes.actualizar_propios', 'Actualizar datos de contacto y participación propios'),
    ('proyectos.consultar', 'Consultar proyectos'),
    ('proyectos.catalogo', 'Consultar catálogo público de proyectos'),
    ('proyectos.crear', 'Crear proyectos y actividades'),
    ('proyectos.actualizar', 'Actualizar proyectos y estados'),
    ('proyectos.asignar', 'Asignar estudiantes a proyectos'),
    ('horas.registrar', 'Registrar horas de actividades'),
    ('horas.registrar_para_estudiantes', 'Registrar horas para estudiantes asignados'),
    ('horas.revisar', 'Revisar registros de horas');

INSERT IGNORE INTO roles_permisos (rol_id, permiso_id)
SELECT roles.rol_id, permisos.permiso_id
FROM roles
CROSS JOIN permisos
WHERE roles.nombre_rol = 'administrador';

-- El coordinador puede ver el panel y decidir sobre las solicitudes.
INSERT IGNORE INTO roles_permisos (rol_id, permiso_id)
SELECT roles.rol_id, permisos.permiso_id
FROM roles
CROSS JOIN permisos
WHERE roles.nombre_rol = 'coordinador'
  AND permisos.nombre_permiso IN ('panel.administracion', 'solicitudes.revisar', 'estudiantes.consultar',
      'estudiantes.actualizar', 'proyectos.consultar', 'proyectos.crear', 'proyectos.actualizar',
      'proyectos.asignar', 'horas.registrar_para_estudiantes', 'horas.revisar');

INSERT IGNORE INTO roles_permisos (rol_id, permiso_id)
SELECT roles.rol_id, permisos.permiso_id
FROM roles CROSS JOIN permisos
WHERE roles.nombre_rol = 'estudiante'
  AND permisos.nombre_permiso IN ('horas.registrar', 'proyectos.catalogo', 'estudiantes.actualizar_propios');

-- El docente trabaja con proyectos y valida horas de sus actividades,
-- pero no administra cuentas ni aprueba solicitudes de registro.
INSERT IGNORE INTO roles_permisos (rol_id, permiso_id)
SELECT roles.rol_id, permisos.permiso_id
FROM roles CROSS JOIN permisos
WHERE roles.nombre_rol = 'docente'
  AND permisos.nombre_permiso IN ('estudiantes.consultar', 'proyectos.consultar',
      'proyectos.crear', 'proyectos.actualizar', 'proyectos.catalogo', 'horas.revisar');

-- Esta cuenta sirve para entrar la primera vez; cambiale la clave luego.
INSERT IGNORE INTO usuarios (nombre_completo, correo, carnet, clave_hash, estado_activo)
VALUES (
    'Administrador',
    'admin@itca.edu.sv',
    NULL,
    '$2y$10$MBPYPdU8BGMInmHbWvQMReX3/oWkubCoUC48MTw.g8XkFdUguuYMC',
    1
);

INSERT IGNORE INTO usuarios_roles (usuario_id, rol_id)
SELECT usuarios.usuario_id, roles.rol_id
FROM usuarios
CROSS JOIN roles
WHERE usuarios.correo = 'admin@itca.edu.sv'
  AND roles.nombre_rol = 'administrador';
