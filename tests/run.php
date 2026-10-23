<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/models/Database.php';
require_once dirname(__DIR__) . '/models/Role.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Student.php';
require_once dirname(__DIR__) . '/controllers/HttpController.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FALLO: ' . $message);
    }
}

$databasePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'itca-tests-' . bin2hex(random_bytes(8)) . '.sqlite';
putenv('APP_DB_PATH=' . $databasePath);
putenv('APP_ENV=development');

try {
    $pdo = databaseConnection();
    Database::initialize($pdo);
    $users = new User($pdo);
    $roles = new Role($pdo);
    $admin = $users->authenticate('admin@itca.edu.sv', 'Admin123!Secure');
    expect($admin !== null, 'las credenciales iniciales válidas deben autenticar.');
    expect($users->authenticate('admin@itca.edu.sv', 'incorrecta') === null, 'una contraseña inválida debe rechazarse.');
    expect($users->authenticate('', '') === null, 'campos vacíos no deben autenticar.');
    expect($roles->userHasPermission((int) $admin['id'], 'users.manage_roles'), 'el administrador debe poder gestionar roles.');

    $students = new Student($pdo);
    $studentId = $users->create(
        'Estudiante de prueba',
        'estudiante@example.test',
        'A12345',
        'clave123',
        'estudiante',
        true
    );
    expect($users->authenticate('A12345', 'clave123') !== null, 'el carnet también debe permitir iniciar sesión.');
    expect(!$roles->userHasPermission($studentId, 'users.manage_roles'), 'un estudiante no debe gestionar roles.');
    expect(!$roles->userHasPermission($studentId, 'dashboard.admin'), 'un estudiante no debe acceder al panel administrativo.');
    $students->create('Estudiante de prueba', 'estudiante@example.test', '0000-0000', 'software', 1);
    $studentRecord = $pdo->query("SELECT user_id FROM students WHERE email = 'estudiante@example.test'")->fetch();
    expect((int) $studentRecord['user_id'] === $studentId, 'la ficha de estudiante debe vincularse con su cuenta.');

    $staffId = $users->create(
        'Docente de prueba',
        'docente@example.test',
        null,
        'clave123',
        'docente',
        true
    );
    expect(!$roles->userHasPermission($staffId, 'users.view'), 'un docente no debe consultar la administración de usuarios.');
    $assistantId = $users->create(
        'Asistente de prueba',
        'asistente@example.test',
        null,
        'clave123',
        'asistente',
        true
    );
    expect(!$roles->userHasPermission($assistantId, 'users.manage_status'), 'un asistente no debe modificar estados de cuenta.');

    $roles->assign($studentId, (int) $roles->findByName('docente')['id']);
    expect($roles->forUser($studentId)['name'] === 'docente', 'el rol debe poder cambiarse y consultarse.');

    try {
        $users->create('Duplicado', 'estudiante@example.test', 'B67890', 'clave123', 'estudiante', true);
        expect(false, 'el correo duplicado debe violar la restricción de unicidad.');
    } catch (PDOException) {
        expect(true, 'la restricción única de correo debe rechazarse.');
    }

    try {
        $users->create('Carnet duplicado', 'otro@example.test', 'A12345', 'clave123', 'estudiante', true);
        expect(false, 'el carnet duplicado debe violar la restricción de unicidad.');
    } catch (PDOException) {
        expect(true, 'la restricción única de carnet debe rechazarse.');
    }

    $users->setActive($studentId, false);
    expect($users->authenticate('A12345', 'clave123') === null, 'una cuenta desactivada no debe iniciar sesión.');
    expect($users->findActiveById($studentId) === null, 'una cuenta desactivada no debe conservar acceso autenticado.');

    $http = new HttpController($pdo);
    $rendered = $http->insertFieldError('<input name="email">', 'email', 'Correo <invalido>');
    expect(str_contains($rendered, 'role="alert"'), 'el error de validación debe renderizarse junto al campo.');
    expect(str_contains($rendered, 'Correo &lt;invalido&gt;'), 'los mensajes de error deben escaparse.');

    echo "OK: autenticación, validación, roles, permisos, unicidad y desactivación.\n";
} finally {
    unset($http, $students, $roles, $users, $pdo);
    if (is_file($databasePath)) {
        unlink($databasePath);
    }
    putenv('APP_DB_PATH');
    putenv('APP_ENV');
}
