<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Router;
use App\Controllers\AuthController;
use App\Controllers\ProductController;
use App\Controllers\CustomerController;
use App\Controllers\InventoryController;
use App\Controllers\OrderController;
use App\Controllers\CommissionController;

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

// API v1
$router->get('/api/v1/products', [ProductController::class, 'index']);
$router->get('/api/v1/products/{id}', [ProductController::class, 'show']);

$router->get('/api/v1/customers', [CustomerController::class, 'index']);
$router->post('/api/v1/customers', [CustomerController::class, 'store']);

$router->get('/api/v1/inventory', [InventoryController::class, 'index']);
$router->post('/api/v1/inventory/stock-in', [InventoryController::class, 'stockIn']);
$router->post('/api/v1/inventory/stock-out', [InventoryController::class, 'stockOut']);

$router->post('/api/v1/orders', [OrderController::class, 'store']);
$router->get('/api/v1/orders/{id}', [OrderController::class, 'show']);

$router->get('/api/v1/commissions', [CommissionController::class, 'index']);
$router->post('/api/v1/commissions/{id}/approve', [CommissionController::class, 'approve']);
$router->post('/api/v1/commissions/{id}/pay', [CommissionController::class, 'markPaid']);

// Dispatch
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
