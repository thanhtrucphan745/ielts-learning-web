<?php
require_once __DIR__ . '/_layout.php';

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
    <div class="col-xl-4 col-md-6 mb-3"><div class="card content-card border-left-primary h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total users</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo (int) ($totals['total'] ?? 0); ?></div></div></div></div>
    <div class="col-xl-4 col-md-6 mb-3"><div class="card content-card border-left-success h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Admins</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo (int) ($totals['admin_users'] ?? 0); ?></div></div></div></div>
    <div class="col-xl-4 col-md-6 mb-3"><div class="card content-card border-left-info h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-info text-uppercase mb-1">Students</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo (int) ($totals['student_users'] ?? 0); ?></div></div></div></div>
</div>

<div class="card content-card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">User list</h6>
        <form class="form-inline" method="get">
            <input type="text" name="q" value="<?php echo htmlspecialchars($queryText, ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-sm mr-2" placeholder="Search name, email, phone">
            <select name="role" class="form-control form-control-sm mr-2">
                <option value="all"<?php echo $roleFilter === 'all' ? ' selected' : ''; ?>>All roles</option>
                <option value="1"<?php echo $roleFilter === '1' ? ' selected' : ''; ?>>Admin</option>
                <option value="2"<?php echo $roleFilter === '2' ? ' selected' : ''; ?>>Student</option>
            </select>
            <button class="btn btn-sm btn-primary">Filter</button>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Phone</th><th>Role</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$users): ?>
                        <tr><td colspan="7" class="text-center text-muted">No users found.</td></tr>
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
                                    <a href="user_edit.php?id=<?php echo (int) $user['id']; ?>" class="btn btn-sm btn-info mb-1">Edit</a>
                                    <a href="user_delete.php?id=<?php echo (int) $user['id']; ?>" class="btn btn-sm btn-danger mb-1" onclick="return confirm('Delete this user?');">Delete</a>
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
