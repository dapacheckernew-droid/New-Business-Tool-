<?php
$pageTitle = 'Payables | Finance Manager';
require_once __DIR__ . '/partials/header.php';

$db = get_user_db($_SESSION['user_db']);
$message = '';
$error = '';
$editRecord = null;

$statuses = ['pending' => 'Pending', 'partial' => 'Partially Paid', 'paid' => 'Paid'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $recordId = isset($_POST['record_id']) ? (int)$_POST['record_id'] : null;
        $recordDate = trim($_POST['record_date'] ?? '');
        $partyName = trim($_POST['party_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $dueDate = trim($_POST['due_date'] ?? '');
        $status = $_POST['status'] ?? 'pending';

        if ($recordDate === '' || $partyName === '' || $amount <= 0 || !array_key_exists($status, $statuses)) {
            $error = 'Please provide all required information and ensure the amount is greater than zero.';
        } else {
            $params = [
                ':record_date' => $recordDate,
                ':party_name' => $partyName,
                ':description' => $description,
                ':amount' => $amount,
                ':due_date' => $dueDate ?: null,
                ':status' => $status,
            ];
            if ($recordId) {
                $params[':id'] = $recordId;
                $stmt = $db->prepare('UPDATE payables SET record_date = :record_date, party_name = :party_name, description = :description, amount = :amount, due_date = :due_date, status = :status WHERE id = :id');
                $stmt->execute($params);
                $message = 'Payable updated successfully.';
            } else {
                $stmt = $db->prepare('INSERT INTO payables (record_date, party_name, description, amount, due_date, status) VALUES (:record_date, :party_name, :description, :amount, :due_date, :status)');
                $stmt->execute($params);
                $message = 'Payable recorded successfully.';
            }
        }
    } elseif ($action === 'delete') {
        $recordId = (int)($_POST['record_id'] ?? 0);
        $db->prepare('DELETE FROM payables WHERE id = :id')->execute([':id' => $recordId]);
        $message = 'Payable removed.';
    }
}

if (isset($_GET['edit'])) {
    $recordId = (int)$_GET['edit'];
    $stmt = $db->prepare('SELECT * FROM payables WHERE id = :id');
    $stmt->execute([':id' => $recordId]);
    $editRecord = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$payables = $db->query('SELECT * FROM payables ORDER BY due_date IS NULL, due_date ASC, record_date DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="row">
    <div class="col-lg-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><?= $editRecord ? 'Edit Payable' : 'Add Payable' ?></h5>
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
                        <label class="form-label" for="record_date">Date</label>
                        <input type="date" class="form-control" id="record_date" name="record_date" required value="<?= sanitize($editRecord['record_date'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="party_name">Party</label>
                        <input type="text" class="form-control" id="party_name" name="party_name" required value="<?= sanitize($editRecord['party_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= sanitize($editRecord['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="amount">Amount</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="amount" name="amount" required value="<?= sanitize($editRecord['amount'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="due_date">Due Date</label>
                        <input type="date" class="form-control" id="due_date" name="due_date" value="<?= sanitize($editRecord['due_date'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <?php foreach ($statuses as $value => $label): ?>
                                <option value="<?= $value ?>" <?= (($editRecord['status'] ?? 'pending') === $value) ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save Payable</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Outstanding Payables</h5>
                <span class="badge bg-secondary"><?= count($payables) ?> records</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Due</th>
                            <th>Party</th>
                            <th>Status</th>
                            <th class="text-end">Amount</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$payables): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">No payables recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($payables as $payable): ?>
                            <tr>
                                <td><?= sanitize($payable['due_date'] ?: '-') ?></td>
                                <td>
                                    <div class="fw-semibold"><?= sanitize($payable['party_name']) ?></div>
                                    <small class="text-muted"><?= sanitize($payable['description'] ?? '') ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark text-uppercase small"><?= sanitize($payable['status']) ?></span></td>
                                <td class="text-end">$<?= number_format((float)$payable['amount'], 2) ?></td>
                                <td class="text-end">
                                    <a href="?edit=<?= (int)$payable['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this payable?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="record_id" value="<?= (int)$payable['id'] ?>">
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
