<?php
require_once __DIR__ . '/_layout.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $name = trim((string) ($_POST['name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $role = (int) ($_POST['role'] ?? 2);

    if ($name === '' || $username === '' || $email === '' || $password === '') {
        $message = 'Vui lòng nhập đủ họ tên, tên đăng nhập, email và mật khẩu.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Email không hợp lệ.';
    } elseif (!in_array($role, [1, 2], true)) {
        $message = 'Vai trò không hợp lệ.';
    } else {
        $check = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        if ($check) {
            $check->bind_param('ss', $username, $email);
            $check->execute();
            $duplicate = $check->get_result()?->fetch_assoc();
            $check->close();

            if ($duplicate) {
                $message = 'Tên đăng nhập hoặc email đã tồn tại.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $insert = $conn->prepare('INSERT INTO users (name, username, email, phone, role, password) VALUES (?, ?, ?, ?, ?, ?)');
                if ($insert) {
                    $insert->bind_param('ssssis', $name, $username, $email, $phone, $role, $hashedPassword);
                    $insert->execute();
                    $insert->close();
                    $message = 'Đã tạo người dùng mới.';
                }
            }
        }
    }
}

$queryText = trim((string) ($_GET['q'] ?? ''));
$roleFilter = trim((string) ($_GET['role'] ?? 'all'));

$sql = "SELECT id, name, username, email, phone, role FROM users";
$params = [];
$types = '';
$conditions = [];

if ($queryText !== '') {
    $conditions[] = "(name LIKE ? OR username LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $like = '%' . $queryText . '%';
    $params = [$like, $like, $like, $like];
    $types .= 'ssss';
}

if (in_array($roleFilter, ['1', '2'], true)) {
    $conditions[] = 'role = ?';
    $params[] = (int) $roleFilter;
    $types .= 'i';
}

if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= ' ORDER BY id DESC';
$stmt = $conn->prepare($sql);
$users = [];
if ($stmt) {
    if ($params) {
        if (count($params) === 4) {
            $stmt->bind_param($types, $params[0], $params[1], $params[2], $params[3]);
        } elseif (count($params) === 5) {
            $stmt->bind_param($types, $params[0], $params[1], $params[2], $params[3], $params[4]);
        } else {
            $stmt->bind_param($types, $params[0]);
        }
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result?->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
}

$totals = ['total' => 0, 'admin' => 0, 'student' => 0];
$result = $conn->query("SELECT COUNT(*) AS total, SUM(CASE WHEN role = 1 THEN 1 ELSE 0 END) AS admin_users, SUM(CASE WHEN role = 2 THEN 1 ELSE 0 END) AS student_users FROM users");
if ($result) {
    $totals = $result->fetch_assoc() ?: $totals;
}

admin_render_header('Users Management', 'users', 'Manage user records');
?>
<div class="row mb-4">
    <div class="col-xl-4 col-md-6 mb-3"><div class="card content-card border-left-primary h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng người dùng</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo (int) ($totals['total'] ?? 0); ?></div></div></div></div>
    <div class="col-xl-4 col-md-6 mb-3"><div class="card content-card border-left-success h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Quản trị viên</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo (int) ($totals['admin_users'] ?? 0); ?></div></div></div></div>
    <div class="col-xl-4 col-md-6 mb-3"><div class="card content-card border-left-info h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-info text-uppercase mb-1">Học viên</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo (int) ($totals['student_users'] ?? 0); ?></div></div></div></div>
</div>

<?php if ($message): ?>
<div class="alert alert-info"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="card content-card shadow mb-4">
    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Thêm người dùng</h6></div>
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="create_user" value="1">
            <div class="row">
                <div class="col-md-4 form-group"><label>Họ tên</label><input class="form-control" name="name" required></div>
                <div class="col-md-4 form-group"><label>Tên đăng nhập</label><input class="form-control" name="username" required></div>
                <div class="col-md-4 form-group"><label>Email</label><input type="email" class="form-control" name="email" required></div>
                <div class="col-md-4 form-group"><label>Số điện thoại</label><input class="form-control" name="phone"></div>
                <div class="col-md-4 form-group"><label>Mật khẩu</label><input type="password" class="form-control" name="password" required></div>
                <div class="col-md-4 form-group"><label>Vai trò</label><select class="form-control" name="role"><option value="2">Học viên</option><option value="1">Quản trị viên</option></select></div>
            </div>
            <button class="btn btn-primary">Tạo người dùng</button>
        </form>
    </div>
</div>

<div class="card content-card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Danh sách người dùng</h6>
        <form class="form-inline" method="get">
            <input type="text" name="q" value="<?php echo htmlspecialchars($queryText, ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-sm mr-2" placeholder="Tìm theo tên, email, số điện thoại">
            <select name="role" class="form-control form-control-sm mr-2">
                <option value="all"<?php echo $roleFilter === 'all' ? ' selected' : ''; ?>>Tất cả vai trò</option>
                <option value="1"<?php echo $roleFilter === '1' ? ' selected' : ''; ?>>Quản trị viên</option>
                <option value="2"<?php echo $roleFilter === '2' ? ' selected' : ''; ?>>Học viên</option>
            </select>
            <button class="btn btn-sm btn-primary">Lọc</button>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th><th>Họ tên</th><th>Tên đăng nhập</th><th>Email</th><th>Số điện thoại</th><th>Vai trò</th><th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$users): ?>
                        <tr><td colspan="7" class="text-center text-muted">Không có người dùng nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo (int) $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="badge badge-<?php echo ((int) $user['role'] === 1) ? 'danger' : 'primary'; ?>"><?php echo auth_role_label((int) $user['role']); ?></span></td>
                                <td>
                                    <a href="user_edit.php?id=<?php echo (int) $user['id']; ?>" class="btn btn-sm btn-info mb-1">Sửa</a>
                                    <a href="user_delete.php?id=<?php echo (int) $user['id']; ?>" class="btn btn-sm btn-danger mb-1" onclick="return confirm('Xóa người dùng này?');">Xóa</a>
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
