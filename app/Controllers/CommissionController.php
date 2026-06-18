<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

class CommissionController extends BaseController
{
    protected PDO $pdo;

    public function __construct(array &$app)
    {
        parent::__construct($app);
        $this->pdo = ($this->app['db'])();
    }

    public function index(): void
    {
        $salesRepId = $_GET['sales_rep_id'] ?? null;
        $params = [];
        $sql = 'SELECT c.* FROM commissions c';
        if ($salesRepId) { $sql .= ' WHERE c.sales_rep_id = :sr'; $params[':sr'] = $salesRepId; }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->json($rows);
    }

    public function approve(): void
    {
        $id = $this->getIdFromUri();
        if (!$id) { $this->json(['error'=>'id required'],422); return; }
        $u = $this->pdo->prepare('UPDATE commissions SET status = "approved" WHERE id = :id');
        $u->execute([':id'=>$id]);
        $this->json(['message'=>'approved']);
    }

    public function markPaid(): void
    {
        $id = $this->getIdFromUri();
        if (!$id) { $this->json(['error'=>'id required'],422); return; }
        $p = $this->pdo->prepare('UPDATE commissions SET status = "paid", paid_at = NOW() WHERE id = :id');
        $p->execute([':id'=>$id]);
        $this->json(['message'=>'paid']);
    }

    protected function getIdFromUri(): ?int
    {
        $parts = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
        $last = end($parts);
        return is_numeric($last) ? (int)$last : null;
    }
}
