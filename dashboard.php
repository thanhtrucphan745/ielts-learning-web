<?php
require_once __DIR__ . '/auth.php';

auth_require_login();
$user = auth_user();
$role = (int) $user['role'];
$roleLabel = auth_role_label($role);

if ($role === 3) {
    header('Location: teacher/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 m-0">Dashboard</h1>
        <a href="logout.php" class="btn btn-outline-danger">Logout</a>
    </div>

    <?php if (!empty($_GET['error']) && $_GET['error'] === 'access_denied'): ?>
        <div class="alert alert-warning">Access denied for this page.</div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <p class="mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="mb-2"><strong>Username:</strong> <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="mb-3"><strong>Vai trò:</strong> <?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></p>

            <?php if ($role === 1): ?>
                <a href="admin.php" class="btn btn-primary">Go to Admin page</a>
            <?php elseif ($role === 2): ?>
                <a href="student.php" class="btn btn-success">Go to Student page</a>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
