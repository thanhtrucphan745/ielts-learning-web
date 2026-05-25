<?php
if (!function_exists('load_env_file')) {
    function load_env_file($path)
    {
        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (strpos($line, '=') === false) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if ($name === '') {
                continue;
            }

            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') || (substr($value, 0, 1) === '\'' && substr($value, -1) === '\'')) {
                $value = substr($value, 1, -1);
            }

            if (getenv($name) === false) {
                putenv($name . '=' . $value);
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

load_env_file(__DIR__ . '/.env');

$conn = new mysqli("localhost", "root", "", "ielts_web");

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

if (!mysqli_set_charset($conn, 'utf8mb4')) {
    die('Không thể thiết lập utf8mb4 cho kết nối MySQL.');
}

if (!defined('COZE_BOT_ID')) {
    define('COZE_BOT_ID', getenv('COZE_BOT_ID') ?: '');
}

if (!defined('COZE_WEBCHAT_TOKEN')) {
    define('COZE_WEBCHAT_TOKEN', getenv('COZE_WEBCHAT_TOKEN') ?: '');
}

if (!defined('OPENAI_API_KEY')) {
    define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');
}

if (!defined('OPENAI_MODEL')) {
    define('OPENAI_MODEL', getenv('OPENAI_MODEL') ?: 'gpt-4o-mini');
}

if (!function_exists('skill_uploads_column_exists')) {
    function skill_uploads_column_exists(mysqli $conn, string $columnName): bool
    {
        $columnName = $conn->real_escape_string($columnName);
        $sql = "SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'skill_uploads' AND column_name = '{$columnName}' LIMIT 1";
        $result = $conn->query($sql);

        return $result instanceof mysqli_result && $result->num_rows > 0;
    }
}

if (!function_exists('ensure_skill_uploads_table')) {
    function ensure_skill_uploads_table(mysqli $conn): bool
    {
        static $checked = false;
        static $exists = false;

        if ($checked) {
            return $exists;
        }

        $checked = true;
        $sql = "CREATE TABLE IF NOT EXISTS `skill_uploads` (
          `id` int NOT NULL AUTO_INCREMENT,
          `skill` varchar(50) NOT NULL,
          `title` varchar(255) DEFAULT NULL,
          `description` text,
          `filename` varchar(255) NOT NULL,
          `original_name` varchar(255) DEFAULT NULL,
          `mime` varchar(100) DEFAULT NULL,
          `size` int DEFAULT NULL,
          `uploaded_by` int DEFAULT NULL,
          `audio_filename` varchar(255) NULL,
          `audio_original_name` varchar(255) NULL,
          `audio_mime` varchar(100) NULL,
          `audio_size` int NULL,
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        try {
            $exists = $conn->query($sql) === true;
        } catch (Throwable $e) {
            $exists = false;
        }

        if ($exists) {
            $columnsToAdd = [
                'audio_filename' => 'ALTER TABLE skill_uploads ADD COLUMN audio_filename VARCHAR(255) NULL AFTER filename',
                'audio_original_name' => 'ALTER TABLE skill_uploads ADD COLUMN audio_original_name VARCHAR(255) NULL AFTER audio_filename',
                'audio_mime' => 'ALTER TABLE skill_uploads ADD COLUMN audio_mime VARCHAR(100) NULL AFTER audio_original_name',
                'audio_size' => 'ALTER TABLE skill_uploads ADD COLUMN audio_size INT NULL AFTER audio_mime',
            ];

            foreach ($columnsToAdd as $column => $alterSql) {
                if (!skill_uploads_column_exists($conn, $column)) {
                    try {
                        $conn->query($alterSql);
                    } catch (Throwable $e) {
                        // ignore duplicate column or alter failures
                    }
                }
            }
        }

        return $exists;
    }
}

if (!function_exists('writing_submissions_column_exists')) {
    function writing_submissions_column_exists(mysqli $conn, string $columnName): bool
    {
        $columnName = $conn->real_escape_string($columnName);
        $sql = "SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'writing_submissions' AND column_name = '{$columnName}' LIMIT 1";
        $result = $conn->query($sql);

        return $result instanceof mysqli_result && $result->num_rows > 0;
    }
}

if (!function_exists('ensure_writing_submissions_table')) {
    function ensure_writing_submissions_table(mysqli $conn): bool
    {
        static $checked = false;
        static $exists = false;

        if ($checked) {
            return $exists;
        }

        $checked = true;
        $sql = "CREATE TABLE IF NOT EXISTS `writing_submissions` (
          `id` int NOT NULL AUTO_INCREMENT,
          `student_id` int NOT NULL,
          `test_id` int NOT NULL,
          `answer_text` text NOT NULL,
          `word_count` int NOT NULL DEFAULT 0,
          `score` int DEFAULT NULL,
          `feedback` text DEFAULT NULL,
          `status` varchar(50) NOT NULL DEFAULT 'submitted',
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          `graded_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `student_id_idx` (`student_id`),
          KEY `test_id_idx` (`test_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        try {
            $exists = $conn->query($sql) === true;
        } catch (Throwable $e) {
            $exists = false;
        }

        if ($exists) {
            $columnsToAdd = [
                'word_count' => 'ALTER TABLE writing_submissions ADD COLUMN word_count int NOT NULL DEFAULT 0 AFTER answer_text',
                'score' => 'ALTER TABLE writing_submissions ADD COLUMN score int DEFAULT NULL AFTER word_count',
                'feedback' => 'ALTER TABLE writing_submissions ADD COLUMN feedback text DEFAULT NULL AFTER score',
                'status' => 'ALTER TABLE writing_submissions ADD COLUMN status varchar(50) NOT NULL DEFAULT \'submitted\' AFTER feedback',
                'created_at' => 'ALTER TABLE writing_submissions ADD COLUMN created_at datetime DEFAULT CURRENT_TIMESTAMP AFTER status',
                'graded_at' => 'ALTER TABLE writing_submissions ADD COLUMN graded_at datetime DEFAULT NULL AFTER created_at',
            ];

            foreach ($columnsToAdd as $column => $alterSql) {
                if (!writing_submissions_column_exists($conn, $column)) {
                    try {
                        $conn->query($alterSql);
                    } catch (Throwable $e) {
                        // ignore duplicate column or alter failures
                    }
                }
            }
        }

        return $exists;
    }
}

if (!function_exists('speaking_submissions_column_exists')) {
    function speaking_submissions_column_exists(mysqli $conn, string $columnName): bool
    {
        $columnName = $conn->real_escape_string($columnName);
        $sql = "SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'speaking_submissions' AND column_name = '{$columnName}' LIMIT 1";
        $result = $conn->query($sql);

        return $result instanceof mysqli_result && $result->num_rows > 0;
    }
}

if (!function_exists('ensure_speaking_submissions_table')) {
    function ensure_speaking_submissions_table(mysqli $conn): bool
    {
        static $checked = false;
        static $exists = false;

        if ($checked) {
            return $exists;
        }

        $checked = true;
        $sql = "CREATE TABLE IF NOT EXISTS `speaking_submissions` (
          `id` int NOT NULL AUTO_INCREMENT,
          `student_id` int NOT NULL,
          `test_id` int NOT NULL,
          `answer_text` text,
          `audio_filename` varchar(255) DEFAULT NULL,
          `audio_original_name` varchar(255) DEFAULT NULL,
          `audio_mime` varchar(100) DEFAULT NULL,
          `audio_size` int DEFAULT NULL,
          `score` int DEFAULT NULL,
          `feedback` text DEFAULT NULL,
          `status` varchar(50) NOT NULL DEFAULT 'submitted',
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          `graded_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `student_id_idx` (`student_id`),
          KEY `test_id_idx` (`test_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        try {
            $exists = $conn->query($sql) === true;
        } catch (Throwable $e) {
            $exists = false;
        }

        if ($exists) {
            $columnsToAdd = [
                'audio_filename' => 'ALTER TABLE speaking_submissions ADD COLUMN audio_filename VARCHAR(255) DEFAULT NULL AFTER answer_text',
                'audio_original_name' => 'ALTER TABLE speaking_submissions ADD COLUMN audio_original_name VARCHAR(255) DEFAULT NULL AFTER audio_filename',
                'audio_mime' => 'ALTER TABLE speaking_submissions ADD COLUMN audio_mime VARCHAR(100) DEFAULT NULL AFTER audio_original_name',
                'audio_size' => 'ALTER TABLE speaking_submissions ADD COLUMN audio_size INT DEFAULT NULL AFTER audio_mime',
                'score' => 'ALTER TABLE speaking_submissions ADD COLUMN score INT DEFAULT NULL AFTER audio_size',
                'feedback' => 'ALTER TABLE speaking_submissions ADD COLUMN feedback text DEFAULT NULL AFTER score',
                'status' => 'ALTER TABLE speaking_submissions ADD COLUMN status varchar(50) NOT NULL DEFAULT \'submitted\' AFTER feedback',
                'created_at' => 'ALTER TABLE speaking_submissions ADD COLUMN created_at datetime DEFAULT CURRENT_TIMESTAMP AFTER status',
                'graded_at' => 'ALTER TABLE speaking_submissions ADD COLUMN graded_at datetime DEFAULT NULL AFTER created_at',
            ];

            foreach ($columnsToAdd as $column => $alterSql) {
                if (!speaking_submissions_column_exists($conn, $column)) {
                    try {
                        $conn->query($alterSql);
                    } catch (Throwable $e) {
                        // ignore duplicate column or alter failures
                    }
                }
            }
        }

        return $exists;
    }
}

if (!function_exists('test_attempts_column_exists')) {
    function test_attempts_column_exists(mysqli $conn, string $columnName): bool
    {
        $columnName = $conn->real_escape_string($columnName);
        $sql = "SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'test_attempts' AND column_name = '{$columnName}' LIMIT 1";
        $result = $conn->query($sql);

        return $result instanceof mysqli_result && $result->num_rows > 0;
    }
}

if (!function_exists('test_attempt_answers_column_exists')) {
    function test_attempt_answers_column_exists(mysqli $conn, string $columnName): bool
    {
        $columnName = $conn->real_escape_string($columnName);
        $sql = "SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'test_attempt_answers' AND column_name = '{$columnName}' LIMIT 1";
        $result = $conn->query($sql);

        return $result instanceof mysqli_result && $result->num_rows > 0;
    }
}

if (!function_exists('ensure_test_attempts_table')) {
    function ensure_test_attempts_table(mysqli $conn): bool
    {
        static $checked = false;
        static $exists = false;

        if ($checked) {
            return $exists;
        }

        $checked = true;
        $sql = "CREATE TABLE IF NOT EXISTS `test_attempts` (
          `id` int NOT NULL AUTO_INCREMENT,
          `student_id` int NOT NULL,
          `skill` varchar(50) NOT NULL,
          `test_id` int NOT NULL,
          `test_title` varchar(255) NOT NULL,
          `score` int NOT NULL DEFAULT 0,
          `total_questions` int NOT NULL DEFAULT 0,
          `band_score` decimal(4,2) DEFAULT NULL,
          `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `student_id_idx` (`student_id`),
          KEY `skill_idx` (`skill`),
          KEY `test_id_idx` (`test_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        try {
            $exists = $conn->query($sql) === true;
        } catch (Throwable $e) {
            $exists = false;
        }

        if ($exists) {
            $columnsToAdd = [
                'id' => 'ALTER TABLE test_attempts MODIFY COLUMN id int NOT NULL AUTO_INCREMENT',
                'student_id' => 'ALTER TABLE test_attempts ADD COLUMN student_id int NOT NULL AFTER id',
                'skill' => 'ALTER TABLE test_attempts ADD COLUMN skill varchar(50) NOT NULL AFTER student_id',
                'test_id' => 'ALTER TABLE test_attempts ADD COLUMN test_id int NOT NULL AFTER skill',
                'test_title' => 'ALTER TABLE test_attempts ADD COLUMN test_title varchar(255) NOT NULL AFTER test_id',
                'score' => 'ALTER TABLE test_attempts ADD COLUMN score int NOT NULL DEFAULT 0 AFTER test_title',
                'total_questions' => 'ALTER TABLE test_attempts ADD COLUMN total_questions int NOT NULL DEFAULT 0 AFTER score',
                'band_score' => 'ALTER TABLE test_attempts ADD COLUMN band_score decimal(4,2) DEFAULT NULL AFTER total_questions',
                'submitted_at' => 'ALTER TABLE test_attempts ADD COLUMN submitted_at datetime DEFAULT CURRENT_TIMESTAMP AFTER band_score',
            ];

            foreach ($columnsToAdd as $column => $alterSql) {
                if (!test_attempts_column_exists($conn, $column)) {
                    try {
                        $conn->query($alterSql);
                    } catch (Throwable $e) {
                        // ignore duplicate column or alter failures
                    }
                }
            }
        }

        return $exists;
    }
}

if (!function_exists('ensure_test_attempt_answers_table')) {
    function ensure_test_attempt_answers_table(mysqli $conn): bool
    {
        static $checked = false;
        static $exists = false;

        if ($checked) {
            return $exists;
        }

        $checked = true;
        $sql = "CREATE TABLE IF NOT EXISTS `test_attempt_answers` (
          `id` int NOT NULL AUTO_INCREMENT,
          `attempt_id` int NOT NULL,
          `question_index` int NOT NULL,
          `question_text` text NOT NULL,
          `selected_answer` int DEFAULT NULL,
          `correct_answer` int NOT NULL,
          `selected_text` text DEFAULT NULL,
          `correct_text` text DEFAULT NULL,
          `is_correct` tinyint(1) NOT NULL DEFAULT 0,
          `explanation` text DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `attempt_id_idx` (`attempt_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        try {
            $exists = $conn->query($sql) === true;
        } catch (Throwable $e) {
            $exists = false;
        }

        if ($exists) {
            $columnsToAdd = [
                'id' => 'ALTER TABLE test_attempt_answers MODIFY COLUMN id int NOT NULL AUTO_INCREMENT',
                'attempt_id' => 'ALTER TABLE test_attempt_answers ADD COLUMN attempt_id int NOT NULL AFTER id',
                'question_index' => 'ALTER TABLE test_attempt_answers ADD COLUMN question_index int NOT NULL AFTER attempt_id',
                'question_text' => 'ALTER TABLE test_attempt_answers ADD COLUMN question_text text NOT NULL AFTER question_index',
                'selected_answer' => 'ALTER TABLE test_attempt_answers ADD COLUMN selected_answer int DEFAULT NULL AFTER question_text',
                'correct_answer' => 'ALTER TABLE test_attempt_answers ADD COLUMN correct_answer int NOT NULL AFTER selected_answer',
                'selected_text' => 'ALTER TABLE test_attempt_answers ADD COLUMN selected_text text DEFAULT NULL AFTER correct_answer',
                'correct_text' => 'ALTER TABLE test_attempt_answers ADD COLUMN correct_text text DEFAULT NULL AFTER selected_text',
                'is_correct' => 'ALTER TABLE test_attempt_answers ADD COLUMN is_correct tinyint(1) NOT NULL DEFAULT 0 AFTER correct_text',
                'explanation' => 'ALTER TABLE test_attempt_answers ADD COLUMN explanation text DEFAULT NULL AFTER is_correct',
            ];

            foreach ($columnsToAdd as $column => $alterSql) {
                if (!test_attempt_answers_column_exists($conn, $column)) {
                    try {
                        $conn->query($alterSql);
                    } catch (Throwable $e) {
                        // ignore duplicate column or alter failures
                    }
                }
            }
        }

        return $exists;
    }
}

if (!function_exists('ensure_test_attempt_tables')) {
    function ensure_test_attempt_tables(mysqli $conn): bool
    {
        return ensure_test_attempts_table($conn) && ensure_test_attempt_answers_table($conn);
    }
}

?>