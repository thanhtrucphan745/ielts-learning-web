<?php
require_once __DIR__ . '/auth.php';

auth_start_session();
$currentUser = auth_user();
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$basePath = $basePath === '' ? '/' : $basePath . '/';
$displayName = $currentUser['name'] ?? ($currentUser['username'] ?? 'User');
$avatarText = strtoupper(mb_substr($displayName, 0, 1, 'UTF-8'));
$avatarPath = !empty($currentUser['avatar']) ? $currentUser['avatar'] : '';
$avatarUrl = $avatarPath !== '' ? $avatarPath : '';
$role = isset($currentUser['role']) ? (int) $currentUser['role'] : 0;

$flashcards = [
    ['band' => '0-3.5', 'skill' => 'reading', 'front' => 'Skim', 'back' => 'Read quickly to understand the general idea of a passage without focusing on every detail.', 'vn' => 'Đọc lướt để nắm ý chính của bài mà không cần tập trung vào từng chi tiết.', 'tag' => 'Reading Strategy'],
    ['band' => '0-3.5', 'skill' => 'writing', 'front' => 'Simple Sentence', 'back' => 'A short sentence with one clear idea.', 'vn' => 'Câu đơn giản là một câu ngắn chỉ chứa một ý rõ ràng.', 'tag' => 'Writing Basics'],
    ['band' => '0-3.5', 'skill' => 'listening', 'front' => 'Slow Audio', 'back' => 'Audio spoken slowly and clearly to help beginners understand.', 'vn' => 'Âm thanh được nói chậm và rõ để người mới bắt đầu dễ hiểu hơn.', 'tag' => 'Listening Basics'],
    ['band' => '0-3.5', 'skill' => 'speaking', 'front' => 'Self-Introduction', 'back' => 'Introduce yourself using very simple words and basic grammar.', 'vn' => 'Giới thiệu bản thân bằng từ vựng đơn giản và ngữ pháp cơ bản.', 'tag' => 'Speaking Basics'],

    ['band' => '3.5-4.5', 'skill' => 'reading', 'front' => 'Scan', 'back' => 'Search quickly for a specific name, number, or detail in the text.', 'vn' => 'Đọc quét để tìm nhanh một tên riêng, con số hoặc chi tiết cụ thể trong bài.', 'tag' => 'Reading Strategy'],
    ['band' => '3.5-4.5', 'skill' => 'writing', 'front' => 'Topic Sentence', 'back' => 'The first sentence of a paragraph that introduces the main point.', 'vn' => 'Câu chủ đề là câu đầu đoạn giới thiệu ý chính của đoạn văn.', 'tag' => 'Writing Structure'],
    ['band' => '3.5-4.5', 'skill' => 'listening', 'front' => 'Signposting', 'back' => 'Words and phrases that guide listeners through the structure of a talk or conversation.', 'vn' => 'Các từ và cụm từ chỉ dẫn giúp người nghe theo dõi cấu trúc của bài nói hoặc hội thoại.', 'tag' => 'Listening Skill'],
    ['band' => '3.5-4.5', 'skill' => 'speaking', 'front' => 'Longer Sentences', 'back' => 'Use more than one clause to explain your idea in a fuller way.', 'vn' => 'Dùng câu dài hơn với nhiều mệnh đề để diễn đạt ý đầy đủ hơn.', 'tag' => 'Speaking Development'],

    ['band' => '4.5-5.5', 'skill' => 'reading', 'front' => 'Keywords', 'back' => 'Important words in a question that help you locate the correct answer in the passage.', 'vn' => 'Từ khóa quan trọng trong câu hỏi giúp bạn tìm ra đáp án đúng trong bài đọc.', 'tag' => 'Reading Strategy'],
    ['band' => '4.5-5.5', 'skill' => 'writing', 'front' => 'Coherence', 'back' => 'How clearly your ideas connect so the reader can follow your writing easily.', 'vn' => 'Sự mạch lạc giúp các ý liên kết rõ ràng để người đọc dễ theo dõi.', 'tag' => 'Writing Structure'],
    ['band' => '4.5-5.5', 'skill' => 'listening', 'front' => 'Distractor', 'back' => 'An answer choice that sounds correct but is not the right answer.', 'vn' => 'Đáp án gây nhiễu là lựa chọn nghe có vẻ đúng nhưng không phải đáp án chính xác.', 'tag' => 'Listening Skill'],
    ['band' => '4.5-5.5', 'skill' => 'speaking', 'front' => 'Linking Words', 'back' => 'Words such as because, however, and therefore help connect your ideas.', 'vn' => 'Các từ nối như because, however, therefore giúp liên kết ý rõ ràng hơn.', 'tag' => 'Speaking Development'],

    ['band' => '5.5-6.0', 'skill' => 'reading', 'front' => 'Inference', 'back' => 'Understanding meaning that is implied rather than directly stated.', 'vn' => 'Suy luận là hiểu ý ngầm chứ không chỉ ý được nói trực tiếp.', 'tag' => 'Reading Higher Level'],
    ['band' => '5.5-6.0', 'skill' => 'writing', 'front' => 'Thesis Statement', 'back' => 'One clear sentence that states the main idea or opinion of your essay.', 'vn' => 'Câu luận điểm rõ ràng nêu ý chính hoặc quan điểm của bài viết.', 'tag' => 'Writing Structure'],
    ['band' => '5.5-6.0', 'skill' => 'listening', 'front' => 'Prediction', 'back' => 'Guess what kind of answer you expect before you hear the audio.', 'vn' => 'Dự đoán loại câu trả lời bạn sẽ nghe trước khi bắt đầu nghe audio.', 'tag' => 'Listening Skill'],
    ['band' => '5.5-6.0', 'skill' => 'speaking', 'front' => 'Lexical Resource', 'back' => 'Your range of vocabulary and how accurately you use it in conversation.', 'vn' => 'Vốn từ vựng và mức độ chính xác khi bạn sử dụng từ trong giao tiếp.', 'tag' => 'Speaking Skill'],
];

$bands = [
    'all' => 'All Bands',
    '0-3.5' => 'Band 0 - 3.5',
    '3.5-4.5' => 'Band 3.5 - 4.5',
    '4.5-5.5' => 'Band 4.5 - 5.5',
    '5.5-6.0' => 'Band 5.5 - 6.0',
];

$skills = [
    'all' => 'All Cards',
    'reading' => 'Reading',
    'writing' => 'Writing',
    'listening' => 'Listening',
    'speaking' => 'Speaking',
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Flashcards - eLEARNING</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700;800&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        body {
            background:
                radial-gradient(circle at top left, rgba(13, 110, 253, 0.12), transparent 28%),
                radial-gradient(circle at bottom right, rgba(32, 201, 151, 0.12), transparent 24%),
                #f8fbff;
        }

        .flashcard-shell {
            perspective: 1200px;
            min-height: 360px;
        }

        .flashcard-inner {
            position: relative;
            width: 100%;
            min-height: 360px;
            transform-style: preserve-3d;
            transition: transform 0.6s ease;
        }

        .flashcard-inner.is-flipped {
            transform: rotateY(180deg);
        }

        .flashcard-face {
            position: absolute;
            inset: 0;
            backface-visibility: hidden;
            border-radius: 1.5rem;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: 0 1rem 3rem rgba(15, 23, 42, 0.12);
        }

        .flashcard-back {
            transform: rotateY(180deg);
        }

        .filter-pill {
            border: 1px solid rgba(13, 110, 253, 0.15);
            background: #fff;
            color: #0d6efd;
            border-radius: 999px;
            padding: 0.7rem 1.2rem;
            font-weight: 700;
            transition: all 0.2s ease;
        }

        .filter-pill.active,
        .filter-pill:hover {
            background: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }

        .skill-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 999px;
            padding: 0.45rem 0.8rem;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .meaning-block {
            border-left: 4px solid rgba(255, 255, 255, 0.55);
            padding-left: 1rem;
        }

        .meaning-label {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            opacity: 0.85;
            margin-bottom: 0.35rem;
        }

        .meaning-text {
            font-size: 1.05rem;
            line-height: 1.7;
            margin-bottom: 0;
        }

        .flashcard-meta {
            font-size: 0.95rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>

    <?php include __DIR__ . '/nav.php'; ?>

    <div class="container-fluid bg-primary py-5 mb-5 page-header">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <h1 class="display-3 text-white animated slideInDown">Flashcards</h1>
                    <p class="text-white mb-0 fs-5">Quick revision cards for IELTS Reading, Writing, Listening, and Speaking.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container-xxl py-5">
        <div class="container">
            <div class="row justify-content-center mb-4">
                <div class="col-lg-10 text-center wow fadeInUp" data-wow-delay="0.1s">
                    <h6 class="section-title bg-white text-center text-primary px-3">Study Smarter</h6>
                    <h2 class="mb-3">Learn key IELTS ideas with flashcards</h2>
                    <p class="text-muted mb-0">Tap a card to flip it, or use the buttons to move through the deck. Each card is grouped by skill so you can revise the part you need most.</p>
                </div>
            </div>

            <div class="row justify-content-center mb-4">
                <div class="col-lg-10 d-flex flex-wrap justify-content-center gap-2">
                    <?php foreach ($bands as $key => $label): ?>
                        <button type="button" class="filter-pill<?php echo $key === 'all' ? ' active' : ''; ?>" data-band-filter="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="row justify-content-center mb-4">
                <div class="col-lg-10 d-flex flex-wrap justify-content-center gap-2">
                    <?php foreach ($skills as $key => $label): ?>
                        <button type="button" class="filter-pill<?php echo $key === 'all' ? ' active' : ''; ?>" data-skill-filter="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">
                    <div class="flashcard-shell wow fadeInUp" data-wow-delay="0.2s">
                        <div class="flashcard-inner" id="flashcardInner">
                            <div class="flashcard-face flashcard-front bg-white p-5">
                                <div class="d-flex justify-content-between align-items-start mb-4">
                                    <span class="skill-badge bg-primary text-white" id="cardTag">Reading Strategy</span>
                                    <span class="text-muted fw-semibold" id="cardCounter">1 / 12</span>
                                </div>
                                <h2 class="display-6 fw-bold mb-3" id="cardFront">Skim</h2>
                                <p class="lead text-secondary mb-4">Read quickly to understand the general idea of a passage without focusing on every detail.</p>
                                <div class="mt-auto d-flex align-items-center justify-content-between">
                                    <span class="flashcard-meta text-muted"><i class="fa fa-sync-alt me-2"></i>Click the card to flip</span>
                                    <button type="button" class="btn btn-outline-primary" id="flipButton">Flip Card</button>
                                </div>
                            </div>
                            <div class="flashcard-face flashcard-back bg-primary text-white p-5">
                                <div class="d-flex justify-content-between align-items-start mb-4">
                                    <span class="skill-badge bg-white text-primary" id="cardTagBack">Reading Strategy</span>
                                    <span class="text-white-50 fw-semibold" id="cardCounterBack">1 / 12</span>
                                </div>
                                <h2 class="display-6 fw-bold mb-3" id="cardFrontBack">Skim</h2>
                                <div class="meaning-block mb-3">
                                    <span class="meaning-label">English Meaning</span>
                                    <p class="meaning-text" id="cardBack">Read quickly to understand the general idea of a passage without focusing on every detail.</p>
                                </div>
                                <div class="meaning-block mb-4">
                                    <span class="meaning-label">Tiếng Việt</span>
                                    <p class="meaning-text" id="cardVietnamese">Đọc lướt để nắm ý chính của bài mà không cần tập trung vào từng chi tiết.</p>
                                </div>
                                <div class="mt-auto d-flex align-items-center justify-content-between">
                                    <span class="flashcard-meta text-white-50"><i class="fa fa-lightbulb me-2"></i>Use this in your revision notes</span>
                                    <button type="button" class="btn btn-light text-primary" id="flipButtonBack">Flip Back</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                        <button type="button" class="btn btn-primary px-4" id="prevCard"><i class="fa fa-arrow-left me-2"></i>Previous</button>
                        <button type="button" class="btn btn-outline-primary px-4" id="shuffleCard"><i class="fa fa-random me-2"></i>Shuffle</button>
                        <button type="button" class="btn btn-primary px-4" id="nextCard">Next<i class="fa fa-arrow-right ms-2"></i></button>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-3">
                <div class="col-md-6 col-lg-3">
                    <div class="bg-white rounded-4 shadow-sm h-100 p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;">
                                <i class="fa fa-book"></i>
                            </div>
                            <h5 class="mb-0">Reading</h5>
                        </div>
                        <p class="text-muted mb-0">Flashcards về skim, scan, keyword với nghĩa tiếng Việt để ôn nhanh và nhớ lâu.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="bg-white rounded-4 shadow-sm h-100 p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;">
                                <i class="fa fa-pen"></i>
                            </div>
                            <h5 class="mb-0">Writing</h5>
                        </div>
                        <p class="text-muted mb-0">Ôn structure, coherence và thesis statement bằng thẻ song ngữ dễ hiểu.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="bg-white rounded-4 shadow-sm h-100 p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;">
                                <i class="fa fa-headphones"></i>
                            </div>
                            <h5 class="mb-0">Listening</h5>
                        </div>
                        <p class="text-muted mb-0">Luyện prediction, distractor và signposting kèm nghĩa tiếng Việt rõ ràng.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="bg-white rounded-4 shadow-sm h-100 p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;">
                                <i class="fa fa-microphone"></i>
                            </div>
                            <h5 class="mb-0">Speaking</h5>
                        </div>
                        <p class="text-muted mb-0">Ôn fluency, pronunciation và lexical resource bằng flashcard song ngữ.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        const flashcards = <?php echo json_encode($flashcards, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const state = {
            band: 'all',
            skill: 'all',
            index: 0,
            flipped: false,
            currentList: flashcards
        };

        const flashcardInner = document.getElementById('flashcardInner');
        const cardTag = document.getElementById('cardTag');
        const cardTagBack = document.getElementById('cardTagBack');
        const cardCounter = document.getElementById('cardCounter');
        const cardCounterBack = document.getElementById('cardCounterBack');
        const cardFront = document.getElementById('cardFront');
        const cardFrontBack = document.getElementById('cardFrontBack');
        const cardBack = document.getElementById('cardBack');
        const cardVietnamese = document.getElementById('cardVietnamese');
        const bandButtons = document.querySelectorAll('[data-band-filter]');
        const skillButtons = document.querySelectorAll('[data-skill-filter]');

        function filteredList() {
            return flashcards.filter((item) => {
                const bandMatches = state.band === 'all' || item.band === state.band;
                const skillMatches = state.skill === 'all' || item.skill === state.skill;
                return bandMatches && skillMatches;
            });
        }

        function applyCard() {
            state.currentList = filteredList();
            if (state.currentList.length === 0) {
                return;
            }
            if (state.index >= state.currentList.length) {
                state.index = 0;
            }

            const item = state.currentList[state.index];
            cardTag.textContent = item.tag;
            cardTagBack.textContent = item.tag;
            cardCounter.textContent = `${state.index + 1} / ${state.currentList.length} • ${item.band}`;
            cardCounterBack.textContent = `${state.index + 1} / ${state.currentList.length} • ${item.band}`;
            cardFront.textContent = item.front;
            cardFrontBack.textContent = item.front;
            cardBack.textContent = item.back;
            cardVietnamese.textContent = item.vn;
            flashcardInner.classList.toggle('is-flipped', state.flipped);
        }

        function setBandFilter(filter) {
            state.band = filter;
            state.index = 0;
            state.flipped = false;
            bandButtons.forEach((button) => {
                button.classList.toggle('active', button.dataset.bandFilter === filter);
            });
            applyCard();
        }

        function setSkillFilter(filter) {
            state.skill = filter;
            state.index = 0;
            state.flipped = false;
            skillButtons.forEach((button) => {
                button.classList.toggle('active', button.dataset.skillFilter === filter);
            });
            applyCard();
        }

        document.getElementById('flipButton').addEventListener('click', () => {
            state.flipped = !state.flipped;
            flashcardInner.classList.toggle('is-flipped', state.flipped);
        });

        document.getElementById('flipButtonBack').addEventListener('click', () => {
            state.flipped = !state.flipped;
            flashcardInner.classList.toggle('is-flipped', state.flipped);
        });

        document.getElementById('prevCard').addEventListener('click', () => {
            state.index = (state.index - 1 + state.currentList.length) % state.currentList.length;
            state.flipped = false;
            applyCard();
        });

        document.getElementById('nextCard').addEventListener('click', () => {
            state.index = (state.index + 1) % state.currentList.length;
            state.flipped = false;
            applyCard();
        });

        document.getElementById('shuffleCard').addEventListener('click', () => {
            const list = filteredList();
            if (list.length <= 1) {
                return;
            }
            state.index = Math.floor(Math.random() * list.length);
            state.flipped = false;
            applyCard();
        });

        bandButtons.forEach((button) => {
            button.addEventListener('click', () => setBandFilter(button.dataset.bandFilter));
        });

        skillButtons.forEach((button) => {
            button.addEventListener('click', () => setSkillFilter(button.dataset.skillFilter));
        });

        flashcardInner.addEventListener('click', () => {
            state.flipped = !state.flipped;
            flashcardInner.classList.toggle('is-flipped', state.flipped);
        });

        applyCard();
    </script>
</body>
</html>
