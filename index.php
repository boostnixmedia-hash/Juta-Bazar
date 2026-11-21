<?php
define('JB_EN', true); // storefront: English by default, বাংলা when the visitor switches
require __DIR__ . '/inc/core.php';
require __DIR__ . '/inc/layout.php';
lang(); // reads ?lang=en|bn and remembers it (English by default)

$route = trim((string)($_GET['route'] ?? ''), '/');
if ($route === '' && isset($_GET['r'])) $route = trim((string)$_GET['r'], '/'); // works even without mod_rewrite
$parts = $route === '' ? [] : explode('/', $route);
$page = $parts[0] ?? '';

switch ($page) {
    case '': home(); break;
    case 'shop': shop(); break;
    case 'c': shop($parts[1] ?? ''); break;
    case 'p': product($parts[1] ?? ''); break;
    case 'cart': cart_page(); break;
    case 'checkout': checkout_page(); break;
    case 'order': order_done($parts[1] ?? ''); break;
    case 'track': track_page(); break;
    case 'login': case 'register': case 'account': case 'logout': header('Location: ' . url('track')); exit; // customer login turned off (owner's request)
    case 'api': api($parts[1] ?? ''); break;
    case 'sitemap.xml': sitemap(); break;
    case 'robots.txt': header('Content-Type: text/plain'); echo "User-agent: *\nDisallow: /admin/\nDisallow: /cart\nDisallow: /checkout\nDisallow: /account\nSitemap: " . full_url('sitemap.xml') . "\n"; break;
    default: not_found();
}

function status_label(string $st): string {
    if (is_bn()) return ORDER_STATUS[$st] ?? $st;
    return ['new' => 'Received', 'confirmed' => 'Confirmed', 'shipped' => 'On the way', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled', 'returned' => 'Returned'][$st] ?? ucfirst($st);
}

function full_url(string $p = ''): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($p);
}

function not_found(): void {
    http_response_code(404);
    page_head(t('Not found', 'পাওয়া যায়নি'));
    echo '<section class="wrap empty"><h1>404</h1><p>' . t('We couldn\'t find this page.', 'এই পেজটি খুঁজে পাওয়া যায়নি।') . '</p><a class="btn" href="' . e(url()) . '">' . t('Back to home', 'হোমে ফিরে যান') . '</a></section>';
    page_foot();
}

/* ================= HOME ================= */
function home(): void {
    $s = settings();
    $cols = collections();
    $byCol = [];
    $st = db()->prepare('SELECT * FROM products WHERE active=1 AND collection_id=? ORDER BY featured DESC, sort, id DESC LIMIT 8');
    foreach ($cols as $c) { $st->execute([$c['id']]); $byCol[$c['id']] = $st->fetchAll(); }
    $colName = []; foreach ($cols as $c) $colName[$c['id']] = col_name($c);
    $featured = db()->query('SELECT * FROM products WHERE active=1 AND featured=1 ORDER BY sort, id DESC LIMIT 8')->fetchAll();
    if (!$featured) $featured = db()->query('SELECT * FROM products WHERE active=1 ORDER BY id DESC LIMIT 8')->fetchAll();
    $heroImg = $s['hero_image'] ? img_url($s['hero_image']) : url('assets/img/demo/hero-full.webp');
    $spot = $featured[0] ?? null;
    page_head('', '', full_url(ltrim(parse_url($heroImg, PHP_URL_PATH) ?: '', '/')));
    ?>
<section class="hx">
  <picture>
    <?php if (!$s['hero_image']): ?><source media="(max-width:700px)" srcset="<?= e(url('assets/img/demo/hero-full-s.webp')) ?>"><?php endif; ?>
    <img class="hx-img" src="<?= e($heroImg) ?>" alt="<?= e($s['shop_name_en']) ?> new collection" fetchpriority="high" width="1600" height="1382">
  </picture>
  <div class="wrap hx-in">
    <span class="hx-kick"><?= e(SL('hero_kicker')) ?></span>
    <h1><?= e(is_bn() ? $s['hero_title'] : $s['hero_title_en']) ?></h1>
    <p><?= e(SL('hero_sub')) ?></p>
    <div class="hx-btns">
      <a class="btn hx-b1" href="<?= e(url('shop')) ?>"><?= e(SL('hero_btn')) ?> <?= icon('arrow', 18) ?></a>
      <?php if ($cols): ?><a class="btn hx-b2" href="#collections"><?= t('Collections', 'কালেকশন') ?></a><?php endif; ?>
    </div>
  </div>
  <?php if ($spot): ?>
  <a class="hx-tag" href="<?= e(url('p/' . $spot['slug'])) ?>"><img src="<?= e(p_first_image($spot)) ?>" alt="" width="64" height="64"><span><small><?= t('Pick of the week', 'এই সপ্তাহের সেরা') ?></small><b><?= e($spot['name']) ?></b><i><?= money($spot['price']) ?></i></span></a>
  <?php endif; ?>
</section>

<?php if ($cols): ?>
<section class="cols" id="collections" aria-label="Collections">
  <div class="wrap">
    <div class="sec-head"><h2><small><?= t('Shop by collection', 'কালেকশন অনুযায়ী') ?></small><?= t('Our Collections', 'আমাদের কালেকশন') ?></h2><a href="<?= e(url('shop')) ?>" class="more"><?= t('View all', 'সব দেখুন') ?> <?= icon('arrow', 16) ?></a></div>
    <div class="col-row">
      <?php foreach ($cols as $c): ?>
      <a class="col-tile" href="<?= e(url('c/' . $c['slug'])) ?>">
        <img src="<?= e(img_url($c['image'])) ?>" alt="<?= e($c['name']) ?>" loading="lazy" width="600" height="800">
        <span><b><?= e(col_name($c)) ?></b><i><?= t('Shop now', 'দেখুন') ?> <?= icon('arrow', 14) ?></i></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($featured): ?>
<section class="sec">
  <div class="wrap">
    <div class="sec-head"><h2><small><?= t('Trending now', 'এখন জনপ্রিয়') ?></small><?= t('Best Sellers', 'বেস্ট সেলার') ?></h2><a href="<?= e(url('shop')) ?>" class="more"><?= t('View all', 'সব দেখুন') ?> <?= icon('arrow', 16) ?></a></div>
    <div class="grid rail cap4"><?php foreach ($featured as $i => $p) echo product_card($p, $colName[$p['collection_id']] ?? '', $i < 2); ?></div>
  </div>
</section>
<?php endif; ?>

<?php $n = 0; foreach ($cols as $c): if (!$c['show_home'] || empty($byCol[$c['id']])) continue; $n++; ?>
<section class="sec col-sec<?= $n % 2 === 0 ? ' flip' : '' ?>" id="col-<?= e($c['slug']) ?>">
  <div class="wrap col-in">
    <a class="col-banner" href="<?= e(url('c/' . $c['slug'])) ?>">
      <img src="<?= e(img_url($c['image'])) ?>" alt="<?= e($c['name']) ?>" loading="lazy" width="800" height="1000">
      <span class="col-banner-txt"><small><?= bn_num(str_pad((string)$n, 2, '0', STR_PAD_LEFT)) ?> / <?= t('Collection', 'কালেকশন') ?></small><h2><?= e(col_name($c)) ?></h2><?php if (col_tag($c)): ?><p><?= e(col_tag($c)) ?></p><?php endif; ?><i class="more"><?= is_bn() ? e(col_name($c)) . ' দেখুন' : 'Shop ' . e(col_name($c)) ?> <?= icon('arrow', 16) ?></i></span>
    </a>
    <div class="grid rail cap4"><?php foreach ($byCol[$c['id']] as $p) echo product_card($p, col_name($c)); ?></div>
  </div>
</section>
<?php if ($n === 2 && trim(SL('promo_title'))): ?>
<section class="promo">
  <img class="promo-bg" src="<?= e($s['promo_image'] ? img_url($s['promo_image']) : url('assets/img/demo/real/frm-3.webp')) ?>" alt="" loading="lazy" width="800" height="800">
  <div class="wrap promo-in">
    <div><small><?= t('Special offer', 'বিশেষ অফার') ?></small><h2><?= e(SL('promo_title')) ?></h2><p><?= e(SL('promo_sub')) ?></p></div>
    <a class="btn btn-line" href="<?= e(url('shop')) ?>"><?= e(SL('promo_btn')) ?> <?= icon('arrow', 18) ?></a>
  </div>
</section>
<?php endif; endforeach; ?>

<section class="sec" id="recent" hidden><div class="wrap">
  <div class="sec-head"><h2><small><?= t('Your history', 'আপনার দেখা') ?></small><?= t('Recently viewed', 'সম্প্রতি দেখেছেন') ?></h2></div>
  <div class="grid rail" id="recent-grid"></div>
</div></section>

<section class="about">
  <div class="wrap about-in">
    <h2><small><?= t('About us', 'আমাদের কথা') ?></small><?= is_bn() ? 'কেন ' . e($s['shop_name']) . '?' : 'Why ' . e($s['shop_name_en']) . '?' ?></h2><span class="orn" aria-hidden="true"></span>
    <p><?= nl2br(e(SL('about'))) ?></p>
  </div>
</section>
<script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@type' => 'ShoeStore', 'name' => $s['shop_name'], 'alternateName' => $s['shop_name_en'], 'url' => full_url(), 'telephone' => $s['phone'], 'address' => $s['address'], 'image' => $heroImg, 'priceRange' => '৳৳'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php
    page_foot();
}

/* ================= SHOP / COLLECTION ================= */
function shop(string $slug = ''): void {
    $cols = collections();
    $colName = []; foreach ($cols as $c) $colName[$c['id']] = col_name($c);
    $col = null;
    if ($slug !== '') {
        foreach ($cols as $c) if ($c['slug'] === $slug) $col = $c;
        if (!$col) { not_found(); return; }
    }
    $q = trim((string)($_GET['q'] ?? ''));
    $sort = $_GET['sort'] ?? 'new';
    $size = trim((string)($_GET['size'] ?? ''));
    $where = ['active=1']; $args = [];
    if ($col) { $where[] = 'collection_id=?'; $args[] = $col['id']; }
    if ($q !== '') { $where[] = '(name LIKE ? OR brand LIKE ? OR tags LIKE ? OR short LIKE ? OR colors LIKE ?)'; for ($i = 0; $i < 5; $i++) $args[] = "%$q%"; }
    if ($size !== '' && ctype_digit($size)) { $where[] = "(',' || REPLACE(sizes,' ','') || ',') LIKE ?"; $args[] = "%,$size,%"; }
    $order = ['new' => 'featured DESC, sort, id DESC', 'low' => 'price ASC', 'high' => 'price DESC', 'off' => '(old_price-price) DESC'][$sort] ?? 'id DESC';
    $st = db()->prepare('SELECT * FROM products WHERE ' . implode(' AND ', $where) . " ORDER BY $order");
    $st->execute($args);
    $items = $st->fetchAll();
    $title = $col ? col_name($col) : ($q !== '' ? t("Results for \"$q\"", "\"$q\" এর ফলাফল") : t('All Shoes', 'সব জুতা'));
    page_head($title, $col ? col_tag($col) : '');
    $base = $col ? url('c/' . $col['slug']) : url('shop');
    ?>
<section class="list-head">
  <div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(url()) ?>"><?= t('Home', 'হোম') ?></a> / <?= $col ? '<a href="' . e(url('shop')) . '">' . t('All Shoes', 'সব জুতা') . '</a> / ' : '' ?><span><?= e($title) ?></span></nav>
    <h1><?= e($title) ?> <small><?= bn_num(count($items)) ?> <?= t('items', 'টি জুতা') ?></small></h1>
    <?php if ($col && col_tag($col)): ?><p><?= e(col_tag($col)) ?></p><?php endif; ?>
    <div class="chips" >
      <a class="chip<?= !$col ? ' on' : '' ?>" href="<?= e(url('shop')) ?>"><?= t('All', 'সব') ?></a>
      <?php foreach ($cols as $c): ?><a class="chip<?= $col && $col['id'] == $c['id'] ? ' on' : '' ?>" href="<?= e(url('c/' . $c['slug'])) ?>"><?= e(col_name($c)) ?></a><?php endforeach; ?>
    </div>
    <form class="filters" method="get" action="<?= e($base) ?>">
      <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
      <label><?= t('Sort', 'সাজান') ?> <select name="sort" onchange="this.form.submit()">
        <?php foreach (['new' => t('Featured', 'বাছাই করা'), 'low' => t('Price: low to high', 'দাম: কম থেকে বেশি'), 'high' => t('Price: high to low', 'দাম: বেশি থেকে কম'), 'off' => t('Biggest discount', 'সবচেয়ে বেশি ছাড়')] as $k => $v): ?><option value="<?= $k ?>"<?= $sort === $k ? ' selected' : '' ?>><?= $v ?></option><?php endforeach; ?>
      </select></label>
      <label><?= t('Size', 'সাইজ') ?> <select name="size" onchange="this.form.submit()"><option value=""><?= t('All sizes', 'সব সাইজ') ?></option>
        <?php for ($z = 36; $z <= 46; $z++): ?><option value="<?= $z ?>"<?= $size === (string)$z ? ' selected' : '' ?>><?= bn_num($z) ?></option><?php endfor; ?>
      </select></label>
    </form>
  </div>
</section>
<section class="sec pt0"><div class="wrap"><h2 class="sr"><?= t('Products', 'জুতা') ?></h2>
  <?php if ($items): ?><div class="grid"><?php foreach ($items as $i => $p) echo product_card($p, $col ? '' : ($colName[$p['collection_id']] ?? ''), $i < 4); ?></div>
  <?php else: ?><div class="empty"><p><?= t('No shoes found right now.', 'এই মুহূর্তে কোনো জুতা পাওয়া যায়নি।') ?></p><a class="btn" href="<?= e(url('shop')) ?>"><?= t('View all shoes', 'সব জুতা দেখুন') ?></a></div><?php endif; ?>
</div></section>
<?php
    page_foot();
}

/* ================= PRODUCT ================= */
function product(string $slug): void {
    $st = db()->prepare('SELECT p.*, c.name AS cname_bn, c.name_en AS cname_en, c.slug AS cslug FROM products p LEFT JOIN collections c ON c.id=p.collection_id WHERE p.slug=? AND p.active=1');
    $st->execute([$slug]);
    $p = $st->fetch();
    if (!$p) { not_found(); return; }
    $p['cname'] = is_bn() ? ($p['cname_bn'] ?: $p['cname_en']) : ($p['cname_en'] ?: $p['cname_bn']);
    db()->prepare('UPDATE products SET views=views+1 WHERE id=?')->execute([$p['id']]);
    $imgs = p_images($p) ?: [''];
    $sizes = list_csv($p['sizes']);
    $colors = list_csv($p['colors']);
    $d = discount_pct($p);
    $s = settings();
    $rel = db()->prepare('SELECT * FROM products WHERE active=1 AND collection_id=? AND id<>? ORDER BY featured DESC, RANDOM() LIMIT 4');
    $rel->execute([$p['collection_id'], $p['id']]);
    $related = $rel->fetchAll();
    page_head($p['name'] . ($p['cname'] ? ' | ' . $p['cname'] : ''), p_short($p), full_url(ltrim(parse_url(p_first_image($p), PHP_URL_PATH) ?: '', '/')));
    $pdata = ['id' => (int)$p['id'], 'name' => $p['name'], 'price' => (int)$p['price'], 'img' => p_first_image($p), 'slug' => $p['slug'], 'stock' => (int)$p['stock']];
    ?>
<section class="pd wrap">
  <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(url()) ?>"><?= t('Home', 'হোম') ?></a> / <?php if ($p['cname']): ?><a href="<?= e(url('c/' . $p['cslug'])) ?>"><?= e($p['cname']) ?></a> / <?php endif; ?><span><?= e($p['name']) ?></span></nav>
  <div class="pd-in">
    <div class="gallery<?= count($imgs) > 1 ? ' multi' : '' ?>">
      <div class="g-main" id="gmain">
        <?php foreach ($imgs as $i => $im): ?><img src="<?= e(img_url($im)) ?>" alt="<?= e($p['name']) ?> <?= t('photo', 'ছবি') ?> <?= $i + 1 ?>" width="800" height="800" <?= $i ? 'loading="lazy"' : 'fetchpriority="high"' ?>><?php endforeach; ?>
      </div>
      <?php if (count($imgs) > 1): ?>
      <div class="g-thumbs"><?php foreach ($imgs as $i => $im): ?><button data-goto="<?= $i ?>" class="<?= $i ? '' : 'on' ?>" aria-label="<?= t('Photo', 'ছবি') ?> <?= $i + 1 ?>"><img src="<?= e(img_url($im)) ?>" alt="" loading="lazy" width="120" height="120"></button><?php endforeach; ?></div>
      <?php endif; ?>
    </div>
    <div class="pd-info">
      <?php if ($p['cname']): ?><a class="card-col" href="<?= e(url('c/' . $p['cslug'])) ?>"><?= e($p['cname']) ?></a><?php endif; ?>
      <h1><?= e($p['name']) ?></h1>
      <?php if ($p['brand']): ?><div class="brand"><?= t('Brand', 'ব্র্যান্ড') ?>: <?= e($p['brand']) ?></div><?php endif; ?>
      <div class="price big"><b><?= money($p['price']) ?></b><?php if ($d): ?><s><?= money($p['old_price']) ?></s><i class="b-off"><?= bn_num($d) ?>% <?= t('OFF', 'ছাড়') ?></i><?php endif; ?></div>
      <?php if (p_short($p)): ?><p class="short"><?= e(p_short($p)) ?></p><?php endif; ?>
      <form id="buy" class="buy" data-product='<?= e(json_encode($pdata, JSON_UNESCAPED_UNICODE)) ?>'>
        <?php if ($colors): ?>
        <fieldset><legend><?= t('Color', 'রং') ?></legend><div class="opts"><?php foreach ($colors as $i => $c): ?><label><input type="radio" name="color" value="<?= e($c) ?>"<?= $i ? '' : ' checked' ?>><span><?= e($c) ?></span></label><?php endforeach; ?></div></fieldset>
        <?php endif; ?>
        <?php if ($sizes): ?>
        <fieldset><legend><?= t('Select size', 'সাইজ বাছুন') ?> <a href="#sizechart" class="link" data-sizeguide><?= t('Size guide', 'সাইজ গাইড') ?></a></legend><div class="opts sizes"><?php foreach ($sizes as $z): ?><label><input type="radio" name="size" value="<?= e($z) ?>"><span><?= e(bn_num($z)) ?></span></label><?php endforeach; ?></div></fieldset>
        <?php endif; ?>
        <div class="qty-row">
          <div class="qty"><button type="button" data-q="-1" aria-label="<?= t('Decrease', 'কমান') ?>"><?= icon('minus', 18) ?></button><input name="qty" type="number" value="1" min="1" max="10" aria-label="<?= t('Quantity', 'পরিমাণ') ?>"><button type="button" data-q="1" aria-label="<?= t('Increase', 'বাড়ান') ?>"><?= icon('plus', 18) ?></button></div>
          <?php if ((int)$p['stock'] > 0 && (int)$p['stock'] <= 5): ?><span class="low"><?= t('Only ' . (int)$p['stock'] . ' left!', 'মাত্র ' . bn_num((int)$p['stock']) . 'টি বাকি!') ?></span><?php endif; ?>
        </div>
        <?php if ((int)$p['stock'] > 0): ?>
        <div class="buy-btns">
          <button class="btn btn-block" name="go" value="buy" type="submit"><?= t('Buy now', 'এখনই কিনুন') ?></button>
          <button class="btn btn-ghost btn-block" name="go" value="cart" type="submit"><?= icon('bag', 18) ?> <?= t('Add to cart', 'কার্টে যোগ করুন') ?></button>
        </div>
        <?php else: ?><div class="btn btn-block btn-dis"><?= t('Sold out', 'স্টক শেষ') ?></div><?php endif; ?>
        <a class="btn btn-wa btn-block" target="_blank" rel="noopener" href="https://wa.me/<?= e(preg_replace('/\D/', '', $s['whatsapp'])) ?>?text=<?= rawurlencode(t('Hi, I want to order: ', 'আমি অর্ডার করতে চাই: ') . "{$p['name']} (" . full_url('p/' . $p['slug']) . ')') ?>"><?= icon('wa', 18) ?> <?= t('Order on WhatsApp', 'হোয়াটসঅ্যাপে অর্ডার করুন') ?></a>
      </form>
      <ul class="perks">
        <li><?= icon('cash', 20) ?> <?= t('Cash on delivery: pay when it reaches you', 'ক্যাশ অন ডেলিভারি: হাতে পেয়ে টাকা দিন') ?></li>
        <li><?= icon('truck', 20) ?> <?= t('Delivery: inside Dhaka', 'ডেলিভারি: ঢাকার ভেতরে') ?> <?= money($s['delivery_in']) ?>, <?= t('outside Dhaka', 'ঢাকার বাইরে') ?> <?= money($s['delivery_out']) ?></li>
        <li><?= icon('swap', 20) ?> <?= t('Easy exchange if the size doesn\'t fit', 'সাইজ না মিললে সহজ এক্সচেঞ্জ') ?></li>
      </ul>
      <details class="acc" open><summary><?= t('Details', 'বিস্তারিত') ?></summary><div class="desc"><?= nl2br(e(p_desc($p) ?: p_short($p))) ?></div></details>
      <details class="acc" id="sizechart"><summary><?= t('Size guide', 'সাইজ গাইড') ?></summary>
        <table class="sz"><thead><tr><th>EU</th><th>UK</th><th><?= t('Foot length (cm)', 'পায়ের দৈর্ঘ্য (সেমি)') ?></th></tr></thead><tbody>
        <?php foreach ([38 => [5, 24], 39 => [6, 24.7], 40 => [6.5, 25.4], 41 => [7, 26], 42 => [8, 26.7], 43 => [9, 27.4], 44 => [9.5, 28]] as $eu => $v): ?><tr><td><?= bn_num($eu) ?></td><td><?= bn_num($v[0]) ?></td><td><?= bn_num($v[1]) ?></td></tr><?php endforeach; ?>
        </tbody></table><p class="muted"><?= t('Not sure? Write your foot length in the order note and we\'ll match it.', 'নিশ্চিত না? অর্ডারের নোটে পায়ের দৈর্ঘ্য লিখে দিন, আমরা মিলিয়ে পাঠাবো।') ?></p></details>
    </div>
  </div>
</section>
<?php if ($related): ?>
<section class="sec"><div class="wrap">
  <div class="sec-head"><h2><small><?= t('You may also like', 'আপনার পছন্দ হতে পারে') ?></small><?= t('More to explore', 'আরও দেখুন') ?></h2></div>
  <div class="grid rail"><?php foreach ($related as $r) echo product_card($r, $p['cname'] ?? ''); ?></div>
</div></section>
<?php endif; ?>
<section class="sec" id="recent" hidden><div class="wrap">
  <div class="sec-head"><h2><small><?= t('Your history', 'আপনার দেখা') ?></small><?= t('Recently viewed', 'সম্প্রতি দেখেছেন') ?></h2></div>
  <div class="grid rail" id="recent-grid"></div>
</div></section>
<div class="sticky-buy only-m"><div><b><?= money($p['price']) ?></b><?php if ($d): ?><s><?= money($p['old_price']) ?></s><?php endif; ?></div><button class="btn" data-scroll-buy><?= t('Buy now', 'এখনই কিনুন') ?></button></div>
<script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $p['name'], 'image' => array_map('img_url', p_images($p)), 'description' => p_short($p), 'brand' => ['@type' => 'Brand', 'name' => $p['brand'] ?: $s['shop_name']], 'category' => $p['cname'],
    'offers' => ['@type' => 'Offer', 'price' => (int)$p['price'], 'priceCurrency' => 'BDT', 'availability' => (int)$p['stock'] > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock', 'url' => full_url('p/' . $p['slug'])]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php
    page_foot();
}

/* ================= CART & CHECKOUT ================= */
function cart_page(): void {
    page_head(t('Cart', 'কার্ট'));
    ?>
<section class="wrap cart-page">
  <h1><?= t('Your Cart', 'আপনার কার্ট') ?></h1><h2 class="sr"><?= t('Items in your cart', 'কার্টের জুতা') ?></h2>
  <div id="cart-root" class="cart-root"><p class="muted"><?= t('Loading...', 'লোড হচ্ছে...') ?></p></div>
</section>
<?php
    page_foot();
}

function checkout_page(): void {
    $s = settings();
    $cu = customer();
    page_head(t('Checkout', 'অর্ডার কনফার্ম'));
    ?>
<section class="wrap checkout">
  <h1><?= t('Confirm your order', 'অর্ডার কনফার্ম করুন') ?></h1>
  <div class="co-in">
    <form id="co-form" class="co-form" novalidate>
      <label><?= t('Your name', 'আপনার নাম') ?> *<input name="name" required maxlength="80" autocomplete="name" placeholder="<?= t('e.g. Rakib Hasan', 'যেমন: রাকিব হাসান') ?>" value="<?= e($cu['name'] ?? '') ?>"></label>
      <label><?= t('Mobile number', 'মোবাইল নাম্বার') ?> *<input name="phone" required inputmode="tel" maxlength="20" autocomplete="tel" placeholder="01XXXXXXXXX" value="<?= e($cu['phone'] ?? '') ?>"></label>
      <label><?= t('Full address', 'সম্পূর্ণ ঠিকানা') ?> *<textarea name="address" required maxlength="300" rows="3" autocomplete="street-address" placeholder="<?= t('House/road, area, thana, district', 'বাসা/রোড, এলাকা, থানা, জেলা') ?>"><?= e($cu['address'] ?? '') ?></textarea></label>
      <fieldset class="area"><legend><?= t('Delivery area', 'ডেলিভারি এলাকা') ?> *</legend>
        <label><input type="radio" name="area" value="in"<?= ($cu['area'] ?? 'in') !== 'out' ? ' checked' : '' ?>><span><?= e(t('Inside Dhaka city', $s['delivery_in_label'])) ?> <b><?= money($s['delivery_in']) ?></b></span></label>
        <label><input type="radio" name="area" value="out"<?= ($cu['area'] ?? '') === 'out' ? ' checked' : '' ?>><span><?= e(t('Outside Dhaka city', $s['delivery_out_label'])) ?> <b><?= money($s['delivery_out']) ?></b></span></label>
      </fieldset>
      <label><?= t('Note (optional)', 'নোট (ঐচ্ছিক)') ?><textarea name="note" maxlength="300" rows="2" placeholder="<?= t('e.g. foot length, delivery time', 'যেমন: পায়ের মাপ, ডেলিভারির সময়') ?>"></textarea></label>
      <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
      <div class="pay"><?= icon('cash', 22) ?> <span><b><?= t('Cash on delivery', 'ক্যাশ অন ডেলিভারি') ?></b><small><?= t('Pay when your order reaches you', 'পণ্য হাতে পেয়ে টাকা পরিশোধ করুন') ?></small></span></div>
      <p class="err" id="co-err" role="alert" hidden></p>
      <button class="btn btn-block" type="submit" id="co-btn"><?= t('Place order', 'অর্ডার কনফার্ম করুন') ?></button>
    </form>
    <aside class="co-sum" id="co-sum" aria-label="<?= t('Order summary', 'অর্ডার সামারি') ?>"><p class="muted"><?= t('Loading...', 'লোড হচ্ছে...') ?></p></aside>
  </div>
</section>
<?php
    page_foot();
}

function order_done(string $code): void {
    $st = db()->prepare('SELECT * FROM orders WHERE code=?');
    $st->execute([$code]);
    $o = $st->fetch();
    $ok = $o && hash_equals((string)($_COOKIE['jb_last'] ?? ''), $o['code']);
    if (!$o) { not_found(); return; }
    $s = settings();
    page_head(t('Order placed', 'অর্ডার সম্পন্ন'));
    ?>
<section class="wrap done">
  <div class="done-ic"><?= icon('check', 44) ?></div>
  <h1><?= t('Thank you! We got your order', 'ধন্যবাদ! আপনার অর্ডার পেয়েছি') ?></h1>
  <p><?= t('Order number', 'অর্ডার নাম্বার') ?>: <b class="code"><?= e($o['code']) ?></b></p>
  <p class="muted"><?= is_bn() ? 'আমাদের প্রতিনিধি শীঘ্রই ' . ($ok ? e(bn_num($o['phone'])) . ' নাম্বারে ' : '') . 'কল করে অর্ডার কনফার্ম করবেন।' : 'Our team will call you' . ($ok ? ' on ' . e($o['phone']) : '') . ' shortly to confirm the order.' ?></p>
  <?php if ($ok): ?>
  <div class="done-box">
    <?php foreach (json_decode($o['items'], true) ?: [] as $it): ?><div class="row"><span><?= e($it['name']) ?> <?= $it['size'] ? '(' . t('size', 'সাইজ') . ' ' . e(bn_num($it['size'])) . ')' : '' ?> × <?= bn_num($it['qty']) ?></span><b><?= money($it['price'] * $it['qty']) ?></b></div><?php endforeach; ?>
    <div class="row"><span><?= t('Delivery', 'ডেলিভারি') ?></span><b><?= money($o['delivery']) ?></b></div>
    <div class="row total"><span><?= t('Total (cash on delivery)', 'মোট (ক্যাশ অন ডেলিভারি)') ?></span><b><?= money($o['total']) ?></b></div>
  </div>
  <?php endif; ?>
  <div class="done-btns">
    <a class="btn btn-wa" target="_blank" rel="noopener" href="https://wa.me/<?= e(preg_replace('/\D/', '', $s['whatsapp'])) ?>?text=<?= rawurlencode(t('My order number is ', 'আমার অর্ডার নাম্বার ') . $o['code']) ?>"><?= icon('wa', 18) ?> <?= t('Message on WhatsApp', 'WhatsApp-এ যোগাযোগ') ?></a>
    <a class="btn btn-ghost" href="<?= e(url('shop')) ?>"><?= t('Continue shopping', 'আরও কেনাকাটা করুন') ?></a>
  </div>
</section>
<?php if ($ok && $s['pixel_id']): ?><script>window.fbq&&fbq('track','Purchase',{value:<?= (int)$o['total'] ?>,currency:'BDT'});</script><?php endif; ?>
<?php
    page_foot();
}

function track_page(): void {
    $code = strtoupper(trim((string)($_GET['code'] ?? '')));
    $phone = preg_replace('/\D/', '', (string)($_GET['phone'] ?? ''));
    $o = null; $tried = $code !== '' && $phone !== '';
    if ($tried && rate_ok('track', 20, 600)) {
        $st = db()->prepare('SELECT * FROM orders WHERE code=?'); $st->execute([$code]); $o = $st->fetch();
        if ($o && substr(preg_replace('/\D/', '', $o['phone']), -10) !== substr($phone, -10)) $o = null;
    }
    page_head(t('Track order', 'অর্ডার ট্র্যাক'));
    $steps = ['new', 'confirmed', 'shipped', 'delivered'];
    ?>
<section class="wrap track">
  <h1><?= t('Track your order', 'অর্ডার ট্র্যাক করুন') ?></h1>
  <form class="track-form" method="get" action="<?= e(url('track')) ?>">
    <input name="code" placeholder="<?= t('Order number (e.g. JB-1001)', 'অর্ডার নাম্বার (যেমন JB-1001)') ?>" value="<?= e($code) ?>" required aria-label="<?= t('Order number', 'অর্ডার নাম্বার') ?>">
    <input name="phone" placeholder="<?= t('Mobile number', 'মোবাইল নাম্বার') ?>" inputmode="tel" value="<?= e($_GET['phone'] ?? '') ?>" required aria-label="<?= t('Mobile number', 'মোবাইল নাম্বার') ?>">
    <button class="btn"><?= t('Check', 'দেখুন') ?></button>
  </form>
  <?php if ($tried && !$o): ?><p class="err"><?= t('No order found with this number and mobile.', 'এই নাম্বার ও মোবাইলে কোনো অর্ডার পাওয়া যায়নি।') ?></p><?php endif; ?>
  <?php if ($o): $idx = array_search($o['status'], $steps, true); ?>
  <div class="track-res">
    <p><?= t('Order', 'অর্ডার') ?> <b><?= e($o['code']) ?></b>: <b><?= e(status_label($o['status'])) ?></b></p>
    <?php if ($idx !== false): ?><ol class="steps"><?php foreach ($steps as $i => $st): ?><li class="<?= $i <= $idx ? 'on' : '' ?>"><?= e(status_label($st)) ?></li><?php endforeach; ?></ol><?php endif; ?>
  </div>
  <?php endif; ?>
</section>
<?php
    page_foot();
}

/* ================= API ================= */
function api(string $what): void {
    if ($what === 'products') {
        $ids = array_slice(array_filter(array_map('intval', explode(',', (string)($_GET['ids'] ?? '')))), 0, 50);
        if (!$ids) json_out([]);
        $st = db()->prepare('SELECT id,name,slug,price,old_price,images,sizes,stock FROM products WHERE active=1 AND id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
        $st->execute($ids);
        $out = [];
        foreach ($st as $p) $out[$p['id']] = ['id' => (int)$p['id'], 'name' => $p['name'], 'slug' => $p['slug'], 'price' => (int)$p['price'], 'img' => p_first_image($p), 'stock' => (int)$p['stock'], 'url' => url('p/' . $p['slug'])];
        json_out($out);
    }
    if ($what === 'order' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $in = json_decode(file_get_contents('php://input') ?: '', true) ?: [];
        if (!empty($in['website'])) json_out(['ok' => false, 'error' => 'blocked'], 400);
        if (!rate_ok('order', 6, 3600)) json_out(['ok' => false, 'error' => t('Too many tries. Please try again a little later.', 'অনেকবার চেষ্টা হয়েছে। একটু পরে আবার চেষ্টা করুন বা WhatsApp-এ যোগাযোগ করুন।')], 429);
        $name = trim(mb_substr((string)($in['name'] ?? ''), 0, 80));
        $phone = preg_replace('/[^\d+]/', '', (string)($in['phone'] ?? ''));
        $digits = preg_replace('/\D/', '', $phone);
        $address = trim(mb_substr((string)($in['address'] ?? ''), 0, 300));
        $note = trim(mb_substr((string)($in['note'] ?? ''), 0, 300));
        $area = ($in['area'] ?? 'in') === 'out' ? 'out' : 'in';
        if (mb_strlen($name) < 2) json_out(['ok' => false, 'error' => t('Please enter your name.', 'আপনার নাম লিখুন।')], 422);
        if (!preg_match('/^(?:\+?88)?01[3-9]\d{8}$/', $digits)) json_out(['ok' => false, 'error' => t('Please enter a valid mobile number (01XXXXXXXXX).', 'সঠিক মোবাইল নাম্বার দিন (01XXXXXXXXX)।')], 422);
        if (mb_strlen($address) < 8) json_out(['ok' => false, 'error' => t('Please enter your full address.', 'সম্পূর্ণ ঠিকানা লিখুন।')], 422);
        $items = []; $sub = 0;
        $st = db()->prepare('SELECT * FROM products WHERE id=? AND active=1');
        foreach (array_slice((array)($in['items'] ?? []), 0, 30) as $it) {
            $st->execute([(int)($it['id'] ?? 0)]);
            $p = $st->fetch();
            if (!$p) continue;
            $qty = max(1, min(10, (int)($it['qty'] ?? 1)));
            $size = mb_substr((string)($it['size'] ?? ''), 0, 10);
            $sizes = list_csv($p['sizes']);
            if ($sizes && !in_array($size, $sizes, true)) json_out(['ok' => false, 'error' => $p['name'] . ': ' . t('please select a size.', 'সাইজ বেছে নিন।')], 422);
            if ((int)$p['stock'] < $qty) json_out(['ok' => false, 'error' => $p['name'] . ': ' . t('not enough stock.', 'এই পরিমাণ স্টকে নেই।')], 422);
            $items[] = ['id' => (int)$p['id'], 'name' => $p['name'], 'size' => $size, 'color' => mb_substr((string)($it['color'] ?? ''), 0, 30), 'qty' => $qty, 'price' => (int)$p['price']];
            $sub += $qty * (int)$p['price'];
        }
        if (!$items) json_out(['ok' => false, 'error' => t('Your cart is empty.', 'কার্ট খালি।')], 422);
        $del = (int)S($area === 'in' ? 'delivery_in' : 'delivery_out');
        if ((int)S('free_over') > 0 && $sub >= (int)S('free_over')) $del = 0;
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            $cu = customer();
            $pdo->prepare('INSERT INTO orders(name,phone,address,area,note,items,subtotal,delivery,total,status,ip,created,updated,customer_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$name, $phone, $address, $area, $note, json_encode($items, JSON_UNESCAPED_UNICODE), $sub, $del, $sub + $del, 'new', client_ip(), $now, $now, $cu['id'] ?? null]);
            if ($cu && $cu['address'] === '') $pdo->prepare('UPDATE customers SET address=?, area=? WHERE id=?')->execute([$address, $area, $cu['id']]);
            $id = (int)$pdo->lastInsertId();
            $code = 'JB-' . (1000 + $id);
            $pdo->prepare('UPDATE orders SET code=? WHERE id=?')->execute([$code, $id]);
            $dec = $pdo->prepare('UPDATE products SET stock=MAX(0,stock-?) WHERE id=?');
            foreach ($items as $it) $dec->execute([$it['qty'], $it['id']]);
            $pdo->commit();
        } catch (Throwable $e) { $pdo->rollBack(); json_out(['ok' => false, 'error' => t('Could not save the order, please try again.', 'অর্ডার সেভ করা যায়নি, আবার চেষ্টা করুন।')], 500); }
        setcookie('jb_last', $code, ['expires' => time() + 86400 * 30, 'path' => base_url(), 'samesite' => 'Lax', 'httponly' => true]);
        if (S('notify_email') && function_exists('mail')) {
            $body = "নতুন অর্ডার $code\nনাম: $name\nফোন: $phone\nঠিকানা: $address\nএলাকা: " . ($area === 'in' ? 'ঢাকার ভেতরে' : 'ঢাকার বাইরে') . "\nনোট: $note\n\n";
            foreach ($items as $it) $body .= "- {$it['name']} | সাইজ {$it['size']} | {$it['color']} | x{$it['qty']} = " . ($it['qty'] * $it['price']) . "\n";
            $body .= "\nমোট: " . ($sub + $del) . " টাকা (ডেলিভারি $del)";
            @mail(S('notify_email'), '=?UTF-8?B?' . base64_encode("নতুন অর্ডার $code") . '?=', $body, "Content-Type: text/plain; charset=UTF-8\r\nFrom: noreply@" . preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
        }
        json_out(['ok' => true, 'code' => $code, 'url' => url('order/' . $code)]);
    }
    json_out(['error' => 'not found'], 404);
}

function sitemap(): void {
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    echo '<url><loc>' . e(full_url()) . '</loc><priority>1.0</priority></url><url><loc>' . e(full_url('shop')) . '</loc></url>';
    foreach (collections() as $c) echo '<url><loc>' . e(full_url('c/' . $c['slug'])) . '</loc></url>';
    foreach (db()->query('SELECT slug,updated FROM products WHERE active=1') as $p) echo '<url><loc>' . e(full_url('p/' . rawurlencode($p['slug']))) . '</loc><lastmod>' . e(substr((string)$p['updated'], 0, 10)) . '</lastmod></url>';
    echo '</urlset>';
}


/* ================= CUSTOMER ACCOUNTS ================= */
function auth_page(string $mode): void {
    if (customer()) { header('Location: ' . url('account')); exit; }
    shop_session();
    $err = ''; $old = ['name' => '', 'phone' => '', 'email' => ''];
    $next = preg_match('~^[a-z/0-9-]*$~', (string)($_GET['next'] ?? '')) ? (string)($_GET['next'] ?? '') : '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $phone = norm_phone((string)($_POST['phone'] ?? ''));
        $pw = (string)($_POST['pw'] ?? '');
        $old = ['name' => trim((string)($_POST['name'] ?? '')), 'phone' => (string)($_POST['phone'] ?? ''), 'email' => trim((string)($_POST['email'] ?? ''))];
        if (!shop_csrf_ok()) $err = 'পেজের সময় শেষ, আবার চেষ্টা করুন।';
        elseif (!rate_ok('auth', 10, 900)) $err = 'অনেকবার চেষ্টা হয়েছে। ১৫ মিনিট পর আবার চেষ্টা করুন।';
        elseif (!preg_match('/^01[3-9]\d{8}$/', $phone)) $err = 'সঠিক মোবাইল নাম্বার দিন (01XXXXXXXXX)।';
        elseif ($mode === 'register') {
            if (mb_strlen($old['name']) < 2) $err = 'আপনার নাম লিখুন।';
            elseif (mb_strlen($pw) < 6) $err = 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের দিন।';
            elseif ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $err = 'ইমেইল ঠিক নেই (না দিলেও চলবে)।';
            else {
                $st = db()->prepare('SELECT id FROM customers WHERE phone=?'); $st->execute([$phone]);
                if ($st->fetch()) $err = 'এই নাম্বারে আগেই অ্যাকাউন্ট আছে। লগইন করুন।';
                else {
                    $now = date('Y-m-d H:i:s');
                    db()->prepare('INSERT INTO customers(name,phone,email,pass,created,last_login) VALUES(?,?,?,?,?,?)')->execute([mb_substr($old['name'], 0, 80), $phone, mb_substr($old['email'], 0, 120), password_hash($pw, PASSWORD_DEFAULT), $now, $now]);
                    $cid = (int)db()->lastInsertId();
                    db()->prepare("UPDATE orders SET customer_id=? WHERE customer_id IS NULL AND REPLACE(REPLACE(phone,'+88',''),'-','')=?")->execute([$cid, $phone]);
                    session_regenerate_id(true); $_SESSION['cid'] = $cid;
                    header('Location: ' . url($next ?: 'account')); exit;
                }
            }
        } else {
            $st = db()->prepare('SELECT * FROM customers WHERE phone=?'); $st->execute([$phone]); $c = $st->fetch();
            if (!$c || !password_verify($pw, (string)$c['pass'])) $err = 'নাম্বার বা পাসওয়ার্ড ভুল।';
            elseif ($c['blocked']) $err = 'এই অ্যাকাউন্টটি বন্ধ আছে। আমাদের সাথে যোগাযোগ করুন।';
            else {
                db()->prepare('UPDATE customers SET last_login=? WHERE id=?')->execute([date('Y-m-d H:i:s'), $c['id']]);
                session_regenerate_id(true); $_SESSION['cid'] = (int)$c['id'];
                header('Location: ' . url($next ?: 'account')); exit;
            }
        }
    }
    $reg = $mode === 'register';
    page_head($reg ? 'অ্যাকাউন্ট খুলুন' : 'লগইন');
    $wa = preg_replace('/\D/', '', S('whatsapp'));
    ?>
<section class="auth">
  <div class="auth-art" aria-hidden="true"><img src="<?= e(S('hero_image') ? img_url(S('hero_image')) : url('assets/img/demo/hero-full.webp')) ?>" alt=""><b><?= e(S('shop_name_en')) ?></b><span><?= $reg ? 'অ্যাকাউন্ট খুললে অর্ডার ট্র্যাক করা আর পরের বার দ্রুত অর্ডার করা যায়।' : 'আবার স্বাগতম! আপনার অর্ডারগুলো দেখুন।' ?></span></div>
  <form method="post" class="auth-form" novalidate>
    <div class="auth-tabs"><a href="<?= e(url('login' . ($next ? '?next=' . $next : ''))) ?>" class="<?= $reg ? '' : 'on' ?>">লগইন</a><a href="<?= e(url('register' . ($next ? '?next=' . $next : ''))) ?>" class="<?= $reg ? 'on' : '' ?>">নতুন অ্যাকাউন্ট</a></div>
    <h1><?= $reg ? 'নতুন অ্যাকাউন্ট খুলুন' : 'আপনার অ্যাকাউন্টে ঢুকুন' ?></h1>
    <?php if ($err): ?><p class="err" role="alert"><?= e($err) ?></p><?php endif; ?>
    <input type="hidden" name="csrf" value="<?= e(shop_csrf()) ?>">
    <?php if ($reg): ?><label>আপনার নাম<input name="name" value="<?= e($old['name']) ?>" required autocomplete="name"></label><?php endif; ?>
    <label>মোবাইল নাম্বার<input name="phone" value="<?= e($old['phone']) ?>" required inputmode="tel" autocomplete="tel" placeholder="01XXXXXXXXX"></label>
    <?php if ($reg): ?><label>ইমেইল (ঐচ্ছিক)<input name="email" type="email" value="<?= e($old['email']) ?>" autocomplete="email"></label><?php endif; ?>
    <label>পাসওয়ার্ড<input name="pw" type="password" required minlength="6" autocomplete="<?= $reg ? 'new-password' : 'current-password' ?>"></label>
    <button class="btn btn-block"><?= $reg ? 'অ্যাকাউন্ট খুলুন' : 'লগইন' ?></button>
    <?php if (!$reg): ?><p class="muted small">পাসওয়ার্ড ভুলে গেছেন? <a class="link" target="_blank" rel="noopener" href="https://wa.me/<?= e($wa) ?>?text=<?= rawurlencode('আমার অ্যাকাউন্টের পাসওয়ার্ড ভুলে গেছি') ?>">WhatsApp-এ জানান</a>, আমরা নতুন পাসওয়ার্ড দিয়ে দেবো।</p><?php endif; ?>
    <p class="muted small">অ্যাকাউন্ট ছাড়াও অর্ডার করা যায়। অ্যাকাউন্ট থাকলে সব অর্ডার এক জায়গায় দেখতে পাবেন।</p>
  </form>
</section>
<?php
    page_foot();
}

function account_page(): void {
    $c = customer();
    if (!$c) { header('Location: ' . url('login?next=account')); exit; }
    $msg = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && shop_csrf_ok()) {
        if (($_POST['do'] ?? '') === 'profile') {
            db()->prepare('UPDATE customers SET name=?, email=?, address=?, area=? WHERE id=?')->execute([mb_substr(trim((string)$_POST['name']), 0, 80) ?: $c['name'], mb_substr(trim((string)$_POST['email']), 0, 120), mb_substr(trim((string)$_POST['address']), 0, 300), ($_POST['area'] ?? 'in') === 'out' ? 'out' : 'in', $c['id']]);
            $msg = 'তথ্য সেভ হয়েছে ✓';
        } elseif (($_POST['do'] ?? '') === 'pw') {
            if (!password_verify((string)$_POST['old'], (string)$c['pass'])) $msg = '!পুরনো পাসওয়ার্ড ভুল।';
            elseif (mb_strlen((string)$_POST['new']) < 6) $msg = '!নতুন পাসওয়ার্ড কমপক্ষে ৬ অক্ষর।';
            else { db()->prepare('UPDATE customers SET pass=? WHERE id=?')->execute([password_hash((string)$_POST['new'], PASSWORD_DEFAULT), $c['id']]); $msg = 'পাসওয়ার্ড বদলানো হয়েছে ✓'; }
        }
        $st = db()->prepare('SELECT * FROM customers WHERE id=?'); $st->execute([$c['id']]); $c = $st->fetch();
    }
    $st = db()->prepare('SELECT * FROM orders WHERE customer_id=? ORDER BY id DESC LIMIT 50'); $st->execute([$c['id']]);
    $orders = $st->fetchAll();
    page_head('আমার অ্যাকাউন্ট');
    $steps = ['new', 'confirmed', 'shipped', 'delivered'];
    ?>
<section class="wrap account">
  <div class="acc-head"><div><small>MY ACCOUNT</small><h1>আসসালামু আলাইকুম, <?= e($c['name']) ?></h1><p class="muted"><?= e(bn_num($c['phone'])) ?></p></div><a class="btn btn-ghost btn-sm" href="<?= e(url('logout')) ?>">লগআউট</a></div>
  <?php if ($msg): ?><p class="<?= $msg[0] === '!' ? 'err' : 'okmsg' ?>"><?= e(ltrim($msg, '!')) ?></p><?php endif; ?>
  <div class="acc-grid">
    <div>
      <h2>আমার অর্ডার <small><?= bn_num(count($orders)) ?>টি</small></h2>
      <?php if (!$orders): ?><div class="sum"><p>এখনও কোনো অর্ডার নেই।</p><a class="btn" href="<?= e(url('shop')) ?>">জুতা দেখুন</a></div><?php endif; ?>
      <?php foreach ($orders as $o): $idx = array_search($o['status'], $steps, true); ?>
      <article class="ord">
        <div class="ord-top"><b><?= e($o['code']) ?></b><span class="muted"><?= e(bn_num(date('d/m/Y', strtotime($o['created'])))) ?></span><span class="ord-st st-<?= e($o['status']) ?>"><?= e(ORDER_STATUS[$o['status']] ?? $o['status']) ?></span></div>
        <ul><?php foreach (json_decode($o['items'], true) ?: [] as $it): ?><li><?= e($it['name']) ?> <?= $it['size'] ? '(' . t('size', 'সাইজ') . ' ' . e(bn_num($it['size'])) . ')' : '' ?> × <?= bn_num($it['qty']) ?></li><?php endforeach; ?></ul>
        <?php if ($idx !== false): ?><ol class="steps"><?php foreach ($steps as $i => $stp): ?><li class="<?= $i <= $idx ? 'on' : '' ?>"><?= e(ORDER_STATUS[$stp]) ?></li><?php endforeach; ?></ol><?php endif; ?>
        <div class="ord-tot">মোট <b><?= money($o['total']) ?></b></div>
      </article>
      <?php endforeach; ?>
    </div>
    <div>
      <form method="post" class="co-form sum"><h2>আমার তথ্য</h2><input type="hidden" name="csrf" value="<?= e(shop_csrf()) ?>"><input type="hidden" name="do" value="profile">
        <label>নাম<input name="name" value="<?= e($c['name']) ?>"></label>
        <label>ইমেইল<input name="email" type="email" value="<?= e($c['email']) ?>"></label>
        <label>ডেলিভারির ঠিকানা<textarea name="address" rows="3"><?= e($c['address']) ?></textarea></label>
        <fieldset class="area"><legend>এলাকা</legend><label><input type="radio" name="area" value="in"<?= $c['area'] !== 'out' ? ' checked' : '' ?>><span><?= e(S('delivery_in_label')) ?></span></label><label><input type="radio" name="area" value="out"<?= $c['area'] === 'out' ? ' checked' : '' ?>><span><?= e(S('delivery_out_label')) ?></span></label></fieldset>
        <button class="btn btn-block">সেভ করুন</button>
      </form>
      <form method="post" class="co-form sum" style="margin-top:16px"><h2>পাসওয়ার্ড বদলান</h2><input type="hidden" name="csrf" value="<?= e(shop_csrf()) ?>"><input type="hidden" name="do" value="pw">
        <label>পুরনো পাসওয়ার্ড<input type="password" name="old" autocomplete="current-password"></label><label>নতুন পাসওয়ার্ড<input type="password" name="new" minlength="6" autocomplete="new-password"></label>
        <button class="btn btn-ghost btn-block">বদলান</button>
      </form>
    </div>
  </div>
</section>
<?php
    page_foot();
}
