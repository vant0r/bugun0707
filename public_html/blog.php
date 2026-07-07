<?php
require_once __DIR__ . '/includes/public_layout.php';

$lang = vpy_lang_code();
$is_cyrl = $lang === 'uz_cyrillic';
$slug = vpy_get('slug');

$single = null;
if ($slug) {
    foreach (vpy_read_json('blog', []) as $p) {
        if (($p['slug'] ?? '') === $slug && ($p['status'] ?? '') === 'published') {
            $single = $p;
            $p['views'] = ((int)($p['views'] ?? 0)) + 1;
            vpy_upsert('blog', $p);
            break;
        }
    }
    if (!$single) { http_response_code(404); }
}

if ($single) {
    $title = $is_cyrl && !empty($single['title_cyrl']) ? $single['title_cyrl'] : $single['title'];
    $excerpt = $is_cyrl && !empty($single['excerpt_cyrl']) ? $single['excerpt_cyrl'] : $single['excerpt'];
    $content = $is_cyrl && !empty($single['content_cyrl']) ? $single['content_cyrl'] : $single['content'];
    $cat = $is_cyrl && !empty($single['category_cyrl']) ? $single['category_cyrl'] : ($single['category'] ?? '');
    vpy_public_head($title, $excerpt, <<<CSS
.post-hero{padding:140px 0 50px;text-align:center}
.post-meta{display:flex;align-items:center;justify-content:center;gap:18px;margin:18px 0 30px;flex-wrap:wrap;font-size:0.88rem;color:var(--muted)}
.post-meta .chip{padding:6px 14px;background:var(--glass);border:1px solid var(--border);border-radius:var(--pill);color:var(--primary);font-weight:600}
.post-content{max-width:760px;margin:30px auto 0;padding:48px;background:var(--glass-strong);backdrop-filter:blur(30px);border:1px solid var(--border);border-radius:var(--r-lg);box-shadow:var(--shadow);font-size:1.05rem;line-height:1.8;color:var(--dark-soft)}
.post-content p{margin-bottom:1.2em}
.back-btn{display:inline-flex;align-items:center;gap:8px;margin-bottom:20px;padding:10px 18px;background:var(--glass);border:1px solid var(--border);border-radius:var(--pill);font-size:0.88rem;font-weight:500}
.back-btn:hover{background:var(--glass-strong);transform:translateX(-4px)}
@media (max-width:768px){.post-content{padding:30px 24px}}
CSS);
    vpy_public_navbar('blog');
    ?>
    <main>
    <section class="post-hero">
        <div class="container">
            <a href="/blog.php" class="back-btn">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                <?= e(t('blog_back')) ?>
            </a>
            <div class="post-meta">
                <span class="chip"><?= e($cat) ?></span>
                <span><?= e(vpy_date($single['created_at'], 'd.m.Y')) ?></span>
                <span>·</span>
                <span><?= (int)$single['read_time'] ?> <?= e(t('blog_minutes')) ?></span>
                <span>·</span>
                <span><?= number_format((int)$single['views']) ?> <?= e(t('blog_views')) ?></span>
            </div>
            <h1 class="h-display" style="max-width:920px;margin:0 auto"><?= e($title) ?></h1>
            <p class="lead" style="margin:24px auto 0;text-align:center"><?= e($excerpt) ?></p>
        </div>
    </section>
    <section style="padding:30px 0 80px">
        <div class="container">
            <article class="post-content">
                <?php foreach (preg_split('/\n\n+/u', trim($content)) as $para): ?>
                    <p><?= nl2br(e($para)) ?></p>
                <?php endforeach; ?>
            </article>
        </div>
    </section>
    </main>
    <?php
    vpy_public_footer();
    exit;
}

$posts = array_values(vpy_filter('blog', fn($p) => ($p['status'] ?? '') === 'published'));
usort($posts, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
$page = max(1, (int)vpy_get('p', 1));
$pag = vpy_paginate($posts, 9, $page);

vpy_public_head(t('blog_title'), t('blog_subtitle'), <<<CSS
.posts-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:50px}
.posts-grid .featured{grid-column:span 2;grid-row:span 2}
.post-card{display:flex;flex-direction:column;background:var(--glass);backdrop-filter:blur(30px);border:1px solid var(--border);border-radius:var(--r-lg);padding:28px;transition:transform var(--t),box-shadow var(--t);overflow:hidden;position:relative;min-height:300px}
.post-card.featured{min-height:420px;background:linear-gradient(135deg,var(--dark) 0%,#2A2520 100%);color:#fff;border:1px solid rgba(232,168,56,0.18)}
.post-card.featured::before{content:"";position:absolute;top:-50%;right:-30%;width:80%;height:160%;background:radial-gradient(ellipse,rgba(232,168,56,0.18),transparent 60%);pointer-events:none}
.post-card:hover{transform:translateY(-6px);box-shadow:var(--shadow-lg)}
.post-card .post-meta-row{display:flex;align-items:center;gap:12px;margin-bottom:18px;font-size:0.78rem;color:var(--muted);flex-wrap:wrap;position:relative;z-index:2}
.post-card.featured .post-meta-row{color:rgba(255,253,249,0.7)}
.post-card .chip{padding:5px 12px;background:rgba(13,107,78,0.08);color:var(--primary);border-radius:var(--pill);font-weight:600;font-size:0.72rem}
.post-card.featured .chip{background:rgba(232,168,56,0.18);color:var(--accent)}
.post-card h2{font-family:var(--serif);font-size:1.3rem;font-weight:600;line-height:1.2;margin-bottom:14px;letter-spacing:-0.01em;position:relative;z-index:2}
.post-card.featured h2{font-size:2rem;color:#fff}
.post-card p{font-size:0.92rem;line-height:1.55;color:var(--dark-soft);flex:1;position:relative;z-index:2}
.post-card.featured p{color:rgba(255,253,249,0.7)}
.post-card .read-more{display:inline-flex;align-items:center;gap:6px;margin-top:20px;padding:8px 0;font-size:0.85rem;font-weight:600;color:var(--primary);position:relative;z-index:2}
.post-card.featured .read-more{color:var(--accent)}
.post-card .read-more svg{transition:transform var(--t-bounce)}
.post-card:hover .read-more svg{transform:translateX(4px)}
@media (max-width:1024px){.posts-grid{grid-template-columns:repeat(2,1fr)}.posts-grid .featured{grid-column:span 2}}
@media (max-width:640px){.posts-grid{grid-template-columns:1fr}.posts-grid .featured,.post-card{grid-column:span 1;min-height:auto}.post-card.featured{min-height:300px}.post-card.featured h2{font-size:1.4rem}}
.pagination{display:flex;gap:8px;justify-content:center;margin-top:40px;flex-wrap:wrap}
.pagination a,.pagination span{min-width:42px;height:42px;padding:0 14px;border-radius:14px;display:grid;place-items:center;font-size:0.9rem;font-weight:600;color:var(--dark-soft);background:var(--glass);border:1px solid var(--border)}
.pagination a:hover{background:var(--glass-strong)}
.pagination .active{background:var(--primary);color:#fff;border-color:var(--primary)}
CSS);
vpy_public_navbar('blog');
?>

<main>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow"><?= e(t('nav_blog')) ?></span>
        <h1 class="h-display"><?= e(t('blog_title')) ?></h1>
        <p class="lead"><?= e(t('blog_subtitle')) ?></p>
    </div>
</section>

<section style="padding-top:0">
    <div class="container">
        <?php if (empty($pag['items'])): ?>
            <div style="text-align:center;padding:80px 20px;color:var(--muted)">
                <p><?= e(t('user_no_tests')) ?></p>
            </div>
        <?php else: ?>
        <div class="posts-grid">
            <?php foreach ($pag['items'] as $i => $p):
                $title = $is_cyrl && !empty($p['title_cyrl']) ? $p['title_cyrl'] : $p['title'];
                $excerpt = $is_cyrl && !empty($p['excerpt_cyrl']) ? $p['excerpt_cyrl'] : $p['excerpt'];
                $cat = $is_cyrl && !empty($p['category_cyrl']) ? $p['category_cyrl'] : ($p['category'] ?? '');
                $featured_class = ($i === 0 && $page === 1 && !empty($p['featured'])) ? 'featured' : '';
            ?>
            <a href="/blog.php?slug=<?= e($p['slug']) ?>" class="post-card reveal r<?= min($i + 1, 4) ?> <?= $featured_class ?>">
                <div class="post-meta-row">
                    <span class="chip"><?= e($cat) ?></span>
                    <span><?= e(vpy_date($p['created_at'], 'd.m.Y')) ?></span>
                    <span>·</span>
                    <span><?= (int)$p['read_time'] ?> <?= e(t('blog_minutes')) ?></span>
                </div>
                <h2><?= e($title) ?></h2>
                <p><?= e($excerpt) ?></p>
                <span class="read-more">
                    <?= e(t('blog_read_more')) ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </span>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if ($pag['pages'] > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $pag['pages']; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="?p=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
</main>

<?php vpy_public_footer(); ?>
