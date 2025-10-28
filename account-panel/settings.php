<?php
$pageTitle = 'Settings | Finance Manager';
require_once __DIR__ . '/partials/header.php';

$db = get_user_db($_SESSION['user_db']);
$message = '';

$settingsKeys = [
    'business_name' => 'Business Name',
    'business_email' => 'Business Email',
    'phone_number' => 'Contact Phone',
    'bank_details' => 'Bank Details',
    'default_currency' => 'Default Currency',
    'default_vendor' => 'Preferred Vendor',
    'default_party' => 'Primary Party',
    'fiscal_year_start' => 'Fiscal Year Start',
    'notes' => 'Internal Notes',
];

$currentSettings = [];
$stmt = $db->query('SELECT key, value FROM settings');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $currentSettings[$row['key']] = $row['value'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($settingsKeys as $key => $label) {
        $value = $_POST[$key] ?? '';
        $value = is_string($value) ? trim($value) : '';

        $exists = array_key_exists($key, $currentSettings);
        if ($exists) {
            $stmt = $db->prepare('UPDATE settings SET value = :value WHERE key = :key');
        } else {
            $stmt = $db->prepare('INSERT INTO settings (key, value) VALUES (:key, :value)');
        }
        $stmt->execute([
            ':key' => $key,
            ':value' => $value,
        ]);
        $currentSettings[$key] = $value;
    }

    $message = 'Settings updated successfully.';
}
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Business Configuration</h5>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?= sanitize($message) ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="business_name">Business Name</label>
                            <input type="text" class="form-control" id="business_name" name="business_name" value="<?= sanitize($currentSettings['business_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="business_email">Business Email</label>
                            <input type="email" class="form-control" id="business_email" name="business_email" value="<?= sanitize($currentSettings['business_email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="phone_number">Contact Phone</label>
                            <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?= sanitize($currentSettings['phone_number'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="default_currency">Default Currency</label>
                            <input type="text" class="form-control" id="default_currency" name="default_currency" value="<?= sanitize($currentSettings['default_currency'] ?? '') ?>" placeholder="USD">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="default_vendor">Preferred Vendor</label>
                            <input type="text" class="form-control" id="default_vendor" name="default_vendor" value="<?= sanitize($currentSettings['default_vendor'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="default_party">Primary Party</label>
                            <input type="text" class="form-control" id="default_party" name="default_party" value="<?= sanitize($currentSettings['default_party'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="fiscal_year_start">Fiscal Year Start</label>
                            <input type="date" class="form-control" id="fiscal_year_start" name="fiscal_year_start" value="<?= sanitize($currentSettings['fiscal_year_start'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="bank_details">Bank Details</label>
                            <textarea class="form-control" id="bank_details" name="bank_details" rows="3"><?= sanitize($currentSettings['bank_details'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="notes">Internal Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4"><?= sanitize($currentSettings['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="d-grid d-md-flex justify-content-md-end mt-4">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/partials/footer.php';
