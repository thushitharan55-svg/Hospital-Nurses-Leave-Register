<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

logout_user();
session_start();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
flash_set('success', 'You have been signed out.');
redirect('auth/login.php');
