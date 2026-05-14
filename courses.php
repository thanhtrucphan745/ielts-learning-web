<!DOCTYPE html>
<html lang="vi">

<head>
    <?php
    $bandDescriptors = [
        '0-3.5' => [
            'title' => 'Band 0 - 3.5: Beginner Level',
            'description' => 'You are starting your English learning journey. This level is perfect for beginners who need to build fundamental skills.',
            'skills' => [
                ['skill' => 'Reading', 'desc' => 'You understand very little from written texts. Start with simple materials and build vocabulary gradually.', 'icon' => 'fa-book'],
                ['skill' => 'Writing', 'desc' => 'You cannot produce coherent writing yet. Focus on basic sentence structure and simple words.', 'icon' => 'fa-pen'],
                ['skill' => 'Listening', 'desc' => 'You understand very little from spoken English. Start with slow, clear audio.', 'icon' => 'fa-headphones'],
                ['skill' => 'Speaking', 'desc' => 'You struggle to express yourself in English. Practice simple conversations regularly.', 'icon' => 'fa-microphone']
            ]
        ],
        '3.5-4.5' => [
            'title' => 'Band 3.5 - 4.5: Elementary Level',
            'description' => 'You have basic English skills. You can handle simple, everyday situations but struggle with complex topics.',
            'skills' => [
                ['skill' => 'Reading', 'desc' => 'You can understand simple texts with familiar topics. You often miss detailed information.', 'icon' => 'fa-book'],
                ['skill' => 'Writing', 'desc' => 'You can write simple sentences with basic structure. You make frequent grammar errors.', 'icon' => 'fa-pen'],
                ['skill' => 'Listening', 'desc' => 'You understand basic conversations if spoken clearly. You miss complex ideas.', 'icon' => 'fa-headphones'],
                ['skill' => 'Speaking', 'desc' => 'You can produce simple sentences with frequent pauses. Your pronunciation needs improvement.', 'icon' => 'fa-microphone']
            ]
        ],
        '4.5-5.5' => [
            'title' => 'Band 4.5 - 5.5: Intermediate Level',
            'description' => 'You have intermediate English skills. You can handle most everyday situations and understand main ideas in complex texts.',
            'skills' => [
                ['skill' => 'Reading', 'desc' => 'You understand main ideas in texts with some detailed comprehension. You may miss subtleties.', 'icon' => 'fa-book'],
                ['skill' => 'Writing', 'desc' => 'You write with adequate structure and mostly correct grammar. Your expression is sometimes unclear.', 'icon' => 'fa-pen'],
                ['skill' => 'Listening', 'desc' => 'You understand main points in conversations and lectures. You may miss some details.', 'icon' => 'fa-headphones'],
                ['skill' => 'Speaking', 'desc' => 'You speak with some fluency but make grammatical errors. Your vocabulary is adequate for most topics.', 'icon' => 'fa-microphone']
            ]
        ],
        '5.5-6.0' => [
            'title' => 'Band 5.5 - 6.0: Upper-Intermediate Level',
            'description' => 'You have strong English skills approaching professional level. You can handle complex topics and communicate effectively.',
            'skills' => [
                ['skill' => 'Reading', 'desc' => 'You understand both main ideas and details in complex texts. Your accuracy is 70-80%.', 'icon' => 'fa-book'],
                ['skill' => 'Writing', 'desc' => 'You write with clear structure, accurate grammar, and appropriate vocabulary. Ready for advanced tasks.', 'icon' => 'fa-pen'],
                ['skill' => 'Listening', 'desc' => 'You understand conversations and lectures with 70-80% accuracy. You catch most details.', 'icon' => 'fa-headphones'],
                ['skill' => 'Speaking', 'desc' => 'You speak fluently and clearly with mostly correct pronunciation. Your vocabulary is rich and appropriate.', 'icon' => 'fa-microphone']
            ]
        ]
    ];

    $selectedBand = isset($_GET['band']) ? $_GET['band'] : '5.5-6.0';
    $bandInfo = $bandDescriptors[$selectedBand] ?? $bandDescriptors['5.5-6.0'];
    ?>
    <meta charset="utf-8">
    <title>Khóa học - eLEARNING</title>
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
    <nav class="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">
        <a href="index.php" class="navbar-brand d-flex align-items-center px-4 px-lg-5">
            <h2 class="m-0 text-primary"><i class="fa fa-book me-3"></i>eLEARNING</h2>
        </a>
        <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav ms-auto p-4 p-lg-0">
                <a href="index.php" class="nav-item nav-link">Trang chủ</a>
                <a href="about.php" class="nav-item nav-link">Giới thiệu</a>
                <a href="courses.php" class="nav-item nav-link active">Khóa học</a>
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Trang</a>
                    <div class="dropdown-menu fade-down m-0">
                        <a href="team.php" class="dropdown-item">Đội ngũ</a>
                        <a href="testimonial.php" class="dropdown-item">Nhẫn xét</a>
                        <a href="flashcard.php" class="dropdown-item">Flashcard</a>
                        <a href="study_plan.php" class="dropdown-item">Lộ trình học</a>
                        <a href="chat.php" class="dropdown-item">AI Chat</a>
                        <a href="pages/ielts_tips.php" class="dropdown-item">IELTS Tips</a>
                        
                    </div>
                </div>
                <a href="contact.php" class="nav-item nav-link">Liên hệ</a>
            </div>
            <a href="register.php" class="btn btn-primary py-4 px-lg-5 d-none d-lg-block">Đăng ký<i class="fa fa-arrow-right ms-3"></i></a>
        </div>
    </nav>
    <!-- Navbar End -->


    <!-- Header Start -->
    <div class="container-fluid bg-primary py-5 mb-5 page-header">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <h1 class="display-3 text-white animated slideInDown">Khóa học</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center">
                            <li class="breadcrumb-item"><a class="text-white" href="index.php">Trang chủ</a></li>
                            <li class="breadcrumb-item"><a class="text-white" href="#">Trang</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">Khóa học</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- Header End -->


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
                                <img class="img-fluid" src="img/cat-1.jpg" alt="">
                                <div class="bg-white text-center position-absolute bottom-0 end-0 py-2 px-3" style="margin: 1px;">
                                    <h5 class="m-0">Web Design</h5>
                                    <small class="text-primary">49 Courses</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-6 col-md-12 wow zoomIn" data-wow-delay="0.3s">
                            <a class="position-relative d-block overflow-hidden" href="">
                                <img class="img-fluid" src="img/cat-2.jpg" alt="">
                                <div class="bg-white text-center position-absolute bottom-0 end-0 py-2 px-3" style="margin: 1px;">
                                    <h5 class="m-0">Graphic Design</h5>
                                    <small class="text-primary">49 Courses</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-6 col-md-12 wow zoomIn" data-wow-delay="0.5s">
                            <a class="position-relative d-block overflow-hidden" href="">
                                <img class="img-fluid" src="img/cat-3.jpg" alt="">
                                <div class="bg-white text-center position-absolute bottom-0 end-0 py-2 px-3" style="margin: 1px;">
                                    <h5 class="m-0">Video Editing</h5>
                                    <small class="text-primary">49 Courses</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 col-md-6 wow zoomIn" data-wow-delay="0.7s" style="min-height: 350px;">
                    <a class="position-relative d-block h-100 overflow-hidden" href="">
                        <img class="img-fluid position-absolute w-100 h-100" src="img/cat-4.jpg" alt="" style="object-fit: cover;">
                        <div class="bg-white text-center position-absolute bottom-0 end-0 py-2 px-3" style="margin:  1px;">
                            <h5 class="m-0">Online Marketing</h5>
                            <small class="text-primary">49 Courses</small>
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
                <h6 class="section-title bg-white text-center text-primary px-3">Band Information</h6>
                <h1 class="mb-5"><?php echo htmlspecialchars($bandInfo['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="fs-5 text-muted"><?php echo htmlspecialchars($bandInfo['description'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="row g-4 justify-content-center">
                <?php foreach ($bandInfo['skills'] as $index => $skillData): ?>
                <div class="col-lg-6 col-md-12 wow fadeInUp" data-wow-delay="<?php echo 0.1 + ($index * 0.1); ?>s">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <i class="fas <?php echo htmlspecialchars($skillData['icon'], ENT_QUOTES, 'UTF-8'); ?> fa-3x text-primary mb-3"></i>
                                <h4 class="card-title mb-3"><?php echo htmlspecialchars($skillData['skill'], ENT_QUOTES, 'UTF-8'); ?></h4>
                            </div>
                            <p class="text-muted mb-4"><?php echo htmlspecialchars($skillData['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <a href="<?php echo strtolower($skillData['skill']); ?>.php" class="btn btn-primary w-100"><i class="fa fa-play me-2"></i>Start <?php echo htmlspecialchars($skillData['skill'], ENT_QUOTES, 'UTF-8'); ?> Test</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <!-- Courses End -->


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
                    <a class="btn btn-link" href="contact.php">Contact Us</a>
                    <a class="btn btn-link" href="">Privacy Policy</a>
                    <a class="btn btn-link" href="">Terms & Condition</a>
                    <a class="btn btn-link" href="">FAQs & Help</a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Contact</h4>
                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>123 Street, New York, USA</p>
                    <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+012 345 67890</p>
                    <p class="mb-2"><i class="fa fa-envelope me-3"></i>info@example.com</p>
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
