# Plataforma ITCA-FEPADE

El proyecto usa un MVC sencillo: `Views/` tiene las pantallas, `models/` habla con MySQL y `controllers/` atiende los formularios y permisos.

## Preparar la base de datos

La app no crea la base ni las tablas por su cuenta. En SQLyog:

1. Conectate a MySQL o MariaDB.
2. Abre [sistema_academico.sql](../database/sistema_academico.sql).
3. Ejecuta el script completo.

Se crea la base `sistema_academico`, sus tablas, roles, permisos y una cuenta admin de prueba. El script no borra tablas ni cuentas existentes; `CREATE TABLE IF NOT EXISTS` tampoco actualiza columnas de tablas antiguas.

Para una instalación nueva, ejecuta el script base y después las migraciones [v3](../database/migracion_sistema_academico_v3.sql), [v4](../database/migracion_sistema_academico_v4.sql) y [v5](../database/migracion_sistema_academico_v5.sql). Para actualizar una instalación anterior, haz primero una copia de seguridad; ejecuta el script base, luego la migración [v2](../database/migracion_sistema_academico_v2.sql), seguida por v3, v4 y v5. La v2 completa columnas del sistema académico existente; v3 agrega las tablas para solicitudes, notificaciones, respuestas y documentos emitidos; v4 agrega evaluaciones por criterios, confirmaciones de asistencia, historial y adjuntos privados de solicitudes, avisos/aceptaciones de privacidad y bitácora de notificaciones; v5 agrega cancelaciones de actividades y metadatos de versión de plantilla en documentos emitidos. Estas migraciones conservan los registros existentes; v3, v4 y v5 pueden volver a ejecutarse. La v2 debe ejecutarse completa desde SQLyog/MySQL con permisos para crear procedimientos.

Cuenta inicial:

- Correo: `admin@itca.edu.sv`
- Clave: `Admin123!Secure`

Usala para entrar y cambiala antes de compartir el sistema.

Desde **Gestion de Roles**, el admin puede asignar el rol `Coordinador de proyeccion social`. Tanto el admin como el coordinador reciben el aviso de solicitudes pendientes al entrar al panel.

## Módulos de proyección social

Las rutas `dashboard_licenciada.php`, `calendario_actividades.php`, `consulta_respuestas.php`, `crear_plantilla.php`, `diplomas_generar.php`, `cartas_participacion.php` y `mis_solicitudes.php` se sirven desde las vistas existentes sin modificar sus archivos. El panel y calendario consultan actividades/proyectos guardados; las solicitudes de estudiantes se guardan y pueden ser aprobadas o rechazadas por personal autorizado; las plantillas se usan para generar notificaciones internas al crear actividades; y los diplomas/cartas imprimibles solo se emiten para estudiantes asignados con horas aprobadas en esa actividad.

La consulta de respuestas es de solo lectura y muestra los datos cargados en `respuestas_cuestionario` y `respuestas_cuestionario_detalle`. La aplicación no incluye administración de cuestionarios ni captura de respuestas desde estas vistas.

Los documentos se generan como páginas HTML imprimibles; el navegador permite imprimirlos o guardarlos como PDF. Incluyen carrera, año académico, modalidad de participación y periodo del proyecto del expediente, además de la actividad, las horas aprobadas y la fecha de emisión. Cada emisión guarda una copia de los datos y el código/versión de plantilla usados para que una reimpresión no dependa de cambios posteriores al expediente. No se requiere configuración SMTP ni una biblioteca de PDF.

## Evaluaciones, asistencia y privacidad

El personal con permiso puede definir criterios de evaluación y escalas (0–10), iniciar evaluaciones para estudiantes asignados, guardar borradores y cerrar una evaluación con calificación calculada e historial de responsable/fecha. Las evaluaciones cerradas no se pueden editar. Las personas estudiantes pueden confirmar o declinar asistencia hasta la fecha límite configurada para la actividad; si no se define, el plazo vence al iniciar.

Las solicitudes pueden devolverse con observaciones para que el estudiante las corrija y reenvíe. Sus adjuntos PDF/JPG/PNG (hasta 8 MB) se guardan fuera del directorio público y se sirven mediante una ruta que comprueba propietario o permiso de revisión. Las notificaciones nuevas son internas; no hay envío por correo configurado. La administración puede publicar una versión de aviso de privacidad. Solo debe exigirse aceptación después de cargar texto institucional aprobado; las versiones y aceptaciones anteriores se conservan y el personal autorizado puede consultar el historial de aceptaciones. Las actividades canceladas conservan su motivo, fecha y responsable; no permiten nuevas respuestas de asistencia ni emisión de documentos.

## Permisos por rol

Las rutas y acciones POST verifican permisos en el servidor; ocultar un enlace no concede ni revoca acceso.

| Rol | Acceso |
| --- | --- |
| Administrador | Administracion completa de cuentas, roles, expedientes, proyectos y horas; acceso a los módulos de proyección social. |
| Coordinador | Panel, revisión de solicitudes, expedientes, proyectos, asignaciones y registro/revisión de horas para estudiantes; calendario, respuestas, plantillas y documentos. |
| Docente | Consulta de expedientes, proyectos, calendario y respuestas; creación/actualización de proyectos y revisión de horas. No administra cuentas ni asignaciones. |
| Asistente | Sin permisos de modulos asignados por defecto. |
| Estudiante | Catálogo de proyectos, calendario de actividades de proyectos activos, registro y resumen de sus propias horas, edición de sus datos de contacto y gestión de sus propias solicitudes. No puede abrir expedientes ajenos. |

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


