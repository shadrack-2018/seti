-- Sample seed data for SETI Platform
USE seti_platform;

-- Roles
INSERT IGNORE INTO roles (id, name, slug, description) VALUES
(1,'Super Administrator','super_admin','Full system access'),
(2,'Administrator','admin','Operational admin'),
(3,'Sales Representative','sales_rep','Sales representative'),
(4,'Business Customer','business_customer','Business purchasing account'),
(5,'Individual Customer','individual_customer','Retail purchasing account');

-- Admin user
INSERT IGNORE INTO users (id, email, password_hash, first_name, last_name, phone, is_active, is_email_verified, created_at) VALUES
(1, 'admin@seti.local', '