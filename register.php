<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

auth_start_session();

if (auth_user()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$name = '';
$username = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($name === '' || $username === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        if (!$stmt) {
            $error = 'Database error while checking account.';
        } else {
            $stmt->bind_param('ss', $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $existingUser = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if ($existingUser) {
                $error = 'Username or email already exists.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $role = 2;
                $avatar = '';

                $stmt = $conn->prepare('INSERT INTO users (name, email, username, password, role, phone, avatar) VALUES (?, ?, ?, ?, ?, ?, ?)');
                if (!$stmt) {
                    $error = 'Database error while preparing registration.';
                } else {
                    $stmt->bind_param('ssssiss', $name, $email, $username, $hashedPassword, $role, $phone, $avatar);

                    if ($stmt->execute()) {
                        $userId = (int) $stmt->insert_id;
                        auth_start_session();
                        $_SESSION['auth_user'] = [
                            'id' => $userId,
                            'name' => $name,
                            'email' => $email,
                            'username' => $username,
                            'role' => $role,
                            'phone' => $phone,
                            'avatar' => $avatar,
                        ];

                        header('Location: index.php');
                        exit;
                    }

                    $error = 'Registration failed. Please try again.';
                    $stmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0d6efd 0%, #0b3d91 100%);
        }
        .register-card {
            border: 0;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 1.5rem 3rem rgba(0, 0, 0, 0.2);
        }
        .register-hero {
            background: linear-gradient(160deg, rgba(13, 110, 253, 0.95), rgba(32, 201, 151, 0.95));
            color: #fff;
        }
    </style>
</head>
<body class="d-flex align-items-center py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">
                <div class="card register-card">
                    <div class="row g-0">
                        <div class="col-lg-5 register-hero p-5 d-flex flex-column justify-content-between">
                            <div>
                                <h1 class="h2 fw-bold mb-3">Tham gia IELTS Learning</h1>
                                <p class="mb-4">Tạo tài khoản để làm bài test IELTS, dùng flashcard và theo dõi tiến độ học tập của bạn.</p>
                            </div>
                            <div class="mt-4">
                                <div class="d-flex align-items-center mb-3"><i class="fa fa-book me-3"></i>Đọc, Viết, Nghe, Nói</div>
                                <div class="d-flex align-items-center mb-3"><i class="fa fa-bolt me-3"></i>Luyện nhanh band 0-6</div>
                                <div class="d-flex align-items-center"><i class="fa fa-language me-3"></i>Hỗ trợ tiếng Anh và tiếng Việt</div>
                            </div>
                        </div>
                        <div class="col-lg-7 p-5 bg-white">
                            <h2 class="h4 mb-3">Tạo tài khoản</h2>
                            <p class="text-muted mb-4">Đã có tài khoản? <a href="login.php">Đăng nhập tại đây</a>.</p>

                            <?php if ($error !== ''): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>

                            <form method="post" action="">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Họ và tên</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="username" class="form-label">Tên đăng nhập</label>
                                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Số điện thoại</label>
                                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">Mật khẩu</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 mt-4 py-2">Đăng ký</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
