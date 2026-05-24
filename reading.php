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

function reading_validate_payload(array $payload): array
{
    if (!isset($payload['title']) || trim((string) $payload['title']) === '') {
        return ['ok' => false, 'message' => 'Thiếu title trong file JSON.'];
    }

    if (!isset($payload['passage']) || trim((string) $payload['passage']) === '') {
        return ['ok' => false, 'message' => 'Thiếu passage trong file JSON.'];
    }

    if (!isset($payload['questions']) || !is_array($payload['questions']) || empty($payload['questions'])) {
        return ['ok' => false, 'message' => 'Thiếu mảng questions hợp lệ.'];
    }

    foreach ($payload['questions'] as $index => $question) {
        if (!is_array($question)) {
            return ['ok' => false, 'message' => 'Câu hỏi ' . ((int) $index + 1) . ' không hợp lệ.'];
        }

        if (!isset($question['question']) || trim((string) $question['question']) === '') {
            return ['ok' => false, 'message' => 'Câu hỏi ' . ((int) $index + 1) . ' thiếu nội dung.'];
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

function reading_band_score(int $correct, int $total): float
{
    if ($total <= 0) {
        return 0.0;
    }

    $percentage = ($correct / $total) * 100;

    if ($percentage >= 90) {
        return 7.5;
    }

    if ($percentage >= 80) {
        return 6.5;
    }

    if ($percentage >= 70) {
        return 6.0;
    }

    if ($percentage >= 60) {
        return 5.5;
    }

    if ($percentage >= 50) {
        return 5.0;
    }

    if ($percentage >= 40) {
        return 4.5;
    }

    if ($percentage >= 30) {
        return 4.0;
    }

    if ($percentage >= 20) {
        return 3.0;
    }

    return 2.0;
}

function reading_option_letter(int $index): string
{
    return chr(65 + $index);
}

function reading_question_explanation(array $question, int $correctAnswer): string
{
    $explanation = trim((string) ($question['explanation'] ?? ''));
    if ($explanation !== '') {
        return $explanation;
    }

    $correctLetter = reading_option_letter($correctAnswer);
    $options = is_array($question['options'] ?? null) ? $question['options'] : [];
    $correctText = isset($options[$correctAnswer]) ? trim((string) $options[$correctAnswer]) : '';

    if ($correctText !== '') {
        return 'Giải thích: đáp án đúng là ' . $correctLetter . '. ' . $correctText . '.';
    }

    return 'Giải thích: đáp án đúng là ' . $correctLetter . '.';
}

function reading_load_test(mysqli $conn, ?int $uploadId = null): array
{
    $uploadsDir = __DIR__ . '/uploads/reading';
    $records = [];

    if ($uploadId !== null) {
        $stmt = $conn->prepare('SELECT id, skill, title, description, filename, original_name, mime, size, uploaded_by, created_at FROM skill_uploads WHERE skill = ? AND id = ? LIMIT 1');
        if (!$stmt) {
            return ['ok' => false, 'message' => 'Không đọc được dữ liệu bài Reading từ database.'];
        }

        $skill = 'reading';
        $stmt->bind_param('si', $skill, $uploadId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $records[] = $row;
            }
        }
        $stmt->close();
    } else {
        $stmt = $conn->prepare('SELECT id, skill, title, description, filename, original_name, mime, size, uploaded_by, created_at FROM skill_uploads WHERE skill = ? ORDER BY id DESC LIMIT 50');
        if (!$stmt) {
            return ['ok' => false, 'message' => 'Không đọc được dữ liệu bài Reading từ database.'];
        }

        $skill = 'reading';
        $stmt->bind_param('s', $skill);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $records[] = $row;
            }
        }
        $stmt->close();
    }

    foreach ($records as $record) {
        $storedName = basename((string) ($record['filename'] ?? ''));
        if ($storedName === '') {
            continue;
        }

        $filePath = $uploadsDir . '/' . $storedName;
        if (!is_file($filePath)) {
            continue;
        }

        $raw = @file_get_contents($filePath);
        $payload = json_decode((string) $raw, true);
        if (!is_array($payload)) {
            continue;
        }

        $validation = reading_validate_payload($payload);
        if (!$validation['ok']) {
            continue;
        }

        return [
            'ok' => true,
            'record' => $record,
            'payload' => $payload,
            'filePath' => $filePath,
        ];
    }

    return ['ok' => false, 'message' => 'Chưa có bài Reading JSON hợp lệ để hiển thị.'];
}

$bandScore = null;
$feedback = '';
$score = 0;
$totalQuestions = 0;
$questionResults = [];
$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$selectedUploadId = isset($_POST['upload_id']) ? (int) $_POST['upload_id'] : null;
$readingLoad = reading_load_test($conn, $submitted && $selectedUploadId ? $selectedUploadId : null);
$readingRecord = $readingLoad['record'] ?? null;
$readingPayload = $readingLoad['payload'] ?? null;
$readingError = $readingLoad['message'] ?? '';
$questions = [];
$title = 'Reading Diagnostic Test';
$description = '';
$passage = '';

if ($readingLoad['ok'] ?? false) {
    $title = (string) ($readingPayload['title'] ?? $title);
    $description = (string) ($readingPayload['description'] ?? ($readingRecord['description'] ?? ''));
    $passage = (string) ($readingPayload['passage'] ?? '');
    $questions = is_array($readingPayload['questions'] ?? null) ? $readingPayload['questions'] : [];
    $totalQuestions = count($questions);
}

if ($submitted && ($readingLoad['ok'] ?? false)) {
    $score = 0;
    foreach ($questions as $index => $question) {
        $correctAnswer = (int) ($question['answer'] ?? -1);
        $userAnswer = isset($_POST['answers'][$index]) ? (int) $_POST['answers'][$index] : -1;
        $questionResults[$index] = [
            'correctAnswer' => $correctAnswer,
            'userAnswer' => $userAnswer,
            'isCorrect' => $userAnswer === $correctAnswer,
        ];
        if ($userAnswer === $correctAnswer) {
            $score++;
        }
    }

    $bandScore = reading_band_score($score, $totalQuestions);
    $percentage = $totalQuestions > 0 ? ($score / $totalQuestions) * 100 : 0;

    if ($percentage >= 80) {
        $feedback = 'Bạn làm rất tốt phần Reading. Hãy tiếp tục luyện thêm passage dài hơn và canh thời gian sát hơn.';
    } elseif ($percentage >= 60) {
        $feedback = 'Kết quả ổn định. Bạn đã nắm được ý chính và nên luyện thêm câu hỏi chi tiết.';
    } elseif ($percentage >= 40) {
        $feedback = 'Bạn đã có nền tảng cơ bản, nhưng cần cải thiện kỹ năng tìm keyword và đối chiếu thông tin trong passage.';
    } elseif ($percentage >= 20) {
        $feedback = 'Bạn nên luyện thêm skimming, scanning và từ vựng học thuật để tăng độ chính xác.';
    } else {
        $feedback = 'Bạn cần luyện lại từ mức cơ bản. Hãy tập đọc ngắn, đọc chậm và xác định keyword trong câu hỏi trước.';
    }

    if ($currentUser && isset($currentUser['id'])) {
        streak_mark_activity($conn, (int) $currentUser['id'], 'reading', 'json_test', $score, max(1, $totalQuestions), (float) $bandScore, 20);
    }
}

$avatarUrl = $avatarPath !== '' ? $avatarPath : '';
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
                    <p class="text-white mb-0"><?php echo htmlspecialchars($description !== '' ? $description : 'Reading test from uploaded JSON', ENT_QUOTES, 'UTF-8'); ?></p>
                    <nav aria-label="breadcrumb" class="mt-3">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a class="text-white" href="index.php">Home</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">Reading Test</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="container-xxl py-5">
        <div class="container">
            <?php if ($readingError !== ''): ?>
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-10">
                        <div class="alert alert-warning border-0 shadow-sm"><?php echo htmlspecialchars($readingError, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($submitted && ($readingLoad['ok'] ?? false)): ?>
                <div class="row justify-content-center mb-5">
                    <div class="col-lg-8">
                        <div class="card border-primary shadow-lg">
                            <div class="card-body text-center p-5">
                                <h2 class="card-title text-primary mb-3">Kết quả bài làm</h2>
                                <div class="display-1 text-primary fw-bold mb-2"><?php echo htmlspecialchars(number_format((float) $bandScore, 1), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="h5 text-muted mb-4"><?php echo htmlspecialchars((string) $score, ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars((string) $totalQuestions, ENT_QUOTES, 'UTF-8'); ?> câu đúng</div>
                                <div class="row g-3 justify-content-center mb-4 text-start">
                                    <div class="col-md-4">
                                        <div class="bg-light border rounded-3 p-3 h-100">
                                            <div class="text-muted small text-uppercase">Số câu đúng</div>
                                            <div class="h4 mb-0 text-primary"><?php echo htmlspecialchars((string) $score, ENT_QUOTES, 'UTF-8'); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="bg-light border rounded-3 p-3 h-100">
                                            <div class="text-muted small text-uppercase">Tổng câu</div>
                                            <div class="h4 mb-0 text-primary"><?php echo htmlspecialchars((string) $totalQuestions, ENT_QUOTES, 'UTF-8'); ?></div>
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
                                <a href="reading.php" class="btn btn-primary me-2">Làm lại bài mới nhất</a>
                                <a href="index.php" class="btn btn-outline-primary">Back to Home</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="bg-light rounded p-4 p-lg-5 shadow-sm">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                            <div>
                                <h3 class="mb-1">Read the passage and answer the questions</h3>
                                <p class="text-muted mb-0">Bài Reading được lấy từ file JSON mới nhất trong database.</p>
                            </div>
                            <?php if ($readingLoad['ok'] ?? false): ?>
                                <div class="text-md-end mt-3 mt-md-0">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">Latest test #<?php echo htmlspecialchars((string) $readingRecord['id'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!($readingLoad['ok'] ?? false)): ?>
                            <div class="alert alert-info mb-0">
                                Chưa có bài Reading JSON hợp lệ trong uploads/reading. Hãy upload một file JSON qua admin/add_skill.php?skill=reading.
                            </div>
                        <?php else: ?>
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
                                            Uploaded file: <?php echo htmlspecialchars((string) ($readingRecord['original_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    </div>
                                    <p class="mb-0" style="white-space: pre-line;"><?php echo htmlspecialchars($passage, ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>

                            <form method="post" action="reading.php">
                                <input type="hidden" name="upload_id" value="<?php echo htmlspecialchars((string) $readingRecord['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php foreach ($questions as $index => $question): ?>
                                    <?php
                                        $questionText = (string) ($question['question'] ?? '');
                                        $options = is_array($question['options'] ?? null) ? $question['options'] : [];
                                        $questionNumber = $index + 1;
                                        $result = $questionResults[$index] ?? null;
                                        $isAnswered = $result !== null && (int) $result['userAnswer'] >= 0;
                                        $isCorrect = $result !== null ? (bool) $result['isCorrect'] : false;
                                    ?>
                                    <div class="mb-4 p-3 bg-white rounded border <?php echo $submitted ? ($isCorrect ? 'border-success' : 'border-danger') : ''; ?>">
                                        <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                                            <p class="fw-bold mb-3"><?php echo htmlspecialchars($questionNumber . '. ' . $questionText, ENT_QUOTES, 'UTF-8'); ?></p>
                                            <?php if ($submitted && $isAnswered): ?>
                                                <span class="badge <?php echo $isCorrect ? 'bg-success' : 'bg-danger'; ?> align-self-md-start">
                                                    <?php echo $isCorrect ? 'Correct' : 'Incorrect'; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php foreach ($options as $optionIndex => $optionText): ?>
                                            <?php
                                                $optionLetter = reading_option_letter((int) $optionIndex);
                                                $isSelected = isset($_POST['answers'][$index]) && (string) $_POST['answers'][$index] === (string) $optionIndex;
                                                $isRightAnswer = $submitted && $result !== null && (int) $result['correctAnswer'] === (int) $optionIndex;
                                                $optionClass = $submitted ? ($isRightAnswer ? 'text-success fw-semibold' : ($isSelected ? 'text-danger' : '')) : '';
                                            ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="answers[<?php echo htmlspecialchars((string) $index, ENT_QUOTES, 'UTF-8'); ?>]" id="q<?php echo htmlspecialchars((string) $questionNumber, ENT_QUOTES, 'UTF-8'); ?>_<?php echo htmlspecialchars($optionLetter, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars((string) $optionIndex, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $optionIndex === 0 ? 'required' : ''; ?> <?php echo $isSelected ? 'checked' : ''; ?>>
                                                <label class="form-check-label <?php echo htmlspecialchars($optionClass, ENT_QUOTES, 'UTF-8'); ?>" for="q<?php echo htmlspecialchars((string) $questionNumber, ENT_QUOTES, 'UTF-8'); ?>_<?php echo htmlspecialchars($optionLetter, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($optionLetter . '. ' . (string) $optionText, ENT_QUOTES, 'UTF-8'); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if ($submitted && $result !== null): ?>
                                            <div class="mt-3 small <?php echo $isCorrect ? 'text-success' : 'text-danger'; ?>">
                                                <?php if ($isCorrect): ?>
                                                    Đáp án đúng: <?php echo htmlspecialchars(reading_option_letter((int) $result['correctAnswer']), ENT_QUOTES, 'UTF-8'); ?>.
                                                <?php else: ?>
                                                    Bạn chọn <?php echo htmlspecialchars($result['userAnswer'] >= 0 ? reading_option_letter((int) $result['userAnswer']) : 'chưa chọn', ENT_QUOTES, 'UTF-8'); ?>, đáp án đúng là <?php echo htmlspecialchars(reading_option_letter((int) $result['correctAnswer']), ENT_QUOTES, 'UTF-8'); ?>.
                                                <?php endif; ?>
                                            </div>
                                            <div class="mt-2 small text-muted">
                                                <?php echo htmlspecialchars(reading_question_explanation($question, (int) $result['correctAnswer']), ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>

                                <button class="btn btn-primary py-3 px-5" type="submit">Submit Test</button>
                            </form>
                        <?php endif; ?>
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
