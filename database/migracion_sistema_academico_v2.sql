USE sistema_academico;

DROP PROCEDURE IF EXISTS migrar_v2_columna_si_falta;
DROP PROCEDURE IF EXISTS migrar_v2_indice_si_falta;

DELIMITER //
CREATE PROCEDURE migrar_v2_columna_si_falta(
    IN tabla_objetivo VARCHAR(64),
    IN columna_objetivo VARCHAR(64),
    IN definicion_columna VARCHAR(1000)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = tabla_objetivo
          AND column_name = columna_objetivo
    ) THEN
        SET @sql_migracion = CONCAT(
            'ALTER TABLE `', REPLACE(tabla_objetivo, '`', '``'),
            '` ADD COLUMN `', REPLACE(columna_objetivo, '`', '``'),
            '` ', definicion_columna
        );
        PREPARE sentencia_migracion FROM @sql_migracion;
        EXECUTE sentencia_migracion;
        DEALLOCATE PREPARE sentencia_migracion;
    END IF;
END//

CREATE PROCEDURE migrar_v2_indice_si_falta(
    IN tabla_objetivo VARCHAR(64),
    IN indice_objetivo VARCHAR(64),
    IN definicion_indice VARCHAR(1000)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = tabla_objetivo
          AND index_name = indice_objetivo
    ) THEN
        SET @sql_migracion = CONCAT(
            'ALTER TABLE `', REPLACE(tabla_objetivo, '`', '``'),
            '` ADD ', definicion_indice
        );
        PREPARE sentencia_migracion FROM @sql_migracion;
        EXECUTE sentencia_migracion;
        DEALLOCATE PREPARE sentencia_migracion;
    END IF;
END//
DELIMITER ;

CALL migrar_v2_columna_si_falta('usuarios', 'anio_academico', 'TINYINT UNSIGNED NULL');
CALL migrar_v2_columna_si_falta(
    'usuarios',
    'modalidad_participacion',
    'ENUM(''Voluntariado'', ''Horas sociales'', ''Práctica profesional'') NULL'
);

CALL migrar_v2_columna_si_falta('estudiantes', 'carnet', 'VARCHAR(32) NULL');
CALL migrar_v2_columna_si_falta(
    'estudiantes',
    'modalidad_participacion',
    'ENUM(''Voluntariado'', ''Horas sociales'', ''Práctica profesional'') NOT NULL DEFAULT ''Horas sociales'''
);
CALL migrar_v2_columna_si_falta('estudiantes', 'nombres', 'VARCHAR(100) NULL');
CALL migrar_v2_columna_si_falta('estudiantes', 'apellidos', 'VARCHAR(100) NULL');
CALL migrar_v2_columna_si_falta('estudiantes', 'dui', 'VARCHAR(20) NULL');
CALL migrar_v2_columna_si_falta('estudiantes', 'fecha_nacimiento', 'DATE NULL');
CALL migrar_v2_columna_si_falta('estudiantes', 'genero', 'ENUM(''M'', ''F'') NULL');
CALL migrar_v2_columna_si_falta('estudiantes', 'direccion', 'VARCHAR(255) NULL');
CALL migrar_v2_columna_si_falta(
    'estudiantes',
    'estado_registro',
    'ENUM(''Activo'', ''Inactivo'') NOT NULL DEFAULT ''Activo'''
);
CALL migrar_v2_columna_si_falta(
    'estudiantes',
    'fecha_actualizacion',
    'TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP'
);

CALL migrar_v2_columna_si_falta(
    'actividades_proyecto',
    'modalidad',
    'ENUM(''Presencial'', ''Virtual'', ''Mixta'') NOT NULL DEFAULT ''Presencial'''
);
CALL migrar_v2_columna_si_falta('registros_horas', 'reportado_por', 'BIGINT UNSIGNED NULL');
CALL migrar_v2_columna_si_falta('registros_horas', 'fecha_actividad', 'DATE NULL');

CALL migrar_v2_indice_si_falta(
    'estudiantes',
    'uq_estudiantes_carnet',
    'UNIQUE KEY `uq_estudiantes_carnet` (`carnet`)'
);

UPDATE registros_horas rh
JOIN actividades_proyecto a ON a.actividad_id = rh.actividad_id
SET rh.fecha_actividad = DATE(a.fecha_inicio)
WHERE rh.fecha_actividad IS NULL
  AND rh.registro_id > 0;

ALTER TABLE registros_horas
    MODIFY COLUMN fecha_actividad DATE NOT NULL;

UPDATE estudiantes e
LEFT JOIN usuarios u ON u.usuario_id = e.usuario_id
SET e.carnet = u.carnet
WHERE e.carnet IS NULL
  AND u.carnet IS NOT NULL;

UPDATE estudiantes
SET nombres = SUBSTRING_INDEX(nombre_completo, ' ', 1)
WHERE nombres IS NULL;

UPDATE estudiantes
SET apellidos = CASE
    WHEN LOCATE(' ', nombre_completo) > 0
        THEN TRIM(SUBSTRING(nombre_completo, LOCATE(' ', nombre_completo) + 1))
    ELSE nombre_completo
END
WHERE apellidos IS NULL;

UPDATE usuarios u
JOIN estudiantes e ON e.usuario_id = u.usuario_id
SET u.anio_academico = e.anio_academico
WHERE u.anio_academico IS NULL;

UPDATE usuarios u
JOIN estudiantes e ON e.usuario_id = u.usuario_id
SET u.modalidad_participacion = e.modalidad_participacion
WHERE u.modalidad_participacion IS NULL;

INSERT IGNORE INTO permisos (nombre_permiso, descripcion)
VALUES ('horas.registrar_para_estudiantes', 'Registrar horas para estudiantes asignados');

INSERT IGNORE INTO roles_permisos (rol_id, permiso_id)
SELECT r.rol_id, p.permiso_id
FROM roles r
CROSS JOIN permisos p
WHERE r.nombre_rol = 'coordinador'
  AND p.nombre_permiso = 'horas.registrar_para_estudiantes';

DROP PROCEDURE migrar_v2_columna_si_falta;
DROP PROCEDURE migrar_v2_indice_si_falta;
