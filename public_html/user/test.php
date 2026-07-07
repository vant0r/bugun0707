<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_login('/login.php');

$u = vpy_user();
$type = vpy_get('type', 'quick');
$bilet_id = (int)vpy_get('bilet', 0);

// Bilet mavjudligini tekshirish
if ($type === 'bilet50') {
    $max_bilet50 = vpy_test_bilet50_count();
    if ($bilet_id < 1 || $bilet_id > $max_bilet50) {
        vpy_redirect('/user/testlar.php?error=invalid_bilet50');
    }
} elseif ($bilet_id > 0 && ($bilet_id < 1 || $bilet_id > 65)) {
    vpy_redirect('/user/test.php?error=invalid_ticket');
}

if (vpy_is_post() && vpy_post('action') === 'finish' && vpy_csrf_check(vpy_post('csrf'))) {
    $answers_json = vpy_post('answers');
    $duration = (int)vpy_post('duration');
    $session_type = vpy_post('test_type', 'quick');
    $session_bilet = (int)vpy_post('bilet_id', 0);
    $answers = json_decode($answers_json, true) ?: [];
    $correct = 0;
    $detail = [];
    $pdo = vpy_pdo();
    if ($pdo && !empty($answers)) {
        $ids = array_map('intval', array_keys($answers));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $st = $pdo->prepare("SELECT * FROM test_savollar WHERE id IN ($placeholders)");
        $st->execute($ids);
        $qs_by_id = [];
        foreach ($st->fetchAll() as $q) $qs_by_id[(int)$q['id']] = $q;
        foreach ($answers as $qid => $ans) {
            $q = $qs_by_id[(int)$qid] ?? null;
            if ($q && strtoupper($ans) === strtoupper($q['togri'])) $correct++;
            $detail[] = ['question_id' => (int)$qid, 'answer' => $ans, 'correct' => $q ? $q['togri'] : null, 'is_correct' => $q && strtoupper($ans) === strtoupper($q['togri'])];
        }
    }
    if ($session_type !== 'bilet50') {
        $row = [
            'id' => vpy_id_next('natijalar'),
            'user_id' => (int)$u['id'],
            'type' => $session_bilet ? 'bilet' : $session_type,
            'bilet_id' => $session_bilet ?: null,
            'score' => $correct,
            'total' => count($answers),
            'correct' => $correct,
            'wrong' => count($answers) - $correct,
            'duration' => $duration,
            'answers' => $detail,
            'created_at' => date('Y-m-d H:i:s')
        ];
        vpy_upsert('natijalar', $row);
        $u['tests_taken'] = ((int)($u['tests_taken'] ?? 0)) + 1;
        if ($correct > (int)($u['best_score'] ?? 0)) $u['best_score'] = $correct;
        vpy_upsert('users', $u);
        vpy_log('test_finish', "Test yakunlandi: $correct/" . count($answers), ['user_id' => $u['id'], 'type' => $row['type']]);
        vpy_redirect('/user/test-result.php?id=' . $row['id']);
    } else {
        // Biletlar 50 - mashq rejimi, natijalar saqlanmaydi
        $_SESSION['bilet50_result'] = [
            'score' => $correct,
            'total' => count($answers),
            'correct' => $correct,
            'wrong' => count($answers) - $correct,
            'duration' => $duration,
            'bilet_num' => $session_bilet,
            'answers' => $detail
        ];
        vpy_redirect('/user/test-result.php?type=bilet50');
    }
}

// Savollarni olish - bilet50, exam, bilet bo'yicha yoki tasodifiy
if ($type === 'bilet50' && $bilet_id > 0) {
    // Biletlar 50 - virtual bilet (50 ta savol, id bo'yicha tartiblangan)
    $questions = vpy_test_questions_bilet50($bilet_id);
} elseif ($type === 'exam') {
    // Imtihon topshirish - tasodifiy biletdan 20 ta savol
    $pdo_exam = vpy_pdo();
    $exam_bilet = 0;
    if ($pdo_exam) {
        try {
            $st_exam = $pdo_exam->query("SELECT DISTINCT bilet_id FROM test_savollar WHERE holat='faol' AND bilet_id > 0 ORDER BY RAND() LIMIT 1");
            $row_exam = $st_exam->fetch();
            if ($row_exam) $exam_bilet = (int)$row_exam['bilet_id'];
        } catch (Exception $e) {}
    }
    if ($exam_bilet > 0) {
        $questions = vpy_test_questions_by_bilet($exam_bilet);
        $bilet_id = $exam_bilet; // Bilet raqamini ko'rsatish uchun
    } else {
        $questions = vpy_test_questions(20, null);
    }
} elseif ($type === 'xatolar' && $bilet_id > 0) {
    // Xatolarim - foydalanuvchi xato qilgan savollar
    $all_results_x = vpy_filter('natijalar', fn($r) => (int)$r['user_id'] === (int)$u['id']);
    $wrong_qids = [];
    foreach ($all_results_x as $rx) {
        if (empty($rx['answers'])) continue;
        foreach ($rx['answers'] as $ax) {
            if (empty($ax['is_correct'])) {
                $wrong_qids[(int)$ax['question_id']] = true;
            }
        }
    }
    $pdo_x = vpy_pdo();
    $questions = [];
    if ($pdo_x && !empty($wrong_qids)) {
        $ids_x = array_keys($wrong_qids);
        $ph_x = implode(',', array_fill(0, count($ids_x), '?'));
        $st_x = $pdo_x->prepare("SELECT * FROM test_savollar WHERE id IN ($ph_x) AND holat='faol' ORDER BY bilet_id ASC, tartib ASC");
        $st_x->execute($ids_x);
        $all_wrong = $st_x->fetchAll();
        // 20 talik biletlarga bo'lish
        $chunks_x = array_chunk($all_wrong, 20);
        $questions = $chunks_x[$bilet_id - 1] ?? [];
    }
} elseif ($bilet_id > 0) {
    // Bilet bo'yicha savollarni olish (bilet_id bo'yicha filter)
    $questions = vpy_test_questions_by_bilet($bilet_id);
} else {
    // Tasodifiy 20 ta savol
    $questions = vpy_test_questions(20, null);
}

if (empty($questions)) {
    vpy_panel_head(t('test_title'), '');
    vpy_panel_sidebar('test', false);
    echo '<main class="main">';
    vpy_panel_topbar(t('test_title'));
    echo '<div class="card empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg><h3>Savollar topilmadi</h3><p>';
    if ($bilet_id > 0) {
        echo 'Bilet #' . $bilet_id . ' uchun savollar mavjud emas.';
    } else {
        echo 'Avval administrator SQL bazasini import qilishi kerak.';
    }
    echo '</p></div>';
    echo '</main>';
    vpy_panel_foot();
    exit;
}

$lang_code = vpy_lang_code();
$is_cyrl = $lang_code === 'uz_cyrillic';

vpy_panel_head($bilet_id ? sprintf('%s %02d / 65', t('ticket_label'), $bilet_id) : t('test_title'), <<<CSS
.test-wrap{max-width:920px;margin:0 auto}
.test-head{background:var(--glass-strong);backdrop-filter:blur(30px);border:1px solid var(--border);border-radius:var(--r-lg);padding:24px 28px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap;position:sticky;top:14px;z-index:5;box-shadow:0 14px 30px rgba(30,27,24,0.06)}
.test-progress-wrap{flex:1;min-width:200px}
.test-progress-meta{display:flex;justify-content:space-between;font-size:0.82rem;color:var(--dark-soft);margin-bottom:8px}
.test-progress-bar{height:8px;border-radius:var(--pill);background:rgba(180,160,130,0.18);overflow:hidden}
.test-progress-bar > div{height:100%;background:linear-gradient(90deg,var(--primary),var(--accent));border-radius:var(--pill);transition:width 0.5s cubic-bezier(0.4,0,0.2,1);box-shadow:0 0 12px rgba(232,168,56,0.4)}
.timer-box{display:flex;align-items:center;gap:10px;padding:10px 18px;background:linear-gradient(135deg,var(--accent),#D88F1A);color:#fff;border-radius:var(--pill);font-weight:700;font-size:1rem;box-shadow:0 8px 20px rgba(232,168,56,0.35);font-variant-numeric:tabular-nums}
.timer-box.urgent{background:linear-gradient(135deg,#FF6058,#C73E36);animation:pulse 1s ease-in-out infinite}
@keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.05)}}
.timer-dot{width:8px;height:8px;border-radius:50%;background:#fff;animation:p 1s ease-in-out infinite}
@keyframes p{0%,100%{opacity:1}50%{opacity:0.4}}
.q-card{background:var(--glass-strong);backdrop-filter:blur(30px);border:1px solid var(--border);border-radius:var(--r-lg);padding:36px;box-shadow:var(--shadow-sm);transition:opacity 0.3s,transform 0.3s;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none;-webkit-touch-callout:none}
.q-card img{-webkit-user-drag:none;-khtml-user-drag:none;-moz-user-drag:none;-o-user-drag:none;pointer-events:none}
.q-num{font-family:var(--serif);font-size:0.9rem;font-weight:600;color:var(--primary);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:14px}
.q-text{font-family:var(--serif);font-size:clamp(1.2rem,2vw,1.5rem);font-weight:500;line-height:1.4;color:var(--dark);margin-bottom:30px;letter-spacing:-0.01em}
.q-image{margin-bottom:24px;border-radius:var(--r);overflow:hidden;background:#fff;border:1px solid var(--border);max-height:320px;display:grid;place-items:center}
.q-image img{width:100%;max-height:320px;object-fit:contain;display:block}
.q-answers{display:flex;flex-direction:column;gap:12px}
.q-answer{display:flex;align-items:center;gap:16px;padding:18px 22px;background:rgba(255,253,249,0.6);border:1.5px solid var(--border);border-radius:var(--r);font-size:0.97rem;cursor:pointer;transition:var(--t);text-align:left;width:100%;color:var(--dark)}
.q-answer:hover{background:var(--light);border-color:var(--primary);transform:translateX(4px)}
.q-answer.selected{background:linear-gradient(135deg,rgba(13,107,78,0.08),rgba(232,168,56,0.06));border-color:var(--primary);box-shadow:0 6px 20px rgba(13,107,78,0.12)}
.q-answer.selected .letter{background:var(--primary);color:#fff}
.q-answer.correct{background:rgba(34,197,94,0.15);border-color:#22c55e}
.q-answer.correct .letter{background:#22c55e;color:#fff}
.q-answer.wrong{background:rgba(239,68,68,0.15);border-color:#ef4444}
.q-answer.wrong .letter{background:#ef4444;color:#fff}
.q-nav{display:flex;justify-content:space-between;gap:14px;margin-top:24px}
.q-grid{display:flex;gap:6px;flex-wrap:wrap;margin-top:14px;padding:14px;background:var(--glass);border-radius:var(--r);border:1px solid var(--border)}
.q-grid button{width:38px;height:38px;border-radius:10px;border:1.5px solid var(--border);background:transparent;font-weight:700;font-size:0.85rem;cursor:pointer;transition:var(--t);color:var(--dark-soft)}
.q-grid button:hover{background:rgba(13,107,78,0.06);border-color:var(--primary)}
.q-grid button.current{background:var(--primary);color:#fff;border-color:var(--primary)}
.q-grid button.answered-correct{background:#22c55e;border-color:#22c55e;color:#fff}
.q-grid button.answered-wrong{background:#ef4444;border-color:#ef4444;color:#fff}
.confirm{position:fixed;inset:0;background:rgba(30,27,24,0.5);backdrop-filter:blur(8px);display:none;align-items:center;justify-content:center;z-index:100;padding:20px}
.confirm.show{display:flex}
.confirm-card{background:#fff;border-radius:var(--r-lg);padding:36px;max-width:440px;width:100%;text-align:center;box-shadow:0 30px 60px rgba(0,0,0,0.3)}
.confirm-card h3{font-family:var(--serif);font-size:1.4rem;font-weight:600;margin-bottom:10px}
.confirm-card p{color:var(--muted);margin-bottom:26px}
.confirm-actions{display:flex;gap:12px;justify-content:center}
.shortcuts-info{font-size:0.75rem;color:var(--muted);text-align:center;margin-top:12px;padding:8px;background:rgba(0,0,0,0.03);border-radius:var(--r);border:1px dashed var(--border)}
.shortcuts-info kbd{background:var(--light);padding:2px 10px;border-radius:4px;border:1px solid var(--border);font-size:0.7rem;font-weight:700;color:var(--dark-soft)}
.ticket-badge{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--primary),var(--accent));color:#fff;padding:6px 18px;border-radius:var(--pill);font-weight:700;font-size:0.85rem}
@media (max-width:640px){.q-card{padding:24px}.test-head{padding:18px;flex-direction:column;align-items:stretch}}
.q-card.answered .q-answer{cursor:default;pointer-events:none}
.q-card.answered .q-answer:hover{transform:none;border-color:var(--border);background:rgba(255,253,249,0.6)}
CSS);
vpy_panel_sidebar('test', false);
?>

<main class="main">
<div class="test-wrap">
    <div class="test-head">
        <div class="test-progress-wrap">
            <div class="test-progress-meta">
                <span>
                    <?php if ($bilet_id > 0): ?>
                        <span class="ticket-badge">🎫 <?= sprintf('%02d / 65', $bilet_id) ?></span>
                    <?php endif; ?>
                    <strong id="qNum">1</strong> / <?= count($questions) ?> <?= e(t('test_question')) ?>
                </span>
                <span id="answeredCount">0 javob</span>
            </div>
            <div class="test-progress-bar"><div id="progressFill" style="width:5%"></div></div>
        </div>
        <div class="timer-box" id="timer">
            <span class="timer-dot"></span>
            <span id="timerVal">25:00</span>
        </div>
    </div>

    <form method="post" id="testForm">
        <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
        <input type="hidden" name="action" value="finish">
        <input type="hidden" name="answers" id="answersInput" value="{}">
        <input type="hidden" name="duration" id="durationInput" value="0">
        <input type="hidden" name="test_type" value="<?= e($type) ?>">
        <input type="hidden" name="bilet_id" value="<?= (int)$bilet_id ?>">

        <div id="qContainer">
            <?php foreach ($questions as $i => $q):
                $svol = $is_cyrl && !empty($q['savol_cyrl']) ? $q['savol_cyrl'] : $q['savol'];
                $a = $is_cyrl && !empty($q['variant_a_cyrl']) ? $q['variant_a_cyrl'] : $q['variant_a'];
                $b = $is_cyrl && !empty($q['variant_b_cyrl']) ? $q['variant_b_cyrl'] : $q['variant_b'];
                $c = $is_cyrl && !empty($q['variant_c_cyrl']) ? $q['variant_c_cyrl'] : ($q['variant_c'] ?? '');
                $d = $is_cyrl && !empty($q['variant_d_cyrl']) ? $q['variant_d_cyrl'] : ($q['variant_d'] ?? '');
                $variants = [['A', $a], ['B', $b]];
                if ($c !== '') $variants[] = ['C', $c];
                if ($d !== '') $variants[] = ['D', $d];
            ?>
                <div class="q-card" data-q-index="<?= $i ?>" data-q-id="<?= (int)$q['id'] ?>" style="<?= $i === 0 ? '' : 'display:none' ?>">
                    <div class="q-num"><?= e(t('test_question')) ?> <?= $i + 1 ?></div>
                    <h2 class="q-text"><?= e($svol) ?></h2>
                    <?php if (!empty($q['rasm'])): ?>
                        <div class="q-image"><img src="<?= e($q['rasm']) ?>" alt="" loading="lazy"></div>
                    <?php endif; ?>
                    <div class="q-answers">
                        <?php $fi = 1; foreach ($variants as $v): ?>
                            <button type="button" class="q-answer" data-letter="<?= e($v[0]) ?>">
                                <span class="letter">F<?= $fi ?></span>
                                <span><?= e($v[1]) ?></span>
                            </button>
                        <?php $fi++; endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="q-nav">
            <button type="button" class="btn btn-ghost" id="btnPrev" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                <?= e(t('test_prev')) ?>
            </button>
            <button type="button" class="btn btn-primary" id="btnNext">
                <?= e(t('test_next')) ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </button>
        </div>

        <div class="q-grid" id="qGrid">
            <?php for ($i = 0; $i < count($questions); $i++): ?>
                <button type="button" data-jump="<?= $i ?>" class="<?= $i === 0 ? 'current' : '' ?>"><?= $i + 1 ?></button>
            <?php endfor; ?>
        </div>
        
        <div class="shortcuts-info">
            <kbd>F1</kbd> <kbd>F2</kbd> <kbd>F3</kbd> <kbd>F4</kbd> — javoblar &nbsp;|&nbsp; 
            <kbd>F6</kbd> — oldingi &nbsp;|&nbsp; 
            <kbd>F7</kbd> — keyingi &nbsp;|&nbsp;
            <kbd>1-4</kbd> — raqamli tanlov
        </div>
    </form>
</div>

<div class="confirm" id="confirmDialog">
    <div class="confirm-card">
        <h3><?= e(t('test_finish')) ?>?</h3>
        <p><?= e(t('test_confirm_finish')) ?></p>
        <div class="confirm-actions">
            <button type="button" class="btn btn-ghost" id="cancelFinish"><?= e(t('btn_no')) ?></button>
            <button type="button" class="btn btn-success" id="confirmFinish"><?= e(t('btn_yes')) ?>, <?= e(t('test_finish')) ?></button>
        </div>
    </div>
</div>

</main>

<script>
(function(){
    // Savol matnini va rasmlarni nusxalashdan himoya qilish
    var testWrap = document.querySelector('.test-wrap');
    if (testWrap) {
        testWrap.addEventListener('copy', function(e){ e.preventDefault(); });
        testWrap.addEventListener('cut', function(e){ e.preventDefault(); });
        testWrap.addEventListener('contextmenu', function(e){ e.preventDefault(); });
        testWrap.addEventListener('selectstart', function(e){ e.preventDefault(); });
        testWrap.addEventListener('dragstart', function(e){ e.preventDefault(); });
    }
    document.addEventListener('keydown', function(e){
        var k = e.key ? e.key.toLowerCase() : '';
        if ((e.ctrlKey || e.metaKey) && (k === 'c' || k === 'x' || k === 'u' || k === 's' || k === 'p')) {
            if (e.target.closest('.test-wrap') || !document.activeElement || document.activeElement === document.body) {
                e.preventDefault();
            }
        }
    });
})();
(function(){
    var questions = document.querySelectorAll('.q-card');
    var total = questions.length;
    var current = 0;
    var answers = {};
    var startTime = Date.now();
    var totalDuration = <?= $type === 'bilet50' ? 3600 : 1500 ?>;
    var testType = '<?= e($type) ?>';
    var wrongCount = 0;
    var maxWrong = testType === 'exam' ? 2 : 9999;
    var endTime = startTime + totalDuration * 1000;
    var qNum = document.getElementById('qNum');
    var progressFill = document.getElementById('progressFill');
    var answeredCount = document.getElementById('answeredCount');
    var timerVal = document.getElementById('timerVal');
    var timerBox = document.getElementById('timer');
    var btnPrev = document.getElementById('btnPrev');
    var btnNext = document.getElementById('btnNext');
    var qGrid = document.getElementById('qGrid');
    var answersInput = document.getElementById('answersInput');
    var durationInput = document.getElementById('durationInput');
    var form = document.getElementById('testForm');
    var dialog = document.getElementById('confirmDialog');
    
    // To'g'ri javoblarni saqlash uchun
    var correctAnswers = {};
    <?php foreach ($questions as $q): ?>
        correctAnswers[<?= (int)$q['id'] ?>] = '<?= e($q['togri']) ?>';
    <?php endforeach; ?>

    function show(i){
        questions.forEach(function(q, idx){ q.style.display = idx === i ? '' : 'none'; });
        current = i;
        qNum.textContent = i + 1;
        progressFill.style.width = ((i + 1) / total * 100) + '%';
        btnPrev.disabled = i === 0;
        btnNext.innerHTML = (i === total - 1)
            ? '<?= e(t('test_finish')) ?> <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>'
            : '<?= e(t('test_next')) ?> <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
        qGrid.querySelectorAll('button').forEach(function(b, idx){
            b.classList.toggle('current', idx === i);
        });
        updateGridColors();
    }

    function updateGridColors() {
        qGrid.querySelectorAll('button').forEach(function(b){
            var idx = parseInt(b.getAttribute('data-jump'), 10);
            var qCard = questions[idx];
            var qid = qCard.getAttribute('data-q-id');
            var answer = answers[qid];
            
            // Avval barcha classlarni olib tashlash
            b.classList.remove('answered-correct', 'answered-wrong');
            
            if (answer) {
                var isCorrect = answer.toUpperCase() === correctAnswers[qid].toUpperCase();
                if (isCorrect) {
                    b.classList.add('answered-correct');
                } else {
                    b.classList.add('answered-wrong');
                }
            }
        });
    }

    questions.forEach(function(q, qi){
        q.querySelectorAll('.q-answer').forEach(function(a){
            a.addEventListener('click', function(){
                var qid = q.getAttribute('data-q-id');
                
                // Agar savol allaqachon javob berilgan bo'lsa, qayta tanlashga ruxsat bermaslik
                if (answers[qid] !== undefined) return;
                
                var selectedLetter = a.getAttribute('data-letter');
                
                // Avval barcha javoblarni tozalash
                q.querySelectorAll('.q-answer').forEach(function(x){ 
                    x.classList.remove('selected', 'correct', 'wrong'); 
                });
                
                // Tanlangan javobni belgilash
                a.classList.add('selected');
                answers[qid] = selectedLetter;
                answersInput.value = JSON.stringify(answers);
                answeredCount.textContent = Object.keys(answers).length + ' javob';
                
                // Javob to'g'ri yoki xato ekanligini tekshirish
                var isCorrect = selectedLetter.toUpperCase() === correctAnswers[qid].toUpperCase();
                if (isCorrect) {
                    a.classList.add('correct');
                } else {
                    a.classList.add('wrong');
                    wrongCount++;
                    // To'g'ri javobni ko'rsatish
                    q.querySelectorAll('.q-answer').forEach(function(x){
                        if (x.getAttribute('data-letter').toUpperCase() === correctAnswers[qid].toUpperCase()) {
                            x.classList.add('correct');
                        }
                    });
                }
                
                updateGridColors();
                // Savolni "javob berilgan" deb belgilash
                q.classList.add('answered');
                
                // Imtihon rejimida 2 ta xato bo'lsa avtomatik tugatish
                if (wrongCount >= maxWrong) {
                    setTimeout(function(){
                        alert('Imtihondan o\'tmadingiz! ' + wrongCount + ' ta xato javob berdingiz.');
                        submitForm();
                    }, 800);
                    return;
                }
            });
        });
    });

    btnNext.addEventListener('click', function(){
        if (current === total - 1) {
            dialog.classList.add('show');
        } else {
            show(current + 1);
        }
    });
    btnPrev.addEventListener('click', function(){ if (current > 0) show(current - 1); });
    qGrid.querySelectorAll('button').forEach(function(b){
        b.addEventListener('click', function(){ show(parseInt(b.getAttribute('data-jump'), 10)); });
    });

    document.getElementById('cancelFinish').addEventListener('click', function(){ dialog.classList.remove('show'); });
    document.getElementById('confirmFinish').addEventListener('click', function(){ submitForm(); });

    function submitForm(){
        durationInput.value = Math.floor((Date.now() - startTime) / 1000);
        answersInput.value = JSON.stringify(answers);
        form.submit();
    }

    function tick(){
        var rem = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
        var m = Math.floor(rem / 60);
        var s = rem % 60;
        timerVal.textContent = m + ':' + (s < 10 ? '0' : '') + s;
        if (rem < 60) timerBox.classList.add('urgent');
        if (rem === 0) submitForm();
    }
    tick();
    setInterval(tick, 1000);

    // Klaviatura boshqaruvi
    document.addEventListener('keydown', function(e){
        // F1, F2, F3, F4 - javob variantlari
        if (e.key === 'F1' || e.key === 'F2' || e.key === 'F3' || e.key === 'F4') {
            e.preventDefault();
            var letters = ['A', 'B', 'C', 'D'];
            var index = parseInt(e.key.replace('F', '')) - 1;
            if (index < letters.length) {
                var qCard = questions[current];
                var btn = qCard.querySelector('.q-answer[data-letter="' + letters[index] + '"]');
                if (btn) btn.click();
            }
        }
        
        // F6 - oldingi savol
        if (e.key === 'F6') {
            e.preventDefault();
            if (current > 0) show(current - 1);
        }
        
        // F7 - keyingi savol
        if (e.key === 'F7') {
            e.preventDefault();
            if (current === total - 1) {
                dialog.classList.add('show');
            } else {
                show(current + 1);
            }
        }
        
        // Arrow keys
        if (e.key === 'ArrowRight') {
            e.preventDefault();
            if (current === total - 1) {
                dialog.classList.add('show');
            } else {
                show(current + 1);
            }
        }
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            if (current > 0) show(current - 1);
        }
        if (['1','2','3','4'].indexOf(e.key) !== -1) {
            var letters = ['A','B','C','D'];
            var letter = letters[parseInt(e.key, 10) - 1];
            var qCard = questions[current];
            var btn = qCard.querySelector('.q-answer[data-letter="' + letter + '"]');
            if (btn) btn.click();
        }
        // Enter - testni tugatish
        if (e.key === 'Enter' && dialog.classList.contains('show')) {
            submitForm();
        }
        // Escape - dialogni yopish
        if (e.key === 'Escape' && dialog.classList.contains('show')) {
            dialog.classList.remove('show');
        }
    });

    window.addEventListener('beforeunload', function(e){
        if (Object.keys(answers).length > 0 && Object.keys(answers).length < total) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
})();
</script>

<?php vpy_panel_foot(); ?>