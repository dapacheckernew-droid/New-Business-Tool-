<?php
$pageTitle = 'Credits | Finance Manager';
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
        $source = trim($_POST['source'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);

        if ($recordDate === '' || $amount <= 0) {
            $error = 'Please provide a valid date and amount greater than zero.';
        } else {
            $params = [
                ':record_date' => $recordDate,
                ':source' => $source,
                ':description' => $description,
                ':amount' => $amount,
            ];
            if ($recordId) {
                $params[':id'] = $recordId;
                $stmt = $db->prepare('UPDATE credits SET record_date = :record_date, source = :source, description = :description, amount = :amount WHERE id = :id');
                $stmt->execute($params);
                $message = 'Credit updated successfully.';
            } else {
                $stmt = $db->prepare('INSERT INTO credits (record_date, source, description, amount) VALUES (:record_date, :source, :description, :amount)');
                $stmt->execute($params);
                $message = 'Credit recorded successfully.';
            }
        }
    } elseif ($action === 'delete') {
        $recordId = (int)($_POST['record_id'] ?? 0);
        $db->prepare('DELETE FROM credits WHERE id = :id')->execute([':id' => $recordId]);
        $message = 'Credit removed.';
    }
}

if (isset($_GET['edit'])) {
    $recordId = (int)$_GET['edit'];
    $stmt = $db->prepare('SELECT * FROM credits WHERE id = :id');
    $stmt->execute([':id' => $recordId]);
    $editRecord = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$credits = $db->query('SELECT * FROM credits ORDER BY record_date DESC, id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="row">
    <div class="col-lg-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><?= $editRecord ? 'Edit Credit' : 'Add Credit' ?></h5>
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
                        <label class="form-label" for="source">Source</label>
                        <input type="text" class="form-control" id="source" name="source" value="<?= sanitize($editRecord['source'] ?? '') ?>">
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
                        <button type="submit" class="btn btn-primary">Save Credit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Credit Records</h5>
                <span class="badge bg-secondary"><?= count($credits) ?> records</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Source</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$credits): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">No credits recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($credits as $credit): ?>
                            <tr>
                                <td><?= sanitize($credit['record_date']) ?></td>
                                <td><?= sanitize($credit['source'] ?? '') ?></td>
                                <td><?= sanitize($credit['description'] ?? '') ?></td>
                                <td class="text-end">$<?= number_format((float)$credit['amount'], 2) ?></td>
                                <td class="text-end">
                                    <a href="?edit=<?= (int)$credit['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this credit?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="record_id" value="<?= (int)$credit['id'] ?>">
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
