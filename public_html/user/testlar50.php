<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_login('/login.php');

$u = vpy_user();

vpy_panel_head('Biletlar 50', <<<CSS
.bilet-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:16px}
.bilet-card{position:relative;aspect-ratio:1;background:var(--glass-strong);backdrop-filter:blur(30px);border:1px solid var(--border);border-radius:var(--r);padding:18px;display:flex;flex-direction:column;justify-content:space-between;transition:transform var(--t),box-shadow var(--t),border-color var(--t);text-decoration:none;color:inherit;overflow:hidden}
.bilet-card:hover{transform:translateY(-4px);box-shadow:var(--shadow);border-color:var(--primary)}
.bilet-num{font-family:var(--serif);font-size:2.2rem;font-weight:700;line-height:1;letter-spacing:-0.02em;color:var(--primary)}
.bilet-meta{display:flex;flex-direction:column;gap:2px;position:relative;z-index:2}
.bilet-label{font-size:0.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;font-weight:600}
.bilet-count{font-size:0.92rem;color:var(--dark);font-weight:600}
@media (max-width:640px){.bilet-grid{grid-template-columns:repeat(3,1fr);gap:12px}.bilet-card{padding:14px}.bilet-num{font-size:1.6rem}}
@media (max-width:380px){.bilet-grid{grid-template-columns:repeat(2,1fr)}}
CSS);
vpy_panel_sidebar('testlar50', false);
?>

<main class="main">
<?php vpy_panel_topbar('Biletlar 50 · Mashq rejimi', 'Barcha savollar 50 talik guruhlarga bo\'lingan'); ?>

<div class="card">
    <div class="card-head">
        <h2>Biletlar 50 · Mashq rejimi</h2>
        <span class="chip" style="background:rgba(232,168,56,0.15);color:#A87830;font-weight:700">&#x23F1; 60 daqiqa · 50 savol</span>
    </div>
    <p style="color:var(--muted);margin-bottom:18px;font-size:0.9rem;">
        Barcha savollar 50 talik guruhlarga bo'lingan. Natijalar saqlanmaydi — faqat mashq uchun.
    </p>
    <div class="bilet-grid">
        <?php
        $total_bilet50 = vpy_test_bilet50_count();
        for ($i = 1; $i <= $total_bilet50; $i++):
        ?>
            <a href="/user/test.php?type=bilet50&bilet=<?= $i ?>" class="bilet-card">
                <div>
                    <div class="bilet-label">Bilet 50</div>
                    <div class="bilet-num"><?= sprintf('%02d', $i) ?></div>
                </div>
                <div class="bilet-meta">
                    <span class="bilet-count">50 <?= e(t('ticket_count')) ?></span>
                </div>
            </a>
        <?php endfor; ?>
    </div>
</div>

</main>

<?php vpy_panel_foot(); ?>