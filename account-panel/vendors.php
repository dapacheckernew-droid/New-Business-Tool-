<?php
$pageTitle = 'Vendors | Finance Manager';
require_once __DIR__ . '/partials/header.php';

$db = get_user_db($_SESSION['user_db']);
$message = '';
$error = '';
$editRecord = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $recordId = isset($_POST['record_id']) ? (int)$_POST['record_id'] : null;
        $name = trim($_POST['name'] ?? '');
        $contactInfo = trim($_POST['contact_info'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($name === '') {
            $error = 'Name is required for a vendor record.';
        } else {
            $params = [
                ':name' => $name,
                ':contact_info' => $contactInfo,
                ':notes' => $notes,
            ];
            if ($recordId) {
                $params[':id'] = $recordId;
                $stmt = $db->prepare('UPDATE vendors SET name = :name, contact_info = :contact_info, notes = :notes WHERE id = :id');
                $stmt->execute($params);
                $message = 'Vendor updated successfully.';
            } else {
                $stmt = $db->prepare('INSERT INTO vendors (name, contact_info, notes) VALUES (:name, :contact_info, :notes)');
                $stmt->execute($params);
                $message = 'Vendor added successfully.';
            }
        }
    } elseif ($action === 'delete') {
        $recordId = (int)($_POST['record_id'] ?? 0);
        $db->prepare('DELETE FROM vendors WHERE id = :id')->execute([':id' => $recordId]);
        $message = 'Vendor removed.';
    }
}

if (isset($_GET['edit'])) {
    $recordId = (int)$_GET['edit'];
    $stmt = $db->prepare('SELECT * FROM vendors WHERE id = :id');
    $stmt->execute([':id' => $recordId]);
    $editRecord = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$vendors = $db->query('SELECT * FROM vendors ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="row">
    <div class="col-lg-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><?= $editRecord ? 'Edit Vendor' : 'Add Vendor' ?></h5>
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
                        <label class="form-label" for="name">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required value="<?= sanitize($editRecord['name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="contact_info">Contact Info</label>
                        <textarea class="form-control" id="contact_info" name="contact_info" rows="2"><?= sanitize($editRecord['contact_info'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?= sanitize($editRecord['notes'] ?? '') ?></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save Vendor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Vendor Directory</h5>
                <span class="badge bg-secondary"><?= count($vendors) ?> records</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$vendors): ?>
                            <tr>
                                <td colspan="3" class="text-center py-4">No vendors recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($vendors as $vendor): ?>
                            <tr>
                                <td><?= sanitize($vendor['name']) ?></td>
                                <td><?= nl2br(sanitize($vendor['contact_info'] ?? '')) ?></td>
                                <td class="text-end">
                                    <a href="?edit=<?= (int)$vendor['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this vendor?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="record_id" value="<?= (int)$vendor['id'] ?>">
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
