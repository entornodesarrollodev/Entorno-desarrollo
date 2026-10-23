<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/models/Role.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Student.php';
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

$pdo = databaseConnection();
Database::initialize($pdo);
$http = new HttpController($pdo);
$auth = new AuthController($pdo, $http);
$admin = new AdminController($pdo, $http);
$pages = new PageController($http);
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (str_starts_with($path, '/Views/') || str_starts_with($path, '/data/')) {
    http_response_code(404);
    exit('No encontrado.');
}

match ($path) {
    '/', '/login.php' => $pages->showLogin(),
    '/auth.php' => $auth->login(),
    '/register.php' => $auth->register(),
    '/logout.php' => $auth->logout(),
    '/dashboard.php' => $pages->showDashboard(),
    '/dashboard_admin.php' => $pages->showAdminDashboard(),
    '/roles.php' => $pages->showRoles(),
    '/personal.php' => $pages->showPersonal(),
    '/estudiante.php' => $pages->showStudentForm(),
    '/guardar_personal.php' => $admin->saveStaff(),
    '/guardar_estudiante.php' => $admin->saveStudent(),
    '/admin/assign-role' => $admin->assignRole(),
    '/admin/toggle-status' => $admin->toggleStatus(),
    default => http_response_code(404),
};
