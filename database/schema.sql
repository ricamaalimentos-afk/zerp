CREATE TABLE IF NOT EXISTS customer_groups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  code VARCHAR(40) NOT NULL UNIQUE,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_customer_groups_name (name),
  INDEX idx_customer_groups_code (code)
);

CREATE TABLE IF NOT EXISTS payment_methods (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  code VARCHAR(40) NOT NULL UNIQUE,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_payment_methods_name (name),
  INDEX idx_payment_methods_code (code)
);

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(160) NOT NULL,
  base_price DECIMAL(12,2) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  image_url VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS price_tables (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  customer_group_id INT NOT NULL,
  product_id INT NOT NULL,
  price DECIMAL(12,2) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_price_tables_group_product (customer_group_id, product_id),
  FOREIGN KEY (customer_group_id) REFERENCES customer_groups(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_name VARCHAR(160) NOT NULL,
  customer_group_id INT NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'draft',
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (customer_group_id) REFERENCES customer_groups(id)
);

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  negotiated_price DECIMAL(12,2) NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  line_total DECIMAL(12,2) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE IF NOT EXISTS receivable_titles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  payment_method_id INT NULL,
  due_date DATE NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  balance DECIMAL(12,2) NOT NULL,
  status VARCHAR(20) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_receivable_status_due (status, due_date),
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)
);

CREATE TABLE IF NOT EXISTS receivable_events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title_id INT NOT NULL,
  action VARCHAR(20) NOT NULL,
  amount DECIMAL(12,2) NULL,
  payment_method_id INT NULL,
  reason VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (title_id) REFERENCES receivable_titles(id),
  FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)
);

CREATE TABLE IF NOT EXISTS audits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_name VARCHAR(80) NOT NULL,
  action VARCHAR(60) NOT NULL,
  entity VARCHAR(80) NOT NULL,
  entity_id VARCHAR(40) NOT NULL,
  before_json JSON NULL,
  after_json JSON NULL,
  ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_audits_user_entity_date (user_name, entity, created_at)
);

CREATE TABLE IF NOT EXISTS auth_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_name VARCHAR(80) NOT NULL UNIQUE,
  csrf_token VARCHAR(64) NOT NULL,
  updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL,
  INDEX idx_login_attempts_ip_date (ip, attempted_at)
);

CREATE TABLE IF NOT EXISTS company_config (
  id INT PRIMARY KEY,
  logo_url VARCHAR(255) NULL,
  updated_at DATETIME NOT NULL
);
INSERT IGNORE INTO company_config (id, logo_url, updated_at) VALUES (1, NULL, NOW());
