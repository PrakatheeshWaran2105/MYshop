<?php
require 'config/bootstrap.php';
$stmt = $pdo->query('SELECT id, name, created_at, status FROM products');
print_r($stmt->fetchAll());
