<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../config.php';

auth_require_role(1);

$allowed = ['reading','listening','writing','speaking'];
$skill = strtolower(trim((string) ($_GET['skill'] ?? '')));
if (!in_array($skill, $allowed, true)) {
    header('Location: add_skill.php');
    exit;
}

$file = (string) ($_GET['file'] ?? '');
if ($file === '') {
    header('Location: add_skill.php?skill=' . urlencode($skill));
    exit;
}

$uploadsDir = __DIR__ . '/../uploads/' . $skill;
$path = realpath($uploadsDir . '/' . $file);
$uploadsRoot = realpath($uploadsDir);
// Ensure file is inside uploads dir
if ($path && $uploadsRoot !== false && strpos($path, $uploadsRoot) === 0 && is_file($path)) {
    @unlink($path);
    @unlink($path . '.json');

    if (isset($conn) && $conn instanceof mysqli) {
        $stmt = $conn->prepare('DELETE FROM skill_uploads WHERE skill = ? AND filename = ?');
        if ($stmt) {
            $stmt->bind_param('ss', $skill, $file);
            $stmt->execute();
            $stmt->close();
        }
    }
}

header('Location: add_skill.php?skill=' . urlencode($skill));
exit;
