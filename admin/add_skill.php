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
$message = '';
$uploadsDir = __DIR__ . '/../uploads/' . $skill;
if (!is_dir($uploadsDir)) {
    if (!@mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
        $message = 'Không tạo được thư mục uploads/' . $skill . '. Kiểm tra quyền ghi.';
    }
}

// file restrictions and DB storage
$maxSize = 10 * 1024 * 1024; // 10 MB
$allowedExts = $isReadingSkill ? ['json'] : ['pdf','doc','docx','txt','mp3','m4a','wav','jpg','jpeg','png'];

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));

    if ($message === '') {
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

    if ($message === '') {
        $f = $_FILES['file'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts, true)) {
            $message = $isReadingSkill ? 'Chỉ được upload file .json cho Reading.' : 'Loại file không được phép.';
        } elseif ($f['size'] > $maxSize) {
            $message = 'Kích thước file vượt quá giới hạn 10MB.';
        } elseif ($isReadingSkill) {
            $jsonRaw = @file_get_contents($f['tmp_name']);
            $payload = json_decode((string) $jsonRaw, true);
            if (!is_array($payload)) {
                $message = 'File JSON không hợp lệ: ' . json_last_error_msg();
            } else {
                $validation = validate_reading_json_payload($payload);
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

        if ($message === '') {
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
$dbMetaByFile = [];
if ($isReadingSkill && isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare('SELECT filename, title, description, original_name, mime, size, uploaded_by, created_at FROM skill_uploads WHERE skill = ? ORDER BY id DESC');
    if ($stmt) {
        $readingSkill = 'reading';
        $stmt->bind_param('s', $readingSkill);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $fileKey = (string) ($row['filename'] ?? '');
                if ($fileKey !== '' && !isset($dbMetaByFile[$fileKey])) {
                    $dbMetaByFile[$fileKey] = $row;
                }
            }
        }
        $stmt->close();
    }
}

$dh = @opendir($uploadsDir);
if ($dh) {
    while (($entry = readdir($dh)) !== false) {
        if ($entry === '.' || $entry === '..') continue;
        if (!$isReadingSkill && substr($entry, -5) === '.json') continue;
        $meta = null;
        if ($isReadingSkill) {
            $meta = $dbMetaByFile[$entry] ?? null;
        } else {
            $metaFile = $uploadsDir . '/' . $entry . '.json';
            if (is_file($metaFile)) {
                $m = @json_decode(@file_get_contents($metaFile), true);
                if (is_array($m)) $meta = $m;
            }
        }
        $files[] = [
            'name' => $entry,
            'url' => '../uploads/' . $skill . '/' . rawurlencode($entry),
            'meta' => $meta,
        ];
    }
    closedir($dh);
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
                <label class="form-label">Chọn file từ máy</label>
                <input type="file" name="file" class="form-control" required accept="<?php echo $isReadingSkill ? '.json,application/json,text/plain' : '.pdf,.doc,.docx,.txt,.mp3,.m4a,.wav,.jpg,.jpeg,.png'; ?>" />
                                <?php if ($isReadingSkill): ?>
                                        <div style="margin-top:10px;padding:12px 14px;border:1px solid #d6d8db;border-radius:8px;background:#f8f9fa;font-size:14px;line-height:1.6;">
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
