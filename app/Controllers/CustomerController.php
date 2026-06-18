<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Validators\InputValidator;

class CustomerController extends BaseController
{
    protected PDO $pdo;

    public function __construct(array &$app)
    {
        parent::__construct($app);
        $this->pdo = ($this->app['db'])();
    }

    public function index(): void
    {
        $stmt = $this->pdo->query('SELECT id, type, first_name, last_name, business_name, email, phone, status FROM customers');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->json($rows);
    }

    public function store(): void
    {
        $data = $this->input();
        $required = ['email','type'];
        $missing = InputValidator::requireFields($data, $required);
        if (!empty($missing)) { $this->json(['error'=>'missing_fields','fields'=>$missing],422); return; }
        if (!InputValidator::isEmail($data['email'])) { $this->json(['error'=>'invalid_email'],422); return; }

        $stmt = $this->pdo->prepare('INSERT INTO customers (type, first_name, last_name, business_name, contact_person, email, phone, status, created_at) VALUES (:type,:first_name,:last_name,:business_name,:contact_person,:email,:phone,:status,NOW())');
        $stmt->execute([
            ':type'=>$data['type'],
            ':first_name'=>$data['first_name'] ?? null,
            ':last_name'=>$data['last_name'] ?? null,
            ':business_name'=>$data['business_name'] ?? null,
            ':contact_person'=>$data['contact_person'] ?? null,
            ':email'=>$data['email'],
            ':phone'=>$data['phone'] ?? null,
            ':status'=>$data['status'] ?? 'pending'
        ]);
        $id = (int)$this->pdo->lastInsertId();
        $this->json(['id'=>$id],201);
    }
}
