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
    telefono VARCHAR(32) NULL,
    carrera VARCHAR(100) NOT NULL,
    anio_academico TINYINT UNSIGNED NOT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (estudiante_id),
    UNIQUE KEY uq_estudiantes_usuario (usuario_id),
    UNIQUE KEY uq_estudiantes_correo (correo),
    CONSTRAINT chk_estudiantes_anio CHECK (anio_academico IN (1, 2)),
    CONSTRAINT fk_estudiantes_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (usuario_id) ON DELETE SET NULL
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
    ('solicitudes.revisar', 'Revisar solicitudes de registro');

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
  AND permisos.nombre_permiso IN ('panel.administracion', 'solicitudes.revisar');

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
