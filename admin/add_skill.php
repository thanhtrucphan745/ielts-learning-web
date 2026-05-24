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

$uploadsDir = __DIR__ . '/../uploads/' . $skill;
if (!is_dir($uploadsDir)) {
    @mkdir($uploadsDir, 0755, true);
}

// file restrictions and DB storage
$message = '';
$maxSize = 10 * 1024 * 1024; // 10 MB
$allowedExts = ['pdf','doc','docx','txt','mp3','m4a','wav','jpg','jpeg','png'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));

    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Vui lòng chọn file hợp lệ để tải lên.';
    } else {
        $f = $_FILES['file'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts, true)) {
            $message = 'Loại file không được phép.';
        } elseif ($f['size'] > $maxSize) {
            $message = 'Kích thước file vượt quá giới hạn 10MB.';
        } else {
            $safeName = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', basename($f['name']));
            $target = $uploadsDir . '/' . time() . '_' . $safeName;

            // extra mime-type check
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? finfo_file($finfo, $f['tmp_name']) : '';
            if ($finfo) finfo_close($finfo);

            if (move_uploaded_file($f['tmp_name'], $target)) {
                // store metadata JSON sidecar
                $meta = [
                    'original_name' => $f['name'],
                    'stored_name' => basename($target),
                    'title' => $title,
                    'description' => $description,
                    'uploaded_at' => date('c'),
                    'uploaded_by' => auth_user()['id'] ?? null,
                    'mime' => $mime,
                    'size' => $f['size'],
                ];
                @file_put_contents($target . '.json', json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                // try to insert into DB if available
                if (isset($conn) && $conn instanceof mysqli) {
                    $stmt = $conn->prepare('INSERT INTO skill_uploads (skill, title, description, filename, original_name, mime, size, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
                    if ($stmt) {
                        $skillParam = $skill;
                        $filenameParam = basename($target);
                        $origParam = $f['name'];
                        $mimeParam = $mime;
                        $sizeParam = (int) $f['size'];
                        $uploadedBy = auth_user()['id'] ?? null;
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
$dh = @opendir($uploadsDir);
if ($dh) {
    while (($entry = readdir($dh)) !== false) {
        if ($entry === '.' || $entry === '..') continue;
        if (substr($entry, -5) === '.json') continue;
        $metaFile = $uploadsDir . '/' . $entry . '.json';
        $meta = null;
        if (is_file($metaFile)) {
            $m = @json_decode(@file_get_contents($metaFile), true);
            if (is_array($m)) $meta = $m;
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
        <h3>Upload file cho: <?php echo htmlspecialchars(strtoupper($skill), ENT_QUOTES, 'UTF-8'); ?></h3>
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
                <label class="form-label">Chọn file</label>
                <input type="file" name="file" class="form-control" />
            </div>
            <button class="btn btn-primary">Tải lên</button>
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
