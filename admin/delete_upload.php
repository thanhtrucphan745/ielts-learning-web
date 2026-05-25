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
        if (function_exists('ensure_skill_uploads_table')) {
            ensure_skill_uploads_table($conn);
        }

        if ($skill === 'listening') {
            $stmt = $conn->prepare('SELECT audio_filename FROM skill_uploads WHERE skill = ? AND filename = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('ss', $skill, $file);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result ? $result->fetch_assoc() : null;
                $stmt->close();
                if ($row && !empty($row['audio_filename'])) {
                    $audioPath = $uploadsDir . '/audio/' . basename((string) $row['audio_filename']);
                    if (is_file($audioPath)) {
                        @unlink($audioPath);
                    }
                }
            }
        }

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
