# Plataforma ITCA-FEPADE

El proyecto usa un MVC sencillo: `Views/` tiene las pantallas, `models/` habla con MySQL y `controllers/` atiende los formularios y permisos.

## Preparar la base de datos

La app no crea la base ni las tablas por su cuenta. En SQLyog:

1. Conectate a MySQL o MariaDB.
2. Abre [sistema_academico.sql](../database/sistema_academico.sql).
3. Ejecuta el script completo.

Se crea la base `sistema_academico`, sus tablas, roles, permisos y una cuenta admin de prueba. El script no borra tablas ni cuentas existentes; `CREATE TABLE IF NOT EXISTS` tampoco actualiza columnas de tablas antiguas.

En una instalación nueva, ejecuta únicamente el script base. Para actualizar una instalación anterior de este repositorio, haz una copia de seguridad, ejecuta primero el script base para crear las tablas nuevas y luego [migracion_sistema_academico_v2.sql](../database/migracion_sistema_academico_v2.sql). La migración agrega las columnas que falten en `usuarios`, `estudiantes`, `actividades_proyecto` y `registros_horas`, y conserva los registros. En horas existentes, la fecha se completa con la fecha de inicio de la actividad relacionada; el reportador histórico queda vacío porque no se guardaba antes. La migración se puede volver a ejecutar. Debe ejecutarse completa desde SQLyog/MySQL con permisos para crear procedimientos.

Cuenta inicial:

- Correo: `admin@itca.edu.sv`
- Clave: `Admin123!Secure`

Usala para entrar y cambiala antes de compartir el sistema.

Desde **Gestion de Roles**, el admin puede asignar el rol `Coordinador de proyeccion social`. Tanto el admin como el coordinador reciben el aviso de solicitudes pendientes al entrar al panel.

## Permisos por rol

Las rutas y acciones POST verifican permisos en el servidor; ocultar un enlace no concede ni revoca acceso.

| Rol | Acceso |
| --- | --- |
| Administrador | Administracion completa de cuentas, roles, expedientes, proyectos y horas. |
| Coordinador | Panel, revision de solicitudes, expedientes, proyectos, asignaciones y registro/revision de horas para estudiantes. |
| Docente | Consulta de expedientes y proyectos, creacion/actualizacion de proyectos y revision de horas. No administra cuentas ni asignaciones. |
| Asistente | Sin permisos de modulos asignados por defecto. |
| Estudiante | Catalogo de proyectos, registro y resumen de sus propias horas, y edicion de sus datos de contacto. No puede abrir expedientes ajenos. |

## Conexion a MySQL

PHP necesita PDO y `pdo_mysql`. La conexion lee estas variables de entorno:

| Variable | Valor si no configuras otra |
| --- | --- |
| `BD_SERVIDOR` | `127.0.0.1` |
| `BD_PUERTO` | `3306` |
| `BD_NOMBRE` | `sistema_academico` |
| `BD_USUARIO` | `root` |
| `BD_CLAVE` | vacia |

En XAMPP, enciende MySQL desde el panel. Si tu usuario tiene clave, configura `BD_CLAVE` antes de arrancar PHP. La conexion esta en [database.php](../config/database.php).

En PowerShell puedes poner tus datos asi (cambia la clave si tu MySQL usa otra):

```powershell
$env:BD_SERVIDOR = "127.0.0.1"
$env:BD_PUERTO = "3306"
$env:BD_NOMBRE = "sistema_academico"
$env:BD_USUARIO = "root"
$env:BD_CLAVE = ""
```

## Ejecutar el proyecto

Desde la carpeta principal del proyecto:

```powershell
php -S 127.0.0.1:8000 router.php
```

Despues abre `http://127.0.0.1:8000/`. Para Apache, habilita `mod_rewrite` y permite `.htaccess`; asi no se podran abrir directo las vistas ni el script SQL.

## Pruebas

Ejecuta `php tests/run.php` contra una base MySQL/MariaDB aislada configurada con las variables de conexion. La prueba cubre permisos, expedientes, asignaciones, actividades, conflictos de horario y resumen/revision de horas.
