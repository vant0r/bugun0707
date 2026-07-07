<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_admin('/login.php');

$pdo = vpy_pdo();
if (!$pdo) {
    vpy_flash_set('error', 'Bazaga ulanib bo\'lmadi');
    vpy_redirect('/admin/savollar.php');
}

// "rasm" ustuni bazada mavjud emasligi sababli saqlash xato berishining oldini olish:
// ustun yo'q bo'lsa, avtomatik qo'shib qo'yamiz.
try {
    $col_check = $pdo->query("SHOW COLUMNS FROM test_savollar LIKE 'rasm'")->fetch();
    if (!$col_check) {
        $pdo->exec("ALTER TABLE test_savollar ADD COLUMN rasm VARCHAR(255) NULL DEFAULT NULL");
    }
} catch (Exception $e) {
    // Ustun qo'shilmasa ham davom etamiz, pastdagi saqlash bosqichida xato ochiq ko'rinadi
}

$id = (int)vpy_get('id');
$q = null;
if ($id) {
    $st = $pdo->prepare("SELECT * FROM test_savollar WHERE id = :id");
    $st->execute([':id' => $id]);
    $q = $st->fetch();
}
$is_edit = !empty($q);

if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf'))) {
    // POST overflow detection - fayl hajmi juda katta bo'lganda $_POST bo'sh bo'ladi
    if (empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
        vpy_flash_set('error', 'Fayl hajmi juda katta. Iltimos, kichikroq rasm yuklang (max 5MB).');
        vpy_redirect('/admin/savollar-form.php' . ($id ? '?id=' . $id : ''));
    }

    $rasm_url = $q['rasm'] ?? '';
    if (!empty($_FILES['rasm']['tmp_name']) && is_uploaded_file($_FILES['rasm']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['rasm']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $fname = 'savol_' . ($id ?: 'new') . '_' . time() . '_' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $ext;
            $dest = VPY_UPLOADS . '/' . $fname;
            if (move_uploaded_file($_FILES['rasm']['tmp_name'], $dest)) {
                $rasm_url = '/assets/uploads/' . $fname;
            }
        } else {
            vpy_flash_set('error', 'Rasm formati noto\'g\'ri. Faqat jpg, jpeg, png, webp, gif qabul qilinadi.');
        }
    } elseif (vpy_post('rasm_remove') === '1') {
        $rasm_url = '';
    }

    $data = [
        'bilet_id' => (int)vpy_post('bilet_id', 1),
        'tartib' => (int)vpy_post('tartib', 1),
        'mavzu' => vpy_post('mavzu', 'umumiy'),
        'qiyinlik' => vpy_post('qiyinlik', 'orta'),
        'savol' => vpy_post('savol'),
        'savol_cyrl' => vpy_post('savol_cyrl'),
        'rasm' => $rasm_url,
        'variant_a' => vpy_post('variant_a'),
        'variant_b' => vpy_post('variant_b'),
        'variant_c' => vpy_post('variant_c'),
        'variant_d' => vpy_post('variant_d'),
        'variant_a_cyrl' => vpy_post('variant_a_cyrl'),
        'variant_b_cyrl' => vpy_post('variant_b_cyrl'),
        'variant_c_cyrl' => vpy_post('variant_c_cyrl'),
        'variant_d_cyrl' => vpy_post('variant_d_cyrl'),
        'togri' => vpy_post('togri', 'A'),
        'izoh' => vpy_post('izoh'),
        'izoh_cyrl' => vpy_post('izoh_cyrl'),
        'holat' => vpy_post('holat', 'faol'),
    ];

    // Safety: tahrirlashda bilet_id ni himoya qilish
    if ($is_edit) {
        $original_bilet = (int)($q['bilet_id'] ?? 0);
        $submitted_bilet = (int)($data['bilet_id'] ?? 0);
        $hidden_original = (int)vpy_post('original_bilet_id', 0);
        
        // Agar bilet_id POST dan kelmasa yoki 0 bo'lsa, asl qiymatini saqlaymiz
        if (!isset($_POST['bilet_id']) || $submitted_bilet < 1) {
            $data['bilet_id'] = $original_bilet;
        }
        
        // Agar rasm yuklangan va bilet_id o'zgargan bo'lsa, hidden field orqali tekshiramiz
        if (!empty($_FILES['rasm']['tmp_name']) && $submitted_bilet !== $hidden_original && $hidden_original > 0) {
            // Bilet_id o'z-o'zidan o'zgarmagan bo'lishi kerak - original qiymatni saqlaymiz
            $data['bilet_id'] = $hidden_original;
        }
    }

    // Keyingi savolni topish uchun
    $next_id = null;
    if ($is_edit) {
        // Joriy bilet va tartibdagi keyingi savolni topamiz
        $next_st = $pdo->prepare("
            SELECT id FROM test_savollar 
            WHERE bilet_id = :bilet_id AND tartib > :tartib 
            ORDER BY tartib ASC LIMIT 1
        ");
        $next_st->execute([
            ':bilet_id' => $data['bilet_id'],
            ':tartib' => $data['tartib']
        ]);
        $next_row = $next_st->fetch();
        if ($next_row) {
            $next_id = (int)$next_row['id'];
        } else {
            // Agar shu biletda keyingi bo'lmasa, keyingi biletning birinchi savolini topamiz
            $next_st2 = $pdo->prepare("
                SELECT id FROM test_savollar 
                WHERE bilet_id > :bilet_id 
                ORDER BY bilet_id ASC, tartib ASC LIMIT 1
            ");
            $next_st2->execute([':bilet_id' => $data['bilet_id']]);
            $next_row2 = $next_st2->fetch();
            if ($next_row2) {
                $next_id = (int)$next_row2['id'];
            }
        }
    }

    if ($is_edit) {
        $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($data)));
        $st = $pdo->prepare("UPDATE test_savollar SET $set WHERE id = :id");
        $data[':id'] = $id;
        $params = [];
        foreach ($data as $k => $v) $params[(strpos($k, ':') === 0 ? $k : ":$k")] = $v;
        try {
            $st->execute($params);
            vpy_flash_set('success', t('msg_updated'));
            
            // Keyingi savolga o'tish
            if ($next_id) {
                vpy_redirect('/admin/savollar-form.php?id=' . $next_id);
            } else {
                vpy_redirect('/admin/savollar.php?bilet=' . (int)$data['bilet_id']);
            }
        } catch (Exception $e) {
            vpy_flash_set('error', 'Saqlashda xato yuz berdi: ' . $e->getMessage());
        }
    } else {
        $cols = implode(',', array_keys($data));
        $vals = ':' . implode(',:', array_keys($data));
        $st = $pdo->prepare("INSERT INTO test_savollar ($cols) VALUES ($vals)");
        foreach ($data as $k => $v) $st->bindValue(":$k", $v);
        try {
            $st->execute();
            vpy_flash_set('success', t('msg_added'));
            vpy_redirect('/admin/savollar.php?bilet=' . (int)$data['bilet_id']);
        } catch (Exception $e) {
            vpy_flash_set('error', 'Saqlashda xato yuz berdi: ' . $e->getMessage());
        }
    }
}

vpy_panel_head($is_edit ? t('admin_edit') . ' #' . $id : t('admin_add'));
vpy_panel_sidebar('savollar', true);
?>
<main class="main">
<?php vpy_panel_topbar(
    $is_edit ? t('admin_edit') . ' · #' . $id : t('admin_add'),
    sprintf('Bilet %02d · Savol %02d', (int)($q['bilet_id'] ?? 1), (int)($q['tartib'] ?? 1)),
    '<a href="/admin/savollar.php" class="btn btn-ghost">' . e(t('btn_back')) . '</a>'
); ?>

<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
    <?php if ($is_edit): ?><input type="hidden" name="original_bilet_id" value="<?= (int)$q['bilet_id'] ?>"><?php endif; ?>

    <div class="card">
        <div class="card-head"><h2>Asosiy ma'lumotlar</h2></div>
        <div class="field-row">
            <div class="field">
                <label>Bilet raqami</label>
                <input type="number" name="bilet_id" min="1" max="62" value="<?= (int)($q['bilet_id'] ?? 1) ?>" required>
            </div>
            <div class="field">
                <label>Tartib</label>
                <input type="number" name="tartib" min="1" max="20" value="<?= (int)($q['tartib'] ?? 1) ?>" required>
            </div>
        </div>
        <div class="field-row">
            <div class="field">
                <label>Mavzu</label>
                <select name="mavzu" style="width:100%;padding:13px 18px;border-radius:14px;border:1px solid var(--border-strong);background:rgba(255,253,249,0.85)">
                    <?php foreach (['belgilar','chiziqlar','signallar','tezlik','parking','kesishma','piyoda','hujjatlar','favqulodda','umumiy'] as $m): ?>
                        <option value="<?= e($m) ?>" <?= ($q['mavzu'] ?? '') === $m ? 'selected' : '' ?>><?= e($m) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Qiyinlik</label>
                <select name="qiyinlik" style="width:100%;padding:13px 18px;border-radius:14px;border:1px solid var(--border-strong);background:rgba(255,253,249,0.85)">
                    <option value="oson" <?= ($q['qiyinlik'] ?? '') === 'oson' ? 'selected' : '' ?>>Oson</option>
                    <option value="orta" <?= ($q['qiyinlik'] ?? 'orta') === 'orta' ? 'selected' : '' ?>>O'rta</option>
                    <option value="qiyin" <?= ($q['qiyinlik'] ?? '') === 'qiyin' ? 'selected' : '' ?>>Qiyin</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:18px">
        <div class="card-head"><h2>Savol matni</h2></div>
        <div class="field"><label>Savol (lotin)</label><textarea name="savol" required><?= e($q['savol'] ?? '') ?></textarea></div>
        <div class="field"><label>Savol (kirill)</label><textarea name="savol_cyrl"><?= e($q['savol_cyrl'] ?? '') ?></textarea></div>
    </div>

    <div class="card" style="margin-top:18px">
        <div class="card-head"><h2>Rasm (ixtiyoriy)</h2></div>
        <?php if (!empty($q['rasm'])): ?>
            <div style="display:flex;align-items:flex-start;gap:18px;margin-bottom:16px;flex-wrap:wrap">
                <img src="<?= e($q['rasm']) ?>" alt="Savol rasmi" style="max-width:260px;max-height:180px;border-radius:14px;border:1px solid var(--border-strong);object-fit:cover">
                <label style="display:flex;align-items:center;gap:8px;font-size:0.9rem;padding-top:6px">
                    <input type="checkbox" name="rasm_remove" value="1"> Mavjud rasmni o'chirish
                </label>
            </div>
        <?php endif; ?>
        <div class="field">
            <label>Yangi rasm yuklash</label>
            <div id="dropZone" style="border:2px dashed var(--border-strong);border-radius:14px;padding:40px 20px;text-align:center;cursor:pointer;transition:all 0.3s;background:rgba(255,253,249,0.6);position:relative">
                <div id="dropContent">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:48px;height:48px;margin:0 auto 12px;color:var(--muted)"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <p style="font-size:0.95rem;color:var(--dark);font-weight:600;margin-bottom:6px">Rasmni shu yerga tashlang</p>
                    <p style="font-size:0.82rem;color:var(--muted)">yoki bosib tanlang · Telegramdan surib olib kelsa ham bo'ladi</p>
                    <p style="font-size:0.75rem;color:var(--muted);margin-top:8px">JPG, PNG, WEBP yoki GIF · Max 5MB</p>
                </div>
                <div id="dropPreview" style="display:none">
                    <img id="previewImg" src="" alt="Preview" style="max-width:300px;max-height:200px;border-radius:10px;object-fit:contain;margin-bottom:10px">
                    <p id="previewName" style="font-size:0.85rem;color:var(--dark);font-weight:500"></p>
                    <button type="button" id="removePreview" style="margin-top:8px;padding:6px 16px;border-radius:8px;border:1px solid #ef4444;color:#ef4444;background:transparent;cursor:pointer;font-size:0.8rem;font-weight:600">✕ O'chirish</button>
                </div>
            </div>
            <input type="file" name="rasm" id="rasmInput" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none">
        </div>
    </div>

    <div class="card" style="margin-top:18px">
        <div class="card-head"><h2>Variantlar</h2></div>
        <?php foreach (['a','b','c','d'] as $L): ?>
            <div style="display:grid;grid-template-columns:auto 1fr 1fr;gap:14px;align-items:start;margin-bottom:14px">
                <div style="padding-top:36px"><label style="display:flex;align-items:center;gap:8px;font-size:0.9rem;font-weight:700"><input type="radio" name="togri" value="<?= strtoupper($L) ?>" <?= strtoupper($q['togri'] ?? 'A') === strtoupper($L) ? 'checked' : '' ?>> <span style="width:32px;height:32px;border-radius:10px;background:rgba(13,107,78,0.08);color:var(--primary);display:grid;place-items:center;font-weight:700"><?= strtoupper($L) ?></span></label></div>
                <div class="field" style="margin:0"><label>Variant <?= strtoupper($L) ?> (lotin)</label><input type="text" name="variant_<?= $L ?>" value="<?= e($q["variant_$L"] ?? '') ?>" <?= in_array($L, ['a','b']) ? 'required' : '' ?>></div>
                <div class="field" style="margin:0"><label>Variant <?= strtoupper($L) ?> (kirill)</label><input type="text" name="variant_<?= $L ?>_cyrl" value="<?= e($q["variant_{$L}_cyrl"] ?? '') ?>"></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card" style="margin-top:18px">
        <div class="card-head"><h2>Izoh</h2></div>
        <div class="field-row">
            <div class="field"><label>Izoh (lotin)</label><textarea name="izoh"><?= e($q['izoh'] ?? '') ?></textarea></div>
            <div class="field"><label>Izoh (kirill)</label><textarea name="izoh_cyrl"><?= e($q['izoh_cyrl'] ?? '') ?></textarea></div>
        </div>
        <div class="field-row">
            <div class="field">
                <label>Holat</label>
                <select name="holat" style="width:100%;padding:13px 18px;border-radius:14px;border:1px solid var(--border-strong);background:rgba(255,253,249,0.85)">
                    <option value="faol" <?= ($q['holat'] ?? 'faol') === 'faol' ? 'selected' : '' ?>>Faol</option>
                    <option value="noaktiv" <?= ($q['holat'] ?? '') === 'noaktiv' ? 'selected' : '' ?>>Noaktiv</option>
                </select>
            </div>
            <div></div>
        </div>
    </div>

    <div style="display:flex;gap:10px;margin-top:18px">
        <button type="submit" class="btn btn-primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg><?= e(t('btn_save')) ?></button>
        <a href="/admin/savollar.php" class="btn btn-ghost"><?= e(t('btn_cancel')) ?></a>
        <?php if ($is_edit): ?>
        <a href="/admin/savollar.php" class="btn btn-ghost" style="margin-left:auto;">Barcha savollar</a>
        <?php endif; ?>
    </div>
</form>
</main>
<script>
(function(){
    var dropZone = document.getElementById('dropZone');
    var fileInput = document.getElementById('rasmInput');
    var dropContent = document.getElementById('dropContent');
    var dropPreview = document.getElementById('dropPreview');
    var previewImg = document.getElementById('previewImg');
    var previewName = document.getElementById('previewName');
    var removeBtn = document.getElementById('removePreview');

    if (!dropZone || !fileInput) return;

    // Click to select file
    dropZone.addEventListener('click', function(e){
        if (e.target === removeBtn || e.target.closest('#removePreview')) return;
        fileInput.click();
    });

    // File input change
    fileInput.addEventListener('change', function(){
        if (fileInput.files.length > 0) {
            showPreview(fileInput.files[0]);
        }
    });

    // Drag events
    ['dragenter', 'dragover'].forEach(function(ev){
        dropZone.addEventListener(ev, function(e){
            e.preventDefault();
            e.stopPropagation();
            dropZone.style.borderColor = 'var(--primary)';
            dropZone.style.background = 'rgba(13,107,78,0.04)';
            dropZone.style.transform = 'scale(1.01)';
        });
    });

    ['dragleave', 'drop'].forEach(function(ev){
        dropZone.addEventListener(ev, function(e){
            e.preventDefault();
            e.stopPropagation();
            dropZone.style.borderColor = 'var(--border-strong)';
            dropZone.style.background = 'rgba(255,253,249,0.6)';
            dropZone.style.transform = 'scale(1)';
        });
    });

    // Drop handler
    dropZone.addEventListener('drop', function(e){
        e.preventDefault();
        var files = e.dataTransfer.files;
        if (files.length > 0) {
            var file = files[0];
            if (isValidImage(file)) {
                // Transfer the dropped file to the file input
                var dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;
                showPreview(file);
            } else {
                alert('Faqat rasm fayllari qabul qilinadi (JPG, PNG, WEBP, GIF), max 5MB');
            }
        }
    });

    // Also handle paste (Ctrl+V) for screenshots
    document.addEventListener('paste', function(e){
        var items = e.clipboardData && e.clipboardData.items;
        if (!items) return;
        for (var i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                var file = items[i].getAsFile();
                if (file && isValidImage(file)) {
                    var dt = new DataTransfer();
                    dt.items.add(file);
                    fileInput.files = dt.files;
                    showPreview(file);
                    e.preventDefault();
                    break;
                }
            }
        }
    });

    // Remove preview
    removeBtn.addEventListener('click', function(e){
        e.stopPropagation();
        fileInput.value = '';
        dropContent.style.display = '';
        dropPreview.style.display = 'none';
        previewImg.src = '';
    });

    function isValidImage(file) {
        var validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (validTypes.indexOf(file.type) === -1) return false;
        if (file.size > 5 * 1024 * 1024) return false; // Max 5MB
        return true;
    }

    function showPreview(file) {
        var reader = new FileReader();
        reader.onload = function(e){
            previewImg.src = e.target.result;
            previewName.textContent = file.name + ' (' + formatSize(file.size) + ')';
            dropContent.style.display = 'none';
            dropPreview.style.display = '';
        };
        reader.readAsDataURL(file);
    }

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    // Global drag highlight for the whole page
    var dragCounter = 0;
    document.addEventListener('dragenter', function(e){
        dragCounter++;
        if (e.dataTransfer && e.dataTransfer.types.indexOf('Files') !== -1) {
            dropZone.style.borderColor = 'var(--accent)';
            dropZone.style.boxShadow = '0 0 20px rgba(232,168,56,0.2)';
        }
    });
    document.addEventListener('dragleave', function(){
        dragCounter--;
        if (dragCounter === 0) {
            dropZone.style.borderColor = 'var(--border-strong)';
            dropZone.style.boxShadow = 'none';
        }
    });
    document.addEventListener('drop', function(){
        dragCounter = 0;
        dropZone.style.borderColor = 'var(--border-strong)';
        dropZone.style.boxShadow = 'none';
    });

    // Klaviatura qisqa tugmasi: Ctrl+Enter - saqlash
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            document.querySelector('form').submit();
        }
    });
})();
</script>
<?php vpy_panel_foot(); ?>