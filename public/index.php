<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Router;
use App\Controllers\AuthController;

$app = require __DIR__ . '/../app/bootstrap.php';

$router = new Router($app);

$router->get('/', function() {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'app' => 'SETI Platform Scaffold']);
});

// Auth routes
$router->post('/auth/register', [AuthController::class, 'register']);
$router->post('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/logout', [AuthController::class, 'logout']);
$router->get('/auth/verify', [AuthController::class, 'verify']);
$router->post('/auth/forgot', [AuthController::class, 'forgot']);
$router->post('/auth/reset', [AuthController::class, 'reset']);

// Dispatch
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
