<?php
require_once __DIR__ . '/_layout.php';

$userId = (int) ($_GET['id'] ?? 0);
if ($userId <= 0) {
    header('Location: users.php');
    exit;
}

$currentUser = auth_user() ?? [];
if ((int) ($currentUser['id'] ?? 0) === $userId) {
    header('Location: users.php?error=self_delete');
    exit;
}

$stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
}

header('Location: users.php');
exit;
