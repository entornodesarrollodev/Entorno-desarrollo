<?php
declare(strict_types=1);

final class Student
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(
        string $fullname,
        string $email,
        ?string $phone,
        string $career,
        int $year
    ): void {
        $userStatement = $this->pdo->prepare(
            "SELECT u.id FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id
             WHERE u.email = :email AND r.name = 'estudiante'"
        );
        $userStatement->execute(['email' => $email]);
        $userId = $userStatement->fetchColumn();

        $statement = $this->pdo->prepare(
            'INSERT INTO students (user_id, fullname, email, phone, career, year)
             VALUES (:user_id, :fullname, :email, :phone, :career, :year)'
        );
        $statement->execute([
            'user_id' => $userId === false ? null : (int) $userId,
            'fullname' => $fullname,
            'email' => $email,
            'phone' => $phone,
            'career' => $career,
            'year' => $year,
        ]);
    }
}
