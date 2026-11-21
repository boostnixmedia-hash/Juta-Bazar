<?php
// Storefront layout: header, footer, product card.

function icon(string $n, int $s = 22): string {
    $p = [
        'bag' => '<path d="M6 8h12l-1 12H7L6 8z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'home' => '<path d="M4 11 12 4l8 7v9h-5v-6H9v6H4z"/>',
        'grid' => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
        'wa' => '<path d="M4 20l1.3-4A8 8 0 1 1 8 18.7z"/><path d="M9 9.5c.3 2 2.5 4.2 4.5 4.5l1-1 1.8.8c-.2 1.2-1 1.7-2.3 1.7-2.7 0-6-3.3-6-6 0-1.3.5-2.1 1.7-2.3l.8 1.8z"/>',
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
        'truck' => '<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
        'swap' => '<path d="M7 4 3 8l4 4M3 8h14M17 20l4-4-4-4M21 16H7"/>',
        'shield' => '<path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'cash' => '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/>',
        'flame' => '<path d="M12 3c1 3 5 5 5 10a5 5 0 0 1-10 0c0-2 1-3.5 2-4.5 0 2 1 3 2 3 0-3-1-5 1-8.5z"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'fb' => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8.5c0-.3.2-.5.5-.5z"/>',
        'ig' => '<rect x="4" y="4" width="16" height="16" rx="4.5"/><circle cx="12" cy="12" r="3.5"/><circle cx="17" cy="7" r=".6"/>',
        'yt' => '<rect x="3" y="6" width="18" height="12" rx="3.5"/><path d="m10.5 9.5 4 2.5-4 2.5z"/>',
        'tt' => '<path d="M14 4v10.5a3.5 3.5 0 1 1-3.5-3.5M14 4c.5 2.5 2.3 4 5 4"/>',
        'pin' => '<path d="M12 21s-6-5.5-6-11a6 6 0 0 1 12 0c0 5.5-6 11-6 11z"/><circle cx="12" cy="10" r="2.2"/>',
        'check' => '<path d="m5 12 4.5 4.5L19 7"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
        'minus' => '<path d="M6 12h12"/>', 'plus' => '<path d="M12 6v12M6 12h12"/>',
    ][$n] ?? '';
    return '<svg class="ic" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

function logo_html(): string {
    if (S('logo')) return '<img src="' . e(img_url(S('logo'))) . '" alt="' . e(S('shop_name')) . '" class="logo-img">';
    $nm = S('shop_name_en') ?: S('shop_name'); $w = preg_split('/\s+/', trim($nm), 2);
    return '<span class="logo-txt"><b><i>' . e($w[0]) . '</i>' . (isset($w[1]) ? ' ' . e($w[1]) : '') . '</b><small>Footwear</small></span>';
}

function page_head(string $title = '', string $desc = '', string $ogImg = ''): void {
    $s = settings();
    $brand = is_bn() ? $s['shop_name'] : $s['shop_name_en'];
    $full = $title ? "$title | $brand" : $brand . ' | ' . t('Shoes Online in Bangladesh', 'অনলাইনে জুতা কিনুন');
    $desc = $desc ?: SL('meta_desc');
    $cols = collections();
    $acc = preg_match('/^#[0-9a-f]{6}$/i', $s['accent']) ? $s['accent'] : '#8c6a3f';
    ?><!doctype html>
<html lang="<?= is_bn() ? 'bn' : 'en' ?>" class="lang-<?= lang() ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($full) ?></title>
<meta name="description" content="<?= e(mb_substr($desc, 0, 160)) ?>">
<meta name="theme-color" content="#16130f">
<?php $canon = (function_exists('full_url') ? full_url() : '/'); $canon = rtrim($canon, '/') . '/' . ltrim(substr((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), strlen(base_url())), '/'); ?>
<link rel="canonical" href="<?= e($canon) ?>">
<meta property="og:url" content="<?= e($canon) ?>">
<link rel="alternate" hreflang="en" href="<?= e($canon) ?>?lang=en"><link rel="alternate" hreflang="bn" href="<?= e($canon) ?>?lang=bn">
<meta property="og:site_name" content="<?= e($s['shop_name_en']) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta property="og:title" content="<?= e($full) ?>">
<meta property="og:description" content="<?= e(mb_substr($desc, 0, 200)) ?>">
<meta property="og:type" content="website">
<?php if ($ogImg): ?><meta property="og:image" content="<?= e($ogImg) ?>"><?php endif; ?>
<link rel="icon" href="<?= e(S('logo') ? img_url(S('logo')) : url('assets/img/favicon.svg')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Anton&family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,600;1,700&family=Jost:wght@400;500;600&family=Noto+Sans+Bengali:wght@400;500;600&display=swap" media="print" onload="this.media='all'">
<link rel="stylesheet" href="<?= e(url('assets/css/site.css')) ?>?v=<?= JB_VERSION ?>">
<style>:root{--accent:<?= e($acc) ?>}</style>
<?php if ($s['pixel_id']): ?><script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?= e($s['pixel_id']) ?>');fbq('track','PageView');</script><?php endif; ?>
<?php if ($s['ga_id']): ?><script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($s['ga_id']) ?>"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','<?= e($s['ga_id']) ?>');</script><?php endif; ?>
<script>window.JB={lang:<?= json_encode(lang()) ?>,base:<?= json_encode(base_url()) ?>,cur:<?= json_encode($s['currency']) ?>,din:<?= (int)$s['delivery_in'] ?>,dout:<?= (int)$s['delivery_out'] ?>,free:<?= (int)$s['free_over'] ?>,wa:<?= json_encode(preg_replace('/\D/', '', $s['whatsapp'])) ?>}</script>
</head>
<body>
<a class="skip" href="#main"><?= t('Skip to content', 'মূল অংশে যান') ?></a>
<header class="hdr" id="hdr">
  <div class="wrap hdr-in">
    <button class="icon-btn only-m" data-open="#drawer" aria-label="Menu"><?= icon('menu') ?></button>
    <a href="<?= e(url()) ?>" class="logo"><?= logo_html() ?></a>
    <nav class="nav only-d" aria-label="Collections">
      <?php $hr = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/'); $hb = trim(parse_url(base_url(), PHP_URL_PATH) ?: '', '/'); if ($hb !== '' && str_starts_with($hr, $hb)) $hr = trim(substr($hr, strlen($hb)), '/'); $cur = str_starts_with($hr, 'c/') ? substr($hr, 2) : ($GLOBALS['jb_cur_col'] ?? ''); ?>
      <a href="<?= e(url()) ?>"<?= ($hr === '' || $hr === 'index.php') ? ' class="on" aria-current="page"' : '' ?>><?= t('Home', 'হোম') ?></a>
      <?php foreach ($cols as $c): ?><a href="<?= e(url('c/' . $c['slug'])) ?>"<?= $cur === $c['slug'] ? ' class="on" aria-current="page"' : '' ?>><?= e(col_name($c)) ?></a><?php endforeach; ?>
    </nav>
    <div class="hdr-act">
      <form class="search only-d" action="<?= e(url('shop')) ?>" role="search"><?= icon('search', 18) ?><input name="q" placeholder="<?= t('Search shoes...', 'জুতা খুঁজুন...') ?>" aria-label="<?= t('Search', 'খুঁজুন') ?>" value="<?= e($_GET['q'] ?? '') ?>"></form>
      <a class="lang-sw" href="<?= e(lang_url(is_bn() ? 'en' : 'bn')) ?>" hreflang="<?= is_bn() ? 'en' : 'bn' ?>" aria-label="<?= is_bn() ? 'Switch to English' : 'Switch to Bangla' ?>"><?= is_bn() ? 'EN' : 'BN' ?></a>
      <button class="icon-btn only-m" data-open="#searchbox" aria-label="<?= t('Search', 'খুঁজুন') ?>"><?= icon('search') ?></button>
      <a class="icon-btn cart-btn" href="<?= e(url('cart')) ?>" aria-label="<?= t('Cart', 'কার্ট') ?>"><?= icon('bag') ?><span class="cart-count" data-cart-count hidden>0</span></a>
    </div>
  </div>
</header>
<div class="searchbox" id="searchbox" hidden>
  <form action="<?= e(url('shop')) ?>" class="wrap" role="search"><?= icon('search', 20) ?><input name="q" placeholder="<?= t('What are you looking for?', 'কী খুঁজছেন?') ?>" aria-label="<?= t('Search', 'খুঁজুন') ?>"><button type="button" class="icon-btn" data-close="#searchbox" aria-label="Close"><?= icon('x') ?></button></form>
</div>
<div class="drawer" id="drawer" hidden>
  <div class="drawer-bg" data-close="#drawer"></div>
  <aside class="drawer-panel" aria-label="Menu">
    <div class="drawer-top"><a href="<?= e(url()) ?>" class="logo"><?= logo_html() ?></a><button class="icon-btn" data-close="#drawer" aria-label="Close"><?= icon('x') ?></button></div>
    <a class="drawer-link" href="<?= e(url()) ?>"><?= t('Home', 'হোম') ?></a>
    <a class="drawer-link" href="<?= e(url('shop')) ?>"><?= t('All Shoes', 'সব জুতা') ?></a>
    <?php foreach ($cols as $c): ?>
      <a class="drawer-link with-img" href="<?= e(url('c/' . $c['slug'])) ?>"><img src="<?= e(img_url($c['image'])) ?>" alt="" loading="lazy"><span><?= e(col_name($c)) ?><?php if (col_tag($c)): ?><small><?= e(col_tag($c)) ?></small><?php endif; ?></span></a>
    <?php endforeach; ?>
    <a class="drawer-link" href="<?= e(url('track')) ?>"><?= t('Track your order', 'অর্ডার ট্র্যাক করুন') ?></a>
    <a class="drawer-link" href="<?= e(lang_url(is_bn() ? 'en' : 'bn')) ?>"><?= is_bn() ? 'English' : 'Bangla' ?></a>
    <div class="drawer-contact">
      <a href="tel:<?= e($s['phone']) ?>"><?= icon('phone', 18) ?> <?= e($s['phone']) ?></a>
      <a href="https://wa.me/<?= e(preg_replace('/\D/', '', $s['whatsapp'])) ?>" target="_blank" rel="noopener"><?= icon('wa', 18) ?> WhatsApp</a>
    </div>
  </aside>
</div>
<main id="main">
<?php
}

function page_foot(): void {
    $s = settings(); $cols = collections();
    $wa = preg_replace('/\D/', '', $s['whatsapp']);
    ?>
</main>
<section class="trust">
  <div class="wrap trust-in">
    <div><?= icon('cash', 28) ?><b><?= t('Cash on delivery', 'ক্যাশ অন ডেলিভারি') ?></b><span><?= t('Pay when the pair reaches you', 'জুতা হাতে পেয়ে টাকা দিন') ?></span></div>
    <div><?= icon('truck', 28) ?><b><?= t('Fast delivery', 'দ্রুত ডেলিভারি') ?></b><span><?= t('Home delivery all over Bangladesh', 'সারা দেশে হোম ডেলিভারি') ?></span></div>
    <div><?= icon('swap', 28) ?><b><?= t('Easy exchange', 'সহজ এক্সচেঞ্জ') ?></b><span><?= t('Wrong size? Swap it easily', 'সাইজ না মিললে সহজে বদলে নিন') ?></span></div>
    <div><?= icon('shield', 28) ?><b><?= t('Quality checked', 'মান যাচাই করা') ?></b><span><?= t('Every pair inspected before dispatch', 'পাঠানোর আগে প্রতিটি জোড়া চেক করা হয়') ?></span></div>
  </div>
</section>
<footer class="ftr">
  <div class="wrap ftr-in">
    <div class="ftr-brand">
      <a href="<?= e(url()) ?>" class="logo logo-inv"><?= logo_html() ?></a>
      <p><?= e(SL('footer_note')) ?></p>
      <div class="socials">
        <?php foreach (['facebook' => 'fb', 'instagram' => 'ig', 'tiktok' => 'tt', 'youtube' => 'yt'] as $k => $ic): if ($s[$k]): ?>
          <a href="<?= e($s[$k]) ?>" target="_blank" rel="noopener" aria-label="<?= e($k) ?>"><?= icon($ic, 20) ?></a>
        <?php endif; endforeach; ?>
      </div>
    </div>
    <div><p class="fh"><?= t('Collections', 'কালেকশন') ?></p><?php foreach ($cols as $c): ?><a href="<?= e(url('c/' . $c['slug'])) ?>"><?= e(col_name($c)) ?></a><?php endforeach; ?></div>
    <div><p class="fh"><?= t('Help', 'সাহায্য') ?></p><a href="<?= e(url('track')) ?>"><?= t('Track order', 'অর্ডার ট্র্যাক') ?></a><a href="<?= e(url('cart')) ?>"><?= t('Cart', 'কার্ট') ?></a><a href="<?= e(url('shop')) ?>"><?= t('All shoes', 'সব জুতা') ?></a><a href="<?= e(lang_url(is_bn() ? 'en' : 'bn')) ?>"><?= is_bn() ? 'English' : 'Bangla' ?></a></div>
    <div><p class="fh"><?= t('Contact', 'যোগাযোগ') ?></p>
      <a href="tel:<?= e($s['phone']) ?>"><?= icon('phone', 16) ?> <?= e(bn_num($s['phone'])) ?></a>
      <a href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener"><?= icon('wa', 16) ?> WhatsApp</a>
      <?php if ($s['email']): ?><a href="mailto:<?= e($s['email']) ?>"><?= e($s['email']) ?></a><?php endif; ?>
      <span><?= icon('pin', 16) ?> <?= e(SL('address')) ?></span>
    </div>
  </div>
  <div class="wrap ftr-bot"><span>© <?= date('Y') ?> <?= e(is_bn() ? $s['shop_name'] : $s['shop_name_en']) ?>. <?= t('All rights reserved.', 'সর্বস্বত্ব সংরক্ষিত।') ?></span><a class="dev" href="https://www.linkedin.com/in/abid-islam-a926ab2b5/" target="_blank" rel="noopener"><span><?= t('Designed &amp; developed by', 'ডিজাইন ও ডেভেলপ করেছেন') ?></span><b><svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zM3 9h4v12H3zM9 9h3.8v1.7h.1c.5-1 1.8-2 3.8-2 4 0 4.8 2.6 4.8 6V21h-4v-5.6c0-1.3 0-3-1.9-3s-2.2 1.5-2.2 2.9V21H9z"/></svg>Abid Islam</b></a></div>
</footer>
<div class="wa-pop" id="wa-pop" lang="bn" hidden role="dialog" aria-label="WhatsApp chat">
  <div class="wa-pop-head"><span class="wa-pop-av"><?= icon('wa', 22) ?></span><div><b><?= e($s['shop_name_en']) ?></b><small>সাধারণত কয়েক মিনিটে উত্তর দেয়</small></div><button type="button" class="wa-x" data-wa-close aria-label="Close"><?= icon('x', 18) ?></button></div>
  <div class="wa-pop-body"><p class="wa-bubble">আসসালামু আলাইকুম 👋<br><?= e(S('wa_popup_bn') ?: S('wa_popup')) ?></p></div>
  <a class="btn btn-wa btn-block" id="wa-go" target="_blank" rel="noopener" href="https://wa.me/<?= e($wa) ?>?text=<?= rawurlencode('আসসালামু আলাইকুম, একটা জুতা সম্পর্কে জানতে চাই।') ?>"><?= icon('wa', 18) ?> হোয়াটসঅ্যাপে কথা বলুন</a>
</div>
<script>window.JB_WAPOP=<?= (int)($s['wa_popup_sec'] ?? 6) ?>;</script>
<a class="wa-float" href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="<?= t('Message us on WhatsApp', 'হোয়াটসঅ্যাপে মেসেজ দিন') ?>"><?= icon('wa', 28) ?></a>
<?php $rt = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/'); $bp = trim(parse_url(base_url(), PHP_URL_PATH) ?: '', '/'); if ($bp !== '' && str_starts_with($rt, $bp)) $rt = trim(substr($rt, strlen($bp)), '/'); ?>
<nav class="tabbar only-m" aria-label="Bottom menu">
  <a href="<?= e(url()) ?>"<?= $rt === '' || $rt === 'index.php' ? ' class="on"' : '' ?>><?= icon('home') ?><span><?= t('Home', 'হোম') ?></span></a>
  <a href="<?= e(url('shop')) ?>"<?= preg_match('#^(shop|c/|p/)#', $rt) ? ' class="on"' : '' ?>><?= icon('grid') ?><span><?= t('Shop', 'শপ') ?></span></a>
  <a href="<?= e(url('cart')) ?>" class="cart-btn<?= str_starts_with($rt, 'cart') || str_starts_with($rt, 'checkout') ? ' on' : '' ?>"><?= icon('bag') ?><span><?= t('Cart', 'কার্ট') ?></span><i class="cart-count" data-cart-count hidden>0</i></a>
  <a class="tb-wa" href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener"><i><?= icon('wa', 22) ?></i><span>WhatsApp</span></a>
</nav>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="<?= e(url('assets/js/site.js')) ?>?v=<?= JB_VERSION ?>" defer></script>
</body>
</html>
<?php
}

/** Small colour swatches from colour names like "Black/White" or "কালো, সাদা". */
function color_dots(string $colors): string {
    static $M = ['black' => '#161616', 'কালো' => '#161616', 'white' => '#f7f7f5', 'সাদা' => '#f7f7f5', 'grey' => '#8d8f93', 'gray' => '#8d8f93', 'ধূসর' => '#8d8f93', 'silver' => '#c4c6c9',
        'brown' => '#6b4226', 'বাদামি' => '#6b4226', 'tan' => '#b07a4a', 'camel' => '#b98a55', 'beige' => '#d9c6a5', 'cream' => '#efe6d2', 'red' => '#c2272d', 'লাল' => '#c2272d', 'maroon' => '#6d1a24', 'wine' => '#6d1a24',
        'blue' => '#2a5bd7', 'নীল' => '#2a5bd7', 'navy' => '#1d2b4f', 'denim' => '#4a6a8f', 'green' => '#2f7d4b', 'সবুজ' => '#2f7d4b', 'olive' => '#6b6b3a', 'yellow' => '#e8c23a', 'হলুদ' => '#e8c23a',
        'orange' => '#e36d2b', 'pink' => '#e58fb0', 'purple' => '#6e4aa8', 'gold' => '#c9a46a', 'coffee' => '#4b3221', 'chocolate' => '#4b2e1e', 'multicolor' => 'conic-gradient(#c2272d,#e8c23a,#2f7d4b,#2a5bd7,#c2272d)'];
    $out = []; $seen = [];
    foreach (preg_split('/[\/,،&+]|\sand\s/u', mb_strtolower($colors)) as $w) {
        $w = trim($w); if ($w === '') continue;
        foreach ($M as $k => $hex) if (mb_strpos($w, $k) !== false && !isset($seen[$hex])) { $seen[$hex] = 1; $out[] = '<i style="background:' . $hex . '" title="' . e($w) . '"></i>'; break; }
        if (count($out) >= 4) break;
    }
    return implode('', $out);
}

/** Auto-sliding offer banners (Admin > Settings > Offer banners). */
function offer_slides(): array {
    $o = [];
    for ($i = 1; $i <= 7; $i++) {
        if (S("of{$i}_on") !== '1' || trim(S("of{$i}_l1") . S("of{$i}_big")) === '') continue;
        $img = S("of{$i}_img"); $lk = trim(S("of{$i}_link"));
        $big = S("of{$i}_big"); $top = S("of{$i}_top"); $end = S("of{$i}_end");
        if (strtolower(trim($big)) === 'auto') { // real biggest discount in the linked collection (or the whole shop)
            $cs = preg_match('#^c/([\w-]+)#', $lk, $mm) ? $mm[1] : '';
            $st = db()->prepare('SELECT MAX(CASE WHEN p.old_price>p.price AND p.price>0 THEN ROUND(100-p.price*100.0/p.old_price) ELSE 0 END) FROM products p LEFT JOIN collections c ON c.id=p.collection_id WHERE p.active=1' . ($cs ? ' AND c.slug=?' : ''));
            $st->execute($cs ? [$cs] : []); $mx = (int)$st->fetchColumn();
            if ($mx > 0) $big = $mx . '%'; else { $top = 'NEW'; $big = 'IN'; $end = 'SHOP'; }
        }
        $o[] = ['l1' => S("of{$i}_l1"), 'l2' => S("of{$i}_l2"), 'top' => $top, 'big' => $big, 'end' => $end, 'theme' => S("of{$i}_theme") ?: 'red',
            'img' => $img ? (str_starts_with($img, 'assets/') ? url($img) : img_url($img)) : '', 'link' => $lk === '' ? url('shop') : (preg_match('#^https?://#', $lk) ? $lk : url(ltrim($lk, '/')))];
    }
    return $o;
}
function offer_slider(): string {
    $sl = offer_slides(); if (!$sl) return '';
    $h = '<section class="ofs" aria-label="Offers" data-ofs><div class="ofs-track">';
    foreach ($sl as $i => $o) {
        $h .= '<a class="of of-' . e($o['theme']) . ($i ? '' : ' on') . '" href="' . e($o['link']) . '"' . ($i ? ' aria-hidden="true" tabindex="-1"' : '') . '>'
            . '<span class="of-l"><span class="of-rays"></span><b class="of-t"><span>' . e($o['l1']) . '</span>' . ($o['l2'] !== '' ? '<span>' . e($o['l2']) . '</span>' : '') . '</b>'
            . '<span class="of-brand">' . e(S('shop_name_en') ?: 'JUTAR BAZAR') . '</span>' . (stripos($o['l1'] . $o['l2'], 'cash') === false ? '<span class="of-cod">CASH ON<br>DELIVERY<small>ALL OVER BD</small></span>' : '') . '</span>'
            . '<span class="of-r"><span class="of-box"><i class="of-top">' . e($o['top']) . '</i><strong>' . e($o['big']) . '</strong>' . ($o['end'] !== '' ? '<em>' . e($o['end']) . '</em>' : '') . '</span>' . (S('of_note') !== '' ? '<small class="of-note">' . e(S('of_note')) . '</small>' : '') . '</span>'
            . ($o['img'] ? '<img class="of-img" src="' . e($o['img']) . '" alt="" ' . ($i ? 'decoding="async"' : 'fetchpriority="high"') . '>' : '')
            . '</a>';
    }
    $h .= '</div>';
    if (count($sl) > 1) { $h .= '<div class="ofs-dots">'; foreach ($sl as $i => $o) $h .= '<button type="button" aria-label="Offer ' . ($i + 1) . '"' . ($i ? '' : ' class="on"') . '></button>'; $h .= '</div>'; }
    return $h . '</section>';
}

function product_card(array $p, string $colName = '', bool $eager = false): string {
    $d = discount_pct($p);
    $img = p_first_image($p);
    $imgs = p_images($p);
    $alt = isset($imgs[1]) ? img_url($imgs[1]) : '';
    $h = '<article class="card">';
    $h .= '<a class="card-img" href="' . e(url('p/' . $p['slug'])) . '">';
    $h .= '<img src="' . e($img) . '" alt="' . e($p['name']) . '" ' . ($eager ? 'fetchpriority="high"' : 'loading="lazy"') . ' decoding="async" width="600" height="600">';
    if ($alt) $h .= '<img class="alt" src="' . e($alt) . '" alt="" loading="lazy" width="600" height="600">';
    $h .= '<span class="badges">' . ($d ? '<i class="b-off">-' . bn_num($d) . '%</i>' : '') . ($p['is_new'] ? '<i class="b-new">' . t('New', 'নতুন') . '</i>' : '') . ((int)$p['stock'] <= 0 ? '<i class="b-out">' . t('Sold out', 'স্টক শেষ') . '</i>' : '') . '</span>';
    $h .= '</a><div class="card-body">';
    if ($colName) $h .= '<span class="card-col">' . e($colName) . '</span>';
    $st = (int)$p['stock'];
    $h .= '<h3><a href="' . e(url('p/' . $p['slug'])) . '">' . e($p['name']) . '</a></h3>';
    $h .= '<div class="price"><b>' . money($p['price']) . '</b>' . ($d ? '<s>' . money($p['old_price']) . '</s>' : '') . '</div>';
    $dots = color_dots($p['colors'] ?? '');
    if ($dots) $h .= '<span class="dots">' . $dots . '</span>';
    if (!empty($p['featured']) && $st > 3) $h .= '<span class="hot">' . icon('flame', 15) . t('Best seller', 'বেস্ট সেলার') . '</span>';
    if ($st > 0 && $st <= 3) $h .= '<span class="low">' . t('Only ' . $st . ' left', 'মাত্র ' . bn_num($st) . 'টি বাকি') . '</span>';
    $h .= '</div></article>';
    return $h;
}
