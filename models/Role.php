<?php
declare(strict_types=1);

final class Role
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT id, name, label FROM roles ORDER BY id')->fetchAll();
    }

    public function findByName(string $name): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, name, label FROM roles WHERE name = :name');
        $statement->execute(['name' => $name]);
        $role = $statement->fetch();

        return $role === false ? null : $role;
    }

    public function userHasPermission(int $userId, string $permission): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1
             FROM user_roles ur
             JOIN role_permissions rp ON rp.role_id = ur.role_id
             JOIN permissions p ON p.id = rp.permission_id
             JOIN users u ON u.id = ur.user_id
             WHERE ur.user_id = :user_id AND p.name = :permission AND u.active = 1'
        );
        $statement->execute(['user_id' => $userId, 'permission' => $permission]);

        return $statement->fetchColumn() !== false;
    }

    public function forUser(int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT r.id, r.name, r.label
             FROM user_roles ur JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);
        $role = $statement->fetch();

        return $role === false ? null : $role;
    }

    public function assign(int $userId, int $roleId): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)
             ON CONFLICT(user_id) DO UPDATE SET role_id = excluded.role_id, assigned_at = CURRENT_TIMESTAMP'
        );
        $statement->execute(['user_id' => $userId, 'role_id' => $roleId]);
    }
}
