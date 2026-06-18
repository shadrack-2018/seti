<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;
use DateTime;

class TokenRepository
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createEmailVerification(int $userId, string $token, DateTime $expiresAt): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO email_verifications (user_id, token, expires_at, created_at) VALUES (:user_id, :token, :expires_at, NOW())');
        $stmt->execute([
            ':user_id' => $userId,
            ':token' => $token,
            ':expires_at' => $expiresAt->format('Y-m-d H:i:s')
        ]);
    }

    public function findEmailVerification(string $token): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM email_verifications WHERE token = :token LIMIT 1');
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function deleteEmailVerification(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM email_verifications WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function createPasswordReset(int $userId, string $token, DateTime $expiresAt): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at, created_at) VALUES (:user_id, :token, :expires_at, NOW())');
        $stmt->execute([
            ':user_id' => $userId,
            ':token' => $token,
            ':expires_at' => $expiresAt->format('Y-m-d H:i:s')
        ]);
    }

    public function findPasswordReset(string $token): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM password_resets WHERE token = :token LIMIT 1');
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function deletePasswordReset(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM password_resets WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
