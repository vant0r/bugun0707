<?php
require_once __DIR__ . '/../includes/panel_layout.php';
require_once __DIR__ . '/../includes/notifications.php';
vpy_require_login('/login.php');

$u = vpy_user();

// Send message
if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf'))) {
    $msg = trim(vpy_post('message', ''));
    $file_url = '';

    // File upload (max 2MB)
    if (!empty($_FILES['chatfile']['tmp_name']) && is_uploaded_file($_FILES['chatfile']['tmp_name'])) {
        if ($_FILES['chatfile']['size'] <= 2 * 1024 * 1024) {
            $ext = strtolower(pathinfo($_FILES['chatfile']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','gif','pdf','doc','docx'])) {
                $fname = 'chat_' . $u['id'] . '_' . time() . '.' . $ext;
                $dest = VPY_UPLOADS . '/' . $fname;
                if (move_uploaded_file($_FILES['chatfile']['tmp_name'], $dest)) {
                    $file_url = '/assets/uploads/' . $fname;
                }
            }
        }
    }

    if (mb_strlen($msg, 'UTF-8') >= 1 || $file_url) {
        $full_msg = $msg;
        if ($file_url) {
            $full_msg = $full_msg ? $full_msg . "\n[fayl:" . $file_url . ']' : '[fayl:' . $file_url . ']';
        }
        vpy_support_send($u['id'], $full_msg, false);
        vpy_flash_set('success', 'Xabar yuborildi');
    }
    vpy_redirect('/user/support.php');
}

$messages = vpy_support_messages($u['id']);

vpy_panel_head("Qo'llab-quvatlash", <<<CSS
.chat-wrap{max-width:700px}
.chat-box{background:var(--glass);backdrop-filter:blur(20px);border:1px solid var(--border-strong);border-radius:var(--r-lg);overflow:hidden}
.chat-messages{padding:24px;max-height:500px;overflow-y:auto;display:flex;flex-direction:column;gap:10px}
.chat-msg{max-width:80%;padding:12px 16px;border-radius:16px;font-size:0.88rem;line-height:1.5;position:relative;animation:msgIn 0.3s ease}
@keyframes msgIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.chat-msg.user{align-self:flex-end;background:var(--primary);color:#fff;border-bottom-right-radius:4px}
.chat-msg.admin{align-self:flex-start;background:var(--surface);border:1px solid var(--border);border-bottom-left-radius:4px}
.chat-msg-time{font-size:0.68rem;opacity:0.6;margin-top:4px}
.chat-msg.user .chat-msg-time{text-align:right}
.chat-msg.admin::before{content:"Admin";display:block;font-size:0.68rem;font-weight:700;color:var(--primary);margin-bottom:3px}
.chat-empty{text-align:center;padding:60px 20px;color:var(--muted)}
.chat-empty svg{width:48px;height:48px;margin:0 auto 12px;opacity:0.3}
.chat-form{display:flex;gap:10px;padding:16px;border-top:1px solid var(--border);background:var(--surface2)}
.chat-form input{flex:1;padding:12px 16px;border-radius:var(--pill);border:1.5px solid var(--border-strong);background:var(--glass);font-size:0.88rem;color:var(--dark)}
.chat-form input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-glow)}
.chat-form button{width:44px;height:44px;border-radius:50%;background:var(--primary);color:#fff;display:grid;place-items:center;border:none;cursor:pointer;transition:transform 0.2s;flex-shrink:0}
.chat-form button:hover{transform:scale(1.08)}
.chat-form button svg{width:18px;height:18px}
.chat-info{margin-bottom:18px;padding:16px;background:var(--blue-soft);border:1px solid var(--border);border-radius:12px;font-size:0.85rem;color:var(--dark-soft);display:flex;align-items:center;gap:10px}
.chat-info svg{width:18px;height:18px;color:var(--primary);flex-shrink:0}
@media (max-width:640px){.chat-msg{max-width:90%}.chat-messages{padding:16px;max-height:400px}}
CSS);
vpy_panel_sidebar('profil', false);
?>
<main class="main">
<?php vpy_panel_topbar("Qo'llab-quvatlash", "Admin bilan bog'lanish"); ?>

<div class="chat-wrap">
    <div class="chat-info">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        <span>Savolingizni yozing — admin imkon qadar tez javob beradi</span>
    </div>

    <div class="chat-box">
        <div class="chat-messages" id="chatMessages">
            <?php if (empty($messages)): ?>
            <div class="chat-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                <p>Hali xabar yo'q. Savolingizni yozing!</p>
            </div>
            <?php else: ?>
                <?php foreach ($messages as $m): ?>
                <div class="chat-msg <?= !empty($m['is_admin']) ? 'admin' : 'user' ?>">
                    <?php
                    $text = $m['message'] ?? '';
                    // Check for file attachment
                    if (preg_match('/\[fayl:([^\]]+)\]/', $text, $fm)) {
                        $file = $fm[1];
                        $text = trim(str_replace($fm[0], '', $text));
                        $is_img = preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $file);
                    } else { $file = ''; $is_img = false; }
                    ?>
                    <?php if ($text): ?><div><?= e($text) ?></div><?php endif; ?>
                    <?php if ($file && $is_img): ?>
                    <a href="<?= e($file) ?>" target="_blank" style="display:block;margin-top:6px"><img src="<?= e($file) ?>" alt="Rasm" style="max-width:200px;border-radius:8px;border:1px solid rgba(255,255,255,0.2)"></a>
                    <?php elseif ($file): ?>
                    <a href="<?= e($file) ?>" target="_blank" style="display:inline-flex;align-items:center;gap:5px;margin-top:6px;padding:6px 12px;background:rgba(255,255,255,0.15);border-radius:8px;font-size:0.78rem;font-weight:600"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>Fayl</a>
                    <?php endif; ?>
                    <div class="chat-msg-time"><?= e(vpy_time_ago($m['created_at'])) ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <form method="post" enctype="multipart/form-data" class="chat-form">
            <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
            <input type="file" name="chatfile" id="chatFile" accept="image/*,.pdf,.doc,.docx" style="display:none" onchange="document.getElementById('fileName').textContent=this.files[0]?.name||''">
            <button type="button" onclick="document.getElementById('chatFile').click()" style="width:38px;height:38px;border-radius:50%;background:var(--surface);border:1px solid var(--border);display:grid;place-items:center;cursor:pointer;flex-shrink:0" title="Fayl yuklash (2MB gacha)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg></button>
            <input type="text" name="message" placeholder="Xabar yozing..." maxlength="1000" autocomplete="off">
            <button type="submit" aria-label="Yuborish"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg></button>
        </form>
        <div id="fileName" style="padding:0 16px 8px;font-size:0.75rem;color:var(--primary);font-weight:600"></div>
    </div>
</div>

<script>
var cm = document.getElementById('chatMessages');
if(cm) cm.scrollTop = cm.scrollHeight;
</script>

</main>
<?php vpy_panel_foot(); ?>
