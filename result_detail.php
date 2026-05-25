<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

auth_start_session();
auth_require_login();
$currentUser = auth_user();
$role = (int) ($currentUser['role'] ?? 0);
if ($role !== 2) {
    if ($role === 1) {
        header('Location: admin/index.php');
        exit;
    }
    if ($role === 3) {
        header('Location: teacher/dashboard.php');
        exit;
    }
    header('Location: login.php');
    exit;
}

$attemptId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($attemptId <= 0) {
    header('Location: my_results.php');
    exit;
}

ensure_test_attempt_tables($conn);

$attempt = null;
$stmt = $conn->prepare('SELECT * FROM test_attempts WHERE id = ? AND student_id = ? LIMIT 1');
if ($stmt) {
    $studentId = (int) $currentUser['id'];
    $stmt->bind_param('ii', $attemptId, $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $attempt = $result ? $result->fetch_assoc() : null;
    $stmt->close();
}

if (!$attempt) {
    header('Location: my_results.php');
    exit;
}

$answers = [];
$stmt = $conn->prepare('SELECT * FROM test_attempt_answers WHERE attempt_id = ? ORDER BY question_index ASC');
if ($stmt) {
    $stmt->bind_param('i', $attemptId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $answers[] = $row;
        }
    }
    $stmt->close();
}

function skill_label(string $skill): string
{
    if ($skill === 'reading') {
        return 'Reading';
    }
    if ($skill === 'listening') {
        return 'Listening';
    }
    if ($skill === 'writing') {
        return 'Writing';
    }
    if ($skill === 'speaking') {
        return 'Speaking';
    }
    return ucfirst($skill);
}

function format_selected_answer($answer, $options): string
{
    if ($answer === null || $answer === '') {
        return 'Chưa chọn';
    }

    $answer = (int) $answer;
    $text = ''; 
    if (is_array($options) && isset($options[$answer])) {
        $text = ' - ' . htmlspecialchars((string) $options[$answer], ENT_QUOTES, 'UTF-8');
    }

    return htmlspecialchars(chr(65 + $answer), ENT_QUOTES, 'UTF-8') . $text;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Chi tiết kết quả - eLEARNING</title>
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
                    <h1 class="display-3 text-white animated slideInDown">Chi tiết kết quả</h1>
                    <p class="text-white mb-0">Xem lại các câu hỏi, đáp án và trạng thái đúng/sai của bạn.</p>
                    <nav aria-label="breadcrumb" class="mt-3">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a class="text-white" href="index.php">Home</a></li>
                            <li class="breadcrumb-item"><a class="text-white" href="my_results.php">Bài đã làm</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">Chi tiết</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="container-xxl py-5">
        <div class="container">
            <div class="row justify-content-center mb-4">
                <div class="col-lg-10">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-start">
                                <div>
                                    <h3 class="mb-2"><?php echo htmlspecialchars((string) ($attempt['test_title'] ?? 'Bài kiểm tra'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p class="text-muted mb-1">Kỹ năng: <?php echo htmlspecialchars(skill_label((string) ($attempt['skill'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p class="text-muted mb-0">Ngày làm: <?php echo htmlspecialchars((string) ($attempt['submitted_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                                <div class="text-end">
                                    <div class="h5 mb-1">Điểm: <?php echo htmlspecialchars((string) ($attempt['score'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars((string) ($attempt['total_questions'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="h4 text-primary mb-1">Band: <?php echo htmlspecialchars((string) ($attempt['band_score'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (empty($answers)): ?>
                        <div class="alert alert-info border-0 shadow-sm">Không tìm thấy chi tiết câu trả lời.</div>
                    <?php else: ?>
                        <?php foreach ($answers as $index => $answer): ?>
                            <div class="card mb-3 <?php echo !empty($answer['is_correct']) ? 'border-success' : 'border-danger'; ?> shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="mb-1">Câu <?php echo htmlspecialchars((string) ($answer['question_index'] + 1), ENT_QUOTES, 'UTF-8'); ?></h5>
                                        <span class="badge <?php echo !empty($answer['is_correct']) ? 'bg-success' : 'bg-danger'; ?>"><?php echo !empty($answer['is_correct']) ? 'Đúng' : 'Sai'; ?></span>
                                    </div>
                                    <p class="mb-3 text-dark"><?php echo nl2br(htmlspecialchars((string) ($answer['question_text'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></p>
                                    <div class="row gy-2">
                                        <div class="col-md-6">
                                            <div class="bg-light rounded p-3">
                                                <div class="text-muted small mb-1">Đáp án bạn chọn</div>
                                                <div class="fw-semibold text-<?php echo !empty($answer['is_correct']) ? 'success' : 'danger'; ?>">
                                                    <?php echo isset($answer['selected_answer']) && $answer['selected_answer'] !== null ? htmlspecialchars(chr(65 + (int) $answer['selected_answer']), ENT_QUOTES, 'UTF-8') : 'Chưa chọn'; ?>
                                                    <?php if ($answer['selected_text'] !== null && trim((string) $answer['selected_text']) !== ''): ?>
                                                        - <?php echo htmlspecialchars((string) $answer['selected_text'], ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="bg-light rounded p-3">
                                                <div class="text-muted small mb-1">Đáp án đúng</div>
                                                <div class="fw-semibold text-success">
                                                    <?php echo htmlspecialchars(chr(65 + (int) ($answer['correct_answer'] ?? -1)), ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php if (trim((string) ($answer['correct_text'] ?? '')) !== ''): ?>
                                                        - <?php echo htmlspecialchars((string) $answer['correct_text'], ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if (trim((string) ($answer['explanation'] ?? '')) !== ''): ?>
                                        <div class="mt-3 bg-white border rounded p-3">
                                            <div class="text-muted small mb-1">Giải thích</div>
                                            <div><?php echo nl2br(htmlspecialchars((string) $answer['explanation'], ENT_QUOTES, 'UTF-8')); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="mt-4">
                        <a href="my_results.php" class="btn btn-outline-primary">Quay lại Bài đã làm</a>
                    </div>
                </div>
            </div>
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
