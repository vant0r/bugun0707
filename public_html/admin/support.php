<?php
require_once __DIR__ . '/../includes/panel_layout.php';
require_once __DIR__ . '/../includes/notifications.php';
vpy_require_admin('/login.php');

// Send reply
if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf'))) {
    $user_id = (int)vpy_post('user_id');
    $msg = trim(vpy_post('message', ''));
    $file_url = '';

    // File upload (max 2MB)
    if (!empty($_FILES['chatfile']['tmp_name']) && is_uploaded_file($_FILES['chatfile']['tmp_name'])) {
        if ($_FILES['chatfile']['size'] <= 2 * 1024 * 1024) {
            $ext = strtolower(pathinfo($_FILES['chatfile']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','gif','pdf','doc','docx'])) {
                $fname = 'chat_admin_' . time() . '.' . $ext;
                $dest = VPY_UPLOADS . '/' . $fname;
                if (move_uploaded_file($_FILES['chatfile']['tmp_name'], $dest)) {
                    $file_url = '/assets/uploads/' . $fname;
                }
            }
        }
    }

    if ($user_id && (mb_strlen($msg, 'UTF-8') >= 1 || $file_url)) {
        $full_msg = $msg;
        if ($file_url) $full_msg = $full_msg ? $full_msg . "\n[fayl:" . $file_url . ']' : '[fayl:' . $file_url . ']';
        vpy_support_send($user_id, $full_msg, true);
        vpy_flash_set('success', 'Javob yuborildi');
    }
    vpy_redirect('/admin/support.php?user=' . $user_id);
}

// Get all chats grouped by user
$all_chats = vpy_read_json('support_chat', []);
$user_ids = array_unique(array_column($all_chats, 'user_id'));
$chat_users = [];
foreach ($user_ids as $uid) {
    $usr = vpy_find('users', 'id', $uid);
    if (!$usr) continue;
    $user_msgs = array_filter($all_chats, fn($m) => (int)$m['user_id'] === (int)$uid);
    $last_msg = end($user_msgs);
    $unread = count(array_filter($user_msgs, fn($m) => empty($m['is_admin'])));
    $chat_users[] = [
        'user' => $usr,
        'last_message' => $last_msg,
        'total' => count($user_msgs),
        'unread_from_user' => $unread
    ];
}
usort($chat_users, fn($a, $b) => strcmp($b['last_message']['created_at'] ?? '', $a['last_message']['created_at'] ?? ''));

// Selected user chat
$selected_user_id = (int)vpy_get('user', 0);
$selected_messages = $selected_user_id ? vpy_support_messages($selected_user_id) : [];
$selected_user = $selected_user_id ? vpy_find('users', 'id', $selected_user_id) : null;

vpy_panel_head("Qo'llab-quvatlash chat", <<<CSS
.support-layout{display:grid;grid-template-columns:300px 1fr;gap:18px;height:calc(100vh - 160px);min-height:400px}
.user-list{background:var(--glass);backdrop-filter:blur(20px);border:1px solid var(--border-strong);border-radius:var(--r-lg);overflow-y:auto;padding:8px}
.user-item{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;cursor:pointer;transition:var(--t);text-decoration:none;color:inherit}
.user-item:hover,.user-item.active{background:var(--blue-soft)}
.user-item.active{border:1px solid var(--primary)}
.user-avatar-sm{width:38px;height:38px;border-radius:50%;display:grid;place-items:center;color:#fff;font-weight:700;font-size:0.75rem;flex-shrink:0}
.user-item-info{flex:1;min-width:0}
.user-item-name{font-weight:700;font-size:0.88rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.user-item-last{font-size:0.75rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px}
.user-item-badge{width:20px;height:20px;border-radius:50%;background:var(--primary);color:#fff;display:grid;place-items:center;font-size:0.65rem;font-weight:700;flex-shrink:0}
.chat-area{display:flex;flex-direction:column;background:var(--glass);backdrop-filter:blur(20px);border:1px solid var(--border-strong);border-radius:var(--r-lg);overflow:hidden}
.chat-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px}
.chat-header-name{font-weight:700;font-size:0.95rem}
.chat-header-phone{font-size:0.78rem;color:var(--muted)}
.chat-body{flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:10px}
.chat-msg{max-width:75%;padding:12px 16px;border-radius:16px;font-size:0.88rem;line-height:1.5}
.chat-msg.from-user{align-self:flex-start;background:var(--surface);border:1px solid var(--border);border-bottom-left-radius:4px}
.chat-msg.from-admin{align-self:flex-end;background:var(--primary);color:#fff;border-bottom-right-radius:4px}
.chat-msg-time{font-size:0.68rem;opacity:0.6;margin-top:4px}
.chat-input{display:flex;gap:10px;padding:14px 16px;border-top:1px solid var(--border);background:var(--surface2)}
.chat-input input{flex:1;padding:12px 16px;border-radius:var(--pill);border:1.5px solid var(--border-strong);background:var(--glass);font-size:0.88rem;color:var(--dark)}
.chat-input input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-glow)}
.chat-input button{width:42px;height:42px;border-radius:50%;background:var(--primary);color:#fff;display:grid;place-items:center;border:none;cursor:pointer;flex-shrink:0}
.chat-input button:hover{transform:scale(1.05)}
.chat-input button svg{width:17px;height:17px}
.chat-empty-state{flex:1;display:grid;place-items:center;text-align:center;color:var(--muted);padding:40px}
.chat-empty-state svg{width:48px;height:48px;margin-bottom:12px;opacity:0.3}
@media (max-width:900px){.support-layout{grid-template-columns:1fr;height:auto}.user-list{max-height:250px}}
CSS);
vpy_panel_sidebar('sozlamalar', true);
?>
<main class="main">
<?php vpy_panel_topbar("Qo'llab-quvatlash", count($chat_users) . " ta suhbat"); ?>

<div class="support-layout">
    <!-- USER LIST -->
    <div class="user-list">
        <?php if (empty($chat_users)): ?>
        <div style="text-align:center;padding:30px;color:var(--muted);font-size:0.85rem">Hali xabarlar yo'q</div>
        <?php else: ?>
        <?php foreach ($chat_users as $cu):
            $usr = $cu['user'];
            $color = vpy_avatar_color($usr['name']);
            $is_active = $selected_user_id === (int)$usr['id'];
        ?>
        <a href="?user=<?= (int)$usr['id'] ?>" class="user-item <?= $is_active ? 'active' : '' ?>">
            <div class="user-avatar-sm" style="background:<?= e($color) ?>"><?= e(vpy_user_initials($usr['name'])) ?></div>
            <div class="user-item-info">
                <div class="user-item-name"><?= e($usr['name']) ?></div>
                <div class="user-item-last"><?= e(mb_substr($cu['last_message']['message'] ?? '', 0, 40, 'UTF-8')) ?></div>
            </div>
            <?php if ($cu['unread_from_user'] > 0): ?>
            <span class="user-item-badge"><?= $cu['unread_from_user'] ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- CHAT AREA -->
    <div class="chat-area">
        <?php if ($selected_user): ?>
        <div class="chat-header">
            <div class="user-avatar-sm" style="background:<?= e(vpy_avatar_color($selected_user['name'])) ?>"><?= e(vpy_user_initials($selected_user['name'])) ?></div>
            <div>
                <div class="chat-header-name"><?= e($selected_user['name']) ?></div>
                <div class="chat-header-phone"><?= e($selected_user['phone']) ?></div>
            </div>
        </div>
        <div class="chat-body" id="chatBody">
            <?php foreach ($selected_messages as $m): ?>
            <div class="chat-msg <?= !empty($m['is_admin']) ? 'from-admin' : 'from-user' ?>">
                <?php
                $text = $m['message'] ?? '';
                if (preg_match('/\[fayl:([^\]]+)\]/', $text, $fm)) {
                    $file = $fm[1]; $text = trim(str_replace($fm[0], '', $text));
                    $is_img = preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $file);
                } else { $file = ''; $is_img = false; }
                ?>
                <?php if ($text): ?><div><?= e($text) ?></div><?php endif; ?>
                <?php if ($file && $is_img): ?>
                <a href="<?= e($file) ?>" target="_blank" style="display:block;margin-top:6px"><img src="<?= e($file) ?>" alt="" style="max-width:180px;border-radius:8px"></a>
                <?php elseif ($file): ?>
                <a href="<?= e($file) ?>" target="_blank" style="display:inline-flex;align-items:center;gap:5px;margin-top:6px;padding:5px 10px;background:rgba(0,0,0,0.1);border-radius:6px;font-size:0.75rem;font-weight:600"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/></svg>Fayl</a>
                <?php endif; ?>
                <div class="chat-msg-time"><?= e(vpy_time_ago($m['created_at'])) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <form method="post" enctype="multipart/form-data" class="chat-input">
            <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
            <input type="hidden" name="user_id" value="<?= (int)$selected_user_id ?>">
            <input type="file" name="chatfile" id="adminChatFile" accept="image/*,.pdf,.doc,.docx" style="display:none">
            <button type="button" onclick="document.getElementById('adminChatFile').click()" style="width:38px;height:38px;border-radius:50%;background:var(--surface);border:1px solid var(--border);display:grid;place-items:center;cursor:pointer;flex-shrink:0" title="Fayl (2MB)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg></button>
            <input type="text" name="message" placeholder="Javob yozing..." maxlength="1000" autocomplete="off" autofocus>
            <button type="submit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg></button>
        </form>
        <?php else: ?>
        <div class="chat-empty-state">
            <div>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                <p style="font-size:0.92rem;font-weight:600">Foydalanuvchini tanlang</p>
                <p style="font-size:0.82rem;margin-top:4px">Chapdan suhbatni tanlang</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
var cb=document.getElementById('chatBody');
if(cb)cb.scrollTop=cb.scrollHeight;
</script>

</main>
<?php vpy_panel_foot(); ?>
