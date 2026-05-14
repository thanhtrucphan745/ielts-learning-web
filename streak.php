<?php

function streak_ensure_tables(mysqli $conn): void
{
    static $initialized = false;
    if ($initialized) {
        return;
    }

    $createSessionsSql = "
        CREATE TABLE IF NOT EXISTS study_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            skill VARCHAR(32) NOT NULL,
            activity_type VARCHAR(64) NOT NULL,
            score INT NULL,
            max_score INT NULL,
            band_score DECIMAL(3,1) NULL,
            duration_minutes INT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_study_sessions_user_created (user_id, created_at),
            CONSTRAINT fk_study_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    $createStreakSql = "
        CREATE TABLE IF NOT EXISTS user_streaks (
            user_id INT PRIMARY KEY,
            current_streak INT NOT NULL DEFAULT 0,
            best_streak INT NOT NULL DEFAULT 0,
            last_active_date DATE NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_user_streaks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    $conn->query($createSessionsSql);
    $conn->query($createStreakSql);
    $initialized = true;
}

function streak_mark_activity(
    mysqli $conn,
    int $userId,
    string $skill,
    string $activityType,
    ?int $score = null,
    ?int $maxScore = null,
    ?float $bandScore = null,
    ?int $durationMinutes = null
): void {
    if ($userId <= 0) {
        return;
    }

    streak_ensure_tables($conn);

    $insertSessionSql = "
        INSERT INTO study_sessions (user_id, skill, activity_type, score, max_score, band_score, duration_minutes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";

    $sessionStmt = $conn->prepare($insertSessionSql);
    if ($sessionStmt) {
        $sessionStmt->bind_param(
            'issiidi',
            $userId,
            $skill,
            $activityType,
            $score,
            $maxScore,
            $bandScore,
            $durationMinutes
        );
        $sessionStmt->execute();
        $sessionStmt->close();
    }

    $today = new DateTimeImmutable('today');
    $yesterday = $today->sub(new DateInterval('P1D'));
    $todayStr = $today->format('Y-m-d');
    $yesterdayStr = $yesterday->format('Y-m-d');

    $selectSql = "SELECT current_streak, best_streak, last_active_date FROM user_streaks WHERE user_id = ? LIMIT 1";
    $selectStmt = $conn->prepare($selectSql);
    if (!$selectStmt) {
        return;
    }

    $selectStmt->bind_param('i', $userId);
    $selectStmt->execute();
    $row = $selectStmt->get_result()?->fetch_assoc();
    $selectStmt->close();

    $current = 1;
    $best = 1;

    if ($row) {
        $current = (int) ($row['current_streak'] ?? 0);
        $best = (int) ($row['best_streak'] ?? 0);
        $lastActive = (string) ($row['last_active_date'] ?? '');

        if ($lastActive === $todayStr) {
            return;
        }

        if ($lastActive === $yesterdayStr) {
            $current += 1;
        } else {
            $current = 1;
        }

        if ($current > $best) {
            $best = $current;
        }

        $updateSql = "UPDATE user_streaks SET current_streak = ?, best_streak = ?, last_active_date = ? WHERE user_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        if ($updateStmt) {
            $updateStmt->bind_param('iisi', $current, $best, $todayStr, $userId);
            $updateStmt->execute();
            $updateStmt->close();
        }
        return;
    }

    $insertStreakSql = "INSERT INTO user_streaks (user_id, current_streak, best_streak, last_active_date) VALUES (?, 1, 1, ?)";
    $insertStreakStmt = $conn->prepare($insertStreakSql);
    if ($insertStreakStmt) {
        $insertStreakStmt->bind_param('is', $userId, $todayStr);
        $insertStreakStmt->execute();
        $insertStreakStmt->close();
    }
}

function streak_get_status(mysqli $conn, int $userId): array
{
    $status = [
        'currentStreak' => 0,
        'bestStreak' => 0,
        'lastActiveDate' => null,
        'completedToday' => false,
        'needsReminder' => true,
        'missedYesterday' => false,
    ];

    if ($userId <= 0) {
        return $status;
    }

    streak_ensure_tables($conn);

    $sql = "SELECT current_streak, best_streak, last_active_date FROM user_streaks WHERE user_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return $status;
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()?->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return $status;
    }

    $today = new DateTimeImmutable('today');
    $yesterday = $today->sub(new DateInterval('P1D'));
    $todayStr = $today->format('Y-m-d');
    $yesterdayStr = $yesterday->format('Y-m-d');

    $lastActive = (string) ($row['last_active_date'] ?? '');

    $status['currentStreak'] = (int) ($row['current_streak'] ?? 0);
    $status['bestStreak'] = (int) ($row['best_streak'] ?? 0);
    $status['lastActiveDate'] = $lastActive !== '' ? $lastActive : null;
    $status['completedToday'] = $lastActive === $todayStr;
    $status['needsReminder'] = $lastActive !== $todayStr;
    $status['missedYesterday'] = $lastActive !== '' && $lastActive < $yesterdayStr;

    return $status;
}
