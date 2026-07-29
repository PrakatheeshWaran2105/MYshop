<?php
declare(strict_types=1);

function getDatabaseConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
    $port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306');
    $name = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'demoproject_db');
    $user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root');
    $pass = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? '');

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        // Ensure reset_otp columns exist on users table
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'reset_otp'")->fetchAll();
            if (empty($cols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN reset_otp VARCHAR(255) DEFAULT NULL, ADD COLUMN reset_otp_expires DATETIME DEFAULT NULL");
            }
            $pdo->exec("UPDATE products SET image = 'oxford_shirt.png' WHERE id = 1 AND (image IS NULL OR image = '')");
            $pdo->exec("UPDATE products SET image = 'midnight_jeans.png' WHERE id = 2 AND (image IS NULL OR image = '')");
            $pdo->exec("UPDATE products SET image = 'essential_tee.png' WHERE id = 3 AND (image IS NULL OR image = '')");
            $pdo->exec("UPDATE products SET image = 'utility_shirt.png' WHERE id = 4 AND (image IS NULL OR image = '')");
            $pdo->exec("UPDATE products SET image = 'black_denim.png' WHERE id = 5 AND (image IS NULL OR image = '')");
            $pdo->exec("UPDATE products SET image = 'sand_tee.png' WHERE id = 6 AND (image IS NULL OR image = '')");
            $pdo->exec("UPDATE products SET image = 'evening_shirt.png' WHERE id = 7 AND (image IS NULL OR image = '')");
            $pdo->exec("UPDATE products SET image = 'stone_jeans.png' WHERE id = 8 AND (image IS NULL OR image = '')");
            
            // Create wishlist table if not exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS wishlist (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                product_id INT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_user_product (user_id, product_id),
                CONSTRAINT fk_wishlist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_wishlist_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )");
        } catch (\Throwable $t) {
            // Ignore if tables don't exist yet
        }
    } catch (PDOException $exception) {
        http_response_code(500);
        exit('Database connection failed. Check config/.env and start MySQL.');
    }

    return $pdo;
}
