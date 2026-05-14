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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correctAnswers = ['1_A', '2_B', '3_C', '4_A', '5_B'];
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
        $feedback = 'Excellent! You have strong reading comprehension skills (Band 6). You demonstrate accuracy of 70-80% and can understand both main ideas and detailed information clearly. You are ready for advanced IELTS reading passages.';
    } elseif ($percentage >= 60) {
        $bandScore = 5;
        $feedback = 'Good! You have competent reading comprehension skills (Band 5). You understand main ideas with 60-70% accuracy, though you may miss some specific details. Continue practicing with varied texts to strengthen your skills.';
    } elseif ($percentage >= 40) {
        $bandScore = 4;
        $feedback = 'Fair! You have basic reading comprehension skills (Band 4). You can grasp general information with 50-60% accuracy, but details often confuse you. Focus on vocabulary building and practicing with more complex texts.';
    } elseif ($percentage >= 20) {
        $bandScore = 2;
        $feedback = 'You need more practice (Band 2-3). You understand very little from the text. Start with simpler materials and build your vocabulary and comprehension strategies gradually.';
    } else {
        $bandScore = 0;
        $feedback = 'Keep practicing! Reading comprehension takes time (Band 0-1). We recommend starting with beginner-level materials and reading short articles daily to develop your skills.';
    }

    if ($currentUser && isset($currentUser['id'])) {
        streak_mark_activity($conn, (int) $currentUser['id'], 'reading', 'diagnostic_test', $score, 5, (float) $bandScore, 20);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Reading Diagnostic Test - eLEARNING</title>
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

    <nav class="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">
        <a href="index.php" class="navbar-brand d-flex align-items-center px-4 px-lg-5">
            <h2 class="m-0 text-primary"><i class="fa fa-book me-3"></i>eLEARNING</h2>
        </a>
        <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav ms-auto p-4 p-lg-0">
                <a href="index.php" class="nav-item nav-link">Home</a>
                <a href="about.php" class="nav-item nav-link">About</a>
                <a href="contact.php" class="nav-item nav-link">Contact</a>
            </div>
            <div class="d-none d-lg-flex align-items-center gap-2 px-4 px-lg-5">
                <?php if ($currentUser): ?>
                    <div class="dropdown">
                        <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php if ($avatarUrl !== ''): ?>
                                <img src="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar" class="rounded-circle me-2" style="width:44px;height:44px;object-fit:cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width:44px;height:44px;font-weight:700;">
                                    <?php echo htmlspecialchars($avatarText, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>
                            <span class="fw-bold text-dark">Hi, <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2 text-primary"></i>Hồ sơ cá nhân</a></li>
                            <li><a class="dropdown-item" href="practice.php"><i class="fas fa-list-alt me-2 text-primary"></i>Quản lý tin đăng</a></li>
                            <?php if ($role === 1): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item fw-semibold text-primary" href="admin/index.php"><i class="fas fa-shield-alt me-2"></i>Quản trị hệ thống</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-power-off me-2"></i>Đăng xuất</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-primary py-2 px-4">Login</a>
                    <a href="register.php" class="btn btn-primary py-2 px-4">Đăng ký<i class="fa fa-arrow-right ms-3"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container-fluid bg-primary py-5 mb-5 page-header">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <h1 class="display-3 text-white animated slideInDown">Reading Diagnostic Test</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center">
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
            <?php if ($bandScore !== null): ?>
                <div class="row justify-content-center mb-5">
                    <div class="col-lg-8">
                        <div class="card border-primary shadow-lg">
                            <div class="card-body text-center p-5">
                                <h2 class="card-title text-primary mb-4">Your Band Score</h2>
                                <div class="display-1 text-primary fw-bold mb-3"><?php echo htmlspecialchars($bandScore, ENT_QUOTES, 'UTF-8'); ?></div>
                                <p class="card-text fs-5 mb-4"><?php echo htmlspecialchars($feedback, ENT_QUOTES, 'UTF-8'); ?></p>
                                <a href="reading.php" class="btn btn-primary me-2">Retake Test</a>
                                <a href="index.php" class="btn btn-outline-primary">Back to Home</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="bg-light rounded p-4">
                            <h3 class="mb-4">Read the passage and answer the questions</h3>
                            
                            <div class="card mb-4 border-0 shadow-sm">
                                <div class="card-body">
                                    <p><strong>Passage:</strong></p>
                                    <p>The London Eye, also known as the Millennium Wheel, is a giant observation wheel located on the South Bank of the River Thames in London. It was opened to the public on March 9, 2000, and has become one of the most iconic landmarks in London. The wheel is 443 feet tall and offers panoramic views of the city from its 32 sealed passenger capsules. Each capsule can hold up to 25 people. A complete rotation takes approximately 30 minutes, allowing visitors to enjoy the sights at a leisurely pace. The London Eye is open daily and attracts millions of visitors from around the world each year.</p>
                                </div>
                            </div>

                            <form method="post" action="reading.php">
                                <div class="mb-4">
                                    <p><strong>1. When was the London Eye opened to the public?</strong></p>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q1" id="q1_A" value="1_A" required>
                                        <label class="form-check-label" for="q1_A">A. March 9, 2000</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q1" id="q1_B" value="1_B">
                                        <label class="form-check-label" for="q1_B">B. March 9, 2001</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q1" id="q1_C" value="1_C">
                                        <label class="form-check-label" for="q1_C">C. March 9, 1999</label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <p><strong>2. How many passenger capsules does the London Eye have?</strong></p>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q2" id="q2_A" value="2_A" required>
                                        <label class="form-check-label" for="q2_A">A. 25</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q2" id="q2_B" value="2_B">
                                        <label class="form-check-label" for="q2_B">B. 32</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q2" id="q2_C" value="2_C">
                                        <label class="form-check-label" for="q2_C">C. 30</label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <p><strong>3. What is the height of the London Eye?</strong></p>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q3" id="q3_A" value="3_A" required>
                                        <label class="form-check-label" for="q3_A">A. 350 feet</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q3" id="q3_B" value="3_B">
                                        <label class="form-check-label" for="q3_B">B. 400 feet</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q3" id="q3_C" value="3_C">
                                        <label class="form-check-label" for="q3_C">C. 443 feet</label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <p><strong>4. How long does a complete rotation of the London Eye take?</strong></p>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q4" id="q4_A" value="4_A" required>
                                        <label class="form-check-label" for="q4_A">A. Approximately 30 minutes</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q4" id="q4_B" value="4_B">
                                        <label class="form-check-label" for="q4_B">B. Approximately 15 minutes</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q4" id="q4_C" value="4_C">
                                        <label class="form-check-label" for="q4_C">C. Approximately 60 minutes</label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <p><strong>5. Where is the London Eye located?</strong></p>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q5" id="q5_A" value="5_A" required>
                                        <label class="form-check-label" for="q5_A">A. North Bank of the Thames</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q5" id="q5_B" value="5_B">
                                        <label class="form-check-label" for="q5_B">B. South Bank of the Thames</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q5" id="q5_C" value="5_C">
                                        <label class="form-check-label" for="q5_C">C. East Bank of the Thames</label>
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
