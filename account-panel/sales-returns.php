<?php
$pageTitle = 'Sales Returns | Finance Manager';
require_once __DIR__ . '/partials/header.php';

$db = get_user_db($_SESSION['user_db']);
$message = '';
$error = '';
$editRecord = null;

$salesOptions = $db->query('SELECT id, record_date, client_name, amount FROM sales ORDER BY record_date DESC')->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $recordId = isset($_POST['record_id']) ? (int)$_POST['record_id'] : null;
        $saleId = isset($_POST['sale_id']) && $_POST['sale_id'] !== '' ? (int)$_POST['sale_id'] : null;
        $recordDate = trim($_POST['record_date'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        if ($recordDate === '' || $amount <= 0) {
            $error = 'Please provide a valid date and amount greater than zero.';
        } else {
            $params = [
                ':sale_id' => $saleId,
                ':record_date' => $recordDate,
                ':amount' => $amount,
                ':reason' => $reason,
            ];
            if ($recordId) {
                $params[':id'] = $recordId;
                $stmt = $db->prepare('UPDATE sales_returns SET sale_id = :sale_id, record_date = :record_date, amount = :amount, reason = :reason WHERE id = :id');
                $stmt->execute($params);
                $message = 'Sales return updated successfully.';
            } else {
                $stmt = $db->prepare('INSERT INTO sales_returns (sale_id, record_date, amount, reason) VALUES (:sale_id, :record_date, :amount, :reason)');
                $stmt->execute($params);
                $message = 'Sales return recorded successfully.';
            }
        }
    } elseif ($action === 'delete') {
        $recordId = (int)($_POST['record_id'] ?? 0);
        $db->prepare('DELETE FROM sales_returns WHERE id = :id')->execute([':id' => $recordId]);
        $message = 'Sales return removed.';
    }
}

if (isset($_GET['edit'])) {
    $recordId = (int)$_GET['edit'];
    $stmt = $db->prepare('SELECT * FROM sales_returns WHERE id = :id');
    $stmt->execute([':id' => $recordId]);
    $editRecord = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$salesReturns = $db->query('SELECT sr.*, s.client_name, s.record_date AS sale_date FROM sales_returns sr LEFT JOIN sales s ON s.id = sr.sale_id ORDER BY sr.record_date DESC, sr.id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="row">
    <div class="col-lg-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><?= $editRecord ? 'Edit Sales Return' : 'Add Sales Return' ?></h5>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?= sanitize($message) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= sanitize($error) ?></div>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="action" value="save">
                    <?php if ($editRecord): ?>
                        <input type="hidden" name="record_id" value="<?= (int)$editRecord['id'] ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label" for="sale_id">Linked Sale</label>
                        <select class="form-select" id="sale_id" name="sale_id">
                            <option value="">Unlinked Return</option>
                            <?php foreach ($salesOptions as $sale): ?>
                                <option value="<?= (int)$sale['id'] ?>" <?= (($editRecord['sale_id'] ?? '') == $sale['id']) ? 'selected' : '' ?>>
                                    #<?= (int)$sale['id'] ?> — <?= sanitize($sale['client_name'] ?: 'Client') ?> (<?= sanitize($sale['record_date']) ?>, $<?= number_format((float)$sale['amount'], 2) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="record_date">Return Date</label>
                        <input type="date" class="form-control" id="record_date" name="record_date" required value="<?= sanitize($editRecord['record_date'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="amount">Amount</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="amount" name="amount" required value="<?= sanitize($editRecord['amount'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="reason">Reason</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3"><?= sanitize($editRecord['reason'] ?? '') ?></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save Sales Return</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Sales Return History</h5>
                <span class="badge bg-secondary"><?= count($salesReturns) ?> records</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Sale</th>
                            <th>Amount</th>
                            <th>Reason</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$salesReturns): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">No sales returns recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($salesReturns as $return): ?>
                            <tr>
                                <td><?= sanitize($return['record_date']) ?></td>
                                <td>
                                    <?php if (!empty($return['sale_id'])): ?>
                                        <div class="fw-semibold">Sale #<?= (int)$return['sale_id'] ?></div>
                                        <small class="text-muted"><?= sanitize($return['client_name'] ?? 'Client') ?> on <?= sanitize($return['sale_date'] ?? '-') ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Unlinked</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">$<?= number_format((float)$return['amount'], 2) ?></td>
                                <td><?= sanitize($return['reason'] ?? '') ?></td>
                                <td class="text-end">
                                    <a href="?edit=<?= (int)$return['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this sales return?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="record_id" value="<?= (int)$return['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/partials/footer.php';
