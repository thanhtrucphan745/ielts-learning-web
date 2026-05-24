<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

auth_start_session();
$currentUser = auth_user();
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$basePath = $basePath === '' ? '/' : $basePath . '/';
$displayName = $currentUser['name'] ?? ($currentUser['username'] ?? 'User');
$avatarText = strtoupper(mb_substr($displayName, 0, 1, 'UTF-8'));
$avatarPath = !empty($currentUser['avatar']) ? $currentUser['avatar'] : '';
$avatarUrl = $avatarPath !== '' ? $avatarPath : '';
$role = isset($currentUser['role']) ? (int) $currentUser['role'] : 0;

$successMessage = '';
$errorMessage = '';
$nameValue = '';
$emailValue = '';
$subjectValue = '';
$messageValue = '';

if ($currentUser) {
    $nameValue = (string) ($currentUser['name'] ?? $currentUser['username'] ?? '');
    $emailValue = (string) ($currentUser['email'] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nameValue = trim($_POST['name'] ?? '');
    $emailValue = trim($_POST['email'] ?? '');
    $subjectValue = trim($_POST['subject'] ?? '');
    $messageValue = trim($_POST['message'] ?? '');

    if ($nameValue === '' || $emailValue === '' || $subjectValue === '' || $messageValue === '') {
        $errorMessage = 'Vui lòng điền đầy đủ thông tin liên hệ.';
    } elseif (!filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Email không hợp lệ.';
    } else {
        $stmt = $conn->prepare("INSERT INTO contact_messages (`name`, `email`, `subject`, `message`, `created_at`) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param('ssss', $nameValue, $emailValue, $subjectValue, $messageValue);
            if ($stmt->execute()) {
                $successMessage = 'Cảm ơn bạn đã liên hệ. Chúng tôi sẽ phản hồi sớm nhất có thể.';
                $nameValue = '';
                $emailValue = '';
                $subjectValue = '';
                $messageValue = '';
            } else {
                $errorMessage = 'Có lỗi khi lưu liên hệ. Vui lòng thử lại sau.';
            }
            $stmt->close();
        } else {
            $errorMessage = 'Lỗi hệ thống. Vui lòng thử lại sau.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Contact - eLEARNING</title>
    <base href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

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

        <!-- Navbar Start -->
        <?php include __DIR__ . '/nav.php'; ?>
        <!-- Navbar End -->

    <div class="container-fluid bg-primary py-5 mb-5 page-header">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <h1 class="display-3 text-white animated slideInDown">Contact</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center">
                            <li class="breadcrumb-item"><a class="text-white" href="index.php">Home</a></li>
                            <li class="breadcrumb-item"><a class="text-white" href="#">Pages</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">Contact</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    

    <div class="container-xxl py-5">
        <div class="container">
            <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                <h6 class="section-title bg-white text-center text-primary px-3">Contact Us</h6>
                <h1 class="mb-5">Liên hệ với chúng tôi</h1>
            </div>
                <?php if ($currentUser): ?>
                    <div class="alert alert-info mb-4">
                        Bạn sẽ nhận phản hồi của quản trị viên trong biểu tượng chuông ở thanh điều hướng nếu dùng đúng email tài khoản hiện tại.
                    </div>
                <?php endif; ?>
            <div class="row g-4">
                <div class="col-lg-4 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="bg-light rounded p-4 h-100">
                        <h4 class="mb-4">Thông tin liên hệ</h4>
                        <p class="mb-3"><i class="fa fa-map-marker-alt text-primary me-3"></i>182 Lê Duẩn, Trường Vinh, Nghệ An</p>
                        <p class="mb-3"><i class="fa fa-phone-alt text-primary me-3"></i>0849089399</p>
                        <p class="mb-3"><i class="fa fa-envelope text-primary me-3"></i>Phthanhtruc.74nd@gmail.com</p>
                        <p class="mb-0">Gửi câu hỏi, góp ý hoặc yêu cầu hỗ trợ, chúng tôi sẽ phản hồi sớm.</p>
                    </div>
                </div>
                <div class="col-lg-8 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="bg-light rounded p-4">
                        <?php if ($successMessage): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                        <?php if ($errorMessage): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                        <form method="post" action="contact.php">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <input type="text" name="name" class="form-control border-0 py-3" placeholder="Họ và tên" value="<?php echo htmlspecialchars($nameValue, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <input type="email" name="email" class="form-control border-0 py-3" placeholder="Email" value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-12">
                                    <input type="text" name="subject" class="form-control border-0 py-3" placeholder="Tiêu đề" value="<?php echo htmlspecialchars($subjectValue, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-12">
                                    <textarea name="message" class="form-control border-0 py-3" rows="6" placeholder="Nội dung"><?php echo htmlspecialchars($messageValue, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary py-3 px-5" type="submit">Gửi liên hệ</button>
                                </div>
                            </div>
                        </form>
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
                    <a class="btn btn-link" href="#">Privacy Policy</a>
                    <a class="btn btn-link" href="#">Terms & Condition</a>
                    <a class="btn btn-link" href="#">FAQs & Help</a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Contact</h4>
                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>184 Lê Duẩn, Trường Vinh, Nghệ An</p>
                    <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>0849089399</p>
                    <p class="mb-2"><i class="fa fa-envelope me-3"></i>Phthanhtruc.74nd@gmail.com</p>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Location</h4>
                    <div class="pt-2">
                        <iframe src="https://maps.google.com/maps?q=Dai%20hoc%20Vinh&t=&z=15&ie=UTF8&iwloc=&output=embed" style="border:0;width:100%;height:180px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Newsletter</h4>
                    <p>Nhan ban tin moi nhat ve lo trinh hoc va tai lieu IELTS.</p>
                    <div class="position-relative mx-auto" style="max-width: 400px;">
                        <input class="form-control border-0 w-100 py-3 ps-4 pe-5" type="text" placeholder="Your email">
                        <button type="button" class="btn btn-primary py-2 position-absolute top-0 end-0 mt-2 me-2">SignUp</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="copyright">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        &copy; <a class="border-bottom" href="#">IELTS Web</a>, All Right Reserved.
                        Designed By <a class="border-bottom" href="https://htmlcodex.com">HTML Codex</a>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <div class="footer-menu">
                            <a href="index.php">Home</a>
                            <a href="#">Cookies</a>
                            <a href="#">Help</a>
                            <a href="#">FQAs</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>

