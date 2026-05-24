<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/streak.php';

auth_start_session();
$currentUser = auth_user();
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$basePath = $basePath === '' ? '/' : $basePath . '/';
$displayName = $currentUser['name'] ?? ($currentUser['username'] ?? 'User');
$avatarText = strtoupper(mb_substr($displayName, 0, 1, 'UTF-8'));
$avatarPath = !empty($currentUser['avatar']) ? $currentUser['avatar'] : '';
$avatarUrl = $avatarPath !== '' ? $avatarPath : '';
$role = isset($currentUser['role']) ? (int) $currentUser['role'] : 0;

function reading_test_validate_payload(array $payload): array
{
    if (!isset($payload['title']) || trim((string) $payload['title']) === '') {
        return ['ok' => false, 'message' => 'File đề không đúng định dạng.'];
    }

    if (!isset($payload['passage']) || trim((string) $payload['passage']) === '') {
        return ['ok' => false, 'message' => 'File đề không đúng định dạng.'];
    }

    if (!isset($payload['questions']) || !is_array($payload['questions']) || empty($payload['questions'])) {
        return ['ok' => false, 'message' => 'File đề không đúng định dạng.'];
    }

    foreach ($payload['questions'] as $index => $question) {
        if (!is_array($question)) {
            return ['ok' => false, 'message' => 'File đề không đúng định dạng.'];
        }

        if (!isset($question['question']) || trim((string) $question['question']) === '') {
            return ['ok' => false, 'message' => 'File đề không đúng định dạng.'];
        }

        if (!isset($question['options']) || !is_array($question['options']) || count($question['options']) < 2) {
            return ['ok' => false, 'message' => 'File đề không đúng định dạng.'];
        }

        if (!array_key_exists('answer', $question) || !is_numeric($question['answer'])) {
            return ['ok' => false, 'message' => 'File đề không đúng định dạng.'];
        }

        if (array_key_exists('explanation', $question) && !is_string($question['explanation'])) {
            return ['ok' => false, 'message' => 'File đề không đúng định dạng.'];
        }

        $answer = (int) $question['answer'];
        $optionCount = count($question['options']);
        if ($answer < 0 || $answer >= $optionCount || $answer > 3) {
            return ['ok' => false, 'message' => 'File đề không đúng định dạng.'];
        }
    }

    return ['ok' => true, 'message' => ''];
}

function reading_test_option_letter(int $index): string
{
    return chr(65 + $index);
}

function reading_test_band_score(float $percentage): float
{
    if ($percentage >= 80) {
        return 6.0;
    }

    if ($percentage >= 60) {
        return 5.0;
    }

    if ($percentage >= 40) {
        return 4.0;
    }

    if ($percentage >= 20) {
        return 3.0;
    }

    return 2.0;
}

function reading_test_feedback(float $percentage): string
{
    if ($percentage >= 80) {
        return 'Bạn làm tốt phần Reading. Hãy tiếp tục luyện passages dài hơn và canh thời gian tốt hơn.';
    }

    if ($percentage >= 60) {
        return 'Bạn đang ở mức ổn. Tiếp tục luyện keyword matching và đọc chi tiết để tăng độ chính xác.';
    }

    if ($percentage >= 40) {
        return 'Bạn có nền tảng cơ bản. Cần luyện thêm skimming, scanning và đối chiếu thông tin trong passage.';
    }

    if ($percentage >= 20) {
        return 'Bạn nên quay lại luyện từ vựng học thuật và đọc hiểu câu hỏi cẩn thận hơn.';
    }

    return 'Bạn cần luyện lại từ mức cơ bản. Hãy bắt đầu với bài ngắn và xác định keyword trong câu hỏi trước.';
}

function reading_test_explanation(array $question, int $correctAnswer): string
{
    $explanation = trim((string) ($question['explanation'] ?? ''));
    if ($explanation !== '') {
        return $explanation;
    }

    $options = is_array($question['options'] ?? null) ? $question['options'] : [];
    $correctLetter = reading_test_option_letter($correctAnswer);
    $correctText = isset($options[$correctAnswer]) ? trim((string) $options[$correctAnswer]) : '';

    if ($correctText !== '') {
        return 'Giải thích: đáp án đúng là ' . $correctLetter . '. ' . $correctText . '.';
    }

    return 'Giải thích: đáp án đúng là ' . $correctLetter . '.';
}

function reading_test_load_upload(mysqli $conn, int $id): array
{
    if ($id <= 0) {
        return ['ok' => false, 'message' => 'Bài Reading không tồn tại.'];
    }

    if (!ensure_skill_uploads_table($conn)) {
        return ['ok' => false, 'message' => 'Bài Reading không tồn tại.'];
    }

    $stmt = $conn->prepare('SELECT * FROM skill_uploads WHERE id = ? AND skill = ? LIMIT 1');
    if (!$stmt) {
        return ['ok' => false, 'message' => 'Bài Reading không tồn tại.'];
    }

    $skill = 'reading';
    $stmt->bind_param('is', $id, $skill);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return ['ok' => false, 'message' => 'Bài Reading không tồn tại.'];
    }

    $uploadsDir = __DIR__ . '/uploads/reading';
    $fileName = basename((string) ($row['filename'] ?? ''));
    if ($fileName === '') {
        return ['ok' => false, 'message' => 'Không tìm thấy file đề.'];
    }

    $baseReal = realpath($uploadsDir);
    $filePath = $uploadsDir . '/' . $fileName;
    $realFilePath = realpath($filePath);
    if ($baseReal === false || $realFilePath === false || strpos($realFilePath, $baseReal) !== 0 || !is_file($realFilePath)) {
        return ['ok' => false, 'message' => 'Không tìm thấy file đề.'];
    }

    $raw = @file_get_contents($realFilePath);
    if ($raw === false) {
        return ['ok' => false, 'message' => 'Không tìm thấy file đề.'];
    }

    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        return ['ok' => false, 'message' => 'File đề không đúng định dạng.'];
    }

    $validation = reading_test_validate_payload($payload);
    if (!$validation['ok']) {
        return ['ok' => false, 'message' => 'File đề không đúng định dạng.'];
    }

    return [
        'ok' => true,
        'record' => $row,
        'payload' => $payload,
    ];
}

$selectedId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$load = reading_test_load_upload($conn, $selectedId);
$errorMessage = $load['ok'] ? '' : (string) ($load['message'] ?? 'Bài Reading không tồn tại.');
$record = $load['record'] ?? null;
$payload = $load['payload'] ?? null;
$title = 'Reading Test';
$description = (string) ($record['description'] ?? '');
$passage = '';
$questions = [];
$score = 0;
$totalQuestions = 0;
$percentage = 0.0;
$bandScore = null;
$feedback = '';
$questionResults = [];
$practiceSummary = [
    'attempts' => 0,
    'bestScore' => 0,
    'latestScore' => 0,
    'averageScore' => 0,
    'bestBandScore' => null,
];

if ($load['ok']) {
    $title = (string) ($payload['title'] ?? $title);
    $description = (string) ($payload['description'] ?? $description);
    $passage = (string) ($payload['passage'] ?? '');
    $questions = is_array($payload['questions'] ?? null) ? $payload['questions'] : [];
    $totalQuestions = count($questions);
}

if ($submitted && $load['ok']) {
    $score = 0;
    foreach ($questions as $index => $question) {
        $correctAnswer = (int) ($question['answer'] ?? -1);
        $userAnswer = isset($_POST['q' . $index]) ? (int) $_POST['q' . $index] : -1;
        $questionResults[$index] = [
            'correctAnswer' => $correctAnswer,
            'userAnswer' => $userAnswer,
            'isCorrect' => $userAnswer === $correctAnswer,
        ];
        if ($userAnswer === $correctAnswer) {
            $score++;
        }
    }

    $percentage = $totalQuestions > 0 ? ($score / $totalQuestions) * 100 : 0;
    $bandScore = reading_test_band_score($percentage);
    $feedback = reading_test_feedback($percentage);

    if ($currentUser && isset($currentUser['id'])) {
        streak_mark_activity($conn, (int) $currentUser['id'], 'reading', 'reading_test', $score, max(1, $totalQuestions), (float) $bandScore, 20);
        $practiceSummary = streak_get_practice_summary($conn, (int) $currentUser['id'], 'reading');
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> - eLEARNING</title>
    <base href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>">
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

    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>

    <div class="container-fluid bg-primary py-5 mb-5 page-header">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <h1 class="display-3 text-white animated slideInDown"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p class="text-white mb-0"><?php echo htmlspecialchars($description !== '' ? $description : 'Reading test', ENT_QUOTES, 'UTF-8'); ?></p>
                    <nav aria-label="breadcrumb" class="mt-3">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a class="text-white" href="index.php">Home</a></li>
                            <li class="breadcrumb-item"><a class="text-white" href="reading.php">Reading</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">Test</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="container-xxl py-5">
        <div class="container">
            <?php if ($errorMessage !== ''): ?>
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-10">
                        <div class="alert alert-warning border-0 shadow-sm"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($load['ok']): ?>
                <?php if ($submitted): ?>
                    <div class="row justify-content-center mb-5">
                        <div class="col-lg-8">
                            <div class="card border-primary shadow-lg">
                                <div class="card-body text-center p-5">
                                    <h2 class="card-title text-primary mb-3">Kết quả bài làm</h2>
                                    <div class="display-1 text-primary fw-bold mb-2"><?php echo htmlspecialchars(number_format((float) $bandScore, 1), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="h5 text-muted mb-2"><?php echo htmlspecialchars((string) $score, ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars((string) $totalQuestions, ENT_QUOTES, 'UTF-8'); ?> câu đúng</div>
                                    <div class="h6 text-muted mb-4"><?php echo htmlspecialchars(number_format((float) $percentage, 1), ENT_QUOTES, 'UTF-8'); ?>% đúng</div>
                                    <div class="row g-3 justify-content-center mb-4 text-start">
                                        <div class="col-md-4">
                                            <div class="bg-light border rounded-3 p-3 h-100">
                                                <div class="text-muted small text-uppercase">Số câu đúng</div>
                                                <div class="h4 mb-0 text-primary"><?php echo htmlspecialchars((string) $score, ENT_QUOTES, 'UTF-8'); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="bg-light border rounded-3 p-3 h-100">
                                                <div class="text-muted small text-uppercase">Phần trăm</div>
                                                <div class="h4 mb-0 text-primary"><?php echo htmlspecialchars(number_format((float) $percentage, 1), ENT_QUOTES, 'UTF-8'); ?>%</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="bg-light border rounded-3 p-3 h-100">
                                                <div class="text-muted small text-uppercase">Band score</div>
                                                <div class="h4 mb-0 text-primary"><?php echo htmlspecialchars(number_format((float) $bandScore, 1), ENT_QUOTES, 'UTF-8'); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="card-text fs-5 mb-4"><?php echo htmlspecialchars($feedback, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <a href="reading_test.php?id=<?php echo htmlspecialchars((string) $selectedId, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary me-2">Làm lại</a>
                                    <a href="reading.php" class="btn btn-outline-primary">Back to Reading</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="bg-light rounded p-4 p-lg-5 shadow-sm">
                            <div class="card mb-4 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                                        <div>
                                            <h4 class="mb-1"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h4>
                                            <?php if ($description !== ''): ?>
                                                <p class="text-muted mb-0"><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted small align-self-md-end">
                                            Uploaded file: <?php echo htmlspecialchars((string) ($record['original_name'] ?: $record['filename']), ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    </div>
                                    <p class="mb-0" style="white-space: pre-line;"><?php echo htmlspecialchars($passage, ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>

                            <form method="post" action="reading_test.php?id=<?php echo htmlspecialchars((string) $selectedId, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php foreach ($questions as $index => $question): ?>
                                    <?php
                                        $questionText = (string) ($question['question'] ?? '');
                                        $options = is_array($question['options'] ?? null) ? $question['options'] : [];
                                        $questionName = 'q' . $index;
                                        $result = $questionResults[$index] ?? null;
                                        $isCorrect = $result !== null ? (bool) $result['isCorrect'] : false;
                                        $isAnswered = $result !== null && (int) $result['userAnswer'] >= 0;
                                    ?>
                                    <div class="mb-4 p-3 bg-white rounded border <?php echo $submitted ? ($isCorrect ? 'border-success' : 'border-danger') : ''; ?>">
                                        <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                                            <p class="fw-bold mb-3"><?php echo htmlspecialchars(($index + 1) . '. ' . $questionText, ENT_QUOTES, 'UTF-8'); ?></p>
                                            <?php if ($submitted && $isAnswered): ?>
                                                <span class="badge <?php echo $isCorrect ? 'bg-success' : 'bg-danger'; ?> align-self-md-start"><?php echo $isCorrect ? 'Correct' : 'Incorrect'; ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <?php foreach ($options as $optionIndex => $optionText): ?>
                                            <?php
                                                $optionLetter = reading_test_option_letter((int) $optionIndex);
                                                $isSelected = isset($_POST[$questionName]) && (string) $_POST[$questionName] === (string) $optionIndex;
                                                $isRightAnswer = $submitted && $result !== null && (int) $result['correctAnswer'] === (int) $optionIndex;
                                                $optionClass = $submitted ? ($isRightAnswer ? 'text-success fw-semibold' : ($isSelected ? 'text-danger' : '')) : '';
                                            ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="<?php echo htmlspecialchars($questionName, ENT_QUOTES, 'UTF-8'); ?>" id="<?php echo htmlspecialchars($questionName . '_' . $optionLetter, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars((string) $optionIndex, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $optionIndex === 0 ? 'required' : ''; ?> <?php echo $isSelected ? 'checked' : ''; ?>>
                                                <label class="form-check-label <?php echo htmlspecialchars($optionClass, ENT_QUOTES, 'UTF-8'); ?>" for="<?php echo htmlspecialchars($questionName . '_' . $optionLetter, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($optionLetter . '. ' . (string) $optionText, ENT_QUOTES, 'UTF-8'); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>

                                        <?php if ($submitted && $result !== null): ?>
                                            <div class="mt-3 small <?php echo $isCorrect ? 'text-success' : 'text-danger'; ?>">
                                                <?php if ($isCorrect): ?>
                                                    Đáp án đúng: <?php echo htmlspecialchars(reading_test_option_letter((int) $result['correctAnswer']), ENT_QUOTES, 'UTF-8'); ?>.
                                                <?php else: ?>
                                                    Bạn chọn <?php echo htmlspecialchars($result['userAnswer'] >= 0 ? reading_test_option_letter((int) $result['userAnswer']) : 'chưa chọn', ENT_QUOTES, 'UTF-8'); ?>, đáp án đúng là <?php echo htmlspecialchars(reading_test_option_letter((int) $result['correctAnswer']), ENT_QUOTES, 'UTF-8'); ?>.
                                                <?php endif; ?>
                                            </div>
                                            <div class="mt-2 small text-muted"><?php echo htmlspecialchars(reading_test_explanation($question, (int) $result['correctAnswer']), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>

                                <button class="btn btn-primary py-3 px-5" type="submit">Submit Test</button>
                            </form>
                        </div>
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
