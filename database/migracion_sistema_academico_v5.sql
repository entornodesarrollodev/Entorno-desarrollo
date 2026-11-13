USE sistema_academico;

SET @columna_existe = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'documentos_emitidos'
      AND column_name = 'plantilla_codigo'
);
SET @migracion_sql = IF(
    @columna_existe = 0,
    'ALTER TABLE documentos_emitidos ADD COLUMN plantilla_codigo VARCHAR(80) NULL AFTER fecha_emision',
    'SELECT 1'
);
PREPARE migracion_stmt FROM @migracion_sql;
EXECUTE migracion_stmt;
DEALLOCATE PREPARE migracion_stmt;

SET @columna_existe = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'documentos_emitidos'
      AND column_name = 'plantilla_version'
);
SET @migracion_sql = IF(
    @columna_existe = 0,
    'ALTER TABLE documentos_emitidos ADD COLUMN plantilla_version VARCHAR(40) NULL AFTER plantilla_codigo',
    'SELECT 1'
);
PREPARE migracion_stmt FROM @migracion_sql;
EXECUTE migracion_stmt;
DEALLOCATE PREPARE migracion_stmt;

CREATE TABLE IF NOT EXISTS actividad_cancelaciones (
    actividad_id BIGINT UNSIGNED NOT NULL,
    motivo VARCHAR(1000) NOT NULL,
    cancelada_por BIGINT UNSIGNED NOT NULL,
    fecha_cancelacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (actividad_id),
    KEY ix_actividad_cancelacion_fecha (fecha_cancelacion),
    CONSTRAINT fk_actividad_cancelacion_actividad
        FOREIGN KEY (actividad_id) REFERENCES actividades_proyecto (actividad_id) ON DELETE CASCADE,
    CONSTRAINT fk_actividad_cancelacion_usuario
        FOREIGN KEY (cancelada_por) REFERENCES usuarios (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
