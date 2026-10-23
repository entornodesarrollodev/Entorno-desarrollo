<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Rol.php';
require_once __DIR__ . '/models/Usuario.php';
require_once __DIR__ . '/models/Estudiante.php';
require_once __DIR__ . '/models/SolicitudRegistro.php';
require_once __DIR__ . '/models/Validacion.php';
require_once __DIR__ . '/controllers/HttpController.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/PageController.php';

ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Aqui se conectan las piezas y cada ruta va al controlador que le toca.
$conexion = conectarBaseDatos();
$controladorHttp = new HttpController($conexion);
$controladorAcceso = new AuthController($conexion, $controladorHttp);
$controladorAdmin = new AdminController($conexion, $controladorHttp);
$controladorPaginas = new PageController($controladorHttp);
$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (str_starts_with($ruta, '/Views/') || str_starts_with($ruta, '/database/')) {
    http_response_code(404);
    exit('No encontrado.');
}

match ($ruta) {
    '/', '/login.php' => $controladorPaginas->showLogin(),
    '/auth.php' => $controladorAcceso->login(),
    '/register.php' => $controladorAcceso->register(),
    '/logout.php' => $controladorAcceso->logout(),
    '/dashboard.php' => $controladorPaginas->showDashboard(),
    '/dashboard_admin.php' => $controladorPaginas->showAdminDashboard(),
    '/roles.php' => $controladorPaginas->showRoles(),
    '/personal.php' => $controladorPaginas->showPersonal(),
    '/estudiante.php' => $controladorPaginas->showStudentForm(),
    '/guardar_personal.php' => $controladorAdmin->saveStaff(),
    '/guardar_estudiante.php' => $controladorAdmin->saveStudent(),
    '/admin/asignar-rol' => $controladorAdmin->assignRole(),
    '/admin/cambiar-estado' => $controladorAdmin->toggleStatus(),
    '/admin/revisar-solicitud' => $controladorAdmin->reviewRegistration(),
    default => http_response_code(404),
};
