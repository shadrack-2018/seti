<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Repositories\TokenRepository;
use App\Services\AuthService;
use App\Services\MailService;

class AuthController extends BaseController
{
    protected UserRepository $users;
    protected AuthService $authService;

    public function __construct(array &$app)
    {
        parent::__construct($app);
        $pdo = ($this->app['db'])();
        $this->users = new UserRepository($pdo);
        $tokens = new TokenRepository($pdo);
        $mailer = new MailService();
        $this->authService = new AuthService($this->users, $tokens, $mailer);
    }

    public function register(): void
    {
        $data = $this->input();
        $required = ['email','password','first_name','last_name'];
        foreach ($required as $k) {
            if (empty($data[$k])) {
                $this->json(['error' => "$k is required"], 422);
                return;
            }
        }

        try {
            $user = $this->authService->register($data);
            $this->json(['message' => 'registered', 'user' => $user], 201);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function login(): void
    {
        $data = $this->input();
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        if (!$email || !$password) {
            $this->json(['error' => 'email and password required'], 422);
            return;
        }

        try {
            $user = $this->authService->login($email, $password);
            $_SESSION['user_id'] = $user['id'];
            $this->json(['message' => 'ok', 'user' => $user]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 401);
        }
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
        $this->json(['message' => 'logged_out']);
    }

    public function verify(): void
    {
        $token = $_GET['token'] ?? null;
        if (!$token) {
            $this->json(['error' => 'token required'], 422);
            return;
        }

        try {
            $this->authService->verifyEmail($token);
            // Simple HTML response for browser users
            header('Content-Type: text/html; charset=utf-8');
            echo '<h3>Email verified. You can now log in.</h3>';
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function forgot(): void
    {
        $data = $this->input();
        $email = $data['email'] ?? null;
        if (!$email) {
            $this->json(['error' => 'email required'], 422);
            return;
        }

        try {
            $this->authService->forgotPassword($email);
            $this->json(['message' => 'If this email exists, a reset link has been sent']);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function reset(): void
    {
        $data = $this->input();
        $token = $data['token'] ?? null;
        $password = $data['password'] ?? null;
        if (!$token || !$password) {
            $this->json(['error' => 'token and password required'], 422);
            return;
        }

        try {
            $this->authService->resetPassword($token, $password);
            $this->json(['message' => 'Password updated']);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
