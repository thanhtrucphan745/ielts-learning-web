<?php
require_once __DIR__ . '/auth.php';

auth_require_role(2);
$user = auth_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 m-0">Student Page</h1>
        <div class="d-flex gap-2">
            <a href="dashboard.php" class="btn btn-outline-secondary">Dashboard</a>
            <a href="logout.php" class="btn btn-outline-danger">Logout</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h5">Welcome, <?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="text-muted mb-0">You are logged in with role <?php echo (int) $user['role']; ?> (student).</p>
        </div>
    </div>
</div>
</body>
</html>
