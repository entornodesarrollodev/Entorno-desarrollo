# Plataforma ITCA-FEPADE

El proyecto usa un MVC sencillo: `Views/` tiene las pantallas, `models/` habla con MySQL y `controllers/` atiende los formularios y permisos.

## Preparar la base de datos

La app no crea la base ni las tablas por su cuenta. En SQLyog:

1. Conectate a MySQL o MariaDB.
2. Abre [sistema_academico.sql](../database/sistema_academico.sql).
3. Ejecuta el script completo.

Se crea la base `sistema_academico`, sus tablas, roles, permisos y una cuenta admin de prueba. El script se puede ejecutar otra vez: no borra tablas ni cuentas que ya existan. Si ya tenias la base creada, ejecuta otra vez el script para agregar las solicitudes y el rol coordinador.

Cuenta inicial:

- Correo: `admin@itca.edu.sv`
- Clave: `Admin123!Secure`

Usala para entrar y cambiala antes de compartir el sistema.

Desde **Gestion de Roles**, el admin puede asignar el rol `Coordinador de proyeccion social`. Tanto el admin como el coordinador reciben el aviso de solicitudes pendientes al entrar al panel.

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

## Base de datos

El SQL usa nombres faciles de reconocer, sin tildes ni enie:

- `usuarios`: cuentas, carnet, clave protegida y estado.
- `roles` y `permisos`: acceso permitido a cada tipo de cuenta.
- `usuarios_roles` y `roles_permisos`: relacionan cuentas, roles y permisos.
- `estudiantes`: carrera, telefono y anio academico.
- `solicitudes_registro`: solicitudes pendientes, aceptadas o rechazadas.

El correo y el carnet no se pueden repetir. Las claves se guardan como hash, no como texto normal.
Los formularios solo aceptan correos que terminen en `@itca.edu.sv` y rechazan emojis en nombres, correos, claves y demas campos de texto.
Los alumnos nuevos quedan inactivos hasta que el admin o coordinador acepte su solicitud. Una solicitud rechazada no permite entrar y no se puede saltar la aprobacion activando la cuenta desde Gestion de Roles.

## Rutas principales

| Ruta | Para que sirve |
| --- | --- |
| `/login.php`, `/auth.php`, `/register.php` | Entrar y registrar estudiantes |
| `/dashboard.php` | Panel de una cuenta con sesion |
| `/dashboard_admin.php`, `/roles.php`, `/personal.php`, `/estudiante.php` | Pantallas de administracion |
| `/guardar_personal.php`, `/guardar_estudiante.php` | Guardar cuentas y fichas |
| `/admin/asignar-rol`, `/admin/cambiar-estado` | Cambiar rol o estado de cuenta |
| `/admin/revisar-solicitud` | Aceptar o rechazar registro de alumno |
| `/logout.php` | Cerrar la sesion |

## Pruebas

Despues de ejecutar el SQL y configurar `BD_*`, corre:

```powershell
php tests/run.php
```

La prueba agrega usuarios temporales con correos ITCA aleatorios y luego los quita. No toca el admin inicial.
