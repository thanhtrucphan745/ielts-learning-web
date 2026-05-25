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

function listening_validate_json_payload(array $payload): array
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

function listening_band_score(float $percentage): float
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

function listening_option_label(int $index): string
{
    return chr(65 + $index);
}

function listening_save_attempt(mysqli $conn, array $user, int $testId, string $skill, string $testTitle, int $score, int $totalQuestions, float $bandScore, array $questions, array $questionResults): ?int
{
    if (!ensure_test_attempt_tables($conn)) {
        return null;
    }

    $insertAttempt = $conn->prepare('INSERT INTO test_attempts (student_id, skill, test_id, test_title, score, total_questions, band_score, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
    if (!$insertAttempt) {
        return null;
    }

    $studentId = (int) $user['id'];
    $insertAttempt->bind_param('isisiid', $studentId, $skill, $testId, $testTitle, $score, $totalQuestions, $bandScore);
    if (!$insertAttempt->execute()) {
        $insertAttempt->close();
        return null;
    }

    $attemptId = $insertAttempt->insert_id;
    $insertAttempt->close();

    $insertAnswer = $conn->prepare('INSERT INTO test_attempt_answers (attempt_id, question_index, question_text, selected_answer, correct_answer, selected_text, correct_text, is_correct, explanation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if (!$insertAnswer) {
        return $attemptId;
    }

    foreach ($questions as $index => $question) {
        $questionText = trim((string) ($question['question'] ?? ''));
        $correctAnswer = (int) ($question['answer'] ?? -1);
        $result = $questionResults[$index] ?? null;
        $selectedAnswer = $result !== null ? (int) ($result['userAnswer'] ?? -1) : -1;
        $selectedAnswer = $selectedAnswer >= 0 ? $selectedAnswer : null;
        $selectedText = null;
        $correctText = '';

        $options = is_array($question['options'] ?? null) ? $question['options'] : [];
        if ($selectedAnswer !== null && isset($options[$selectedAnswer])) {
            $selectedText = trim((string) $options[$selectedAnswer]);
        }
        if (isset($options[$correctAnswer])) {
            $correctText = trim((string) $options[$correctAnswer]);
        }

        $isCorrect = $result !== null && !empty($result['isCorrect']) ? 1 : 0;
        $explanation = trim((string) ($question['explanation'] ?? ''));

        $insertAnswer->bind_param('iisiissis', $attemptId, $index, $questionText, $selectedAnswer, $correctAnswer, $selectedText, $correctText, $isCorrect, $explanation);
        $insertAnswer->execute();
    }

    $insertAnswer->close();
    return $attemptId;
}

$selectedId = isset($_GET['test_id']) ? (int) $_GET['test_id'] : 0;
$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$errorMessage = '';
$record = null;
$payload = null;
$title = 'Listening';
$description = '';
$questions = [];
$score = 0;
$totalQuestions = 0;
$percentage = 0.0;
$bandScore = null;
$feedback = '';
$questionResults = [];
$attemptId = null;
$practiceSummary = [
    'attempts' => 0,
    'bestScore' => 0,
    'latestScore' => 0,
    'averageScore' => 0,
    'bestBandScore' => null,
];
$items = [];

if (!ensure_skill_uploads_table($conn)) {
    $errorMessage = 'Chưa có bài Listening nào được upload.';
} else {
    if ($selectedId > 0) {
        $stmt = $conn->prepare('SELECT * FROM skill_uploads WHERE id = ? AND skill = ? LIMIT 1');
        if ($stmt) {
            $skill = 'listening';
            $stmt->bind_param('is', $selectedId, $skill);
            $stmt->execute();
            $result = $stmt->get_result();
            $record = $result ? $result->fetch_assoc() : null;
            $stmt->close();
        }

        if (!$record) {
            $errorMessage = 'Bài Listening không tồn tại.';
        } else {
            $jsonFileName = basename((string) ($record['filename'] ?? ''));
            $audioFileName = basename((string) ($record['audio_filename'] ?? ''));

            if ($jsonFileName === '' || $audioFileName === '') {
                $errorMessage = 'Bài Listening thiếu file JSON hoặc audio.';
            } else {
                $jsonPath = __DIR__ . '/uploads/listening/' . $jsonFileName;
                $audioPath = __DIR__ . '/uploads/listening/audio/' . $audioFileName;

                if (!is_file($jsonPath)) {
                    $errorMessage = 'Không tìm thấy file JSON của bài Listening.';
                } elseif (!is_file($audioPath)) {
                    $errorMessage = 'Không tìm thấy file audio của bài Listening.';
                } else {
                    $raw = @file_get_contents($jsonPath);
                    $payload = is_string($raw) ? json_decode($raw, true) : null;
                    if (!is_array($payload)) {
                        $errorMessage = 'File JSON không hợp lệ.';
                    } else {
                        $validation = listening_validate_json_payload($payload);
                        if (!$validation['ok']) {
                            $errorMessage = $validation['message'];
                        } else {
                            $title = (string) ($payload['title'] ?? $title);
                            $description = (string) ($payload['description'] ?? $record['description'] ?? '');
                            $questions = is_array($payload['questions'] ?? null) ? $payload['questions'] : [];
                            $totalQuestions = count($questions);

                            if ($submitted) {
                                foreach ($questions as $index => $question) {
                                    $correctAnswer = (int) ($question['answer'] ?? -1);
                                    $userAnswer = isset($_POST['q' . $index]) && is_numeric($_POST['q' . $index]) ? (int) $_POST['q' . $index] : -1;
                                    $isCorrect = $userAnswer === $correctAnswer;
                                    $questionResults[$index] = [
                                        'question' => trim((string) ($question['question'] ?? '')),
                                        'options' => is_array($question['options'] ?? null) ? $question['options'] : [],
                                        'userAnswer' => $userAnswer,
                                        'correctAnswer' => $correctAnswer,
                                        'isCorrect' => $isCorrect,
                                        'explanation' => trim((string) ($question['explanation'] ?? '')), 
                                    ];
                                    if ($isCorrect) {
                                        $score++;
                                    }
                                }

                                $percentage = $totalQuestions > 0 ? ($score / $totalQuestions) * 100 : 0;
                                $bandScore = listening_band_score($percentage);
                                if ($percentage >= 80) {
                                    $feedback = 'Bạn làm tốt! Hãy tiếp tục luyện nghe để giữ vững Band.';
                                } elseif ($percentage >= 60) {
                                    $feedback = 'Bạn có nền tảng khá. Tiếp tục luyện nghe chi tiết.';
                                } elseif ($percentage >= 40) {
                                    $feedback = 'Bạn đang ở mức trung bình. Cần luyện nghe nhiều hơn.';
                                } elseif ($percentage >= 20) {
                                    $feedback = 'Cần luyện nghe cơ bản nhiều hơn để cải thiện.';
                                } else {
                                    $feedback = 'Bạn nên luyện nghe từ các bài đơn giản và nghe đều đặn.';
                                }

                                if ($currentUser && isset($currentUser['id'])) {
                                    streak_mark_activity($conn, (int) $currentUser['id'], 'listening', 'listening_test', $score, max(1, $totalQuestions), (float) $bandScore, 20);
                                    $practiceSummary = streak_get_practice_summary($conn, (int) $currentUser['id'], 'listening');
                                    $attemptId = listening_save_attempt(
                                        $conn,
                                        $currentUser,
                                        $selectedId,
                                        'listening',
                                        (string) ($payload['title'] ?? ($record['title'] ?? 'Listening Test')),
                                        $score,
                                        $totalQuestions,
                                        (float) $bandScore,
                                        $questions,
                                        $questionResults
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
    } else {
        $stmt = $conn->prepare('SELECT id, title, description, filename, original_name, audio_filename, audio_original_name, created_at FROM skill_uploads WHERE skill = ? ORDER BY created_at DESC, id DESC');
        if ($stmt) {
            $skill = 'listening';
            $stmt->bind_param('s', $skill);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $items[] = $row;
                }
            }
            $stmt->close();
        }

        if (empty($items)) {
            $errorMessage = 'Chưa có bài Listening nào được upload.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($selectedId > 0 ? 'Listening Test' : 'Listening', ENT_QUOTES, 'UTF-8'); ?> - eLEARNING</title>
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

    <div class="container-fluid bg-primary py-5 mb-5 page-header">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <h1 class="display-3 text-white animated slideInDown">Listening</h1>
                    <p class="text-white mb-0">Chọn bài Listening đã upload hoặc vào bài để làm.</p>
                    <nav aria-label="breadcrumb" class="mt-3">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a class="text-white" href="index.php">Home</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">Listening</li>
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

            <?php if ($selectedId > 0 && $errorMessage === '' && $payload !== null): ?>
                <div class="row mb-4">
                    <div class="col-lg-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h2 class="mb-3"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2>
                                <p class="text-muted mb-4"><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php
                                    $audioFilename = basename((string) ($record['audio_filename'] ?? ''));
                                    $audioExt = strtolower(pathinfo($audioFilename, PATHINFO_EXTENSION));
                                    $audioType = 'audio/mpeg';
                                    if ($audioExt === 'wav') {
                                        $audioType = 'audio/wav';
                                    } elseif ($audioExt === 'ogg') {
                                        $audioType = 'audio/ogg';
                                    }
                                ?>
                                <audio controls class="w-100 mb-4">
                                    <source src="uploads/listening/audio/<?php echo rawurlencode($audioFilename); ?>" type="<?php echo htmlspecialchars($audioType, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars('Trình duyệt của bạn không hỗ trợ audio.', ENT_QUOTES, 'UTF-8'); ?>
                                </audio>

                                <?php if ($submitted && $bandScore !== null): ?>
                                    <div class="alert alert-success border-0 shadow-sm mb-4">
                                        <div><strong>Kết quả:</strong> <?php echo htmlspecialchars((string) $score, ENT_QUOTES, 'UTF-8'); ?>/<?php echo htmlspecialchars((string) $totalQuestions, ENT_QUOTES, 'UTF-8'); ?> câu đúng.</div>
                                        <div><strong>Band score:</strong> <?php echo htmlspecialchars((string) $bandScore, ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div><?php echo htmlspecialchars($feedback, ENT_QUOTES, 'UTF-8'); ?></div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($submitted && $bandScore !== null): ?>
                                    <div class="mb-4">
                                        <?php foreach ($questionResults as $index => $result): ?>
                                            <div class="card mb-3">
                                                <div class="card-body">
                                                    <h5 class="card-title">Câu <?php echo htmlspecialchars((string) ($index + 1), ENT_QUOTES, 'UTF-8'); ?>: <?php echo htmlspecialchars($result['question'], ENT_QUOTES, 'UTF-8'); ?></h5>
                                                    <div class="mb-2">
                                                        <span class="badge bg-<?php echo $result['isCorrect'] ? 'success' : 'danger'; ?> me-2"><?php echo $result['isCorrect'] ? 'Đúng' : 'Sai'; ?></span>
                                                        <strong>Đáp án của bạn:</strong> <?php echo $result['userAnswer'] >= 0 ? htmlspecialchars(listening_option_label($result['userAnswer']), ENT_QUOTES, 'UTF-8') : 'Không chọn'; ?>
                                                        <?php if ($result['userAnswer'] >= 0 && isset($result['options'][$result['userAnswer']])): ?>
                                                            - <?php echo htmlspecialchars((string) $result['options'][$result['userAnswer']], ENT_QUOTES, 'UTF-8'); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="mb-2"><strong>Đáp án đúng:</strong> <?php echo htmlspecialchars(listening_option_label($result['correctAnswer']), ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars((string) ($result['options'][$result['correctAnswer']] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                                    <?php if ($result['explanation'] !== ''): ?>
                                                        <div class="text-muted"><strong>Giải thích:</strong> <?php echo htmlspecialchars($result['explanation'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php if ($attemptId !== null): ?>
                                        <div class="mb-4">
                                            <a href="result_detail.php?id=<?php echo htmlspecialchars((string) $attemptId, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-success me-2">Xem trong Bài đã làm</a>
                                            <a href="listening.php?test_id=<?php echo htmlspecialchars((string) $selectedId, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">Làm lại</a>
                                        </div>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <form method="post" action="listening.php?test_id=<?php echo htmlspecialchars((string) $selectedId, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php foreach ($questions as $index => $question): ?>
                                            <div class="mb-4">
                                                <h5><?php echo htmlspecialchars('Câu ' . ($index + 1) . ': ' . (string) ($question['question'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h5>
                                                <?php $options = is_array($question['options'] ?? null) ? $question['options'] : []; ?>
                                                <?php foreach ($options as $optIndex => $option): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="q<?php echo (int) $index; ?>" id="q<?php echo (int) $index; ?>_<?php echo (int) $optIndex; ?>" value="<?php echo (int) $optIndex; ?>" required>
                                                        <label class="form-check-label" for="q<?php echo (int) $index; ?>_<?php echo (int) $optIndex; ?>"><?php echo htmlspecialchars(listening_option_label($optIndex), ENT_QUOTES, 'UTF-8'); ?>. <?php echo htmlspecialchars((string) $option, ENT_QUOTES, 'UTF-8'); ?></label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                        <button class="btn btn-primary py-3 px-5" type="submit">Submit Test</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($selectedId <= 0): ?>
                <div class="row g-4">
                    <?php foreach ($items as $item): ?>
                        <div class="col-lg-6">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h4 class="card-title mb-2"><?php echo htmlspecialchars((string) ($item['title'] ?: 'Listening Test'), ENT_QUOTES, 'UTF-8'); ?></h4>
                                            <p class="text-muted mb-0"><?php echo htmlspecialchars((string) $item['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        </div>
                                        <span class="badge bg-primary">#<?php echo htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <div class="small text-muted mb-2">File đề: <?php echo htmlspecialchars((string) ($item['original_name'] ?: $item['filename']), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="small text-muted mb-4">Audio: <?php echo htmlspecialchars((string) ($item['audio_original_name'] ?: $item['audio_filename']), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="small text-muted mb-4">Uploaded: <?php echo htmlspecialchars((string) $item['created_at'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <a href="listening.php?test_id=<?php echo htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">Làm bài</a>
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
