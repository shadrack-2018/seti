-- migrations/003_seed_sample_data.sql
-- Inserts sample roles, products and customers (idempotent via INSERT ... SELECT WHERE NOT EXISTS)
USE seti_platform;

-- Roles
INSERT INTO roles (name, slug, description, created_at)
SELECT 'Super Administrator', 'super_admin', 'Full system access', NOW()
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE slug = 'super_admin');

INSERT INTO roles (name, slug, description, created_at)
SELECT 'Administrator', 'admin', 'Operational admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE slug = 'admin');

INSERT INTO roles (name, slug, description, created_at)
SELECT 'Sales Representative', 'sales_rep', 'Sales representative', NOW()
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE slug = 'sales_rep');

-- Products
INSERT INTO products (sku, name, slug, price, status, created_at)
SELECT 'PROD-001', 'Acme Widget A', 'acme-widget-a', 1200.00, 'active', NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'PROD-001');

INSERT INTO products (sku, name, slug, price, status, created_at)
SELECT 'PROD-002', 'Acme Widget B', 'acme-widget-b', 850.00, 'active', NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'PROD-002');

INSERT INTO products (sku, name, slug, price, status, created_at)
SELECT 'PROD-003', 'Acme Gadget', 'acme-gadget', 450.00, 'active', NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'PROD-003');

-- Sample customers
INSERT INTO customers (type, first_name, last_name, business_name, contact_person, email, phone, status, created_at)
SELECT 'business', NULL, NULL, 'ABC Enterprises', 'John Doe', 'contact@abc.example', '0711000001', 'active', NOW()
WHERE NOT EXISTS (SELECT 1 FROM customers WHERE email = 'contact@abc.example');

INSERT INTO customers (type, first_name, last_name, business_name, contact_person, email, phone, status, created_at)
SELECT 'individual', 'Jane', 'Smith', NULL, NULL, 'jane.smith@example', '0711000002', 'active', NOW()
WHERE NOT EXISTS (SELECT 1 FROM customers WHERE email = 'jane.smith@example');
