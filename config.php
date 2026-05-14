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
?>