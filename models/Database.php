<?php
declare(strict_types=1);

final class Database
{
    public static function initialize(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                fullname TEXT NOT NULL,
                email TEXT NOT NULL COLLATE NOCASE UNIQUE,
                carnet TEXT COLLATE NOCASE UNIQUE,
                password_hash TEXT NOT NULL,
                active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
                career TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL COLLATE NOCASE UNIQUE,
                label TEXT NOT NULL
            )'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL COLLATE NOCASE UNIQUE,
                description TEXT NOT NULL
            )'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS user_roles (
                user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
                role_id INTEGER NOT NULL REFERENCES roles(id) ON DELETE RESTRICT,
                assigned_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS role_permissions (
                role_id INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
                permission_id INTEGER NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
                PRIMARY KEY (role_id, permission_id)
            )'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS students (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER UNIQUE REFERENCES users(id) ON DELETE SET NULL,
                fullname TEXT NOT NULL,
                email TEXT NOT NULL COLLATE NOCASE UNIQUE,
                phone TEXT,
                career TEXT NOT NULL,
                year INTEGER NOT NULL CHECK (year IN (1, 2)),
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        self::seedRolesAndPermissions($pdo);
        self::seedInitialAdministrator($pdo);
    }

    private static function seedRolesAndPermissions(PDO $pdo): void
    {
        $roles = [
            'admin' => 'Administrador',
            'docente' => 'Docente',
            'asistente' => 'Asistente',
            'estudiante' => 'Estudiante',
        ];
        $insertRole = $pdo->prepare('INSERT OR IGNORE INTO roles (name, label) VALUES (:name, :label)');

        foreach ($roles as $name => $label) {
            $insertRole->execute(['name' => $name, 'label' => $label]);
        }

        $permissions = [
            'users.view' => 'Consultar usuarios y roles',
            'users.create' => 'Crear cuentas internas',
            'users.manage_roles' => 'Asignar y cambiar roles',
            'users.manage_status' => 'Activar y desactivar cuentas',
            'students.create' => 'Registrar estudiantes',
            'dashboard.admin' => 'Acceder al panel administrativo',
        ];
        $insertPermission = $pdo->prepare(
            'INSERT OR IGNORE INTO permissions (name, description) VALUES (:name, :description)'
        );

        foreach ($permissions as $name => $description) {
            $insertPermission->execute(['name' => $name, 'description' => $description]);
        }

        $adminRoleId = self::roleId($pdo, 'admin');
        $insertGrant = $pdo->prepare(
            'INSERT OR IGNORE INTO role_permissions (role_id, permission_id)
             SELECT :role_id, id FROM permissions WHERE name = :permission'
        );

        foreach (array_keys($permissions) as $permission) {
            $insertGrant->execute(['role_id' => $adminRoleId, 'permission' => $permission]);
        }
    }

    private static function seedInitialAdministrator(PDO $pdo): void
    {
        if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
            return;
        }

        $environment = getenv('APP_ENV') ?: 'development';
        $email = getenv('APP_ADMIN_EMAIL') ?: ($environment === 'production' ? '' : 'admin@itca.edu.sv');
        $password = getenv('APP_ADMIN_PASSWORD') ?: ($environment === 'production' ? '' : 'Admin123!Secure');

        if ($email === '' || $password === '') {
            throw new RuntimeException(
                'Configure APP_ADMIN_EMAIL y APP_ADMIN_PASSWORD para crear la primera cuenta administradora.'
            );
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($password) < 12) {
            throw new RuntimeException('El correo inicial debe ser válido y la contraseña tener al menos 12 caracteres.');
        }

        $pdo->beginTransaction();
        try {
            $insertUser = $pdo->prepare(
                'INSERT INTO users (fullname, email, password_hash) VALUES (:fullname, :email, :password_hash)'
            );
            $insertUser->execute([
                'fullname' => 'Administrador',
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $insertRole = $pdo->prepare(
                'INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)'
            );
            $insertRole->execute([
                'user_id' => (int) $pdo->lastInsertId(),
                'role_id' => self::roleId($pdo, 'admin'),
            ]);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    private static function roleId(PDO $pdo, string $roleName): int
    {
        $statement = $pdo->prepare('SELECT id FROM roles WHERE name = :name');
        $statement->execute(['name' => $roleName]);
        $id = $statement->fetchColumn();

        if ($id === false) {
            throw new RuntimeException('No se encontró el rol requerido: ' . $roleName);
        }

        return (int) $id;
    }
}
