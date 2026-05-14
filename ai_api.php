<?php
// Server-side proxy to OpenAI Chat Completions
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

if (empty(OPENAI_API_KEY)) {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');

    if ($message === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Empty message']);
        exit;
    }

    $lowerMessage = function_exists('mb_strtolower') ? mb_strtolower($message, 'UTF-8') : strtolower($message);
    $reply = 'Hiện tại chat đang chạy ở chế độ demo vì chưa cấu hình OPENAI_API_KEY. ';

    if (strpos($lowerMessage, 'writing') !== false || strpos($lowerMessage, 'task 2') !== false) {
        $reply .= 'Với Writing, bạn nên ưu tiên 3 điểm: trả lời đúng yêu cầu đề, chia đoạn rõ ràng, và dùng câu ngắn chính xác trước khi thêm từ vựng nâng cao.';
    } elseif (strpos($lowerMessage, 'speaking') !== false) {
        $reply .= 'Với Speaking, hãy nói mạch lạc, mở rộng câu trả lời bằng ví dụ cá nhân, và luyện phát âm + ngắt nhịp tự nhiên.';
    } elseif (strpos($lowerMessage, 'reading') !== false) {
        $reply .= 'Với Reading, hãy luyện skimming/scanning, làm câu dễ trước, và kiểm soát thời gian cho từng passage.';
    } elseif (strpos($lowerMessage, 'listening') !== false) {
        $reply .= 'Với Listening, nên nghe ý chính, đọc trước câu hỏi, và tập trung từ khóa thay vì cố nghe từng từ.';
    } else {
        $reply .= 'Bạn có thể hỏi về Writing, Speaking, Reading hoặc lộ trình học theo band. Tôi sẽ trả lời ngay theo kinh nghiệm luyện IELTS.';
    }

    echo json_encode(['reply' => $reply, 'demo' => true]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');
$history = $input['history'] ?? []; // optional array of previous messages

if ($message === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Empty message']);
    exit;
}

// Build messages payload: system prompt + history + user message
$messages = [];
$messages[] = [
    'role' => 'system',
    'content' => "You are an expert IELTS coach. Answer in Vietnamese unless the user asks for English. Provide concise, actionable advice for Writing, Speaking, Reading and Listening. Keep replies clear and friendly. If the user asks for examples, include a short example."];

// Append history if provided (should be array of {role,content})
if (is_array($history)) {
    foreach ($history as $h) {
        if (isset($h['role']) && isset($h['content'])) {
            $messages[] = ['role' => $h['role'], 'content' => $h['content']];
        }
    }
}

$messages[] = ['role' => 'user', 'content' => $message];

$payload = [
    'model' => defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o-mini',
    'messages' => $messages,
    'temperature' => 0.2,
    'max_tokens' => 800,
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . OPENAI_API_KEY,
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$err = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Request error: ' . $err]);
    exit;
}

$result = json_decode($response, true);
if ($httpCode >= 400) {
    http_response_code($httpCode);
    echo json_encode(['error' => $result ?? $response]);
    exit;
}

// Extract assistant content
$assistantText = '';
if (isset($result['choices'][0]['message']['content'])) {
    $assistantText = $result['choices'][0]['message']['content'];
}

echo json_encode(['reply' => $assistantText, 'raw' => $result]);
