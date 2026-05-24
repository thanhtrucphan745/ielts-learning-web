<?php
require_once __DIR__ . '/../auth.php';

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
// Ensure file is inside uploads dir
if ($path && strpos($path, realpath($uploadsDir)) === 0 && is_file($path)) {
    @unlink($path);
    @unlink($path . '.json');
}

header('Location: add_skill.php?skill=' . urlencode($skill));
exit;
