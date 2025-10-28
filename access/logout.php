<?php
require_once __DIR__ . '/../includes/init.php';

unset($_SESSION['admin_logged_in']);
header('Location: /access/index.php');
exit;
