<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

class InventoryController extends BaseController
{
    protected PDO $pdo;

    public function __construct(array &$app)
    {
        parent::__construct($app);
        $this->pdo = ($this->app['db'])();
    }

    public function index(): void
    {
        $stmt = $this->pdo->query('SELECT i.id, i.product_id, p.name as product_name, i.quantity, i.reserved, i.location FROM inventory_items i LEFT JOIN products p ON p.id = i.product_id');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->json($rows);
    }

    public function stockIn(): void
    {
        $data = $this->input();
        $productId = $data['product_id'] ?? null;
        $qty = (int)($data['quantity'] ?? 0);
        if (!$productId || $qty <= 0) { $this->json(['error'=>'invalid_payload'],422); return; }

        $this->pdo->beginTransaction();
        $stmt = $this->pdo->prepare('SELECT id, quantity FROM inventory_items WHERE product_id = :pid LIMIT 1');
        $stmt->execute([':pid'=>$productId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $newQty = (int)$row['quantity'] + $qty;
            $u = $this->pdo->prepare('UPDATE inventory_items SET quantity = :q, updated_at = NOW() WHERE id = :id');
            $u->execute([':q'=>$newQty, ':id'=>$row['id']]);
            $invId = $row['id'];
        } else {
            $ins = $this->pdo->prepare('INSERT INTO inventory_items (product_id, quantity, reserved, location, created_at) VALUES (:pid, :q, 0, :loc, NOW())');
            $ins->execute([':pid'=>$productId, ':q'=>$qty, ':loc'=>$data['location'] ?? 'default']);
            $invId = (int)$this->pdo->lastInsertId();
        }

        $t = $this->pdo->prepare('INSERT INTO stock_transactions (inventory_item_id, product_id, variation_id, quantity, transaction_type, reference, user_id, notes, created_at) VALUES (:inv_id, :pid, NULL, :qty, :type, :ref, :user, :notes, NOW())');
        $t->execute([':inv_id'=>$invId, ':pid'=>$productId, ':qty'=>$qty, ':type'=>'stock_in', ':ref'=>$data['reference'] ?? null, ':user'=>$_SESSION['user_id'] ?? null, ':notes'=>$data['notes'] ?? null]);

        $this->pdo->commit();
        $this->json(['message'=>'stock_in_recorded']);
    }

    public function stockOut(): void
    {
        $data = $this->input();
        $productId = $data['product_id'] ?? null;
        $qty = (int)($data['quantity'] ?? 0);
        if (!$productId || $qty <= 0) { $this->json(['error'=>'invalid_payload'],422); return; }

        $this->pdo->beginTransaction();
        $stmt = $this->pdo->prepare('SELECT id, quantity FROM inventory_items WHERE product_id = :pid LIMIT 1 FOR UPDATE');
        $stmt->execute([':pid'=>$productId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || (int)$row['quantity'] < $qty) { $this->pdo->rollBack(); $this->json(['error'=>'insufficient_stock'],400); return; }
        $newQty = (int)$row['quantity'] - $qty;
        $u = $this->pdo->prepare('UPDATE inventory_items SET quantity = :q, updated_at = NOW() WHERE id = :id');
        $u->execute([':q'=>$newQty, ':id'=>$row['id']]);
        $t = $this->pdo->prepare('INSERT INTO stock_transactions (inventory_item_id, product_id, variation_id, quantity, transaction_type, reference, user_id, notes, created_at) VALUES (:inv_id, :pid, NULL, :qty, :type, :ref, :user, :notes, NOW())');
        $t->execute([':inv_id'=>$row['id'], ':pid'=>$productId, ':qty'=>$qty, ':type'=>'stock_out', ':ref'=>$data['reference'] ?? null, ':user'=>$_SESSION['user_id'] ?? null, ':notes'=>$data['notes'] ?? null]);
        $this->pdo->commit();
        $this->json(['message'=>'stock_out_recorded']);
    }
}
