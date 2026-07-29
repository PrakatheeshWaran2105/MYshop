<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);

flash('success', 'You have been successfully logged out.');
header('Location: login.php');
exit;
