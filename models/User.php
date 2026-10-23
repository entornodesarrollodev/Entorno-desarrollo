<?php
declare(strict_types=1);

final class User
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(
        string $fullname,
        string $email,
        ?string $carnet,
        string $password,
        string $roleName,
        bool $active,
        ?string $career = null
    ): int {
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO users (fullname, email, carnet, password_hash, active, career)
                 VALUES (:fullname, :email, :carnet, :password_hash, :active, :career)'
            );
            $statement->execute([
                'fullname' => $fullname,
                'email' => $email,
                'carnet' => $carnet,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'active' => $active ? 1 : 0,
                'career' => $career,
            ]);
            $userId = (int) $this->pdo->lastInsertId();
            $role = (new Role($this->pdo))->findByName($roleName);

            if ($role === null) {
                throw new InvalidArgumentException('El rol seleccionado no existe.');
            }

            (new Role($this->pdo))->assign($userId, (int) $role['id']);
            $this->pdo->commit();

            return $userId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function authenticate(string $identifier, string $password): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, fullname, email, carnet, password_hash, active
             FROM users
             WHERE (email = :email OR carnet = :carnet) AND active = 1'
        );
        $statement->execute(['email' => $identifier, 'carnet' => $identifier]);
        $user = $statement->fetch();

        if ($user === false || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        unset($user['password_hash']);

        return $user;
    }

    public function findActiveById(int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, fullname, email, carnet, active FROM users WHERE id = :id AND active = 1'
        );
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function allWithRoles(): array
    {
        return $this->pdo->query(
            'SELECT u.id, u.fullname, u.email, u.active, r.id AS role_id, r.name AS role_name, r.label AS role_label
             FROM users u
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             ORDER BY u.id'
        )->fetchAll();
    }

    public function setActive(int $userId, bool $active): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET active = :active WHERE id = :id');
        $statement->execute(['active' => $active ? 1 : 0, 'id' => $userId]);

        if ($statement->rowCount() === 0 && $this->findById($userId) === null) {
            throw new InvalidArgumentException('La cuenta solicitada no existe.');
        }
    }

    public function findById(int $userId): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, active FROM users WHERE id = :id');
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function emailExists(string $email): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM users WHERE email = :email');
        $statement->execute(['email' => $email]);

        return $statement->fetchColumn() !== false;
    }

    public function carnetExists(string $carnet): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM users WHERE carnet = :carnet');
        $statement->execute(['carnet' => $carnet]);

        return $statement->fetchColumn() !== false;
    }
}
