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
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        try {
            $exists = $conn->query($sql) === true;
        } catch (Throwable $e) {
            $exists = false;
        }

        return $exists;
    }
}
?>