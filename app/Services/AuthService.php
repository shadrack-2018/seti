<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\TokenRepository;
use RuntimeException;
use DateTime;

class AuthService
{
    protected UserRepository $users;
    protected TokenRepository $tokens;
    protected MailService $mailer;

    public function __construct(UserRepository $users, TokenRepository $tokens = null, MailService $mailer = null)
    {
        $this->users = $users;
        $this->tokens = $tokens ?? new TokenRepository($this->users->getPdo());
        $this->mailer = $mailer ?? new MailService();
    }

    public function register(array $data): array
    {
        if ($this->users->findByEmail($data['email'])) {
            throw new RuntimeException('Email already registered');
        }

        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        $user = $this->users->create([
            'email' => $data['email'],
            'password_hash' => $passwordHash,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => 1
        ]);

        // Generate verification token
        $token = bin2hex(random_bytes(16));
        $expires = (new DateTime())->modify('+2 days');
        $this->tokens->createEmailVerification((int)$user['id'], $token, $expires);

        $verifyUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost:8080', '/') . '/auth/verify?token=' . $token;
        $body = "<p>Hello,</p><p>Please verify your email by clicking the link below:</p><p><a href=\"{$verifyUrl}\">Verify Email</a></p>";
        $this->mailer->send($user['email'], 'Verify your email', $body, true);

        return $user;
    }

    public function login(string $email, string $password): array
    {
        $record = $this->users->findByEmail($email);
        if (!$record) {
            throw new RuntimeException('Invalid credentials');
        }

        $passwordHash = $record['password_hash'] ?? null;
        if (!$passwordHash || !password_verify($password, $passwordHash)) {
            throw new RuntimeException('Invalid credentials');
        }

        if ((int)($record['is_active'] ?? 1) !== 1) {
            throw new RuntimeException('Account inactive');
        }

        if ((int)($record['is_email_verified'] ?? 0) !== 1) {
            throw new RuntimeException('Email not verified');
        }

        return [
            'id' => (int)$record['id'],
            'email' => $record['email'],
            'first_name' => $record['first_name'],
            'last_name' => $record['last_name']
        ];
    }

    public function verifyEmail(string $token): bool
    {
        $row = $this->tokens->findEmailVerification($token);
        if (!$row) {
            throw new RuntimeException('Invalid or expired token');
        }

        $expires = new DateTime($row['expires_at']);
        if ($expires < new DateTime()) {
            $this->tokens->deleteEmailVerification((int)$row['id']);
            throw new RuntimeException('Token expired');
        }

        // mark user verified
        $this->users->markEmailVerified((int)$row['user_id']);
        $this->tokens->deleteEmailVerification((int)$row['id']);
        return true;
    }

    public function forgotPassword(string $email): bool
    {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            // to avoid user enumeration, return true
            return true;
        }

        $token = bin2hex(random_bytes(16));
        $expires = (new DateTime())->modify('+2 hours');
        $this->tokens->createPasswordReset((int)$user['id'], $token, $expires);

        $resetUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost:8080', '/') . '/auth/reset?token=' . $token;
        $body = "<p>Hello,</p><p>Reset your password using the link below (valid for 2 hours):</p><p><a href=\"{$resetUrl}\">Reset Password</a></p>";
        $this->mailer->send($user['email'], 'Password reset', $body, true);
        return true;
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $row = $this->tokens->findPasswordReset($token);
        if (!$row) {
            throw new RuntimeException('Invalid or expired token');
        }

        $expires = new DateTime($row['expires_at']);
        if ($expires < new DateTime()) {
            $this->tokens->deletePasswordReset((int)$row['id']);
            throw new RuntimeException('Token expired');
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->users->updatePassword((int)$row['user_id'], $hash);
        $this->tokens->deletePasswordReset((int)$row['id']);
        return true;
    }
}
