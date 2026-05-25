<?php

function auth_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function auth_user(): ?array
{
    auth_start_session();
    return $_SESSION['auth_user'] ?? null;
}

function auth_ensure_reply_notification_columns(mysqli $conn): void
{
    $result = $conn->query("SHOW COLUMNS FROM contact_messages LIKE 'user_seen_at'");
    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE contact_messages ADD COLUMN user_seen_at DATETIME NULL AFTER replied_at");
    }
}

function auth_role_label(int $role): string
{
    if ($role === 1) {
        return 'Quản trị viên';
    }

    if ($role === 2) {
        return 'Học viên';
    }

    if ($role === 3) {
        return 'Giảng viên';
    }

    return 'Không xác định';
}

function auth_login(mysqli $conn, string $identifier, string $password): array
{
    $identifier = trim($identifier);

    if ($identifier === '' || $password === '') {
        return [
            'ok' => false,
            'message' => 'Please enter username/email and password.'
        ];
    }

    $sql = "SELECT id, name, email, username, password, role, phone, avatar FROM users WHERE username = ? OR email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [
            'ok' => false,
            'message' => 'Database error while preparing login query.'
        ];
    }

    $stmt->bind_param('ss', $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$user) {
        return [
            'ok' => false,
            'message' => 'Account not found.'
        ];
    }

    $storedPassword = (string) ($user['password'] ?? '');

    $valid = password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);
    if (!$valid) {
        return [
            'ok' => false,
            'message' => 'Incorrect password.'
        ];
    }

    $role = isset($user['role']) ? (int) $user['role'] : 0;
    if (!in_array($role, [1, 2, 3], true)) {
        return [
            'ok' => false,
            'message' => 'Your account has no valid role. Please contact admin.'
        ];
    }

    auth_start_session();
    $_SESSION['auth_user'] = [
        'id' => (int) $user['id'],
        'name' => (string) ($user['name'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'username' => (string) ($user['username'] ?? ''),
        'role' => $role,
        'phone' => (string) ($user['phone'] ?? ''),
        'avatar' => (string) ($user['avatar'] ?? '')
    ];

    return [
        'ok' => true,
        'message' => 'Login successful.'
    ];
}

function auth_require_login(): void
{
    if (!auth_user()) {
        header('Location: login.php');
        exit;
    }
}

function require_role($requiredRoles): void
{
    auth_require_login();

    $user = auth_user();
    $role = (int) ($user['role'] ?? 0);
    $allowedRoles = is_array($requiredRoles) ? $requiredRoles : [$requiredRoles];
    $allowedRoles = array_map('intval', $allowedRoles);

    if (!$user || !in_array($role, $allowedRoles, true)) {
        header('Location: login.php?error=access_denied');
        exit;
    }
}

function auth_require_role(int $requiredRole): void
{
    require_role($requiredRole);
}

function require_admin(): void
{
    require_role(1);
}

function require_student(): void
{
    require_role(2);
}

function require_teacher(): void
{
    require_role(3);
}

function auth_redirect_by_role(): void
{
    $user = auth_user();
    if (!$user) {
        header('Location: login.php');
        exit;
    }

    $role = (int) $user['role'];
    if ($role === 1) {
        header('Location: admin/index.php');
        exit;
    }

    if ($role === 2) {
        header('Location: dashboard.php');
        exit;
    }

    if ($role === 3) {
        header('Location: teacher/dashboard.php');
        exit;
    }

    header('Location: login.php');
    exit;
}

function auth_contact_reply_count(mysqli $conn, string $email): int
{
    auth_ensure_reply_notification_columns($conn);

    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM contact_messages WHERE email = ? AND reply_message IS NOT NULL AND user_seen_at IS NULL');
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : [];
    $stmt->close();

    return (int) ($row['total'] ?? 0);
}

function auth_contact_reply_items(mysqli $conn, string $email, int $limit = 5): array
{
    auth_ensure_reply_notification_columns($conn);

    $limit = max(1, min(20, $limit));
    $sql = 'SELECT id, subject, reply_message, replied_at, user_seen_at FROM contact_messages WHERE email = ? AND reply_message IS NOT NULL ORDER BY COALESCE(replied_at, created_at) DESC LIMIT ' . $limit;
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
    $stmt->close();

    return $items;
}

function auth_mark_contact_replies_seen(mysqli $conn, string $email): void
{
    auth_ensure_reply_notification_columns($conn);

    $stmt = $conn->prepare('UPDATE contact_messages SET user_seen_at = NOW() WHERE email = ? AND reply_message IS NOT NULL AND user_seen_at IS NULL');
    if (!$stmt) {
        return;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->close();
}
