<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../config.php';

auth_require_role(1);

$allowed = ['reading','listening','writing','speaking'];
$skill = strtolower(trim((string) ($_GET['skill'] ?? '')));
if (!in_array($skill, $allowed, true)) {
    // default to reading
    $skill = 'reading';
}

$isReadingSkill = $skill === 'reading';
$isListeningSkill = $skill === 'listening';
$isWritingSkill = $skill === 'writing';
$isSpeakingSkill = $skill === 'speaking';
$message = '';
$uploadsDir = __DIR__ . '/../uploads/' . $skill;
if (!is_dir($uploadsDir)) {
    if (!@mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
        $message = 'Không tạo được thư mục uploads/' . $skill . '. Kiểm tra quyền ghi.';
    }
}

if ($isListeningSkill) {
    $audioDir = $uploadsDir . '/audio';
    if (!is_dir($audioDir)) {
        if (!@mkdir($audioDir, 0755, true) && !is_dir($audioDir)) {
            $message = 'Không tạo được thư mục uploads/listening/audio. Kiểm tra quyền ghi.';
        }
    }
}

if ($isSpeakingSkill) {
    $submissionsDir = $uploadsDir . '/submissions';
    if (!is_dir($submissionsDir)) {
        if (!@mkdir($submissionsDir, 0755, true) && !is_dir($submissionsDir)) {
            $message = 'Không tạo được thư mục uploads/speaking/submissions. Kiểm tra quyền ghi.';
        }
    }
}

// file restrictions and DB storage
$maxSize = 10 * 1024 * 1024; // 10 MB
$allowedAudioExts = ['mp3', 'wav', 'ogg'];

function validate_reading_json_payload(array $payload): array
{
    if (!isset($payload['title']) || trim((string) $payload['title']) === '') {
        return ['ok' => false, 'message' => 'JSON phải có trường title.'];
    }

    if (!isset($payload['passage']) || trim((string) $payload['passage']) === '') {
        return ['ok' => false, 'message' => 'JSON phải có trường passage.'];
    }

    if (!isset($payload['questions']) || !is_array($payload['questions']) || empty($payload['questions'])) {
        return ['ok' => false, 'message' => 'JSON phải có mảng questions hợp lệ.'];
    }

    foreach ($payload['questions'] as $index => $question) {
        if (!is_array($question)) {
            return ['ok' => false, 'message' => 'Câu hỏi ' . ((int) $index + 1) . ' không hợp lệ.'];
        }

        if (!isset($question['question']) || trim((string) $question['question']) === '') {
            return ['ok' => false, 'message' => 'Câu hỏi ' . ((int) $index + 1) . ' thiếu nội dung question.'];
        }

        if (!isset($question['options']) || !is_array($question['options']) || count($question['options']) < 2) {
            return ['ok' => false, 'message' => 'Câu hỏi ' . ((int) $index + 1) . ' phải có ít nhất 2 đáp án.'];
        }

        if (!array_key_exists('answer', $question) || !is_numeric($question['answer'])) {
            return ['ok' => false, 'message' => 'Câu hỏi ' . ((int) $index + 1) . ' thiếu answer hợp lệ.'];
        }

        if (array_key_exists('explanation', $question) && !is_string($question['explanation'])) {
            return ['ok' => false, 'message' => 'Câu hỏi ' . ((int) $index + 1) . ' có explanation không hợp lệ.'];
        }

        $answer = (int) $question['answer'];
        $optionCount = count($question['options']);
        if ($answer < 0 || $answer >= $optionCount || $answer > 3) {
            return ['ok' => false, 'message' => 'Câu hỏi ' . ((int) $index + 1) . ' có answer không hợp lệ.'];
        }
    }

    return ['ok' => true, 'message' => ''];
}

function validate_listening_json_payload(array $payload): array
{
    if (!isset($payload['title']) || trim((string) $payload['title']) === '') {
        return ['ok' => false, 'message' => 'JSON Listening phải có trường title.'];
    }

    if (!isset($payload['description']) || trim((string) $payload['description']) === '') {
        return ['ok' => false, 'message' => 'JSON Listening phải có trường description.'];
    }

    if (!isset($payload['questions']) || !is_array($payload['questions']) || empty($payload['questions'])) {
        return ['ok' => false, 'message' => 'JSON Listening phải có mảng questions hợp lệ.'];
    }

    foreach ($payload['questions'] as $index => $question) {
        if (!is_array($question)) {
            return ['ok' => false, 'message' => 'Câu hỏi ' . ((int) $index + 1) . ' không hợp lệ.'];
        }

        if (!isset($question['question']) || trim((string) $question['question']) === '') {
            return ['ok' => false, 'message' => 'Câu hỏi ' . ((int) $index + 1) . ' thiếu nội dung question.'];
        }

        if (!isset($question['options']) || !is_array($question['options']) || count($question['options']) < 2) {
            return ['ok' => false, 'message' => 'Câu hỏi ' . ((int) $index + 1) . ' phải có ít nhất 2 đáp án.'];
        }

        if (!array_key_exists('answer', $question) || !is_numeric($question['answer'])) {
            return ['ok' => false, 'message' => 'Câu hỏi ' . ((int) $index + 1) . ' thiếu answer hợp lệ.'];
        }

        if (array_key_exists('explanation', $question) && !is_string($question['explanation'])) {
            return ['ok' => false, 'message' => 'Câu hỏi ' . ((int) $index + 1) . ' có explanation không hợp lệ.'];
        }

        $answer = (int) $question['answer'];
        $optionCount = count($question['options']);
        if ($answer < 0 || $answer >= $optionCount || $answer > 3) {
            return ['ok' => false, 'message' => 'Câu hỏi ' . ((int) $index + 1) . ' có answer không hợp lệ.'];
        }
    }

    return ['ok' => true, 'message' => ''];
}

function validate_writing_json_payload(array $payload): array
{
    if (!isset($payload['title']) || trim((string) $payload['title']) === '') {
        return ['ok' => false, 'message' => 'JSON Writing phải có trường title.'];
    }

    if (!isset($payload['description']) || trim((string) $payload['description']) === '') {
        return ['ok' => false, 'message' => 'JSON Writing phải có trường description.'];
    }

    if (!isset($payload['prompt']) || trim((string) $payload['prompt']) === '') {
        return ['ok' => false, 'message' => 'JSON Writing phải có trường prompt.'];
    }

    if (array_key_exists('min_words', $payload) && !is_int($payload['min_words']) && !ctype_digit((string) $payload['min_words'])) {
        return ['ok' => false, 'message' => 'JSON Writing trường min_words phải là số nguyên.'];
    }

    if (array_key_exists('max_words', $payload) && !is_int($payload['max_words']) && !ctype_digit((string) $payload['max_words'])) {
        return ['ok' => false, 'message' => 'JSON Writing trường max_words phải là số nguyên.'];
    }

    if (array_key_exists('criteria', $payload) && !is_array($payload['criteria'])) {
        return ['ok' => false, 'message' => 'JSON Writing trường criteria phải là mảng.'];
    }

    return ['ok' => true, 'message' => ''];
}

function validate_speaking_json_payload(array $payload): array
{
    if (!isset($payload['title']) || trim((string) $payload['title']) === '') {
        return ['ok' => false, 'message' => 'JSON Speaking phải có trường title.'];
    }

    if (!isset($payload['description']) || trim((string) $payload['description']) === '') {
        return ['ok' => false, 'message' => 'JSON Speaking phải có trường description.'];
    }

    if (!isset($payload['parts']) || !is_array($payload['parts']) || empty($payload['parts'])) {
        return ['ok' => false, 'message' => 'JSON Speaking phải có mảng parts hợp lệ.'];
    }

    foreach ($payload['parts'] as $index => $part) {
        if (!is_array($part)) {
            return ['ok' => false, 'message' => 'Part ' . ((int) $index + 1) . ' không hợp lệ.'];
        }

        if (!isset($part['part']) || trim((string) $part['part']) === '') {
            return ['ok' => false, 'message' => 'Part ' . ((int) $index + 1) . ' phải có trường part.'];
        }

        if (isset($part['questions']) && !is_array($part['questions'])) {
            return ['ok' => false, 'message' => 'Part ' . ((int) $index + 1) . ' trường questions phải là mảng.'];
        }

        if (isset($part['cue_card']) && !is_string($part['cue_card'])) {
            return ['ok' => false, 'message' => 'Part ' . ((int) $index + 1) . ' trường cue_card không hợp lệ.'];
        }

        if (isset($part['points']) && !is_array($part['points'])) {
            return ['ok' => false, 'message' => 'Part ' . ((int) $index + 1) . ' trường points phải là mảng.'];
        }
    }

    return ['ok' => true, 'message' => ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));

    if ($message === '') {
        if ($isListeningSkill) {
            if (empty($_FILES['json_file']) || empty($_FILES['audio_file'])) {
                $message = 'Vui lòng chọn cả file JSON và file audio.';
            } elseif ($_FILES['json_file']['error'] !== UPLOAD_ERR_OK || $_FILES['audio_file']['error'] !== UPLOAD_ERR_OK) {
                $uploadError = $_FILES['json_file']['error'] !== UPLOAD_ERR_OK ? $_FILES['json_file']['error'] : $_FILES['audio_file']['error'];
                switch ((int) $uploadError) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $message = 'File vượt quá giới hạn cho phép.';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $message = 'File chỉ tải lên được một phần.';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $message = 'Bạn chưa chọn file.';
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $message = 'Thiếu thư mục tạm để upload.';
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $message = 'Không thể ghi file lên máy chủ.';
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $message = 'Bị chặn bởi một extension của PHP.';
                        break;
                    default:
                        $message = 'Upload thất bại (mã lỗi ' . (int) $uploadError . ').';
                        break;
                }
            }
        } elseif ($isSpeakingSkill) {
            if (empty($_FILES['json_file'])) {
                $message = 'Vui lòng chọn file JSON hợp lệ để tải lên.';
            } elseif ($_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
                switch ((int) $_FILES['json_file']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $message = 'File vượt quá giới hạn cho phép.';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $message = 'File chỉ tải lên được một phần.';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $message = 'Bạn chưa chọn file.';
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $message = 'Thiếu thư mục tạm để upload.';
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $message = 'Không thể ghi file lên máy chủ.';
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $message = 'Bị chặn bởi một extension của PHP.';
                        break;
                    default:
                        $message = 'Upload thất bại (mã lỗi ' . (int) $_FILES['json_file']['error'] . ').';
                        break;
                }
            }
        } elseif ($isReadingSkill || $isWritingSkill) {
            if (empty($_FILES['file'])) {
                $message = 'Vui lòng chọn file hợp lệ để tải lên.';
            } elseif ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                switch ((int) $_FILES['file']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $message = 'File vượt quá giới hạn cho phép.';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $message = 'File chỉ tải lên được một phần.';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $message = 'Bạn chưa chọn file.';
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $message = 'Thiếu thư mục tạm để upload.';
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $message = 'Không thể ghi file lên máy chủ.';
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $message = 'Bị chặn bởi một extension của PHP.';
                        break;
                    default:
                        $message = 'Upload thất bại (mã lỗi ' . (int) $_FILES['file']['error'] . ').';
                        break;
                }
            }
        }
    }

    if ($message === '') {
        if ($isListeningSkill) {
            $jsonFile = $_FILES['json_file'];
            $audioFile = $_FILES['audio_file'];
            $jsonExt = strtolower(pathinfo($jsonFile['name'], PATHINFO_EXTENSION));
            $audioExt = strtolower(pathinfo($audioFile['name'], PATHINFO_EXTENSION));

            if ($jsonExt !== 'json') {
                $message = 'File Listening JSON chỉ nhận .json.';
            } elseif (!in_array($audioExt, $allowedAudioExts, true)) {
                $message = 'File audio chỉ nhận .mp3, .wav hoặc .ogg.';
            } elseif ($jsonFile['size'] > $maxSize || $audioFile['size'] > $maxSize) {
                $message = 'Kích thước file vượt quá giới hạn 10MB.';
            } else {
                $jsonRaw = @file_get_contents($jsonFile['tmp_name']);
                $payload = json_decode((string) $jsonRaw, true);
                if (!is_array($payload)) {
                    $message = 'File JSON không hợp lệ: ' . json_last_error_msg();
                } else {
                    $validation = validate_listening_json_payload($payload);
                    if (!$validation['ok']) {
                        $message = $validation['message'];
                    } else {
                        if ($title === '') {
                            $title = trim((string) ($payload['title'] ?? ''));
                        }
                        if ($description === '') {
                            $description = trim((string) ($payload['description'] ?? ''));
                        }
                    }
                }
            }
        } elseif ($isReadingSkill || $isWritingSkill || $isSpeakingSkill) {
            if (!isset($_FILES['json_file'])) {
                $message = 'Vui lòng chọn file JSON hợp lệ để tải lên.';
            } else {
                $jsonFile = $_FILES['json_file'];
                $jsonExt = strtolower(pathinfo($jsonFile['name'], PATHINFO_EXTENSION));

                if ($jsonExt !== 'json') {
                    $message = 'File JSON chỉ nhận .json.';
                } elseif ($jsonFile['size'] > $maxSize) {
                    $message = 'Kích thước file vượt quá giới hạn 10MB.';
                } else {
                    $jsonRaw = @file_get_contents($jsonFile['tmp_name']);
                    $payload = json_decode((string) $jsonRaw, true);
                    if (!is_array($payload)) {
                        $message = 'File JSON không hợp lệ: ' . json_last_error_msg();
                    } else {
                        if ($isReadingSkill) {
                            $validation = validate_reading_json_payload($payload);
                        } elseif ($isWritingSkill) {
                            $validation = validate_writing_json_payload($payload);
                        } else {
                            $validation = validate_speaking_json_payload($payload);
                        }

                        if (!$validation['ok']) {
                            $message = $validation['message'];
                        } else {
                            if ($title === '') {
                                $title = trim((string) ($payload['title'] ?? ''));
                            }
                            if ($description === '') {
                                $description = trim((string) ($payload['description'] ?? ''));
                            }
                        }
                    }
                }
            }
        } else {
            $f = $_FILES['file'];
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $allowedExts = ['pdf','doc','docx','txt','mp3','m4a','wav','jpg','jpeg','png'];

            if (!in_array($ext, $allowedExts, true)) {
                $message = 'Loại file không được phép.';
            } elseif ($f['size'] > $maxSize) {
                $message = 'Kích thước file vượt quá giới hạn 10MB.';
            }
        }
    }

    if ($message === '') {
        if ($isListeningSkill) {
            $jsonSafeName = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', basename($jsonFile['name']));
            $audioSafeName = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', basename($audioFile['name']));
            $randomSuffix = function_exists('random_bytes') ? bin2hex(random_bytes(6)) : uniqid('', true);
            $jsonTargetName = date('Ymd_His') . '_' . $randomSuffix . '_' . $jsonSafeName;
            $audioTargetName = date('Ymd_His') . '_' . $randomSuffix . '_' . $audioSafeName;
            $jsonTarget = $uploadsDir . '/' . $jsonTargetName;
            $audioTarget = $audioDir . '/' . $audioTargetName;

            $jsonFinfo = finfo_open(FILEINFO_MIME_TYPE);
            $jsonMime = $jsonFinfo ? finfo_file($jsonFinfo, $jsonFile['tmp_name']) : '';
            if ($jsonFinfo) {
                finfo_close($jsonFinfo);
            }

            $audioFinfo = finfo_open(FILEINFO_MIME_TYPE);
            $audioMime = $audioFinfo ? finfo_file($audioFinfo, $audioFile['tmp_name']) : '';
            if ($audioFinfo) {
                finfo_close($audioFinfo);
            }

            if (move_uploaded_file($jsonFile['tmp_name'], $jsonTarget) && move_uploaded_file($audioFile['tmp_name'], $audioTarget)) {
                $uploadedBy = (int) (auth_user()['id'] ?? 0);

                if (isset($conn) && $conn instanceof mysqli) {
                    ensure_skill_uploads_table($conn);
                    $stmt = $conn->prepare('INSERT INTO skill_uploads (skill, title, description, filename, original_name, mime, size, audio_filename, audio_original_name, audio_mime, audio_size, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
                    if ($stmt) {
                        $skillParam = $skill;
                        $filenameParam = basename($jsonTarget);
                        $origJsonParam = $jsonFile['name'];
                        $mimeParam = (string) $jsonMime;
                        $sizeParam = (int) $jsonFile['size'];
                        $audioFilenameParam = basename($audioTarget);
                        $audioOrigParam = $audioFile['name'];
                        $audioMimeParam = (string) $audioMime;
                        $audioSizeParam = (int) $audioFile['size'];
                        $stmt->bind_param('ssssssisssii', $skillParam, $title, $description, $filenameParam, $origJsonParam, $mimeParam, $sizeParam, $audioFilenameParam, $audioOrigParam, $audioMimeParam, $audioSizeParam, $uploadedBy);
                        $stmt->execute();
                        $stmt->close();
                    }
                }

                $message = 'Tải lên thành công.';
            } else {
                $message = 'Không thể lưu file, kiểm tra quyền thư mục uploads/';
            }
        } elseif ($isSpeakingSkill) {
            $jsonFile = $_FILES['json_file'];
            $jsonSafeName = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', basename($jsonFile['name']));
            $randomSuffix = function_exists('random_bytes') ? bin2hex(random_bytes(6)) : uniqid('', true);
            $targetName = date('Ymd_His') . '_' . $randomSuffix . '_' . $jsonSafeName;
            $target = $uploadsDir . '/' . $targetName;

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? finfo_file($finfo, $jsonFile['tmp_name']) : '';
            if ($finfo) {
                finfo_close($finfo);
            }

            $jsonRaw = @file_get_contents($jsonFile['tmp_name']);
            $payload = json_decode((string) $jsonRaw, true);
            $validation = is_array($payload) ? validate_speaking_json_payload($payload) : ['ok' => false, 'message' => 'File JSON không hợp lệ: ' . json_last_error_msg()];
            if (!$validation['ok']) {
                $message = $validation['message'];
            } elseif (move_uploaded_file($jsonFile['tmp_name'], $target)) {
                $uploadedBy = (int) (auth_user()['id'] ?? 0);

                if (isset($conn) && $conn instanceof mysqli) {
                    ensure_skill_uploads_table($conn);
                    $stmt = $conn->prepare('INSERT INTO skill_uploads (skill, title, description, filename, original_name, mime, size, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
                    if ($stmt) {
                        $skillParam = $skill;
                        $filenameParam = basename($target);
                        $origJsonParam = $jsonFile['name'];
                        $mimeParam = (string) $mime;
                        $sizeParam = (int) $jsonFile['size'];
                        $stmt->bind_param('ssssssii', $skillParam, $title, $description, $filenameParam, $origJsonParam, $mimeParam, $sizeParam, $uploadedBy);
                        $stmt->execute();
                        $stmt->close();
                    }
                }

                $message = 'Tải lên thành công.';
            } else {
                $message = 'Không thể lưu file, kiểm tra quyền thư mục uploads/';
            }
        } elseif ($isReadingSkill || $isWritingSkill) {
            $f = $_FILES['file'];
            $safeName = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', basename($f['name']));
            $randomSuffix = function_exists('random_bytes') ? bin2hex(random_bytes(6)) : uniqid('', true);
            $targetName = date('Ymd_His') . '_' . $randomSuffix . '_' . $safeName;
            $target = $uploadsDir . '/' . $targetName;

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? finfo_file($finfo, $f['tmp_name']) : '';
            if ($finfo) {
                finfo_close($finfo);
            }

            if (move_uploaded_file($f['tmp_name'], $target)) {
                $uploadedBy = (int) (auth_user()['id'] ?? 0);

                if (isset($conn) && $conn instanceof mysqli) {
                    ensure_skill_uploads_table($conn);
                    $stmt = $conn->prepare('INSERT INTO skill_uploads (skill, title, description, filename, original_name, mime, size, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
                    if ($stmt) {
                        $skillParam = $skill;
                        $filenameParam = basename($target);
                        $origParam = $f['name'];
                        $mimeParam = (string) $mime;
                        $sizeParam = (int) $f['size'];
                        $stmt->bind_param('ssssssii', $skillParam, $title, $description, $filenameParam, $origParam, $mimeParam, $sizeParam, $uploadedBy);
                        $stmt->execute();
                        $stmt->close();
                    }
                }

                $message = 'Tải lên thành công.';
            } else {
                $message = 'Không thể lưu file, kiểm tra quyền thư mục uploads/';
            }
        }
    }
}

$files = [];
if (isset($conn) && $conn instanceof mysqli && ensure_skill_uploads_table($conn)) {
    $stmt = $conn->prepare('SELECT id, title, description, filename, original_name, audio_filename, audio_original_name, mime, size, audio_mime, audio_size, uploaded_by, created_at FROM skill_uploads WHERE skill = ? ORDER BY created_at DESC');
    if ($stmt) {
        $stmt->bind_param('s', $skill);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $files[] = [
                    'id' => (int) ($row['id'] ?? 0),
                    'name' => (string) ($row['filename'] ?? ''),
                    'url' => '../uploads/' . $skill . '/' . rawurlencode((string) ($row['filename'] ?? '')),
                    'meta' => $row,
                ];
            }
        }
        $stmt->close();
    }
}

?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Admin - Upload cho <?php echo htmlspecialchars(ucfirst($skill), ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>body{padding:24px;background:#f8f9fa}</style>
</head>
<body>
    <div class="container">
        <h3>Gửi file lên website cho: <?php echo htmlspecialchars(strtoupper($skill), ENT_QUOTES, 'UTF-8'); ?></h3>
        <p><a href="index.php">&larr; Về Dashboard</a></p>

        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="mb-4">
            <div class="mb-3">
                <label class="form-label">Tiêu đề</label>
                <input name="title" class="form-control" />
            </div>
            <div class="mb-3">
                <label class="form-label">Mô tả</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Chọn file JSON đề thi</label>
                <input type="file" name="json_file" class="form-control" <?php echo ($isListeningSkill || $isReadingSkill || $isWritingSkill || $isSpeakingSkill) ? 'required' : ''; ?> accept=".json,application/json,text/plain" />
            </div>

            <?php if ($isListeningSkill): ?>
                <div class="mb-3">
                    <label class="form-label">Chọn file audio</label>
                    <input type="file" name="audio_file" class="form-control" required accept=".mp3,.wav,.ogg" />
                </div>
            <?php endif; ?>

            <?php if ($isReadingSkill || $isWritingSkill || $isListeningSkill || $isSpeakingSkill): ?>
                <div style="margin-top:10px;padding:12px 14px;border:1px solid #d6d8db;border-radius:8px;background:#f8f9fa;font-size:14px;line-height:1.6;">
                    <?php if ($isReadingSkill): ?>
                        <div><strong>Hướng dẫn JSON Reading</strong></div>
                        <div>Chỉ nhận file <strong>.json</strong>.</div>
                        <div>File cần có: <strong>title</strong>, <strong>passage</strong>, <strong>questions</strong>.</div>
                        <div>Mỗi question cần có: <strong>question</strong>, <strong>options</strong>, <strong>answer</strong>.</div>
                        <div><strong>explanation</strong> là tùy chọn.</div>
                        <div>answer dùng số thứ tự option: <strong>0 = A</strong>, <strong>1 = B</strong>, <strong>2 = C</strong>, <strong>3 = D</strong>.</div>
                        <pre style="margin:10px 0 0;white-space:pre-wrap;word-break:break-word;background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:10px;">{
    "title": "Reading B1",
    "passage": "This is a short reading passage.",
    "questions": [
        {
            "question": "What is the passage about?",
            "options": ["A test", "A song", "A movie", "A game"],
            "answer": 0,
            "explanation": "The passage talks about a test."
        }
    ]
}</pre>
                    <?php elseif ($isWritingSkill): ?>
                        <div><strong>Hướng dẫn JSON Writing</strong></div>
                        <div>Chỉ nhận file <strong>.json</strong>.</div>
                        <div>File cần có: <strong>title</strong>, <strong>description</strong>, <strong>prompt</strong>.</div>
                        <div>Trường <strong>min_words</strong>, <strong>max_words</strong> và <strong>criteria</strong> là tùy chọn.</div>
                        <div>Ví dụ:</div>
                        <pre style="margin:10px 0 0;white-space:pre-wrap;word-break:break-word;background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:10px;">{
    "title": "Writing Task 1 - Email to a friend",
    "description": "Write an email about your holiday.",
    "prompt": "You recently went on a holiday. Write an email to your friend and describe where you went, what you did, and how you felt.",
    "min_words": 120,
    "max_words": 180,
    "criteria": [
        "Task Achievement",
        "Coherence and Cohesion",
        "Vocabulary",
        "Grammar"
    ]
}</pre>
                    <?php elseif ($isSpeakingSkill): ?>
                        <div><strong>Hướng dẫn JSON Speaking</strong></div>
                        <div>Chỉ nhận file <strong>.json</strong>.</div>
                        <div>File cần có: <strong>title</strong>, <strong>description</strong>, <strong>parts</strong>.</div>
                        <div>Một part có thể có <strong>questions</strong>, <strong>cue_card</strong>, và <strong>points</strong>.</div>
                        <div>Ví dụ:</div>
                        <pre style="margin:10px 0 0;white-space:pre-wrap;word-break:break-word;background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:10px;">{
    "title": "Speaking B1 - Personal Information",
    "description": "Speaking practice for B1 students",
    "parts": [
        {
            "part": "Part 1",
            "questions": [
                "What is your full name?",
                "Where do you live?",
                "What do you usually do in your free time?"
            ]
        },
        {
            "part": "Part 2",
            "cue_card": "Describe a teacher you like.",
            "points": [
                "Who this teacher is",
                "What subject they teach",
                "Why you like them"
            ]
        },
        {
            "part": "Part 3",
            "questions": [
                "Why are teachers important?",
                "Do you think online learning is effective?"
            ]
        }
    ]
}</pre>
                    <?php else: ?>
                        <div><strong>Hướng dẫn JSON Listening</strong></div>
                        <div>Chỉ nhận file <strong>.json</strong>.</div>
                        <div>File cần có: <strong>title</strong>, <strong>description</strong>, <strong>questions</strong>.</div>
                        <div>Mỗi question cần có: <strong>question</strong>, <strong>options</strong>, <strong>answer</strong>.</div>
                        <div><strong>explanation</strong> là tùy chọn.</div>
                        <div>answer dùng số thứ tự option: <strong>0 = A</strong>, <strong>1 = B</strong>, <strong>2 = C</strong>, <strong>3 = D</strong>.</div>
                        <pre style="margin:10px 0 0;white-space:pre-wrap;word-break:break-word;background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:10px;">{
    "title": "Listening B1 - Daily Routine",
    "description": "Practice listening test",
    "questions": [
        {
            "question": "What time does Anna usually wake up?",
            "options": [
                "6:00 AM",
                "6:30 AM",
                "7:00 AM",
                "7:30 AM"
            ],
            "answer": 1,
            "explanation": "In the audio, Anna says she wakes up at 6:30 AM."
        }
    ]
}</pre>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            </div>
            <button class="btn btn-primary" type="submit">Gửi lên website</button>
        </form>

        <h5>File đã tải lên</h5>
        <?php if (empty($files)): ?>
            <p class="text-muted">Chưa có file nào.</p>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($files as $f): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo htmlspecialchars($f['meta']['title'] ?? $f['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <div class="small text-muted"><?php echo htmlspecialchars($f['meta']['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div>
                            <a class="btn btn-sm btn-outline-primary me-2" href="<?php echo $f['url']; ?>" target="_blank">Xem</a>
                            <a class="btn btn-sm btn-outline-danger" href="delete_upload.php?skill=<?php echo urlencode($skill); ?>&file=<?php echo rawurlencode($f['name']); ?>">Xóa</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
