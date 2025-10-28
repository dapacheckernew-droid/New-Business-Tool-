<?php
require_once __DIR__ . '/../includes/init.php';

unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_db']);
header('Location: /account-panel/index.php');
exit;
