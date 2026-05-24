<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

auth_start_session();
$currentUser = auth_user();
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$basePath = $basePath === '' ? '/' : $basePath . '/';
$displayName = $currentUser['name'] ?? ($currentUser['username'] ?? 'User');
$avatarText = strtoupper(mb_substr($displayName, 0, 1, 'UTF-8'));
$avatarPath = !empty($currentUser['avatar']) ? $currentUser['avatar'] : '';
$avatarUrl = $avatarPath !== '' ? $avatarPath : '';
$role = isset($currentUser['role']) ? (int) $currentUser['role'] : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>AI Chat - IELTS Learning</title>
    <base href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <link href="img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700;800&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .chat-hero {
            background: radial-gradient(circle at top left, rgba(13, 110, 253, .18), transparent 38%), linear-gradient(135deg, #0f172a 0%, #111827 55%, #1d4ed8 100%);
            color: #fff;
            border-radius: 28px;
            overflow: hidden;
            position: relative;
        }
        .chat-hero::after {
            content: '';
            position: absolute;
            inset: auto -80px -80px auto;
            width: 220px;
            height: 220px;
            background: rgba(255,255,255,.08);
            border-radius: 50%;
        }
        .chat-panel {
            background: rgba(255,255,255,.96);
            border: 1px solid rgba(255,255,255,.5);
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, .16);
            min-height: 540px;
        }
        .prompt-chip {
            border: 1px solid #dbeafe;
            background: #eff6ff;
            color: #0f172a;
            border-radius: 999px;
            padding: .55rem .9rem;
            margin: 0 .5rem .5rem 0;
            display: inline-block;
            font-size: .92rem;
        }
        .chat-note {
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 18px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/nav.php'; ?>
    <div class="container py-5">
        <div class="chat-hero p-4 p-lg-5 mb-4 wow fadeInUp" data-wow-delay="0.1s">
            <div class="row align-items-center g-4">
                <div class="col-lg-7 position-relative">
                    <span class="badge bg-light text-primary mb-3 px-3 py-2">Trợ lý AI Chat</span>
                    <h1 class="display-5 fw-bold mb-3">Hỏi bài IELTS ngay trong website</h1>
                    <p class="lead mb-4" style="max-width: 680px;">
                        Dùng chatbox AI để hỏi về Writing, Speaking, Reading, Listening hoặc chọn band phù hợp.
                        Nhấn nút chat ở góc dưới để bắt đầu trò chuyện.
                    </p>
                    <div class="chat-note p-3 p-md-4 mb-3">
                        <div class="fw-semibold mb-2">Gợi ý câu hỏi:</div>
                        <span class="prompt-chip">Cách tăng band Writing Task 2?</span>
                        <span class="prompt-chip">Nên học gì cho band 4.5?</span>
                        <span class="prompt-chip">Mẹo Speaking part 2 là gì?</span>
                        <span class="prompt-chip">Lộ trình học 3 tháng?</span>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="chat-panel p-4 p-lg-5">
                        <h4 class="mb-3 text-dark">Cửa sổ chat</h4>
                        <p class="text-muted mb-4">Một widget AI sẽ tự mở sau khi tải trang. Nếu chưa thấy, hãy bật pop-up của trình duyệt.</p>
                        <div class="border rounded-3 p-4 bg-light">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <div>
                                    <div class="fw-bold">IELTS AI Coach</div>
                                    <small class="text-success">Sẵn sàng trả lời</small>
                                </div>
                            </div>
                            <p class="mb-3 text-secondary">Bấm vào bong bóng chat để bắt đầu hỏi. Bạn có thể hỏi ngắn hoặc dài, AI sẽ gợi ý hướng học phù hợp.</p>

                            <!-- Chat UI -->
                            <div id="chatWidget" class="d-flex flex-column" style="height:320px;">
                                <div id="chatMessages" class="flex-grow-1 overflow-auto p-2 mb-2 border rounded" style="background:#fff;"></div>
                                <div class="d-flex gap-2">
                                    <input id="chatInput" type="text" class="form-control" placeholder="Gõ câu hỏi của bạn..." />
                                    <button id="chatSend" class="btn btn-primary">Gửi</button>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">Lưu ý: Cần cấu hình `OPENAI_API_KEY` để chat hoạt động.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- OpenAI chat client (simple) -->
    <script>
        (function () {
            var chatContainer = document.getElementById('chatMessages');
            var inputEl = document.getElementById('chatInput');
            var sendBtn = document.getElementById('chatSend');

            function appendMessage(role, text) {
                var el = document.createElement('div');
                el.className = 'mb-3';
                if (role === 'user') {
                    el.innerHTML = '<div class="text-end"><div class="d-inline-block p-2 bg-primary text-white rounded">' + escapeHtml(text) + '</div></div>';
                } else {
                    el.innerHTML = '<div class="d-inline-block p-2 bg-light text-dark rounded">' + escapeHtml(text) + '</div>';
                }
                chatContainer.appendChild(el);
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }

            function escapeHtml(s) {
                return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }

            function setLoading(state) {
                sendBtn.disabled = state;
                if (state) sendBtn.innerText = 'Đang gửi...'; else sendBtn.innerText = 'Gửi';
            }

            async function sendMessage() {
                var msg = inputEl.value.trim();
                if (!msg) return;
                appendMessage('user', msg);
                inputEl.value = '';
                setLoading(true);

                try {
                    var resp = await fetch('ai_api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ message: msg })
                    });
                    var data = await resp.json();
                    if (data.error) {
                        appendMessage('assistant', 'Lỗi: ' + (data.error.message || data.error));
                    } else {
                        appendMessage('assistant', data.reply || '[Không có phản hồi]');
                    }
                } catch (err) {
                    appendMessage('assistant', 'Lỗi kết nối: ' + err.message);
                } finally {
                    setLoading(false);
                }
            }

            sendBtn.addEventListener('click', sendMessage);
            inputEl.addEventListener('keypress', function (e) { if (e.key === 'Enter') { e.preventDefault(); sendMessage(); } });

            // Attach click handlers to suggestion chips on this page
            document.querySelectorAll('.prompt-chip').forEach(function (chip) {
                chip.style.cursor = 'pointer';
                chip.addEventListener('click', function () {
                    var txt = chip.textContent.trim();
                    inputEl.value = txt;
                    sendMessage();
                });
            });

            // If page opened with ?q=..., prefill and auto-send
            try {
                var params = new URLSearchParams(window.location.search);
                var q = params.get('q');
                if (q) {
                    inputEl.value = decodeURIComponent(q);
                    // small delay to ensure UI ready
                    setTimeout(function () { sendMessage(); }, 200);
                }
            } catch (e) { /* ignore URL errors */ }
        })();
    </script>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>