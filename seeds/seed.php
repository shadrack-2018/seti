<?php
declare(strict_types=1);

/**
 * seeds/seed.php
 * Usage: php seeds/seed.php
 *
 * This script will:
 * - insert roles if missing
 * - create admin user (admin@seti.local / P@ssw0rd!) if missing
 * - insert sample products, inventory, customers, a sample order and commission
 *
 * IMPORTANT: change default admin password after first login.
 */

require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

$pdo = require __DIR__ . '/../config/database.php';

function execOrLog($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

try {
    $pdo->beginTransaction();

    // Roles
    $roles = [
        ['name'=>'Super Administrator','slug'=>'super_admin','description'=>'Full system access'],
        ['name'=>'Administrator','slug'=>'admin','description'=>'Operational admin'],
        ['name'=>'Sales Representative','slug'=>'sales_rep','description'=>'Sales representative'],
        ['name'=>'Business Customer','slug'=>'business_customer','description'=>'Business purchasing account'],
        ['name'=>'Individual Customer','slug'=>'individual_customer','description'=>'Retail purchasing account']
    ];
    foreach ($roles as $r) {
        execOrLog($pdo, "INSERT INTO roles (name, slug, description, created_at) SELECT :name, :slug, :desc, NOW() WHERE NOT EXISTS (SELECT 1 FROM roles WHERE slug = :slug)", [':name'=>$r['name'], ':slug'=>$r['slug'], ':desc'=>$r['description']]);
    }

    // Admin user
    $adminEmail = 'admin@seti.local';
    $stmt = execOrLog($pdo, "SELECT id FROM users WHERE email = :email LIMIT 1", [':email'=>$adminEmail]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        $password = 'P@ssw0rd!';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        execOrLog($pdo, "INSERT INTO users (email, password_hash, first_name, last_name, phone, is_active, is_email_verified, created_at) VALUES (:email, :hash, :fn, :ln, :phone, 1, 1, NOW())", [
            ':email'=>$adminEmail, ':hash'=>$hash, ':fn'=>'Super', ':ln'=>'Admin', ':phone'=>'0000000000'
        ]);
        $adminId = (int)$pdo->lastInsertId();
        echo "Created admin user {$adminEmail} with password {$password}\n";
    } else {
        $adminId = (int)$admin['id'];
        echo "Admin user already exists (id={$adminId}).\n";
    }

    // Assign role: super_admin
    $stmt = execOrLog($pdo, "SELECT id FROM roles WHERE slug = 'super_admin' LIMIT 1");
    $role = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($role) {
        execOrLog($pdo, "INSERT INTO user_roles (user_id, role_id) SELECT :uid, :rid WHERE NOT EXISTS (SELECT 1 FROM user_roles WHERE user_id = :uid AND role_id = :rid)", [':uid'=>$adminId, ':rid'=>$role['id']]);
    }

    // Sample products
    $products = [
        ['sku'=>'PROD-001','name'=>'Acme Widget A','price'=>1200.00,'status'=>'active'],
        ['sku'=>'PROD-002','name'=>'Acme Widget B','price'=>850.00,'status'=>'active'],
        ['sku'=>'PROD-003','name'=>'Acme Gadget','price'=>450.00,'status'=>'active'],
    ];
    foreach ($products as $p) {
        execOrLog($pdo, "INSERT INTO products (sku, name, slug, price, status, created_at) SELECT :sku, :name, :slug, :price, :status, NOW() WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = :sku)", [
            ':sku'=>$p['sku'], ':name'=>$p['name'], ':slug'=>strtolower(preg_replace('/[^a-z0-9]+/','-', $p['name'])), ':price'=>$p['price'], ':status'=>$p['status']
        ]);
    }

    // Fill inventory for products
    $stmt = execOrLog($pdo, "SELECT id FROM products");
    $prodRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($prodRows as $idx=>$pr) {
        $pid = (int)$pr['id'];
        $qty = 50 + ($idx * 10);
        execOrLog($pdo, "INSERT INTO inventory_items (product_id, quantity, reserved, location, created_at) SELECT :pid, :q, 0, 'default', NOW() WHERE NOT EXISTS (SELECT 1 FROM inventory_items WHERE product_id = :pid)", [':pid'=>$pid, ':q'=>$qty]);
    }

    // Sample customers
    $customers = [
        ['type'=>'business','business_name'=>'ABC Enterprises','contact_person'=>'John Doe','email'=>'contact@abc.example','phone'=>'0711000001','status'=>'active'],
        ['type'=>'individual','first_name'=>'Jane','last_name'=>'Smith','email'=>'jane.smith@example','phone'=>'0711000002','status'=>'active'],
    ];
    foreach ($customers as $c) {
        $stmt = execOrLog($pdo, "SELECT id FROM customers WHERE email = :email LIMIT 1", [':email'=>$c['email']]);
        if ($stmt->fetch()) continue;
        execOrLog($pdo, "INSERT INTO customers (type, first_name, last_name, business_name, contact_person, email, phone, status, created_at) VALUES (:type, :fn, :ln, :bn, :cp, :email, :phone, :status, NOW())", [
            ':type'=>$c['type'],
            ':fn'=>$c['first_name'] ?? null,
            ':ln'=>$c['last_name'] ?? null,
            ':bn'=>$c['business_name'] ?? null,
            ':cp'=>$c['contact_person'] ?? null,
            ':email'=>$c['email'],
            ':phone'=>$c['phone'] ?? null,
            ':status'=>$c['status']
        ]);
    }

    // Create a sample order and commission for first customer & product
    $stmt = execOrLog($pdo, "SELECT id FROM customers ORDER BY id ASC LIMIT 1");
    $cust = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = execOrLog($pdo, "SELECT id, price FROM products ORDER BY id ASC LIMIT 1");
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cust && $prod) {
        $stmtCheck = execOrLog($pdo, "SELECT id FROM orders WHERE customer_id = :cid LIMIT 1", [':cid'=>$cust['id']]);
        if (!$stmtCheck->fetch()) {
            $orderNumber = 'ORD-' . time();
            $unitPrice = (float)$prod['price'];
            $quantity = 2;
            $subtotal = $unitPrice * $quantity;
            $shipping = 100.00;
            $tax = 0.00;
            $total = $subtotal + $shipping + $tax;
            execOrLog($pdo, "INSERT INTO orders (order_number, customer_id, status, subtotal, shipping, tax, total, assigned_sales_rep_id, created_at) VALUES (:on, :cid, 'shipped', :sub, :ship, :tax, :tot, :sr, NOW())", [
                ':on'=>$orderNumber, ':cid'=>$cust['id'], ':sub'=>$subtotal, ':ship'=>$shipping, ':tax'=>$tax, ':tot'=>$total, ':sr'=>$adminId
            ]);
            $orderId = (int)$pdo->lastInsertId();
            execOrLog($pdo, "INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES (:oid, :pid, :qty, :up, :tp)", [
                ':oid'=>$orderId, ':pid'=>$prod['id'], ':qty'=>$quantity, ':up'=>$unitPrice, ':tp'=>($unitPrice * $quantity)
            ]);

            // Create commission (10% percentage)
            $commissionValue = 10.00; // percent
            $calculatedAmount = round(($subtotal * ($commissionValue / 100.0)), 2);
            execOrLog($pdo, "INSERT INTO commissions (order_id, sales_rep_id, commission_type, commission_value, calculated_amount, status, created_at) VALUES (:oid, :sr, 'percentage', :cval, :camt, 'pending', NOW())", [
                ':oid'=>$orderId, ':sr'=>$adminId, ':cval'=>$commissionValue, ':camt'=>$calculatedAmount
            ]);
        }
    }

    $pdo->commit();
    echo "Seeding complete.\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Seed failed: " . $e->getMessage() . "\n";
    exit(1);
}
