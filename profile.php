<?php
require_once 'auth.php';
require_once 'config.php';

auth_start_session();
auth_require_login();

$currentUser = auth_user();
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$successMessage = '';
$errorMessage = '';

// Handle file uploads
$uploadDir = 'img/avatars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'upload_avatar') {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = 'Vui lòng chọn ảnh để tải lên';
        } else {
            $file = $_FILES['avatar'];
            $fileName = basename($file['name']);
            $fileTmp = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileType = mime_content_type($fileTmp);
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($fileType, $allowedTypes)) {
                $errorMessage = 'Chỉ hỗ trợ ảnh JPG, PNG, GIF, WebP';
            } elseif ($fileSize > 2 * 1024 * 1024) {
                // Max 2MB
                $errorMessage = 'Kích thước ảnh tối đa 2MB';
            } else {
                // Create unique filename
                $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = $currentUser['id'] . '_' . time() . '.' . $ext;
                $uploadPath = $uploadDir . $newFileName;
                
                if (move_uploaded_file($fileTmp, $uploadPath)) {
                    // Delete old avatar if it exists
                    if (!empty($currentUser['avatar']) && file_exists($currentUser['avatar'])) {
                        unlink($currentUser['avatar']);
                    }
                    
                    // Update database
                    $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                    $stmt->bind_param("si", $uploadPath, $currentUser['id']);
                    
                    if ($stmt->execute()) {
                        $successMessage = 'Tải lên ảnh đại diện thành công';
                        $currentUser['avatar'] = $uploadPath;
                        $_SESSION['auth_user'] = $currentUser;
                    } else {
                        $errorMessage = 'Lỗi cập nhật ảnh: ' . $conn->error;
                        unlink($uploadPath);
                    }
                    $stmt->close();
                } else {
                    $errorMessage = 'Không thể lưu ảnh, vui lòng thử lại';
                }
            }
        }
    } elseif ($_POST['action'] === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($name) || empty($email)) {
            $errorMessage = 'Vui lòng điền đầy đủ thông tin';
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $email, $currentUser['id']);
            
            if ($stmt->execute()) {
                $successMessage = 'Cập nhật thông tin thành công';
                $currentUser['name'] = $name;
                $currentUser['email'] = $email;
                $_SESSION['auth_user'] = $currentUser;
            } else {
                $errorMessage = 'Lỗi cập nhật thông tin: ' . $conn->error;
            }
            $stmt->close();
        }
    } elseif ($_POST['action'] === 'change_password') {
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errorMessage = 'Vui lòng điền đầy đủ mật khẩu';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMessage = 'Mật khẩu mới không khớp';
        } else {
            // Verify old password
            if (password_verify($oldPassword, $currentUser['password']) || hash_equals($currentUser['password'], $oldPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashedPassword, $currentUser['id']);
                
                if ($stmt->execute()) {
                    $successMessage = 'Đổi mật khẩu thành công';
                } else {
                    $errorMessage = 'Lỗi đổi mật khẩu: ' . $conn->error;
                }
                $stmt->close();
            } else {
                $errorMessage = 'Mật khẩu cũ không chính xác';
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
    <title>Hồ sơ cá nhân - eLEARNING</title>
    <base href="<?php echo htmlspecialchars($basePath . '/'); ?>">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px 0;
        }
        .profile-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .profile-header {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }
        .profile-avatar {
            flex-shrink: 0;
        }
        .profile-avatar img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #007bff;
        }
        .profile-info {
            flex: 1;
        }
        .profile-info h2 {
            color: #333;
            margin: 0 0 15px 0;
            font-size: 24px;
            font-weight: 600;
        }
        .info-item {
            margin-bottom: 10px;
            display: flex;
            gap: 10px;
        }
        .info-label {
            font-weight: 600;
            color: #555;
            min-width: 100px;
        }
        .info-value {
            color: #777;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
        }
        .form-section {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-row.full {
            grid-template-columns: 1fr;
        }
        .form-group {
            margin-bottom: 0;
        }
        .form-group label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            display: block;
        }
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .btn-update {
            background-color: #007bff;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 14px;
        }
        .btn-update:hover {
            background-color: #0056b3;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .navbar {
            background-color: white;
            border-bottom: 1px solid #ddd;
            margin-bottom: 30px;
            padding: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
        }
        .navbar-logo {
            font-weight: 700;
            color: #007bff;
            font-size: 18px;
            text-decoration: none;
        }
        .navbar-nav {
            display: flex;
            gap: 20px;
        }
        .navbar-nav a {
            color: #555;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        .navbar-nav a:hover {
            color: #007bff;
        }
        .upload-avatar-section {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .upload-area {
            border: 2px dashed #007bff;
            border-radius: 8px;
            padding: 40px 20px;
            cursor: pointer;
            transition: all 0.3s;
            background-color: #f8f9ff;
            margin: 20px 0;
        }
        .upload-area:hover {
            background-color: #f0f3ff;
            border-color: #0056b3;
        }
        .upload-area.dragover {
            background-color: #e7f0ff;
            border-color: #0056b3;
        }
        .upload-area i {
            font-size: 48px;
            color: #007bff;
            margin-bottom: 15px;
        }
        .upload-area p {
            color: #666;
            margin: 10px 0 0 0;
            font-size: 14px;
        }
        #avatarInput {
            display: none;
        }
        .avatar-preview {
            margin: 20px 0;
        }
        .avatar-preview img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            border: 2px solid #ddd;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <div class="navbar">
        <a href="index.php" class="navbar-logo">eLEARNING</a>
        <div class="navbar-nav">
            <a href="index.php">Trang chủ</a>
            <a href="logout.php" style="color: #dc3545;">Đăng xuất</a>
        </div>
    </div>

    <div class="profile-container">
        <!-- Messages -->
        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <img src="<?php echo htmlspecialchars($currentUser['avatar'] ?? 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22150%22 height=%22150%22%3E%3Crect fill=%22%23ccc%22 width=%22150%22 height=%22150%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-family=%22Arial%22 font-size=%2224%22 fill=%22%23999%22%3ENo Image%3C/text%3E%3C/svg%3E'); ?>" alt="Avatar">
            </div>
            <div class="profile-info">
                <h2>Hồ sơ cá nhân</h2>
                <div class="info-item">
                    <span class="info-label">Tên đăng nhập:</span>
                    <span class="info-value"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($currentUser['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Họ và tên:</span>
                    <span class="info-value"><?php echo htmlspecialchars($currentUser['name']); ?></span>
                </div>
            </div>
        </div>

        <!-- Upload Avatar Section -->
        <div class="upload-avatar-section">
            <h3 class="section-title">Thay đổi ảnh đại diện</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_avatar">
                <div class="upload-area" id="uploadArea">
                    <div style="font-size: 24px;">📸</div>
                    <p><strong>Nhấp hoặc kéo thả ảnh vào đây</strong></p>
                    <p style="color: #999; font-size: 12px;">JPG, PNG, GIF, WebP - Tối đa 2MB</p>
                </div>
                <input type="file" id="avatarInput" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" required>
                <div class="avatar-preview" id="previewContainer" style="display: none;">
                    <img id="previewImage" alt="Preview">
                </div>
                <button type="submit" class="btn-update">Tải lên ảnh</button>
            </form>
        </div>

        <!-- Update Profile Section -->
        <div class="form-section">
            <h3 class="section-title">Cập nhật thông tin</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Tên đăng nhập</label>
                        <input type="text" id="username" value="<?php echo htmlspecialchars($currentUser['username']); ?>" disabled style="background-color: #f5f5f5; cursor: not-allowed;">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Họ và tên</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($currentUser['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Số điện thoại</label>
                        <input type="tel" id="phone" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>" disabled style="background-color: #f5f5f5; cursor: not-allowed;">
                    </div>
                </div>

                <button type="submit" class="btn-update">Cập nhật</button>
            </form>
        </div>

        <!-- Change Password Section -->
        <div class="form-section">
            <h3 class="section-title">Đổi mật khẩu</h3>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-row full">
                    <div class="form-group">
                        <label for="old_password">Mật khẩu cũ</label>
                        <input type="password" id="old_password" name="old_password" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">Mật khẩu mới</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Xác nhận mật khẩu</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>

                <button type="submit" class="btn-update">Đổi mật khẩu</button>
            </form>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Avatar upload functionality
        const uploadArea = document.getElementById('uploadArea');
        const avatarInput = document.getElementById('avatarInput');
        const previewContainer = document.getElementById('previewContainer');
        const previewImage = document.getElementById('previewImage');

        // Click to upload
        uploadArea.addEventListener('click', () => {
            avatarInput.click();
        });

        // Handle file selection
        avatarInput.addEventListener('change', handleFileSelect);

        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                avatarInput.files = files;
                handleFileSelect();
            }
        });

        function handleFileSelect() {
            const file = avatarInput.files[0];
            if (file) {
                // Validate file
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                const maxSize = 2 * 1024 * 1024; // 2MB

                if (!allowedTypes.includes(file.type)) {
                    alert('Chỉ hỗ trợ ảnh JPG, PNG, GIF, WebP');
                    avatarInput.value = '';
                    previewContainer.style.display = 'none';
                    return;
                }

                if (file.size > maxSize) {
                    alert('Kích thước ảnh tối đa 2MB');
                    avatarInput.value = '';
                    previewContainer.style.display = 'none';
                    return;
                }

                // Show preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewImage.src = e.target.result;
                    previewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>
