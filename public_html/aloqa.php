<?php
require_once __DIR__ . '/includes/public_layout.php';
require_once __DIR__ . '/includes/notifications.php';

$success = false;
$error = '';
$vals = ['name' => '', 'phone' => '', 'subject' => '', 'message' => ''];

if (vpy_is_post()) {
    if (!vpy_csrf_check(vpy_post('csrf'))) { $error = t('xato_csrf'); }
    else {
        $vals['name'] = vpy_post('name');
        $vals['phone'] = vpy_post('phone');
        $vals['subject'] = vpy_post('subject');
        $vals['message'] = vpy_post('message');
        if (mb_strlen($vals['name'], 'UTF-8') < 2 || mb_strlen($vals['message'], 'UTF-8') < 5) { $error = t('xato_format'); }
        else {
            vpy_log('contact', 'Aloqa xabari', $vals);
            vpy_notify_admin('Yangi aloqa xabari', $vals['name'] . ': ' . $vals['subject']);
            $success = true;
            $vals = ['name' => '', 'phone' => '', 'subject' => '', 'message' => ''];
        }
    }
}
$developer_active = vpy_setting('developer_active', '0') === '1';

/*
 * ====================================================================
 * TA'LIM MUASSASASI XODIMI — quyidagi qiymatlarni haqiqiy ma'lumotlar
 * bilan almashtiring (telefon, telegram, instagram, rasm, tarjimai holi)
 * ====================================================================
 */
$xodim_ism = "Fotimaxon Turdaliyeva";
$xodim_lavozim = "Ta'lim muassasasi ish yurituvchisi";
$xodim_bio = "Avtomaktabning kundalik ish yuritish jarayonlari, hujjatlar aylanishi va o'quvchilar bilan birinchi muloqotni boshqaradi. Har bir nomzodning hujjatlari to'g'ri va o'z vaqtida rasmiylashtirilishi uchun mas'ul.";
$xodim_telefon = "+998701211021";          // haqiqiy raqam bilan almashtiring
$xodim_telegram = "fotima_menedjer";     // @ belgisiz username
$xodim_instagram = "vatanparvar_yaypan";    // @ belgisiz username
$xodim_rasm = "/assets/images/fotimaxon-turdaliyeva.jpg"; // haqiqiy rasm yo'li

vpy_public_head(t('contact_title'), t('contact_subtitle'), <<<CSS
:root{--stamp-gold:#B8893A}
[data-theme="dark"]{--stamp-gold:#D4A94F}
.contact-grid{display:grid;grid-template-columns:1fr 1.3fr;gap:40px;align-items:start;margin-top:24px}
.contact-info{display:flex;flex-direction:column;gap:14px}
.contact-info-card{padding:20px;background:var(--glass-strong);backdrop-filter:blur(20px);border:1px solid var(--border);border-radius:var(--r);display:flex;align-items:flex-start;gap:16px;transition:var(--t)}
.contact-info-card:hover{transform:translateX(3px);border-color:var(--primary)}
.contact-info-ico{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;display:grid;place-items:center;flex-shrink:0;box-shadow:0 4px 12px var(--primary-glow)}
.contact-info-ico svg{width:20px;height:20px}
.contact-info-card h3{font-size:0.95rem;font-weight:700;margin-bottom:3px}
.contact-info-card p,.contact-info-card a{font-size:0.85rem;color:var(--muted);line-height:1.5}
.contact-info-card a:hover{color:var(--primary)}
.contact-form{padding:36px 32px;background:var(--glass-strong);backdrop-filter:blur(20px);border:1px solid var(--border);border-radius:var(--r-lg);box-shadow:var(--shadow)}
.contact-form h2{font-family:var(--serif);font-size:1.4rem;font-weight:600;margin-bottom:6px}
.contact-form .sub{color:var(--muted);font-size:0.85rem;margin-bottom:22px}
.field{margin-bottom:14px}
.field label{display:block;font-size:0.75rem;font-weight:600;color:var(--dark-soft);margin-bottom:5px;text-transform:uppercase;letter-spacing:0.04em}
.field input,.field textarea{width:100%;padding:12px 16px;border-radius:12px;border:1.5px solid var(--border-strong);background:var(--surface);color:var(--dark);font-size:0.92rem;transition:var(--t)}
.field textarea{min-height:120px;resize:vertical}
.field input:focus,.field textarea:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-glow)}
.fld-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.flash{padding:12px 16px;border-radius:12px;margin-bottom:16px;display:flex;align-items:center;gap:8px;font-size:0.88rem}
.flash.success{background:rgba(26,95,180,0.08);color:var(--primary-dark);border:1px solid rgba(26,95,180,0.15)}
.flash.error{background:rgba(220,53,69,0.08);color:#A81D2B;border:1px solid rgba(220,53,69,0.2)}
.flash svg{width:16px;height:16px;flex-shrink:0}
.contact-form .btn{width:100%;padding:15px}
.dev-section{margin-top:60px;padding:40px;background:var(--glass-strong);backdrop-filter:blur(20px);border:1px solid var(--border);border-radius:var(--r-xl);display:flex;align-items:center;gap:28px}
.dev-section .dev-ico{width:60px;height:60px;border-radius:50%;background:var(--blue-soft);color:var(--primary);display:grid;place-items:center;flex-shrink:0;border:2px solid var(--border)}
.dev-section .dev-ico svg{width:28px;height:28px}
.dev-section h3{font-family:var(--serif);font-size:1.2rem;font-weight:700;margin-bottom:3px}
.dev-section .dev-title{font-size:0.82rem;color:var(--primary);font-weight:600;margin-bottom:8px}
.dev-section p{font-size:0.88rem;color:var(--muted);line-height:1.6}
.dev-contacts{display:flex;gap:10px;margin-top:10px}
.dev-contacts a{padding:6px 14px;background:var(--blue-soft);border:1px solid var(--border);border-radius:var(--pill);font-size:0.8rem;font-weight:600;color:var(--primary);transition:var(--t)}
.dev-contacts a:hover{background:var(--primary);color:#fff}
@media (max-width:1024px){.contact-grid{grid-template-columns:1fr}.dev-section{flex-direction:column;text-align:center}}
@media (max-width:640px){.contact-form{padding:26px 20px}.fld-row{grid-template-columns:1fr}}

/* TA'LIM MUASSASASI XODIMI — KATTA, FULL-BLEED "TASDIQLANGAN HUJJAT" BO'LIMI */
/* Diqqat: bu bo'lim ATAYIN doim qoramtir — light/dark rejim almashtirgichiga bog'liq emas,
   shuning uchun var(--dark) emas, mustahkam (fixed) ranglar ishlatiladi */
.staff-feature{position:relative;display:grid;grid-template-columns:1fr 1.15fr;min-height:620px;margin:0 0 90px;background:#10131C;overflow:hidden}
.staff-feature-photo{position:relative;overflow:hidden;background:linear-gradient(135deg,#0D2A4D,#10131C)}
.staff-feature-photo img{width:100%;height:100%;object-fit:cover;display:block;filter:contrast(1.05) saturate(0.96)}
.staff-photo-fallback-big{position:absolute;inset:0;display:none;place-items:center;font-family:var(--serif);font-weight:700;font-size:8rem;color:rgba(255,255,255,0.22)}
.staff-feature-photo::after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,transparent 58%,#10131C 100%)}
.staff-feature-info{position:relative;padding:68px 64px 68px 60px;display:flex;flex-direction:column;justify-content:center;background:linear-gradient(160deg,#10131C 0%,#0D2A4D 145%);color:#fff;overflow:hidden}
.staff-feature-info::before{content:"";position:absolute;inset:0;opacity:0.5;pointer-events:none;background-image:repeating-linear-gradient(118deg,rgba(255,255,255,0.035) 0px,rgba(255,255,255,0.035) 1px,transparent 1px,transparent 9px)}
.sf-eyebrow{position:relative;z-index:2;display:inline-flex;align-items:center;gap:8px;padding:7px 16px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.2);border-radius:var(--pill);font-size:0.72rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--stamp-gold);width:fit-content}
.staff-feature-name{position:relative;z-index:2;font-family:var(--serif);font-weight:700;font-size:clamp(2.4rem,5.2vw,4.4rem);line-height:1.04;margin:20px 0 8px;color:#fff}
.staff-feature-role{position:relative;z-index:2;font-size:1rem;font-weight:700;color:var(--stamp-gold);letter-spacing:0.02em;margin-bottom:30px}
.staff-feature-bio{position:relative;z-index:2;font-size:1rem;line-height:1.75;color:rgba(255,255,255,0.78);max-width:480px;margin-bottom:38px}
.staff-feature-contacts{position:relative;z-index:2;display:flex;gap:12px;flex-wrap:wrap}
.sf-btn{display:inline-flex;align-items:center;gap:9px;padding:14px 24px;border-radius:var(--pill);background:rgba(255,255,255,0.08);border:1.5px solid rgba(255,255,255,0.22);color:#fff;font-size:0.88rem;font-weight:700;transition:var(--t)}
.sf-btn:hover{background:var(--stamp-gold);border-color:var(--stamp-gold);color:#1A140A;transform:translateY(-3px);box-shadow:0 12px 26px rgba(184,137,58,0.35)}
.sf-btn svg{width:17px;height:17px}
.staff-seal{position:absolute;z-index:4;width:148px;height:148px;left:-16px;bottom:44px;border-radius:50%;background:radial-gradient(circle at 35% 30%,#DDB266,var(--stamp-gold) 58%,#8C6A28);display:grid;place-items:center;color:#fff;text-align:center;transform:rotate(-14deg);box-shadow:0 16px 36px rgba(0,0,0,0.4),0 0 0 6px rgba(255,255,255,0.07);border:2px dashed rgba(255,255,255,0.55)}
.staff-seal span{font-size:0.62rem;font-weight:800;letter-spacing:0.08em;line-height:1.5;text-transform:uppercase;padding:0 10px}
.staff-seal small{display:block;font-size:0.5rem;font-weight:600;opacity:0.85;letter-spacing:0.05em;margin-top:2px}
@media (max-width:1024px){
    .staff-feature{grid-template-columns:1fr;min-height:auto;margin:0 0 60px}
    .staff-feature-photo{height:360px}
    .staff-feature-photo::after{background:linear-gradient(180deg,transparent 45%,#10131C 100%)}
    .staff-feature-info{padding:60px 28px 70px}
    .staff-seal{left:24px;top:-64px;bottom:auto;width:118px;height:118px}
}
@media (max-width:560px){
    .staff-feature-name{font-size:clamp(2rem,9vw,2.6rem)}
    .staff-feature-bio{max-width:none}
    .staff-feature-info{padding:60px 22px 60px}
}
CSS);
vpy_public_navbar('aloqa');
?>

<main>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow"><?= e(t('nav_contact')) ?></span>
        <h1 class="h-display" style="margin-top:14px"><?= e(t('contact_title')) ?></h1>
        <p class="lead"><?= e(t('contact_subtitle')) ?></p>
    </div>
</section>

<!-- TA'LIM MUASSASASI XODIMI — katta, full-bleed "tasdiqlangan hujjat" bo'limi -->
<section class="staff-feature reveal">
    <div class="staff-feature-photo">
        <img src="<?= e($xodim_rasm) ?>" alt="<?= e($xodim_ism) ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='grid'">
        <div class="staff-photo-fallback-big" style="display:none"><?= e(vpy_user_initials($xodim_ism)) ?></div>
        <div class="staff-seal">
            <span>TASDIQLANGAN<small>Vatanparvar Yaypan</small></span>
        </div>
    </div>
    <div class="staff-feature-info">
        <span class="sf-eyebrow">TA'LIM MUASSASASI XODIMI</span>
        <h2 class="staff-feature-name"><?= e($xodim_ism) ?></h2>
        <div class="staff-feature-role"><?= e($xodim_lavozim) ?></div>
        <p class="staff-feature-bio"><?= e($xodim_bio) ?></p>
        <div class="staff-feature-contacts">
            <a href="tel:<?= e(preg_replace('/\D/', '', $xodim_telefon)) ?>" class="sf-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                <?= e($xodim_telefon) ?>
            </a>
            <a href="https://t.me/<?= e(ltrim($xodim_telegram, '@')) ?>" target="_blank" class="sf-btn">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                Telegram
            </a>
            <a href="https://instagram.com/<?= e(ltrim($xodim_instagram, '@')) ?>" target="_blank" class="sf-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                Instagram
            </a>
        </div>
    </div>
</section>

<section style="padding-top:10px">
    <div class="container">
        <div class="contact-grid">
            <div class="contact-info">
                <div class="contact-info-card reveal r1">
                    <span class="contact-info-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg></span>
                    <div><h3><?= e(t('footer_phone')) ?></h3><a href="tel:<?= e(preg_replace('/\D/', '', vpy_setting('contact_phone'))) ?>"><?= e(vpy_setting('contact_phone', t('footer_phone_value'))) ?></a></div>
                </div>
                <div class="contact-info-card reveal r2">
                    <span class="contact-info-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
                    <div><h3><?= e(t('footer_email')) ?></h3><a href="mailto:<?= e(vpy_setting('contact_email')) ?>"><?= e(vpy_setting('contact_email', t('footer_email_value'))) ?></a></div>
                </div>
                <div class="contact-info-card reveal r3">
                    <span class="contact-info-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
                    <div><h3><?= e(t('footer_address')) ?></h3><p><?= e(vpy_setting('contact_address', t('footer_address_value'))) ?></p></div>
                </div>
                <div class="contact-info-card reveal r4">
                    <span class="contact-info-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                    <div><h3><?= e(t('footer_hours')) ?></h3><p><?= e(t('footer_hours_value')) ?></p></div>
                </div>
            </div>
            <div class="contact-form reveal r2">
                <h2><?= e(t('contact_title')) ?></h2>
                <p class="sub"><?= e(t('contact_subtitle')) ?></p>
                <?php if ($success): ?>
                <div class="flash success"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg><span><?= e(t('contact_sent')) ?></span></div>
                <?php elseif ($error): ?>
                <div class="flash error"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg><span><?= e($error) ?></span></div>
                <?php endif; ?>
                <form method="post" novalidate>
                    <input type="hidden" name="csrf" value="<?= e(vpy_csrf()) ?>">
                    <div class="fld-row">
                        <div class="field"><label><?= e(t('contact_name')) ?></label><input name="name" type="text" required value="<?= e($vals['name']) ?>"></div>
                        <div class="field"><label><?= e(t('contact_phone')) ?></label><input name="phone" type="tel" required value="<?= e($vals['phone']) ?>"></div>
                    </div>
                    <div class="field"><label><?= e(t('contact_subject')) ?></label><input name="subject" type="text" required value="<?= e($vals['subject']) ?>"></div>
                    <div class="field"><label><?= e(t('contact_message')) ?></label><textarea name="message" required><?= e($vals['message']) ?></textarea></div>
                    <button type="submit" class="btn btn-primary"><?= e(t('contact_send')) ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg></button>
                </form>
            </div>
        </div>
    </div>
</section>

<section style="padding-top:0">
    <div class="container">
        <!-- DEVELOPER SECTION -->
        <?php if ($developer_active && vpy_setting('developer_name')): ?>
        <div class="dev-section reveal">
            <div class="dev-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg></div>
            <div>
                <h3><?= e(vpy_setting('developer_name')) ?></h3>
                <div class="dev-title"><?= e(vpy_setting('developer_title', 'Dasturchi')) ?></div>
                <p><?= e(vpy_setting('developer_description')) ?></p>
                <div class="dev-contacts">
                    <?php if (vpy_setting('developer_phone')): ?><a href="tel:<?= e(preg_replace('/\D/', '', vpy_setting('developer_phone'))) ?>">Qo'ng'iroq</a><?php endif; ?>
                    <?php if (vpy_setting('developer_telegram')): ?><a href="https://t.me/<?= e(ltrim(vpy_setting('developer_telegram'), '@')) ?>">Telegram</a><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
</main>

<?php vpy_public_footer(); ?>