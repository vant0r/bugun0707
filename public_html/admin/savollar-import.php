<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_admin('/login.php');

$pdo = vpy_pdo();
$error = '';
$success = '';
$imported = 0;

if (vpy_is_post() && vpy_csrf_check(vpy_post('csrf'))) {
    if (!$pdo) {
        $error = 'Bazaga ulanib bo\'lmadi';
    } else {
        $bilet_id = (int)vpy_post('bilet_id', 1);
        $togri_javoblar = vpy_post('togri_javoblar', ''); // F1,F2,F3... vergul bilan
        $json_text = trim(vpy_post('json_data', ''));

        if (!$json_text) {
            $error = 'JSON maydon bo\'sh';
        } else {
            $data = json_decode($json_text, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = 'JSON formati xato: ' . json_last_error_msg();
            } elseif (empty($data['savollar']) || !is_array($data['savollar'])) {
                $error = 'JSON da "savollar" massivi topilmadi';
            } else {
                // Parse correct answers
                $correct_list = array_map('trim', explode(',', $togri_javoblar));
                $variant_map = ['F1'=>'A','F2'=>'B','F3'=>'C','F4'=>'D','F5'=>'E'];

                $pdo->beginTransaction();
                try {
                    $tartib = 1;
                    foreach ($data['savollar'] as $idx => $item) {
                        $lot = $item['lotincha'] ?? [];
                        $cyr = $item['kirilcha'] ?? [];

                        $savol = $lot['savol'] ?? '';
                        $savol_cyrl = $cyr['savol'] ?? '';
                        $variants_lot = $lot['variantlar'] ?? [];
                        $variants_cyr = $cyr['variantlar'] ?? [];

                        if (!$savol) continue;

                        // Map F1,F2,F3,F4,F5 → A,B,C,D (max 4 for DB)
                        $va = $variants_lot['F1'] ?? '';
                        $vb = $variants_lot['F2'] ?? '';
                        $vc = $variants_lot['F3'] ?? null;
                        $vd = $variants_lot['F4'] ?? null;

                        $va_c = $variants_cyr['F1'] ?? '';
                        $vb_c = $variants_cyr['F2'] ?? '';
                        $vc_c = $variants_cyr['F3'] ?? null;
                        $vd_c = $variants_cyr['F4'] ?? null;

                        // If 5+ variants, merge last ones into D
                        if (!empty($variants_lot['F5'])) {
                            $vd = ($vd ? $vd . ' / ' : '') . $variants_lot['F5'];
                            $vd_c = ($vd_c ? $vd_c . ' / ' : '') . ($variants_cyr['F5'] ?? '');
                        }

                        // Correct answer
                        $correct_raw = $correct_list[$idx] ?? '';
                        $togri = 'A';
                        if ($correct_raw && isset($variant_map[$correct_raw])) {
                            $togri = $variant_map[$correct_raw];
                        } elseif (preg_match('/^[A-D]$/i', $correct_raw)) {
                            $togri = strtoupper($correct_raw);
                        }

                        $st = $pdo->prepare("INSERT INTO test_savollar (bilet_id, tartib, mavzu, qiyinlik, savol, savol_cyrl, variant_a, variant_b, variant_c, variant_d, variant_a_cyrl, variant_b_cyrl, variant_c_cyrl, variant_d_cyrl, togri, holat) VALUES (:bilet_id, :tartib, :mavzu, :qiyinlik, :savol, :savol_cyrl, :va, :vb, :vc, :vd, :va_c, :vb_c, :vc_c, :vd_c, :togri, 'faol')");

                        $st->execute([
                            ':bilet_id' => $bilet_id,
                            ':tartib' => $tartib,
                            ':mavzu' => 'umumiy',
                            ':qiyinlik' => 'orta',
                            ':savol' => $savol,
                            ':savol_cyrl' => $savol_cyrl,
                            ':va' => $va,
                            ':vb' => $vb,
                            ':vc' => $vc,
                            ':vd' => $vd,
                            ':va_c' => $va_c,
                            ':vb_c' => $vb_c,
                            ':vc_c' => $vc_c,
                            ':vd_c' => $vd_c,
                            ':togri' => $togri,
                        ]);

                        $tartib++;
                        $imported++;
                    }
                    $pdo->commit();
                    $success = $imported . ' ta savol muvaffaqiyatli import qilindi (Bilet #' . $bilet_id . ')';
                    vpy_log('import', 'Savollar import', ['bilet_id' => $bilet_id, 'count' => $imported]);
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Bazaga yozishda xato: ' . $e->getMessage();
                }
            }
        }
    }
}

vpy_panel_head('Savollar import', <<<CSS
.import-info{padding:18px;background:var(--blue-soft);border:1px solid var(--border);border-radius:12px;margin-bottom:18px;font-size:0.85rem;color:var(--dark-soft);line-height:1.6}
.import-info code{background:var(--surface);padding:2px 6px;border-radius:4px;font-size:0.82rem;font-family:monospace}
.json-area{width:100%;min-height:350px;padding:16px;border-radius:12px;border:1.5px solid var(--border-strong);background:var(--surface);color:var(--dark);font-family:monospace;font-size:0.82rem;line-height:1.5;resize:vertical}
.json-area:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-glow)}
.togri-area{width:100%;padding:12px 16px;border-radius:12px;border:1.5px solid var(--border-strong);background:var(--surface);color:var(--dark);font-family:monospace;font-size:0.88rem}
.togri-area:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-glow)}
CSS);
vpy_panel_sidebar('savollar', true);
?>
<main class="main">
<?php vpy_panel_topbar('Savollar import', 'JSON formatda savollar yuklash',
    '<a href="/admin/savollar.php" class="btn btn-ghost">Orqaga</a>'
); ?>

<?php if ($success): ?>
<div class="flash success"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg><span><?= e($success) ?></span></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="flash error"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg><span><?= e($error) ?></span></div>
<?php endif; ?>

<div class="card">
    <div class="card-head"><h2>JSON import</h2></div>

    <div class="import-info">
        <strong>Format:</strong> Quyidagi JSON formatda savollarni copy-paste qiling:<br><br>
        <code>{"savollar": [{"lotincha": {"savol": "...", "variantlar": {"F1": "...", "F2": "...", ...}}, "kirilcha": {...}}, ...]}</code><br><br>
        <strong>Variantlar:</strong> F1=A, F2=B, F3=C, F4=D. 5+ variant bo'lsa D ga birlashtiriladi.<br>
        <strong>To'g'ri javoblar:</strong> Vergul bilan F1,F2,F3... yoki A,B,C,D... tartibda (har bir savolga bittadan)
    </div>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">

        <div class="field-row">
            <div class="field">
                <label>Bilet raqami</label>
                <input type="number" name="bilet_id" min="1" max="62" value="<?= (int)vpy_post('bilet_id', 1) ?>" required>
            </div>
            <div class="field">
                <label>To'g'ri javoblar (vergul bilan: F1,F4,F2,F1,...)</label>
                <input type="text" name="togri_javoblar" class="togri-area" placeholder="F4,F2,F4,F1,F3,F2,F2,F4,F1,F2,F2,F4,F2,F4,F2,F1,F1,F3,F1,F4" value="<?= e(vpy_post('togri_javoblar', '')) ?>">
            </div>
        </div>

        <div class="field">
            <label>JSON ma'lumotlar (copy-paste qiling)</label>
            <textarea name="json_data" class="json-area" placeholder='{"savollar": [{"lotincha": {"savol": "...", "variantlar": {"F1": "...", ...}}, "kirilcha": {...}}, ...]}'><?= e(vpy_post('json_data', '')) ?></textarea>
        </div>

        <div style="display:flex;gap:10px;margin-top:16px">
            <button type="submit" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Import qilish
            </button>
            <button type="button" class="btn btn-ghost" onclick="try{JSON.parse(document.querySelector('.json-area').value);alert('JSON to\'g\'ri!')}catch(e){alert('JSON xato: '+e.message)}">
                JSON tekshirish
            </button>
        </div>
    </form>
</div>

</main>
<?php vpy_panel_foot(); ?>