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
$streakStatus = [
    'currentStreak' => 0,
    'bestStreak' => 0,
    'needsReminder' => false,
    'missedYesterday' => false,
];

if ($currentUser && isset($currentUser['id'])) {
    $streakStatus = streak_get_status($conn, (int) $currentUser['id']);
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>IELTS Learning - Học IELTS bằng tiếng Việt</title>
    <base href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->


    <!-- Navbar Start -->
    <?php include __DIR__ . '/nav.php'; ?>
    <!-- Navbar End -->

    <?php if ($currentUser && !empty($streakStatus['needsReminder'])): ?>
        <div class="container mt-3">
            <div class="alert <?php echo !empty($streakStatus['missedYesterday']) ? 'alert-danger' : 'alert-warning'; ?> d-flex justify-content-between align-items-center" role="alert">
                <div>
                    <i class="fas fa-fire me-2"></i>
                    <?php if (!empty($streakStatus['missedYesterday'])): ?>
                        Bạn đã gián đoạn chuỗi học. Hoàn thành một bài ngay hôm nay để bắt đầu lại.
                    <?php else: ?>
                        Hôm nay bạn chưa hoàn thành mục tiêu học. Làm một bài test để giữ chuỗi.
                    <?php endif; ?>
                </div>
                <a href="reading.php" class="btn btn-sm btn-outline-dark">Học ngay</a>
            </div>
        </div>
    <?php endif; ?>


    <!-- Carousel Start -->
    <div class="container-fluid p-0 mb-5">
        <div class="owl-carousel header-carousel position-relative">
            <div class="owl-carousel-item position-relative">
                <img class="img-fluid" src="img/team.jpg" alt="">
                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center" style="background: rgba(24, 29, 56, .7);">
                    <div class="container">
                        <div class="row justify-content-start">
                            <div class="col-sm-10 col-lg-8">
                                <h5 class="text-primary text-uppercase mb-3 animated slideInDown">Khóa học trực tuyến tốt nhất</h5>
                                <h1 class="display-3 text-white animated slideInDown">Nền tảng học IELTS trực tuyến hàng đầu</h1>
                                <p class="fs-5 text-white mb-4 pb-2">Luyện thi IELTS theo lộ trình rõ ràng, tập trung vào kỹ năng thực tế và có bài test đánh giá trình độ.</p>
                                <a href="about.php" class="btn btn-primary py-md-3 px-md-5 me-3 animated slideInLeft">Xem thêm</a>
                                <a href="register.php" class="btn btn-light py-md-3 px-md-5 animated slideInRight">Đăng ký ngay</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="owl-carousel-item position-relative">
                <img class="img-fluid" src="img/anh2.jpg" alt="">
                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center" style="background: rgba(24, 29, 56, .7);">
                    <div class="container">
                        <div class="row justify-content-start">
                            <div class="col-sm-10 col-lg-8">
                                <h5 class="text-primary text-uppercase mb-3 animated slideInDown">Luyện thi mọi lúc mọi nơi</h5>
                                <h1 class="display-3 text-white animated slideInDown">Học online ngay tại nhà</h1>
                                <p class="fs-5 text-white mb-4 pb-2">Làm bài luyện tập, học flashcard và theo dõi chuỗi học mỗi ngày để giữ động lực.</p>
                                <a href="study_plan.php" class="btn btn-primary py-md-3 px-md-5 me-3 animated slideInLeft">Xem lộ trình</a>
                                <a href="register.php" class="btn btn-light py-md-3 px-md-5 animated slideInRight">Đăng ký ngay</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Carousel End -->

    <!-- British Council Attribution -->
    <div class="container-fluid bg-light py-3 border-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-auto">
                    <p class="mb-0 text-muted"><i class="fa fa-info-circle text-primary me-2"></i><strong>Nguồn:</strong> Nội dung IELTS của chúng tôi tham khảo từ <a href="https://www.britishcouncil.org/" target="_blank" class="text-primary text-decoration-none">British Council</a> và <a href="https://www.ielts.org/" target="_blank" class="text-primary text-decoration-none">IELTS Official</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.1s">
                    <a href="reading.php" class="text-decoration-none">
                        <div class="service-item text-center pt-3">
                            <div class="p-4">
                                <i class="fa fa-3x fa-book text-primary mb-4"></i>
                                <h5 class="mb-3">Reading</h5>
                                <p>Phát triển kỹ năng đọc để hiểu bài học thuật, bài tổng quát và tài liệu thực tế.</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.3s">
                    <a href="writing.php" class="text-decoration-none">
                        <div class="service-item text-center pt-3">
                            <div class="p-4">
                                <i class="fa fa-3x fa-pen text-primary mb-4"></i>
                                <h5 class="mb-3">Writing</h5>
                                <p>Luyện viết với ngữ pháp đúng, mạch lạc và liên kết ý tốt cho bài luận và văn bản trang trọng.</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.5s">
                    <a href="listening.php" class="text-decoration-none">
                        <div class="service-item text-center pt-3">
                            <div class="p-4">
                                <i class="fa fa-3x fa-headphones text-primary mb-4"></i>
                                <h5 class="mb-3">Listening</h5>
                                <p>Xây dựng kỹ năng nghe để hiểu hội thoại, bài giảng và tài liệu âm thanh tiếng Anh thực tế.</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.7s">
                    <a href="speaking.php" class="text-decoration-none">
                        <div class="service-item text-center pt-3">
                            <div class="p-4">
                                <i class="fa fa-3x fa-microphone text-primary mb-4"></i>
                                <h5 class="mb-3">Speaking</h5>
                                <p>Cải thiện phát âm, độ trôi chảy và khả năng giao tiếp trong các tình huống thực tế.</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- Service End -->


    <!-- About Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.1s" style="min-height: 400px;">
                    <div class="position-relative h-100">
                        <img class="img-fluid position-absolute w-100 h-100" src="img/hoso.jpg" alt="" style="object-fit: cover;">
                    </div>
                </div>
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.3s">
                    <h6 class="section-title bg-white text-start text-primary pe-3">Giới thiệu</h6>
                    <h1 class="mb-4">Chào mừng bạn đến với IELTS Learning</h1>
                    <p class="mb-4">Chinh phục IELTS với nền tảng học trực tuyến được xây dựng theo định hướng chuẩn luyện thi, tập trung vào bốn kỹ năng với tính ứng dụng cao.</p>
                    <p class="mb-4">Các bài test giúp bạn xác định trình độ hiện tại và định hướng đến mục tiêu mong muốn. Học theo tốc độ của riêng bạn với bài tập tương tác, tài liệu thực tế và phản hồi rõ ràng.</p>
                    <div class="row gy-2 gx-4 mb-4">
                        <div class="col-sm-6">
                            <p class="mb-0"><i class="fa fa-book text-primary me-2"></i>Reading</p>
                        </div>
                        <div class="col-sm-6">
                            <p class="mb-0"><i class="fa fa-pen text-primary me-2"></i>Writing</p>
                        </div>
                        <div class="col-sm-6">
                            <p class="mb-0"><i class="fa fa-headphones text-primary me-2"></i>Listening</p>
                        </div>
                        <div class="col-sm-6">
                            <p class="mb-0"><i class="fa fa-microphone text-primary me-2"></i>Speaking</p>
                        </div>
                        <div class="col-sm-6">
                            <p class="mb-0"><i class="fa fa-certificate text-primary me-2"></i>Phủ band 0-6</p>
                        </div>
                        <div class="col-sm-6">
                            <p class="mb-0"><i class="fa fa-graduation-cap text-primary me-2"></i>Hướng dẫn chi tiết</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <a class="btn btn-primary py-2 px-4" href="reading.php"><i class="fa fa-play me-2"></i>Bắt đầu bài đọc</a>
                        <a class="btn btn-outline-primary py-2 px-4" href="">Tất cả bài test</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- About End -->


    <!-- Categories Start -->
    <div class="container-xxl py-5 category">
        <div class="container">
            <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                <h6 class="section-title bg-white text-center text-primary px-3">Danh mục</h6>
                <h1 class="mb-5">Danh mục khóa học</h1>
            </div>
            <div class="row g-3">
                <div class="col-lg-7 col-md-6">
                    <div class="row g-3">
                        <div class="col-lg-12 col-md-12 wow zoomIn" data-wow-delay="0.1s">
                            <a class="position-relative d-block overflow-hidden" href="">
                                <img class="img-fluid" src="img/khoahoc1.jpg" alt="">
                                <div class="bg-white text-center position-absolute bottom-0 end-0 py-2 px-3" style="margin: 1px;">
                                    <h5 class="m-0">Thiết kế web</h5>
                                    <small class="text-primary">49 khóa học</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-6 col-md-12 wow zoomIn" data-wow-delay="0.3s">
                            <a class="position-relative d-block overflow-hidden" href="">
                                <img class="img-fluid" src="img/khoahoc.jpg" alt="">
                                <div class="bg-white text-center position-absolute bottom-0 end-0 py-2 px-3" style="margin: 1px;">
                                    <h5 class="m-0">Thiết kế đồ họa</h5>
                                    <small class="text-primary">49 khóa học</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-6 col-md-12 wow zoomIn" data-wow-delay="0.5s">
                            <a class="position-relative d-block overflow-hidden" href="">
                                <img class="img-fluid" src="img/khoahoc2.jpg" alt="">
                                <div class="bg-white text-center position-absolute bottom-0 end-0 py-2 px-3" style="margin: 1px;">
                                    <h5 class="m-0">Biên tập video</h5>
                                    <small class="text-primary">49 khóa học</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 col-md-6 wow zoomIn" data-wow-delay="0.7s" style="min-height: 350px;">
                    <a class="position-relative d-block h-100 overflow-hidden" href="">
                        <img class="img-fluid position-absolute w-100 h-100" src="img/khoahoc3.jpg" alt="" style="object-fit: cover;">
                        <div class="bg-white text-center position-absolute bottom-0 end-0 py-2 px-3" style="margin:  1px;">
                            <h5 class="m-0">Marketing trực tuyến</h5>
                            <small class="text-primary">49 khóa học</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- Categories Start -->


    <!-- Courses Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                <h6 class="section-title bg-white text-center text-primary px-3">Khóa học</h6>
                <h1 class="mb-5">Khóa học phổ biến</h1>
            </div>
            <div class="row g-4 justify-content-center">
                <!-- Reading Skill -->
                <div class="col-lg-6 col-md-12 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <i class="fas fa-book fa-3x text-primary mb-3"></i>
                                <h4 class="card-title mb-3">Reading</h4>
                            </div>
                            <div class="mb-3">
                                <p class="mb-2"><strong>Khoảng band:</strong></p>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fa fa-check text-primary me-2"></i>Band 0 - 3.5</li>
                                    <li class="mb-2"><i class="fa fa-check text-primary me-2"></i>Band 3.5 - 4.5</li>
                                    <li class="mb-2"><i class="fa fa-check text-primary me-2"></i>Band 4.5 - 5.5</li>
                                    <li class="mb-2"><i class="fa fa-check text-primary me-2"></i>Band 5.5 - 6.0</li>
                                </ul>
                            </div>
                            <p class="text-muted mb-4">Đánh giá kỹ năng đọc hiểu của bạn bằng bài test được thiết kế theo định hướng chuẩn luyện thi.</p>
                            <div class="d-flex gap-2">
                                <a href="reading.php" class="btn btn-primary flex-grow-1"><i class="fa fa-play me-2"></i>Bắt đầu</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Writing Skill -->
                <div class="col-lg-6 col-md-12 wow fadeInUp" data-wow-delay="0.2s">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <i class="fas fa-pen fa-3x text-primary mb-3"></i>
                                <h4 class="card-title mb-3">Writing</h4>
                            </div>
                            <div class="mb-3">
                                <p class="mb-2"><strong>Khoảng band:</strong></p>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fa fa-check text-primary me-2"></i>Band 0 - 3.5</li>
                                    <li class="mb-2"><i class="fa fa-check text-primary me-2"></i>Band 3.5 - 4.5</li>
                                    <li class="mb-2"><i class="fa fa-check text-primary me-2"></i>Band 4.5 - 5.5</li>
                                    <li class="mb-2"><i class="fa fa-check text-primary me-2"></i>Band 5.5 - 6.0</li>
                                </ul>
                            </div>
                            <p class="text-muted mb-4">Đánh giá kỹ năng viết gồm ngữ pháp, bố cục và từ vựng với bài test tổng hợp.</p>
                            <div class="d-flex gap-2">
                                <a href="writing.php" class="btn btn-primary flex-grow-1"><i class="fa fa-play me-2"></i>Bắt đầu</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Listening Skill -->
                <div class="col-lg-6 col-md-12 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <i class="fas fa-headphones fa-3x text-primary mb-3"></i>
                                <h4 class="card-title mb-3">Listening</h4>
                            </div>
                            <div class="mb-3">
                                <p class="mb-2"><strong>Khoảng band:</strong></p>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fa fa-check text-primary me-2"></i>Band 0 - 3.5</li>
                                    <li class="mb-2"><i class="fa fa-check text-primary me-2"></i>Band 3.5 - 4.5</li>
                                    <li class="mb-2"><i class="fa fa-check text-primary me-2"></i>Band 4.5 - 5.5</li>
                                    <li class="mb-2"><i class="fa fa-check text-primary me-2"></i>Band 5.5 - 6.0</li>
                                </ul>
                            </div>
                            <p class="text-muted mb-4">Kiểm tra khả năng nghe với tình huống thực tế và nhiều giọng đọc đa dạng.</p>
                            <div class="d-flex gap-2">
                                <a href="listening.php" class="btn btn-primary flex-grow-1"><i class="fa fa-play me-2"></i>Bắt đầu</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Speaking Skill -->
                <div class="col-lg-6 col-md-12 wow fadeInUp" data-wow-delay="0.4s">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <i class="fas fa-microphone fa-3x text-primary mb-3"></i>
                                <h4 class="card-title mb-3">Speaking</h4>
                            </div>
                            <div class="mb-3">
                                <p class="mb-2"><strong>Khoảng band:</strong></p>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fa fa-check text-primary me-2"></i>Band 0 - 3.5</li>
                                    <li class="mb-2"><i class="fa fa-check text-primary me-2"></i>Band 3.5 - 4.5</li>
                                    <li class="mb-2"><i class="fa fa-check text-primary me-2"></i>Band 4.5 - 5.5</li>
                                    <li class="mb-2"><i class="fa fa-check text-primary me-2"></i>Band 5.5 - 6.0</li>
                                </ul>
                            </div>
                            <p class="text-muted mb-4">Đánh giá độ trôi chảy, phát âm và sự tự tin khi nói tiếng Anh bằng bài tự kiểm tra.</p>
                            <div class="d-flex gap-2">
                                <a href="speaking.php" class="btn btn-primary flex-grow-1"><i class="fa fa-play me-2"></i>Bắt đầu</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Courses End -->


    <!-- Flashcards Start -->
    <div class="container-xxl py-5 bg-light">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.1s">
                    <h6 class="section-title bg-white text-start text-primary pe-3">Flashcard</h6>
                    <h1 class="mb-4">Ôn nhanh các kỹ năng IELTS</h1>
                    <p class="mb-4">Dùng flashcard để ôn lại ý chính, chiến lược hữu ích và từ vựng quan trọng trước khi làm bài test. Mục này được thiết kế cho việc luyện nhanh, giúp bạn nhớ kiến thức cốt lõi dễ hơn.</p>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="flashcard-preview-item d-flex align-items-center bg-white rounded p-3 shadow-sm h-100">
                                <i class="fa fa-book text-primary fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-1">Đọc hiểu</h6>
                                    <small class="text-muted">Skim, scan, từ khóa</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="flashcard-preview-item d-flex align-items-center bg-white rounded p-3 shadow-sm h-100">
                                <i class="fa fa-pen text-primary fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-1">Viết</h6>
                                    <small class="text-muted">Bố cục, liên kết, luận điểm</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="flashcard-preview-item d-flex align-items-center bg-white rounded p-3 shadow-sm h-100">
                                <i class="fa fa-headphones text-primary fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-1">Nghe</h6>
                                    <small class="text-muted">Dự đoán, bẫy, tín hiệu</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="flashcard-preview-item d-flex align-items-center bg-white rounded p-3 shadow-sm h-100">
                                <i class="fa fa-microphone text-primary fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-1">Nói</h6>
                                    <small class="text-muted">Trôi chảy, phát âm, từ vựng</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="flashcard.php" class="btn btn-primary py-3 px-5">Mở flashcard</a>
                </div>
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="flashcard-feature-panel position-relative rounded overflow-hidden shadow" style="min-height: 380px;">
                        <div class="bg-primary position-absolute top-0 start-0 w-100 h-100" style="opacity: 0.9;"></div>
                        <div class="position-absolute top-0 start-0 w-100 h-100 p-5 d-flex flex-column justify-content-between text-white">
                            <div>
                                <span class="badge bg-white text-primary mb-3 px-3 py-2">Study Pack</span>
                                <h2 class="fw-bold mb-3">Flip. Review. Remember.</h2>
                                <p class="mb-0">Short sessions work better than long cramming. Flashcards help students learn one concept at a time and keep the revision focused.</p>
                            </div>
                            <div class="flashcard-info-box bg-white text-dark rounded p-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>Available now</strong>
                                    <span class="text-primary fw-bold">4 Skills</span>
                                </div>
                                <small class="text-muted">Reading, Writing, Listening, and Speaking cards are ready to use from the flashcard page.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Flashcards End -->


    <!-- Team Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                <h6 class="section-title bg-white text-center text-primary px-3">Instructors</h6>
                <h1 class="mb-5">Expert Instructors</h1>
            </div>
            <div class="row g-4">
                <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="team-item bg-light">
                        <div class="overflow-hidden">
                            <img class="img-fluid" src="img/teacher1.jpg" alt="">
                        </div>
                        <div class="position-relative d-flex justify-content-center" style="margin-top: -23px;">
                            <div class="bg-light d-flex justify-content-center pt-2 px-1">
                                <a class="btn btn-sm-square btn-primary mx-1" href=""><i class="fab fa-facebook-f"></i></a>
                                <a class="btn btn-sm-square btn-primary mx-1" href=""><i class="fab fa-twitter"></i></a>
                                <a class="btn btn-sm-square btn-primary mx-1" href=""><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                        <div class="text-center p-4">
                            <h5 class="mb-0">Truc Phan</h5>
                            <small>IELTS 8.0</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="team-item bg-light">
                        <div class="overflow-hidden">
                            <img class="img-fluid" src="img/teacher2.jpg" alt="">
                        </div>
                        <div class="position-relative d-flex justify-content-center" style="margin-top: -23px;">
                            <div class="bg-light d-flex justify-content-center pt-2 px-1">
                                <a class="btn btn-sm-square btn-primary mx-1" href=""><i class="fab fa-facebook-f"></i></a>
                                <a class="btn btn-sm-square btn-primary mx-1" href=""><i class="fab fa-twitter"></i></a>
                                <a class="btn btn-sm-square btn-primary mx-1" href=""><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                        <div class="text-center p-4">
                            <h5 class="mb-0">Nam Khanh</h5>
                            <small>IELTS 8.5</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="0.5s">
                    <div class="team-item bg-light">
                        <div class="overflow-hidden">
                            <img class="img-fluid" src="img/teacher3.jpg" alt="">
                        </div>
                        <div class="position-relative d-flex justify-content-center" style="margin-top: -23px;">
                            <div class="bg-light d-flex justify-content-center pt-2 px-1">
                                <a class="btn btn-sm-square btn-primary mx-1" href=""><i class="fab fa-facebook-f"></i></a>
                                <a class="btn btn-sm-square btn-primary mx-1" href=""><i class="fab fa-twitter"></i></a>
                                <a class="btn btn-sm-square btn-primary mx-1" href=""><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                        <div class="text-center p-4">
                            <h5 class="mb-0">Thuy Nga</h5>
                            <small>IELTS 7.5</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="0.7s">
                    <div class="team-item bg-light">
                        <div class="overflow-hidden">
                            <img class="img-fluid" src="img/teacher5.jpg" alt="">
                        </div>
                        <div class="position-relative d-flex justify-content-center" style="margin-top: -23px;">
                            <div class="bg-light d-flex justify-content-center pt-2 px-1">
                                <a class="btn btn-sm-square btn-primary mx-1" href=""><i class="fab fa-facebook-f"></i></a>
                                <a class="btn btn-sm-square btn-primary mx-1" href=""><i class="fab fa-twitter"></i></a>
                                <a class="btn btn-sm-square btn-primary mx-1" href=""><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                        <div class="text-center p-4">
                            <h5 class="mb-0">Quoc Khanh</h5>
                            <small>IELTS 9.0</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Team End -->


    <!-- Testimonial Start -->
    <div class="container-xxl py-5 wow fadeInUp" data-wow-delay="0.1s">
        <div class="container">
            <div class="text-center">
                <h6 class="section-title bg-white text-center text-primary px-3">Testimonial</h6>
                <h1 class="mb-5">Our Students Say!</h1>
            </div>
            <div class="owl-carousel testimonial-carousel position-relative">
                <div class="testimonial-item text-center">
                    <img class="border rounded-circle p-2 mx-auto mb-3" src="img/testimonial-1.jpg" style="width: 80px; height: 80px;">
                    <h5 class="mb-0">Client Name</h5>
                    <p>Profession</p>
                    <div class="testimonial-text bg-light text-center p-4">
                    <p class="mb-0">Tempor erat elitr rebum at clita. Diam dolor diam ipsum sit diam amet diam et eos. Clita erat ipsum et lorem et sit.</p>
                    </div>
                </div>
                <div class="testimonial-item text-center">
                    <img class="border rounded-circle p-2 mx-auto mb-3" src="img/testimonial-2.jpg" style="width: 80px; height: 80px;">
                    <h5 class="mb-0">Client Name</h5>
                    <p>Profession</p>
                    <div class="testimonial-text bg-light text-center p-4">
                    <p class="mb-0">Tempor erat elitr rebum at clita. Diam dolor diam ipsum sit diam amet diam et eos. Clita erat ipsum et lorem et sit.</p>
                    </div>
                </div>
                <div class="testimonial-item text-center">
                    <img class="border rounded-circle p-2 mx-auto mb-3" src="img/testimonial-3.jpg" style="width: 80px; height: 80px;">
                    <h5 class="mb-0">Client Name</h5>
                    <p>Profession</p>
                    <div class="testimonial-text bg-light text-center p-4">
                    <p class="mb-0">Tempor erat elitr rebum at clita. Diam dolor diam ipsum sit diam amet diam et eos. Clita erat ipsum et lorem et sit.</p>
                    </div>
                </div>
                <div class="testimonial-item text-center">
                    <img class="border rounded-circle p-2 mx-auto mb-3" src="img/testimonial-4.jpg" style="width: 80px; height: 80px;">
                    <h5 class="mb-0">Client Name</h5>
                    <p>Profession</p>
                    <div class="testimonial-text bg-light text-center p-4">
                    <p class="mb-0">Tempor erat elitr rebum at clita. Diam dolor diam ipsum sit diam amet diam et eos. Clita erat ipsum et lorem et sit.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Testimonial End -->
        

    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-light footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Quick Link</h4>
                    <a class="btn btn-link" href="">About Us</a>
                    <a class="btn btn-link" href="">Contact Us</a>
                    <a class="btn btn-link" href="">Privacy Policy</a>
                    <a class="btn btn-link" href="">Terms & Condition</a>
                    <a class="btn btn-link" href="">FAQs & Help</a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Contact</h4>
                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>182 Lê Duẩn, Trường Vinh, Nghệ An</p>
                    <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>0849089399</p>
                    <p class="mb-2"><i class="fa fa-envelope me-3"></i>Phthanhtruc.74nd@gmail.com</p>
                    <div class="d-flex pt-2">
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-twitter"></i></a>
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-youtube"></i></a>
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Gallery</h4>
                    <div class="row g-2 pt-2">
                        <div class="col-4">
                            <img class="img-fluid bg-light p-1" src="img/course-1.jpg" alt="">
                        </div>
                        <div class="col-4">
                            <img class="img-fluid bg-light p-1" src="img/course-2.jpg" alt="">
                        </div>
                        <div class="col-4">
                            <img class="img-fluid bg-light p-1" src="img/course-3.jpg" alt="">
                        </div>
                        <div class="col-4">
                            <img class="img-fluid bg-light p-1" src="img/course-2.jpg" alt="">
                        </div>
                        <div class="col-4">
                            <img class="img-fluid bg-light p-1" src="img/course-3.jpg" alt="">
                        </div>
                        <div class="col-4">
                            <img class="img-fluid bg-light p-1" src="img/course-1.jpg" alt="">
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">References</h4>
                    <p class="mb-3">Our IELTS courses are based on official materials from:</p>
                    <a class="btn btn-link" href="https://www.britishcouncil.org/" target="_blank"><i class="fa fa-external-link-alt me-2"></i>British Council</a>
                    <a class="btn btn-link" href="https://www.ielts.org/" target="_blank"><i class="fa fa-external-link-alt me-2"></i>IELTS Official</a>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="copyright">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        &copy; <a class="border-bottom" href="#">IELTS Web</a>, All Right Reserved.

                        <!--/*** This template is free as long as you keep the footer author’s credit link/attribution link/backlink. If you'd like to use the template without the footer author’s credit link/attribution link/backlink, you can purchase the Credit Removal License from "https://htmlcodex.com/credit-removal". Thank you for your support. ***/-->
                        Designed By <a class="border-bottom" href="https://htmlcodex.com">HTML Codex</a><br><br>
                        Distributed By <a class="border-bottom" href="https://themewagon.com">ThemeWagon</a>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <div class="footer-menu">
                            <a href="">Home</a>
                            <a href="">Cookies</a>
                            <a href="">Help</a>
                            <a href="">FQAs</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->


    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>

    <!-- Floating AI Chat Button -->
    <button id="aiChatBtn" class="btn btn-primary" title="AI Chat" style="position:fixed;right:20px;bottom:20px;width:56px;height:56px;border-radius:50%;z-index:1050;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 20px rgba(16,24,40,.2);">
        <i class="fas fa-robot" style="font-size:20px;color:#fff;"></i>
    </button>

    <script>
        (function(){
            var botId = <?php echo json_encode(defined('COZE_BOT_ID') ? COZE_BOT_ID : null, JSON_UNESCAPED_UNICODE); ?>;
            var token = <?php echo json_encode(defined('COZE_WEBCHAT_TOKEN') ? COZE_WEBCHAT_TOKEN : null, JSON_UNESCAPED_UNICODE); ?>;
            var chatClient = null;

            function loadSDK(cb){
                if (window.CozeWebSDK) { cb(); return; }
                var s = document.createElement('script');
                s.src = 'https://sf-cdn.coze.com/obj/unpkg-va/flow-platform/chat-app-sdk/1.2.0-beta.6/libs/oversea/index.js';
                s.onload = cb;
                document.body.appendChild(s);
            }

            function openChat(){
                // Navigate to internal chat page which now uses OpenAI
                window.location.href = 'chat.php';
            }

            document.getElementById('aiChatBtn').addEventListener('click', openChat);
        })();
    </script>


    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>

</html>
