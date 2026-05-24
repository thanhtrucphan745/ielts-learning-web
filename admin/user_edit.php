<?php
require_once __DIR__ . '/_layout.php';

$userId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($userId <= 0) {
    header('Location: users.php');
    exit;
}

$message = '';

$stmt = $conn->prepare('SELECT id, name, username, email, phone, role FROM users WHERE id = ? LIMIT 1');
$user = null;
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()?->fetch_assoc();
    $stmt->close();
}

if (!$user) {
    header('Location: users.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $role = (int) ($_POST['role'] ?? 2);
    $newPassword = (string) ($_POST['password'] ?? '');

    if ($name === '' || $username === '' || $email === '') {
        $message = 'Name, username, and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Email is invalid.';
    } elseif (!in_array($role, [1, 2], true)) {
        $message = 'Role is invalid.';
    } else {
        $check = $conn->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ? LIMIT 1');
        if ($check) {
            $check->bind_param('ssi', $username, $email, $userId);
            $check->execute();
            $duplicate = $check->get_result()?->fetch_assoc();
            $check->close();

            if ($duplicate) {
                $message = 'Username or email already exists.';
            } else {
                if ($newPassword !== '') {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $update = $conn->prepare('UPDATE users SET name = ?, username = ?, email = ?, phone = ?, role = ?, password = ? WHERE id = ?');
                    if ($update) {
                        $update->bind_param('ssssisi', $name, $username, $email, $phone, $role, $hashedPassword, $userId);
                        $update->execute();
                        $update->close();
                        $message = 'User updated.';
                    }
                } else {
                    $update = $conn->prepare('UPDATE users SET name = ?, username = ?, email = ?, phone = ?, role = ? WHERE id = ?');
                    if ($update) {
                        $update->bind_param('ssssii', $name, $username, $email, $phone, $role, $userId);
                        $update->execute();
                        $update->close();
                        $message = 'User updated.';
                    }
                }

                $stmt = $conn->prepare('SELECT id, name, username, email, phone, role FROM users WHERE id = ? LIMIT 1');
                if ($stmt) {
                    $stmt->bind_param('i', $userId);
                    $stmt->execute();
                    $user = $stmt->get_result()?->fetch_assoc();
                    $stmt->close();
                }
            }
        }
    }
}

admin_render_header('Edit User', 'users', 'Update user account information');
if ($message):
?>
<div class="alert alert-info"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card content-card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Edit user #<?php echo (int) $user['id']; ?></h6>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Name</label>
                            <input class="form-control" name="name" value="<?php echo htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Username</label>
                            <input class="form-control" name="username" value="<?php echo htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Phone</label>
                            <input class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Role</label>
                            <select class="form-control" name="role">
                                <option value="1"<?php echo ((int) $user['role'] === 1) ? ' selected' : ''; ?>>Admin</option>
                                <option value="2"<?php echo ((int) $user['role'] === 2) ? ' selected' : ''; ?>>Student</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>New password</label>
                            <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current password">
                        </div>
                    </div>
                    <button class="btn btn-primary">Save changes</button>
                    <a href="users.php" class="btn btn-secondary">Back</a>
                </form>
            </div>
        </div>
    </div>
</div>
<?php admin_render_footer(); ?>
