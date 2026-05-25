<?php
require_once __DIR__ . '/_layout.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['role'])) {
    $userId = (int) $_POST['user_id'];
    $role = (int) $_POST['role'];

    if (in_array($role, [1, 2, 3], true) && $userId > 0) {
        $stmt = $conn->prepare('UPDATE users SET role = ? WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('ii', $role, $userId);
            $stmt->execute();
            $stmt->close();
            $message = 'Đã cập nhật vai trò tài khoản.';
        }
    }
}

$accounts = [];
$result = $conn->query('SELECT id, name, username, email, phone, role FROM users ORDER BY id DESC LIMIT 100');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $accounts[] = $row;
    }
}

admin_render_header('Tài khoản', 'accounts', 'Cập nhật vai trò và thông tin tài khoản');
if ($message):
?>
<div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
<div class="card content-card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Vai trò tài khoản</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered align-middle" width="100%" cellspacing="0">
                <thead><tr><th>ID</th><th>Họ tên</th><th>Tên đăng nhập</th><th>Email</th><th>Số điện thoại</th><th>Vai trò</th><th>Thao tác</th></tr></thead>
                <tbody>
                    <?php if (!$accounts): ?>
                        <tr><td colspan="7" class="text-center text-muted">Không có tài khoản nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($accounts as $account): ?>
                            <tr>
                                <td><?php echo (int) $account['id']; ?></td>
                                <td><?php echo htmlspecialchars($account['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($account['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($account['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($account['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="badge badge-<?php echo ((int) $account['role'] === 1) ? 'danger' : (((int) $account['role'] === 3) ? 'warning' : 'primary'); ?>"><?php echo auth_role_label((int) $account['role']); ?></span></td>
                                <td>
                                    <form method="post" class="form-inline">
                                        <input type="hidden" name="user_id" value="<?php echo (int) $account['id']; ?>">
                                        <select name="role" class="form-control form-control-sm mr-2">
                                            <option value="1"<?php echo ((int) $account['role'] === 1) ? ' selected' : ''; ?>>Quản trị viên</option>
                                            <option value="2"<?php echo ((int) $account['role'] === 2) ? ' selected' : ''; ?>>Học viên</option>
                                            <option value="3"<?php echo ((int) $account['role'] === 3) ? ' selected' : ''; ?>>Giảng viên</option>
                                        </select>
                                        <button class="btn btn-sm btn-primary">Lưu</button>
                                    </form>
                                    <div class="mt-2">
                                        <a href="user_edit.php?id=<?php echo (int) $account['id']; ?>" class="btn btn-sm btn-info">Sửa</a>
                                        <a href="user_delete.php?id=<?php echo (int) $account['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa tài khoản này?');">Xóa</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php admin_render_footer(); ?>
