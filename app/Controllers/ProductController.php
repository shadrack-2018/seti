<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

class ProductController extends BaseController
{
    protected PDO $pdo;

    public function __construct(array &$app)
    {
        parent::__construct($app);
        $this->pdo = ($this->app['db'])();
    }

    public function index(): void
    {
        $stmt = $this->pdo->query('SELECT id, sku, name, price, status FROM products WHERE status = "active"');
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->json($products);
    }

    public function show(): void
    {
        // naive param extraction
        $id = $this->getIdFromUri();
        if (!$id) { $this->json(['error'=>'id required'], 422); return; }
        $stmt = $this->pdo->prepare('SELECT id, sku, name, price, status FROM products WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$p) { $this->json(['error'=>'not_found'], 404); return; }
        $this->json($p);
    }

    protected function getIdFromUri(): ?int
    {
        $parts = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
        $last = end($parts);
        return is_numeric($last) ? (int)$last : null;
    }
}
