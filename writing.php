<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

require_student();
$currentUser = auth_user() ?? [];
$studentId = (int) ($currentUser['id'] ?? 0);
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$basePath = $basePath === '' ? '/' : $basePath . '/';

$selectedId = isset($_GET['test_id']) ? (int) $_GET['test_id'] : 0;
$message = '';
$successfulSubmission = false;
$record = null;
$payload = null;
$latestSubmission = null;
$assignments = [];

function count_utf8_words(string $text): int
{
    $text = trim($text);
    if ($text === '') {
        return 0;
    }

    $words = preg_split('/[^\p{L}\p{N}\']+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    return is_array($words) ? count($words) : 0;
}

function escape_text(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if (!isset($conn) || !($conn instanceof mysqli) || !ensure_skill_uploads_table($conn) || !ensure_writing_submissions_table($conn)) {
    $message = 'Không thể kết nối hoặc truy vấn dữ liệu Writing. Vui lòng thử lại sau.';
}

if ($selectedId > 0 && $message === '') {
    $stmt = $conn->prepare('SELECT id, title, description, filename, original_name, created_at FROM skill_uploads WHERE id = ? AND skill = ? LIMIT 1');
    if ($stmt) {
        $skill = 'writing';
        $stmt->bind_param('is', $selectedId, $skill);
        $stmt->execute();
        $row = $stmt->get_result()?->fetch_assoc();
        $stmt->close();

        if ($row) {
            $record = $row;
            $jsonPath = __DIR__ . '/uploads/writing/' . $record['filename'];
            if (!is_file($jsonPath) || !is_readable($jsonPath)) {
                $message = 'File Writing không tồn tại hoặc không thể đọc: ' . escape_text($record['filename']);
            } else {
                $jsonRaw = @file_get_contents($jsonPath);
                $payload = json_decode((string) $jsonRaw, true);
                if (!is_array($payload)) {
                    $message = 'JSON Writing tải lên không hợp lệ: ' . json_last_error_msg();
                }
            }
        } else {
            $message = 'Không tìm thấy đề Writing phù hợp.';
        }
    } else {
        $message = 'Lỗi truy vấn đề Writing.';
    }
}

if ($selectedId > 0 && $_SERVER['REQUEST_METHOD'] === 'POST' && $message === '') {
    $answerText = trim((string) ($_POST['answer_text'] ?? ''));
    if ($answerText === '') {
        $message = 'Vui lòng nhập bài làm trước khi nộp.';
    } elseif (!$record || !is_array($payload)) {
        $message = 'Không có đề bài hợp lệ để nộp.';
    } else {
        $wordCount = count_utf8_words($answerText);
        $stmt = $conn->prepare('INSERT INTO writing_submissions (student_id, test_id, answer_text, word_count, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
        if ($stmt) {
            $status = 'submitted';
            $stmt->bind_param('iisis', $studentId, $selectedId, $answerText, $wordCount, $status);
            if ($stmt->execute()) {
                $successfulSubmission = true;
                $message = 'Nộp bài thành công, chờ giảng viên chấm.';
            } else {
                $message = 'Không thể lưu bài nộp. Vui lòng thử lại sau.';
            }
            $stmt->close();
        } else {
            $message = 'Lỗi lưu bài nộp.';
        }
    }
}

if ($selectedId > 0 && $message === '') {
    $stmt = $conn->prepare('SELECT id, answer_text, word_count, score, feedback, status, created_at, graded_at FROM writing_submissions WHERE student_id = ? AND test_id = ? ORDER BY created_at DESC');
    if ($stmt) {
        $stmt->bind_param('ii', $studentId, $selectedId);
        $stmt->execute();
        $latestSubmission = $stmt->get_result()?->fetch_assoc();
        $stmt->close();
    }
}

if ($selectedId <= 0 && $message === '') {
    $stmt = $conn->prepare('SELECT id, title, description, filename, original_name, created_at FROM skill_uploads WHERE skill = ? ORDER BY created_at DESC, id DESC');
    if ($stmt) {
        $skill = 'writing';
        $stmt->bind_param('s', $skill);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $assignments[] = $row;
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title><?php echo escape_text($selectedId > 0 ? 'Writing Assignment' : 'Writing Assignments'); ?> - eLEARNING</title>
    <base href="<?php echo escape_text($basePath); ?>">
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
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>

    <?php include __DIR__ . '/nav.php'; ?>

    <div class="container-fluid bg-primary py-5 mb-5 page-header">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <h1 class="display-3 text-white animated slideInDown">Writing Assignments</h1>
                    <p class="text-white mb-0">Nộp bài Writing và chờ giảng viên đánh giá.</p>
                    <nav aria-label="breadcrumb" class="mt-3">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a class="text-white" href="index.php">Home</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">Writing</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="container-xxl py-5">
        <div class="container">
            <?php if ($message !== ''): ?>
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-10">
                        <div class="alert alert-<?php echo $successfulSubmission ? 'success' : 'warning'; ?> border-0 shadow-sm"><?php echo escape_text($message); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($selectedId > 0): ?>
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <?php if (!$record): ?>
                            <div class="alert alert-danger">Không tìm thấy đề Writing.</div>
                        <?php else: ?>
                            <?php
                                $title = trim((string) ($payload['title'] ?? $record['title'] ?? ''));
                                $description = trim((string) ($payload['description'] ?? $record['description'] ?? ''));
                                $prompt = trim((string) ($payload['prompt'] ?? ''));
                                $minWords = isset($payload['min_words']) ? (int) $payload['min_words'] : 0;
                                $maxWords = isset($payload['max_words']) ? (int) $payload['max_words'] : 0;
                                $criteria = is_array($payload['criteria'] ?? null) ? $payload['criteria'] : [];
                            ?>
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body p-4">
                                    <h2 class="mb-3"><?php echo escape_text($title); ?></h2>
                                    <p class="text-muted mb-3"><?php echo escape_text($description); ?></p>
                                    <div class="mb-4">
                                        <h5>Prompt</h5>
                                        <p><?php echo nl2br(escape_text($prompt)); ?></p>
                                    </div>

                                    <?php if ($minWords > 0 || $maxWords > 0 || !empty($criteria)): ?>
                                        <div class="row g-3 mb-4">
                                            <?php if ($minWords > 0): ?>
                                                <div class="col-md-6">
                                                    <div class="bg-light border rounded-3 p-3">
                                                        <strong>Min words</strong>
                                                        <div><?php echo escape_text((string) $minWords); ?></div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($maxWords > 0): ?>
                                                <div class="col-md-6">
                                                    <div class="bg-light border rounded-3 p-3">
                                                        <strong>Max words</strong>
                                                        <div><?php echo escape_text((string) $maxWords); ?></div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($criteria)): ?>
                                            <div class="bg-light border rounded-3 p-3 mb-4">
                                                <strong>Criteria</strong>
                                                <ul class="mb-0 mt-2">
                                                    <?php foreach ($criteria as $criterion): ?>
                                                        <li><?php echo escape_text((string) $criterion); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if ($latestSubmission): ?>
                                        <div class="alert alert-info border-0 shadow-sm mb-4">
                                            <div class="fw-bold mb-2">Lượt nộp gần nhất</div>
                                            <div>Trạng thái: <?php echo escape_text((string) $latestSubmission['status']); ?></div>
                                            <div>Số từ: <?php echo escape_text((string) $latestSubmission['word_count']); ?></div>
                                            <?php if ($latestSubmission['status'] === 'graded'): ?>
                                                <div>Điểm: <?php echo escape_text((string) $latestSubmission['score']); ?></div>
                                                <div>Feedback: <?php echo nl2br(escape_text((string) $latestSubmission['feedback'])); ?></div>
                                            <?php else: ?>
                                                <div>Chờ giảng viên chấm.</div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <form method="post" action="writing.php?test_id=<?php echo escape_text((string) $selectedId); ?>">
                                        <div class="mb-4">
                                            <label for="answer_text" class="form-label">Bài làm của bạn</label>
                                            <textarea id="answer_text" name="answer_text" rows="12" class="form-control" required><?php echo isset($_POST['answer_text']) ? escape_text((string) $_POST['answer_text']) : ''; ?></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Submit Writing</button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php if (empty($assignments)): ?>
                        <div class="col-12">
                            <div class="alert alert-secondary">Chưa có đề Writing nào được tải lên.</div>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($assignments as $item): ?>
                        <div class="col-lg-6">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h4 class="card-title mb-2"><?php echo escape_text((string) ($item['title'] ?: 'Writing Assignment')); ?></h4>
                                            <p class="text-muted mb-2"><?php echo escape_text((string) $item['description']); ?></p>
                                        </div>
                                        <span class="badge bg-primary">#<?php echo escape_text((string) $item['id']); ?></span>
                                    </div>
                                    <div class="small text-muted mb-3">Uploaded: <?php echo escape_text((string) $item['created_at']); ?></div>
                                    <a href="writing.php?test_id=<?php echo escape_text((string) $item['id']); ?>" class="btn btn-primary">Làm bài</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
