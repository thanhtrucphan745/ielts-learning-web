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

function auth_role_label(int $role): string
{
    if ($role === 1) {
        return 'Admin';
    }

    if ($role === 2) {
        return 'Student';
    }

    return 'Unknown';
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
    if (!in_array($role, [1, 2], true)) {
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

function auth_require_role(int $requiredRole): void
{
    auth_require_login();

    $user = auth_user();
    if (!$user || (int) $user['role'] !== $requiredRole) {
        header('Location: login.php?error=access_denied');
        exit;
    }
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
        header('Location: student.php');
        exit;
    }

    header('Location: login.php');
    exit;
}
