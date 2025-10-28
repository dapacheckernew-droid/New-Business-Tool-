<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();

$systemDb = get_system_db();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create-user') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name === '' || $email === '') {
            $error = 'Name and email are required to create a user.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            if ($password === '') {
                $password = bin2hex(random_bytes(5));
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $dbFileName = 'user_' . preg_replace('/[^a-zA-Z0-9_]/', '', uniqid((string)random_int(1000, 9999), true)) . '.sqlite';
            $dbPath = $config['user_data_dir'] . '/' . $dbFileName;

            try {
                create_user_database($dbPath);
                $stmt = $systemDb->prepare('INSERT INTO users (name, email, password_hash, db_path, created_at) VALUES (:name, :email, :password_hash, :db_path, :created_at)');
                $stmt->execute([
                    ':name' => $name,
                    ':email' => strtolower($email),
                    ':password_hash' => $passwordHash,
                    ':db_path' => $dbPath,
                    ':created_at' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
                ]);
                $message = 'User created successfully. Temporary password: ' . $password;
            } catch (PDOException $exception) {
                $error = 'Could not create user: ' . $exception->getMessage();
                if (file_exists($dbPath)) {
                    unlink($dbPath);
                }
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete-user') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $stmt = $systemDb->prepare('SELECT db_path FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $systemDb->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $userId]);
            if (isset($user['db_path']) && file_exists($user['db_path'])) {
                unlink($user['db_path']);
            }
            $message = 'User removed successfully.';
        } else {
            $error = 'User not found or already deleted.';
        }
    }
}

$users = $systemDb->query('SELECT id, name, email, db_path, created_at FROM users ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Business Finance Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="/access/dashboard.php">Admin Panel</a>
        <div class="d-flex">
            <a href="/access/logout.php" class="btn btn-outline-light btn-sm">Sign out</a>
        </div>
    </div>
</nav>
<div class="container my-4">
    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Create New User</h2>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= sanitize($message) ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= sanitize($error) ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="action" value="create-user">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="text" class="form-control" id="password" name="password" placeholder="Leave blank to auto-generate">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Create User</button>
                        </div>
                    </form>
                    <p class="text-muted small mt-3">Each user receives an isolated database stored securely on the server.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Registered Users</h2>
                    <span class="badge bg-primary"><?= count($users) ?> total</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Database</th>
                                <th>Created</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (!$users): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">No users found. Create your first user above.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= sanitize($user['name']) ?></td>
                                    <td><?= sanitize($user['email']) ?></td>
                                    <td><small><?= sanitize(basename($user['db_path'])) ?></small></td>
                                    <td><small><?= sanitize((new DateTimeImmutable($user['created_at']))->format('M d, Y')) ?></small></td>
                                    <td class="text-end">
                                        <form method="post" onsubmit="return confirm('Remove this user? Their data will be permanently deleted.');" class="d-inline">
                                            <input type="hidden" name="action" value="delete-user">
                                            <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
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
</div>
</body>
</html>
