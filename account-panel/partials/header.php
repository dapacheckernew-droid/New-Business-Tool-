<?php
require_once __DIR__ . '/../../includes/init.php';
require_user();

$userName = $_SESSION['user_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Finance Dashboard' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="/account-panel/dashboard.php">Finance Manager</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userNav" aria-controls="userNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="userNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="/account-panel/dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="/account-panel/purchases.php">Purchases</a></li>
                <li class="nav-item"><a class="nav-link" href="/account-panel/sales.php">Sales</a></li>
                <li class="nav-item"><a class="nav-link" href="/account-panel/bank.php">Bank</a></li>
                <li class="nav-item"><a class="nav-link" href="/account-panel/cash.php">Cash</a></li>
                <li class="nav-item"><a class="nav-link" href="/account-panel/payables.php">Payables</a></li>
                <li class="nav-item"><a class="nav-link" href="/account-panel/receivables.php">Receivables</a></li>
                <li class="nav-item"><a class="nav-link" href="/account-panel/parties.php">Parties</a></li>
                <li class="nav-item"><a class="nav-link" href="/account-panel/vendors.php">Vendors</a></li>
                <li class="nav-item"><a class="nav-link" href="/account-panel/credits.php">Credits</a></li>
                <li class="nav-item"><a class="nav-link" href="/account-panel/sales-returns.php">Sales Returns</a></li>
                <li class="nav-item"><a class="nav-link" href="/account-panel/settings.php">Settings</a></li>
            </ul>
            <span class="navbar-text me-3">Welcome, <?= sanitize($userName) ?></span>
            <a href="/account-panel/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>
<div class="container my-4">
