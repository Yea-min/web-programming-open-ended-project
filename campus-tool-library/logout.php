<?php
require_once __DIR__ . '/includes/auth.php';

$_SESSION = [];
session_destroy();
session_start();
flash_set('info', 'You have been logged out.');
header('Location: login.php');
exit;
