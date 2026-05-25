<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

auth_require_role(1);
$user = auth_user();

$totalUsers = 0;
$adminUsers = 0;
$studentUsers = 0;

$query = "SELECT COUNT(*) AS total_users, SUM(CASE WHEN role = 1 THEN 1 ELSE 0 END) AS admin_users, SUM(CASE WHEN role = 2 THEN 1 ELSE 0 END) AS student_users, SUM(CASE WHEN role = 3 THEN 1 ELSE 0 END) AS teacher_users FROM users";
$result = $conn->query($query);
if ($result) {
    $stats = $result->fetch_assoc();
    $totalUsers = (int) ($stats['total_users'] ?? 0);
    $adminUsers = (int) ($stats['admin_users'] ?? 0);
    $studentUsers = (int) ($stats['student_users'] ?? 0);
    $teacherUsers = (int) ($stats['teacher_users'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 m-0">Admin Page</h1>
        <div class="d-flex gap-2">
            <a href="dashboard.php" class="btn btn-outline-secondary">Dashboard</a>
            <a href="logout.php" class="btn btn-outline-danger">Logout</a>
        </div>
    </div>

    <div class="alert alert-primary">
        Signed in as <strong><?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></strong> (role <?php echo (int) $user['role']; ?>)
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card shadow-sm"><div class="card-body"><h2 class="h6">Total users</h2><p class="display-6 mb-0"><?php echo $totalUsers; ?></p></div></div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm"><div class="card-body"><h2 class="h6">Admin users</h2><p class="display-6 mb-0"><?php echo $adminUsers; ?></p></div></div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm"><div class="card-body"><h2 class="h6">Student users</h2><p class="display-6 mb-0"><?php echo $studentUsers; ?></p></div></div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm"><div class="card-body"><h2 class="h6">Teacher users</h2><p class="display-6 mb-0"><?php echo $teacherUsers; ?></p></div></div>
        </div>
    </div>
</div>
</body>
</html>
