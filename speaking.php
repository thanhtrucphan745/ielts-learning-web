<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

require_student();
$currentUser = auth_user() ?? [];
$studentId = (int) ($currentUser['id'] ?? 0);

ensure_skill_uploads_table($conn);
ensure_speaking_submissions_table($conn);

$testId = isset($_GET['test_id']) ? (int) $_GET['test_id'] : 0;
$test = null;
$payload = null;
$message = '';
$errorMessage = '';
$answerText = '';
$allowedAudioExts = ['mp3', 'wav', 'ogg', 'm4a'];
$uploadsDir = __DIR__ . '/uploads/speaking';
$submissionDir = $uploadsDir . '/submissions';

if (!is_dir($uploadsDir)) {
    @mkdir($uploadsDir, 0755, true);
}
if (!is_dir($submissionDir)) {
    @mkdir($submissionDir, 0755, true);
}

if ($testId > 0) {
    $stmt = $conn->prepare('SELECT id, title, description, filename, original_name FROM skill_uploads WHERE id = ? AND skill = ? LIMIT 1');
    if ($stmt) {
        $skill = 'speaking';
        $stmt->bind_param('is', $testId, $skill);
        $stmt->execute();
        $test = $stmt->get_result()?->fetch_assoc();
        $stmt->close();
    }

    if (!$test) {
        header('Location: speaking.php');
        exit;
    }

    $jsonPath = $uploadsDir . '/' . ($test['filename'] ?? '');
    if (is_file($jsonPath) && is_readable($jsonPath)) {
        $raw = @file_get_contents($jsonPath);
        $payload = json_decode((string) $raw, true);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $answerText = trim((string) ($_POST['answer_text'] ?? ''));
        $audioFile = $_FILES['audio_file'] ?? null;
        $audioSelected = $audioFile && isset($audioFile['name']) && $audioFile['error'] !== UPLOAD_ERR_NO_FILE;
        $audioFilename = null;
        $audioOriginalName = null;
        $audioMime = null;
        $audioSize = null;

        if ($answerText === '' && !$audioSelected) {
            $errorMessage = 'Bạn phải nhập câu trả lời text hoặc upload audio.';
        }

        if ($audioSelected && $errorMessage === '') {
            if ($audioFile['error'] !== UPLOAD_ERR_OK) {
                $errorMessage = 'Lỗi upload audio. Mã lỗi: ' . (int) $audioFile['error'];
            } else {
                $audioExt = strtolower(pathinfo($audioFile['name'], PATHINFO_EXTENSION));
                if (!in_array($audioExt, $allowedAudioExts, true)) {
                    $errorMessage = 'Audio chỉ nhận định dạng .mp3, .wav, .ogg hoặc .m4a.';
                }
            }
        }

        if ($errorMessage === '' && $audioSelected) {
            $safeAudioName = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', basename($audioFile['name']));
            $randomSuffix = function_exists('random_bytes') ? bin2hex(random_bytes(6)) : uniqid('', true);
            $targetName = date('Ymd_His') . '_' . $randomSuffix . '_' . $safeAudioName;
            $targetPath = $submissionDir . '/' . $targetName;

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $audioMime = $finfo ? finfo_file($finfo, $audioFile['tmp_name']) : '';
            if ($finfo) {
                finfo_close($finfo);
            }

            if (!move_uploaded_file($audioFile['tmp_name'], $targetPath)) {
                $errorMessage = 'Không thể lưu file audio, kiểm tra quyền ghi thư mục uploads/speaking/submissions.';
            } else {
                $audioFilename = $targetName;
                $audioOriginalName = $audioFile['name'];
                $audioSize = (int) $audioFile['size'];
            }
        }

        if ($errorMessage === '') {
            $stmt = $conn->prepare('INSERT INTO speaking_submissions (student_id, test_id, answer_text, audio_filename, audio_original_name, audio_mime, audio_size, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            if ($stmt) {
                $status = 'submitted';
                $stmt->bind_param('iisssssis', $studentId, $testId, $answerText, $audioFilename, $audioOriginalName, $audioMime, $audioSize, $status);
                if ($stmt->execute()) {
                    $message = 'Nộp bài thành công, chờ giảng viên chấm.';
                    $answerText = '';
                } else {
                    $errorMessage = 'Không thể lưu bài nộp. Vui lòng thử lại sau.';
                }
                $stmt->close();
            } else {
                $errorMessage = 'Lỗi cơ sở dữ liệu khi nộp bài.';
            }
        }
    }
}

$testList = [];
if ($testId === 0) {
    $stmt = $conn->prepare('SELECT id, title, description, created_at FROM skill_uploads WHERE skill = ? ORDER BY created_at DESC, id DESC');
    if ($stmt) {
        $skill = 'speaking';
        $stmt->bind_param('s', $skill);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $testList[] = $row;
            }
        }
        $stmt->close();
    }
}

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function render_speaking_part(array $part): string
{
    $html = '';
    $label = isset($part['part']) ? escape_html((string) $part['part']) : 'Part';
    $html .= '<div class="mb-4">';
    $html .= '<h5 class="mb-3">' . $label . '</h5>';

    if (isset($part['questions']) && is_array($part['questions']) && !empty($part['questions'])) {
        $html .= '<div class="mb-3"><strong>Câu hỏi</strong><ol class="ps-3">';
        foreach ($part['questions'] as $question) {
            $html .= '<li>' . escape_html((string) $question) . '</li>';
        }
        $html .= '</ol></div>';
    }

    if (isset($part['cue_card']) && trim((string) $part['cue_card']) !== '') {
        $html .= '<div class="mb-3"><strong>Cue Card</strong><div class="bg-light border rounded-3 p-3">' . nl2br(escape_html((string) $part['cue_card'])) . '</div></div>';
    }

    if (isset($part['points']) && is_array($part['points']) && !empty($part['points'])) {
        $html .= '<div class="mb-3"><strong>Points</strong><ul class="ps-3">';
        foreach ($part['points'] as $point) {
            $html .= '<li>' . escape_html((string) $point) . '</li>';
        }
        $html .= '</ul></div>';
    }

    $html .= '</div>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Speaking Assignments - eLEARNING</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/nav.php'; ?>

    <div class="container-fluid bg-primary py-5 mb-5 page-header">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <h1 class="display-3 text-white animated slideInDown">Speaking Assignments</h1>
                    <p class="text-white mb-0">Chọn đề Speaking để nộp bài qua text hoặc audio.</p>
                    <nav aria-label="breadcrumb" class="mt-3">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a class="text-white" href="index.php">Home</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">Speaking</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="container-xxl py-5">
        <div class="container">
            <?php if ($testId === 0): ?>
                <div class="row gy-4">
                    <?php if (empty($testList)): ?>
                        <div class="col-12">
                            <div class="alert alert-secondary">Chưa có đề Speaking nào được đăng tải.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($testList as $item): ?>
                            <div class="col-lg-6">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo escape_html((string) ($item['title'] ?? '')); ?></h5>
                                        <p class="card-text text-muted"><?php echo escape_html((string) ($item['description'] ?? '')); ?></p>
                                        <p class="text-muted small mb-3">Đăng tải: <?php echo escape_html((string) ($item['created_at'] ?? '')); ?></p>
                                        <a href="speaking.php?test_id=<?php echo (int) $item['id']; ?>" class="btn btn-primary">Làm bài</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <?php if ($message !== ''): ?>
                            <div class="alert alert-success mb-4"><?php echo escape_html($message); ?></div>
                        <?php endif; ?>
                        <?php if ($errorMessage !== ''): ?>
                            <div class="alert alert-danger mb-4"><?php echo escape_html($errorMessage); ?></div>
                        <?php endif; ?>

                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <h2 class="mb-3"><?php echo escape_html((string) ($test['title'] ?? '')); ?></h2>
                                <p class="text-muted"><?php echo escape_html((string) ($test['description'] ?? '')); ?></p>
                                <hr>
                                <?php if (!is_array($payload)): ?>
                                    <div class="alert alert-warning">Không thể đọc nội dung đề. Vui lòng báo quản trị.</div>
                                <?php else: ?>
                                    <?php foreach ($payload['parts'] as $part): ?>
                                        <?php echo render_speaking_part(is_array($part) ? $part : []); ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <form method="post" enctype="multipart/form-data" class="card border-0 shadow-sm p-4">
                            <div class="mb-4">
                                <label class="form-label">Câu trả lời (text)</label>
                                <textarea name="answer_text" rows="6" class="form-control" placeholder="Nhập câu trả lời của bạn ở đây..."><?php echo escape_html($answerText); ?></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Upload audio (tùy chọn)</label>
                                <input type="file" name="audio_file" class="form-control" accept=".mp3,.wav,.ogg,.m4a,audio/*" />
                                <div class="form-text">Audio tùy chọn. Nếu có, hệ thống sẽ lưu và giảng viên có thể nghe.</div>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Speaking</button>
                            <a href="speaking.php" class="btn btn-outline-secondary ms-2">Quay lại danh sách</a>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="container-fluid bg-dark text-light footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Quick Link</h4>
                    <a class="btn btn-link" href="about.php">About Us</a>
                    <a class="btn btn-link" href="contact.php">Contact Us</a>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
