CREATE DATABASE IF NOT EXISTS demoproject_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE demoproject_db;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    reset_otp VARCHAR(255) DEFAULT NULL,
    reset_otp_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(80) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image VARCHAR(255) DEFAULT NULL,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'pending',
    payment_status VARCHAR(40) NOT NULL DEFAULT 'pending',
    address TEXT NOT NULL,
    razorpay_order_id VARCHAR(100) DEFAULT NULL,
    razorpay_payment_id VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

INSERT INTO products (name, slug, description, category, price, stock, image) VALUES
('Oxford Premium Shirt', 'oxford-premium-shirt', 'A clean regular-fit Oxford shirt for office and smart casual outfits.', 'shirts', 1299.00, 30, 'oxford_shirt.png'),
('Midnight Slim Jeans', 'midnight-slim-jeans', 'Stretch denim with a tapered silhouette and deep indigo wash.', 'jeans', 1799.00, 25, 'midnight_jeans.png'),
('Essential Heavy Tee', 'essential-heavy-tee', 'Premium cotton T-shirt with a structured heavyweight feel.', 'tshirts', 799.00, 45, 'essential_tee.png'),
('Urban Utility Shirt', 'urban-utility-shirt', 'Modern utility-inspired shirt with minimal pocket detailing.', 'shirts', 1499.00, 18, 'utility_shirt.png'),
('Classic Black Denim', 'classic-black-denim', 'Everyday black jeans with a comfortable slim fit.', 'jeans', 1699.00, 22, 'black_denim.png'),
('Relaxed Sand Tee', 'relaxed-sand-tee', 'Relaxed-fit cotton tee in a soft neutral tone.', 'tshirts', 899.00, 40, 'sand_tee.png'),
('Textured Evening Shirt', 'textured-evening-shirt', 'A refined textured shirt designed for evening wear.', 'shirts', 1599.00, 15, 'evening_shirt.png'),
('Stone Wash Jeans', 'stone-wash-jeans', 'Vintage-inspired denim with modern stretch comfort.', 'jeans', 1899.00, 20, 'stone_jeans.png');

-- Default Admin Account (Email: admin@kgf.com | Password: admin@123)
INSERT INTO users (name, email, password, role) VALUES 
('Admin', 'admin@kgf.com', '$2y$10$jJitC/2t.3D0avRxiqveKeeCzdcgygrSTRbudHzExlle3l.jUkPv.', 'admin')
ON DUPLICATE KEY UPDATE password = VALUES(password), role = 'admin';

