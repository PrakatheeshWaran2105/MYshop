<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

define('ROOT_PATH', dirname(__DIR__));

$autoload = ROOT_PATH . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

if (class_exists(\Dotenv\Dotenv::class) && file_exists(ROOT_PATH . '/.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(ROOT_PATH);
    $dotenv->safeLoad();
}

require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/config/mail.php';

$pdo = getDatabaseConnection();

