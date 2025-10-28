<?php
$pageTitle = 'Dashboard | Finance Manager';
require_once __DIR__ . '/partials/header.php';

$userDb = get_user_db($_SESSION['user_db']);

function fetch_single_value(PDO $db, string $query): float
{
    $result = $db->query($query)->fetchColumn();
    return $result !== false ? (float)$result : 0.0;
}

$totalSales = fetch_single_value($userDb, 'SELECT COALESCE(SUM(amount), 0) FROM sales');
$totalPurchases = fetch_single_value($userDb, 'SELECT COALESCE(SUM(amount), 0) FROM purchases');
$bankBalance = fetch_single_value($userDb, 'SELECT COALESCE(SUM(amount), 0) FROM bank_transactions');
$cashBalance = fetch_single_value($userDb, "SELECT COALESCE(SUM(CASE WHEN movement_type = 'in' THEN amount ELSE -amount END), 0) FROM cash_movements");
$receivables = fetch_single_value($userDb, 'SELECT COALESCE(SUM(amount), 0) FROM receivables WHERE status != "closed"');
$payables = fetch_single_value($userDb, 'SELECT COALESCE(SUM(amount), 0) FROM payables WHERE status != "paid"');

$recentSales = $userDb->query('SELECT record_date, client_name, amount FROM sales ORDER BY record_date DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
$recentPurchases = $userDb->query('SELECT record_date, description, amount FROM purchases ORDER BY record_date DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="row g-4">
    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title text-muted">Total Sales</h5>
                <p class="display-6 fw-bold text-success">$<?= number_format($totalSales, 2) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title text-muted">Total Purchases</h5>
                <p class="display-6 fw-bold text-danger">$<?= number_format($totalPurchases, 2) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title text-muted">Bank Balance</h5>
                <p class="display-6 fw-bold text-primary">$<?= number_format($bankBalance, 2) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title text-muted">Cash Balance</h5>
                <p class="display-6 fw-bold text-warning">$<?= number_format($cashBalance, 2) ?></p>
            </div>
        </div>
    </div>
</div>
<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Outstanding Receivables</h5>
                <span class="badge bg-success">$<?= number_format($receivables, 2) ?></span>
            </div>
            <div class="card-body">
                <p class="mb-0 text-muted">Track customer balances to ensure timely payments.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Outstanding Payables</h5>
                <span class="badge bg-danger">$<?= number_format($payables, 2) ?></span>
            </div>
            <div class="card-body">
                <p class="mb-0 text-muted">Monitor vendor obligations and due dates.</p>
            </div>
        </div>
    </div>
</div>
<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Recent Sales</h5>
            </div>
            <div class="card-body">
                <?php if (!$recentSales): ?>
                    <p class="text-muted mb-0">No sales recorded yet.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentSales as $sale): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold"><?= sanitize($sale['client_name'] ?: 'Unnamed client') ?></div>
                                    <small class="text-muted"><?= sanitize($sale['record_date']) ?></small>
                                </div>
                                <span class="fw-bold text-success">$<?= number_format((float)$sale['amount'], 2) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Recent Purchases</h5>
            </div>
            <div class="card-body">
                <?php if (!$recentPurchases): ?>
                    <p class="text-muted mb-0">No purchases recorded yet.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentPurchases as $purchase): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold"><?= sanitize($purchase['description'] ?: 'Purchase') ?></div>
                                    <small class="text-muted"><?= sanitize($purchase['record_date']) ?></small>
                                </div>
                                <span class="fw-bold text-danger">$<?= number_format((float)$purchase['amount'], 2) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/partials/footer.php';
