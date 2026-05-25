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
        $message = 'Vui lòng nhập họ tên, tên đăng nhập và email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Email không hợp lệ.';
    } elseif (!in_array($role, [1, 2, 3], true)) {
        $message = 'Vai trò không hợp lệ.';
    } else {
        $check = $conn->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ? LIMIT 1');
        if ($check) {
            $check->bind_param('ssi', $username, $email, $userId);
            $check->execute();
            $duplicate = $check->get_result()?->fetch_assoc();
            $check->close();

            if ($duplicate) {
                $message = 'Tên đăng nhập hoặc email đã tồn tại.';
            } else {
                if ($newPassword !== '') {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $update = $conn->prepare('UPDATE users SET name = ?, username = ?, email = ?, phone = ?, role = ?, password = ? WHERE id = ?');
                    if ($update) {
                        $update->bind_param('ssssisi', $name, $username, $email, $phone, $role, $hashedPassword, $userId);
                        $update->execute();
                        $update->close();
                        $message = 'Đã cập nhật người dùng.';
                    }
                } else {
                    $update = $conn->prepare('UPDATE users SET name = ?, username = ?, email = ?, phone = ?, role = ? WHERE id = ?');
                    if ($update) {
                        $update->bind_param('ssssii', $name, $username, $email, $phone, $role, $userId);
                        $update->execute();
                        $update->close();
                        $message = 'Đã cập nhật người dùng.';
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

admin_render_header('Sửa người dùng', 'users', 'Cập nhật thông tin tài khoản');
if ($message):
?>
<div class="alert alert-info"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card content-card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Sửa người dùng #<?php echo (int) $user['id']; ?></h6>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Họ tên</label>
                            <input class="form-control" name="name" value="<?php echo htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Tên đăng nhập</label>
                            <input class="form-control" name="username" value="<?php echo htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Số điện thoại</label>
                            <input class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Vai trò</label>
                            <select class="form-control" name="role">
                                <option value="1"<?php echo ((int) $user['role'] === 1) ? ' selected' : ''; ?>>Quản trị viên</option>
                                <option value="2"<?php echo ((int) $user['role'] === 2) ? ' selected' : ''; ?>>Học viên</option>
                                <option value="3"<?php echo ((int) $user['role'] === 3) ? ' selected' : ''; ?>>Giảng viên</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Mật khẩu mới</label>
                            <input type="password" class="form-control" name="password" placeholder="Để trống nếu không đổi mật khẩu">
                        </div>
                    </div>
                    <button class="btn btn-primary">Lưu thay đổi</button>
                    <a href="users.php" class="btn btn-secondary">Quay lại</a>
                </form>
            </div>
        </div>
    </div>
</div>
<?php admin_render_footer(); ?>
