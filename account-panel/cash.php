<?php
$pageTitle = 'Cash Tracker | Finance Manager';
require_once __DIR__ . '/partials/header.php';

$db = get_user_db($_SESSION['user_db']);
$message = '';
$error = '';
$editRecord = null;
$selectedType = 'in';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $recordId = isset($_POST['record_id']) ? (int)$_POST['record_id'] : null;
        $recordDate = trim($_POST['record_date'] ?? '');
        $movementType = $_POST['movement_type'] ?? 'in';
        $description = trim($_POST['description'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);

        if ($recordDate === '' || !in_array($movementType, ['in', 'out'], true) || $amount <= 0) {
            $error = 'Please provide a valid date, movement type, and amount greater than zero.';
        } else {
            $amountValue = $movementType === 'in' ? $amount : $amount;
            $params = [
                ':record_date' => $recordDate,
                ':movement_type' => $movementType,
                ':description' => $description,
                ':amount' => $amountValue,
            ];
            if ($recordId) {
                $params[':id'] = $recordId;
                $stmt = $db->prepare('UPDATE cash_movements SET record_date = :record_date, movement_type = :movement_type, description = :description, amount = :amount WHERE id = :id');
                $stmt->execute($params);
                $message = 'Cash movement updated successfully.';
            } else {
                $stmt = $db->prepare('INSERT INTO cash_movements (record_date, movement_type, description, amount) VALUES (:record_date, :movement_type, :description, :amount)');
                $stmt->execute($params);
                $message = 'Cash movement recorded successfully.';
            }
        }
    } elseif ($action === 'delete') {
        $recordId = (int)($_POST['record_id'] ?? 0);
        $db->prepare('DELETE FROM cash_movements WHERE id = :id')->execute([':id' => $recordId]);
        $message = 'Cash movement removed.';
    }
}

if (isset($_GET['edit'])) {
    $recordId = (int)$_GET['edit'];
    $stmt = $db->prepare('SELECT * FROM cash_movements WHERE id = :id');
    $stmt->execute([':id' => $recordId]);
    $editRecord = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($editRecord) {
        $selectedType = $editRecord['movement_type'] ?? 'in';
    }
}

$movements = $db->query('SELECT * FROM cash_movements ORDER BY record_date DESC, id DESC')->fetchAll(PDO::FETCH_ASSOC);
$cashBalance = (float)$db->query("SELECT COALESCE(SUM(CASE WHEN movement_type = 'in' THEN amount ELSE -amount END), 0) FROM cash_movements")->fetchColumn();
?>
<div class="row">
    <div class="col-lg-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><?= $editRecord ? 'Edit Cash Movement' : 'Add Cash Movement' ?></h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">Current cash balance: <strong>$<?= number_format($cashBalance, 2) ?></strong></div>
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
                        <label class="form-label" for="record_date">Date</label>
                        <input type="date" class="form-control" id="record_date" name="record_date" required value="<?= sanitize($editRecord['record_date'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="movement_type">Type</label>
                        <select class="form-select" id="movement_type" name="movement_type" required>
                            <option value="in" <?= $selectedType === 'in' ? 'selected' : '' ?>>Cash In</option>
                            <option value="out" <?= $selectedType === 'out' ? 'selected' : '' ?>>Cash Out</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= sanitize($editRecord['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="amount">Amount</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="amount" name="amount" required value="<?= sanitize($editRecord['amount'] ?? '') ?>">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save Movement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Cash Movements</h5>
                <span class="badge bg-secondary"><?= count($movements) ?> records</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$movements): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">No cash movements recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($movements as $movement): ?>
                            <tr>
                                <td><?= sanitize($movement['record_date']) ?></td>
                                <td><?= sanitize(ucfirst($movement['movement_type'])) ?></td>
                                <td><?= sanitize($movement['description'] ?? '') ?></td>
                                <td class="text-end">$<?= number_format((float)$movement['amount'], 2) ?></td>
                                <td class="text-end">
                                    <a href="?edit=<?= (int)$movement['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this movement?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="record_id" value="<?= (int)$movement['id'] ?>">
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
