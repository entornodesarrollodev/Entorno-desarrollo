# Plataforma ITCA-FEPADE

Creacion de base de datos provisional

## Requisitos y ejecución local

- PHP 8.1 o superior con las extensiones PDO y `pdo_sqlite`.
- Desde la raíz del proyecto, iniciar el servidor con el router del proyecto:

```powershell
php -S 127.0.0.1:8000 router.php
```

Abrir `http://127.0.0.1:8000/`. 

En desarrollo, el primer administrador se crea automáticamente:

- Correo: `admin@itca.edu.sv`
- Contraseña: `Admin123!Secure`



## Rutas disponibles

| Ruta | Acceso |
| --- | --- |
| `/login.php`, `/auth.php`, `/register.php` | Inicio de sesión y registro de estudiantes |
| `/dashboard.php` | Cualquier usuario autenticado |
| `/dashboard_admin.php`, `/roles.php`, `/personal.php`, `/estudiante.php` | Permisos administrativos asignados al rol |
| `/guardar_personal.php`, `/guardar_estudiante.php` | Creación administrativa con autorización del servidor |
| `/admin/assign-role`, `/admin/toggle-status` | Asignación de roles y activación/desactivación |
| `/logout.php` | Cierre de sesión y retorno al inicio |



