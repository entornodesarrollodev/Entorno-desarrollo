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

