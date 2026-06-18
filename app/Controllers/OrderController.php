<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

class OrderController extends BaseController
{
    protected PDO $pdo;

    public function __construct(array &$app)
    {
        parent::__construct($app);
        $this->pdo = ($this->app['db'])();
    }

    public function store(): void
    {
        $data = $this->input();
        $customerId = $data['customer_id'] ?? null;
        $items = $data['items'] ?? [];
        if (!$customerId || empty($items)) { $this->json(['error'=>'invalid_payload'],422); return; }

        $this->pdo->beginTransaction();
        $orderNumber = 'ORD-' . time();
        $subtotal = 0.0;
        foreach ($items as $it) { $subtotal += ((float)$it['unit_price'] * (int)$it['quantity']); }
        $shipping = (float)($data['shipping'] ?? 0);
        $tax = (float)($data['tax'] ?? 0);
        $total = $subtotal + $shipping + $tax;

        $ins = $this->pdo->prepare('INSERT INTO orders (order_number, customer_id, status, subtotal, shipping, tax, total, assigned_sales_rep_id, shipping_address, billing_address, created_at) VALUES (:on, :cid, :status, :sub, :ship, :tax, :tot, :sr, :ship_addr, :bill_addr, NOW())');
        $ins->execute([':on'=>$orderNumber, ':cid'=>$customerId, ':status'=>'pending_payment', ':sub'=>$subtotal, ':ship'=>$shipping, ':tax'=>$tax, ':tot'=>$total, ':sr'=>$data['assigned_sales_rep_id'] ?? null, ':ship_addr'=>json_encode($data['shipping_address'] ?? null), ':bill_addr'=>json_encode($data['billing_address'] ?? null)]);
        $orderId = (int)$this->pdo->lastInsertId();

        $stmtItem = $this->pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES (:order_id, :product_id, :quantity, :unit_price, :total_price)');
        foreach ($items as $it) {
            $stmtItem->execute([':order_id'=>$orderId, ':product_id'=>$it['product_id'], ':quantity'=>$it['quantity'], ':unit_price'=>$it['unit_price'], ':total_price'=>((float)$it['unit_price']*(int)$it['quantity'])]);
        }

        $this->pdo->commit();
        $this->json(['order_id'=>$orderId,'order_number'=>$orderNumber],201);
    }

    public function show(): void
    {
        $id = $this->getIdFromUri();
        if (!$id) { $this->json(['error'=>'id required'], 422); return; }
        $stmt = $this->pdo->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
        $stmt->execute([':id'=>$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) { $this->json(['error'=>'not_found'],404); return; }
        $stmt2 = $this->pdo->prepare('SELECT * FROM order_items WHERE order_id = :id');
        $stmt2->execute([':id'=>$id]);
        $items = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        $order['items'] = $items;
        $this->json($order);
    }

    protected function getIdFromUri(): ?int
    {
        $parts = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
        $last = end($parts);
        return is_numeric($last) ? (int)$last : null;
    }
}
