<?php
$pageTitle = 'Purchases | Finance Manager';
require_once __DIR__ . '/partials/header.php';

$db = get_user_db($_SESSION['user_db']);
$message = '';
$error = '';
$editRecord = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $recordId = isset($_POST['record_id']) ? (int)$_POST['record_id'] : null;
        $recordDate = trim($_POST['record_date'] ?? '');
        $reference = trim($_POST['reference'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);

        if ($recordDate === '' || $amount <= 0) {
            $error = 'Please provide a valid date and amount greater than zero.';
        } else {
            if ($recordId) {
                $stmt = $db->prepare('UPDATE purchases SET record_date = :record_date, reference = :reference, description = :description, amount = :amount WHERE id = :id');
                $stmt->execute([
                    ':record_date' => $recordDate,
                    ':reference' => $reference,
                    ':description' => $description,
                    ':amount' => $amount,
                    ':id' => $recordId,
                ]);
                $message = 'Purchase updated successfully.';
            } else {
                $stmt = $db->prepare('INSERT INTO purchases (record_date, reference, description, amount) VALUES (:record_date, :reference, :description, :amount)');
                $stmt->execute([
                    ':record_date' => $recordDate,
                    ':reference' => $reference,
                    ':description' => $description,
                    ':amount' => $amount,
                ]);
                $message = 'Purchase recorded successfully.';
            }
        }
    } elseif ($action === 'delete') {
        $recordId = (int)($_POST['record_id'] ?? 0);
        $stmt = $db->prepare('DELETE FROM purchases WHERE id = :id');
        $stmt->execute([':id' => $recordId]);
        $message = 'Purchase removed.';
    }
}

if (isset($_GET['edit'])) {
    $recordId = (int)$_GET['edit'];
    $stmt = $db->prepare('SELECT * FROM purchases WHERE id = :id');
    $stmt->execute([':id' => $recordId]);
    $editRecord = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$purchases = $db->query('SELECT * FROM purchases ORDER BY record_date DESC, id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="row">
    <div class="col-lg-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><?= $editRecord ? 'Edit Purchase' : 'Add Purchase' ?></h5>
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
                        <label class="form-label" for="reference">Reference</label>
                        <input type="text" class="form-control" id="reference" name="reference" value="<?= sanitize($editRecord['reference'] ?? '') ?>">
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
                        <button type="submit" class="btn btn-primary">Save Purchase</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Purchase History</h5>
                <span class="badge bg-secondary"><?= count($purchases) ?> records</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$purchases): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">No purchases recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($purchases as $purchase): ?>
                            <tr>
                                <td><?= sanitize($purchase['record_date']) ?></td>
                                <td><?= sanitize($purchase['reference'] ?? '') ?></td>
                                <td><?= sanitize($purchase['description'] ?? '') ?></td>
                                <td class="text-end">$<?= number_format((float)$purchase['amount'], 2) ?></td>
                                <td class="text-end">
                                    <a href="?edit=<?= (int)$purchase['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this purchase?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="record_id" value="<?= (int)$purchase['id'] ?>">
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
