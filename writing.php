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

$bandScore = null;
$feedback = '';
$score = 0;
$practiceSummary = [
    'attempts' => 0,
    'bestScore' => 0,
    'latestScore' => 0,
    'averageScore' => 0,
    'bestBandScore' => null,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correctAnswers = ['1_A', '2_B', '3_A', '4_A', '5_A'];
    $userAnswers = [
        isset($_POST['q1']) ? $_POST['q1'] : '',
        isset($_POST['q2']) ? $_POST['q2'] : '',
        isset($_POST['q3']) ? $_POST['q3'] : '',
        isset($_POST['q4']) ? $_POST['q4'] : '',
        isset($_POST['q5']) ? $_POST['q5'] : ''
    ];
    
    $score = 0;
    for ($i = 0; $i < 5; $i++) {
        if ($userAnswers[$i] === $correctAnswers[$i]) {
            $score++;
        }
    }
    
    $percentage = ($score / 5) * 100;
    if ($percentage >= 80) {
        $bandScore = 6;
        $feedback = 'Excellent! You have strong writing skills (Band 6). Your writing demonstrates clear structure, accurate grammar, and appropriate vocabulary. You can handle complex writing tasks at advanced IELTS level.';
    } elseif ($percentage >= 60) {
        $bandScore = 5;
        $feedback = 'Good! You have competent writing skills (Band 5). Your writing is generally stable with good sentence structure and natural vocabulary. Minor grammar errors may appear. Keep practicing for perfect accuracy.';
    } elseif ($percentage >= 40) {
        $bandScore = 4;
        $feedback = 'Fair! You have basic writing skills (Band 4). Your structure is simple with basic vocabulary, and you make several grammar errors. Focus on expanding vocabulary and practicing complex sentence structures.';
    } elseif ($percentage >= 20) {
        $bandScore = 2;
        $feedback = 'You need more practice in writing (Band 2-3). Your writing has weak structure with significant errors. Start with simple sentences and gradually build your grammatical foundation.';
    } else {
        $bandScore = 0;
        $feedback = 'Keep practicing! Writing improves with regular practice (Band 0-1). We recommend writing daily sentences, focusing on basic grammar and simple structures first.';
    }

    if ($currentUser && isset($currentUser['id'])) {
        streak_mark_activity($conn, (int) $currentUser['id'], 'writing', 'diagnostic_test', $score, 5, (float) $bandScore, 25);
        $practiceSummary = streak_get_practice_summary($conn, (int) $currentUser['id'], 'writing');
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Writing Diagnostic Test - eLEARNING</title>
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
                    <h1 class="display-3 text-white animated slideInDown">Writing Diagnostic Test</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center">
                            <li class="breadcrumb-item"><a class="text-white" href="index.php">Home</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">Writing Test</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="container-xxl py-5">
        <div class="container">
            <?php if ($bandScore !== null): ?>
                <div class="row justify-content-center mb-5">
                    <div class="col-lg-8">
                        <div class="card border-primary shadow-lg">
                            <div class="card-body text-center p-5">
                                <h2 class="card-title text-primary mb-4">Your Band Score</h2>
                                <div class="display-1 text-primary fw-bold mb-3"><?php echo htmlspecialchars($bandScore, ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="row g-3 justify-content-center mb-4 text-start">
                                    <div class="col-md-4">
                                        <div class="bg-light border rounded-3 p-3 h-100">
                                            <div class="text-muted small text-uppercase">Lượt làm bài</div>
                                            <div class="h4 mb-0 text-primary"><?php echo htmlspecialchars((string) $practiceSummary['attempts'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="bg-light border rounded-3 p-3 h-100">
                                            <div class="text-muted small text-uppercase">Điểm hiện tại</div>
                                            <div class="h4 mb-0 text-primary"><?php echo htmlspecialchars((string) $score, ENT_QUOTES, 'UTF-8'); ?>/5</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="bg-light border rounded-3 p-3 h-100">
                                            <div class="text-muted small text-uppercase">Điểm tốt nhất</div>
                                            <div class="h4 mb-0 text-primary"><?php echo htmlspecialchars((string) $practiceSummary['bestScore'], ENT_QUOTES, 'UTF-8'); ?>/5</div>
                                        </div>
                                    </div>
                                </div>
                                <p class="card-text fs-5 mb-4"><?php echo htmlspecialchars($feedback, ENT_QUOTES, 'UTF-8'); ?></p>
                                <a href="writing.php" class="btn btn-primary me-2">Retake Test</a>
                                <a href="index.php" class="btn btn-outline-primary">Back to Home</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="bg-light rounded p-4">
                            <h3 class="mb-4">Grammar and Writing Structure Quiz</h3>
                            <form method="post" action="writing.php">
                                <div class="mb-4">
                                    <p><strong>1. Choose the correct sentence:</strong></p>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q1" id="q1_A" value="1_A" required>
                                        <label class="form-check-label" for="q1_A">A. She has studied English for five years.</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q1" id="q1_B" value="1_B">
                                        <label class="form-check-label" for="q1_B">B. She study English for five years.</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q1" id="q1_C" value="1_C">
                                        <label class="form-check-label" for="q1_C">C. She is studying English for five years.</label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <p><strong>2. Fill in the blank: "If I _____ you, I would help."</strong></p>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q2" id="q2_A" value="2_A" required>
                                        <label class="form-check-label" for="q2_A">A. was</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q2" id="q2_B" value="2_B">
                                        <label class="form-check-label" for="q2_B">B. were</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q2" id="q2_C" value="2_C">
                                        <label class="form-check-label" for="q2_C">C. am</label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <p><strong>3. Which sentence has the correct word order?</strong></p>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q3" id="q3_A" value="3_A" required>
                                        <label class="form-check-label" for="q3_A">A. Yesterday I went to the store.</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q3" id="q3_B" value="3_B">
                                        <label class="form-check-label" for="q3_B">B. I yesterday went to the store.</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q3" id="q3_C" value="3_C">
                                        <label class="form-check-label" for="q3_C">C. To the store I went yesterday.</label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <p><strong>4. Correct the error: "He don't like coffee."</strong></p>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q4" id="q4_A" value="4_A" required>
                                        <label class="form-check-label" for="q4_A">A. He doesn't like coffee.</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q4" id="q4_B" value="4_B">
                                        <label class="form-check-label" for="q4_B">B. He does not like coffee.</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q4" id="q4_C" value="4_C">
                                        <label class="form-check-label" for="q4_C">C. He don't likes coffee.</label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <p><strong>5. Choose the sentence with correct punctuation:</strong></p>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q5" id="q5_A" value="5_A" required>
                                        <label class="form-check-label" for="q5_A">A. She likes to read, write and paint.</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q5" id="q5_B" value="5_B">
                                        <label class="form-check-label" for="q5_B">B. She likes to read write and paint.</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q5" id="q5_C" value="5_C">
                                        <label class="form-check-label" for="q5_C">C. She likes to, read write and paint.</label>
                                    </div>
                                </div>

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
