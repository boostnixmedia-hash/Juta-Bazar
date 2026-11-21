<?php
// Jutar Bazar admin panel. One file, Bangla UI. Password is set on the first visit.
require dirname(__DIR__) . '/inc/core.php';
require dirname(__DIR__) . '/inc/caption.php';

session_name('jbadmin');
session_set_cookie_params(['lifetime' => 0, 'path' => base_url(), 'httponly' => true, 'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

$a = preg_replace('/[^a-z_]/', '', (string)($_GET['a'] ?? 'dash'));
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

function csrf(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_ok(): bool { $t = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF'] ?? ''; return is_string($t) && hash_equals(csrf(), $t); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf()) . '">'; }
function aurl(string $a = 'dash', array $q = []): string { return url('admin/') . '?' . http_build_query(['a' => $a] + $q); }
function go(string $a = 'dash', array $q = [], string $flash = ''): never { if ($flash) $_SESSION['flash'] = $flash; header('Location: ' . aurl($a, $q)); exit; }
function logged(): bool { return !empty($_SESSION['ok']) && ($_SESSION['ua'] ?? '') === substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 120); }

/* ---------- auth ---------- */
$hash = S('admin_hash');
if ($a === 'logout') { $_SESSION = []; session_destroy(); header('Location: ' . aurl('login')); exit; }
if (!logged()) {
    $err = '';
    if ($isPost && csrf_ok()) {
        $pw = (string)($_POST['pw'] ?? '');
        if ($hash === '') {
            if (mb_strlen($pw) < 6) $err = 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।';
            elseif ($pw !== ($_POST['pw2'] ?? '')) $err = 'দুইবার একই পাসওয়ার্ড লিখুন।';
            else { set_setting('admin_hash', password_hash($pw, PASSWORD_DEFAULT)); $ok = true; }
        } else {
            if (!rate_ok('login', 5, 600)) $err = 'অনেকবার ভুল হয়েছে। ১০ মিনিট পর চেষ্টা করুন।';
            elseif (password_verify($pw, $hash)) $ok = true;
            else $err = 'পাসওয়ার্ড ভুল।';
        }
        if (!empty($ok)) {
            session_regenerate_id(true);
            $_SESSION['ok'] = 1; $_SESSION['ua'] = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 120);
            db()->prepare('DELETE FROM hits WHERE ip=? AND kind=?')->execute([client_ip(), 'login']);
            go('dash');
        }
    }
    admin_head('লগইন', true);
    ?>
    <div class="login">
      <form method="post" class="box">
        <div class="lg-logo"><img src="<?= e(S('logo') ? img_url(S('logo')) : url('assets/img/favicon.svg')) ?>" alt=""><b><?= e(S('shop_name')) ?></b><small>অ্যাডমিন প্যানেল</small></div>
        <?php if ($hash === ''): ?><p class="note">প্রথমবার ঢুকছেন। এখনই একটা পাসওয়ার্ড ঠিক করুন, এরপর থেকে এটা দিয়েই ঢুকবেন।</p><?php endif; ?>
        <?php if ($err): ?><p class="alert bad"><?= e($err) ?></p><?php endif; ?>
        <?= csrf_field() ?>
        <label>পাসওয়ার্ড<input type="password" name="pw" required autofocus autocomplete="<?= $hash ? 'current-password' : 'new-password' ?>"></label>
        <?php if ($hash === ''): ?><label>আবার পাসওয়ার্ড<input type="password" name="pw2" required autocomplete="new-password"></label><?php endif; ?>
        <button class="btn full"><?= $hash ? 'ঢুকুন' : 'পাসওয়ার্ড সেভ করে ঢুকুন' ?></button>
      </form>
    </div>
    <?php
    admin_foot(); exit;
}
if ($isPost && !csrf_ok()) { http_response_code(400); exit('সেশন শেষ হয়ে গেছে। পেজ রিফ্রেশ করে আবার চেষ্টা করুন।'); }

/* ---------- JSON actions ---------- */
if ($a === 'upload' && $isPost) {
    $f = $_FILES['file'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK) json_out(['ok' => false, 'error' => 'আপলোড হয়নি (ফাইল খুব বড় হতে পারে)।'], 400);
    $path = save_image($f['tmp_name']);
    if (!$path) json_out(['ok' => false, 'error' => 'এটা ছবি নয় বা ফরম্যাট সাপোর্ট করে না।'], 400);
    json_out(['ok' => true, 'path' => $path, 'url' => img_url($path)]);
}
if ($a === 'caption' && $isPost) {
    $cols = array_column(collections(false), null, 'id');
    $c = $cols[(int)($_POST['collection_id'] ?? 0)] ?? null;
    $in = ['name' => trim((string)($_POST['name'] ?? '')), 'collection' => $c['name'] ?? '', 'collection_slug' => $c['slug'] ?? '', 'brand' => trim((string)($_POST['brand'] ?? '')),
        'price' => (int)($_POST['price'] ?? 0), 'old_price' => (int)($_POST['old_price'] ?? 0), 'sizes' => (string)($_POST['sizes'] ?? ''), 'colors' => (string)($_POST['colors'] ?? ''), 'notes' => mb_substr((string)($_POST['notes'] ?? ''), 0, 300)];
    $b64 = ''; $mime = 'image/jpeg';
    $img = (string)($_POST['image'] ?? '');
    if ($img !== '' && !preg_match('~^https?://~', $img)) {
        $fp = realpath(JB_ROOT . '/' . ltrim($img, '/'));
        if ($fp && strpos($fp, realpath(JB_ROOT)) === 0 && is_file($fp) && filesize($fp) < 4_000_000) {
            $b64 = base64_encode((string)file_get_contents($fp));
            $mime = (string)(@getimagesize($fp)['mime'] ?? 'image/jpeg');
        }
    }
    $err = '';
    $out = null;
    $tone = in_array($_POST['tone'] ?? '', ['premium', 'offer', 'festive', 'casual'], true) ? $_POST['tone'] : 'premium';
    if (($_POST['mode'] ?? 'ai') !== 'template') $out = caption_gemini($in, $b64, $mime, $err, $tone);
    if ($out) $out += array_diff_key(caption_template($in, null, $tone), $out); // fill anything the AI skipped
    if (!$out) { $out = caption_template($in, null, $tone); $out['note'] = S('gemini_key') ? 'AI এখন সাড়া দেয়নি, তাই টেমপ্লেট দিয়ে লেখা হলো।' : 'টেমপ্লেট দিয়ে লেখা হলো। আরও ভালো লেখার জন্য সেটিংসে ফ্রি Gemini key দিন।'; }
    json_out(['ok' => true] + $out);
}
if ($a === 'gemini_test' && $isPost) {
    $err = '';
    $r = caption_gemini(['name' => 'Test Runner', 'collection' => 'রানিং শু', 'brand' => '', 'price' => 2500, 'old_price' => 0, 'sizes' => '40,41,42', 'colors' => 'কালো'], '', 'image/jpeg', $err);
    json_out($r ? ['ok' => true, 'sample' => $r['short']] : ['ok' => false, 'error' => $err ?: 'কাজ করেনি']);
}

function save_image(string $tmp, int $max = 1600): ?string {
    $info = @getimagesize($tmp);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) return null;
    $dir = 'uploads/' . date('Y/m');
    if (!is_dir(JB_ROOT . "/$dir")) mkdir(JB_ROOT . "/$dir", 0755, true);
    $name = bin2hex(random_bytes(8));
    if (function_exists('imagecreatefromstring')) {
        $src = @imagecreatefromstring((string)file_get_contents($tmp));
        if ($src) {
            $w = imagesx($src); $h = imagesy($src);
            $s = min(1, $max / max($w, $h));
            $nw = max(1, (int)round($w * $s)); $nh = max(1, (int)round($h * $s));
            $dst = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, false); imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            if (function_exists('imagewebp')) { $rel = "$dir/$name.webp"; imagewebp($dst, JB_ROOT . "/$rel", 82); }
            else { $rel = "$dir/$name.jpg"; imagejpeg($dst, JB_ROOT . "/$rel", 85); }
            imagedestroy($src); imagedestroy($dst);
            return $rel;
        }
    }
    $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'][$info[2]];
    $rel = "$dir/$name.$ext";
    return move_uploaded_file($tmp, JB_ROOT . "/$rel") || copy($tmp, JB_ROOT . "/$rel") ? $rel : null;
}
function upload_field(string $field): ?string { $f = $_FILES[$field] ?? null; return ($f && $f['error'] === UPLOAD_ERR_OK) ? save_image($f['tmp_name']) : null; }

/* ---------- POST actions ---------- */
if ($isPost) {
    $pdo = db(); $now = date('Y-m-d H:i:s');
    switch ($a) {
        case 'product_save':
            $id = (int)($_POST['id'] ?? 0);
            $name = trim((string)$_POST['name']);
            if ($name === '') go('product', ['id' => $id], '!নাম দিন।');
            $imgs = array_values(array_filter(array_map('strval', (array)($_POST['images'] ?? [])), fn($p) => $p !== '' && (str_starts_with($p, 'uploads/') || str_starts_with($p, 'assets/') || preg_match('~^https?://~', $p))));
            $sizes = implode(',', list_csv(implode(',', (array)($_POST['sizes'] ?? [])) . ',' . ($_POST['sizes_extra'] ?? '')));
            $f = [
                'name' => $name, 'collection_id' => (int)$_POST['collection_id'] ?: null, 'brand' => trim((string)$_POST['brand']),
                'price' => max(0, (int)$_POST['price']), 'old_price' => max(0, (int)$_POST['old_price']), 'images' => json_encode($imgs),
                'sizes' => $sizes, 'colors' => implode(', ', list_csv((string)$_POST['colors'])), 'stock' => max(0, (int)$_POST['stock']),
                'short' => trim((string)$_POST['short']), 'description' => trim((string)$_POST['description']), 'fb_post' => trim((string)$_POST['fb_post']),
                'short_bn' => trim((string)($_POST['short_bn'] ?? '')), 'description_bn' => trim((string)($_POST['description_bn'] ?? '')), 'fb_post_en' => trim((string)($_POST['fb_post_en'] ?? '')),
                'tags' => trim((string)$_POST['tags']), 'featured' => isset($_POST['featured']) ? 1 : 0, 'is_new' => isset($_POST['is_new']) ? 1 : 0,
                'active' => isset($_POST['active']) ? 1 : 0, 'updated' => $now,
            ];
            if ($f['description'] === '' && $f['short'] === '') {
                $cc = $f['collection_id'] ? db()->query('SELECT name,slug FROM collections WHERE id=' . (int)$f['collection_id'])->fetch() : null;
                $cap = caption_template(['name' => $name, 'collection' => $cc['name'] ?? '', 'collection_slug' => $cc['slug'] ?? '', 'brand' => $f['brand'], 'price' => $f['price'], 'old_price' => $f['old_price'], 'sizes' => $f['sizes'], 'colors' => $f['colors']]);
                foreach (['short', 'description', 'fb_post', 'tags'] as $k) $f[$k] = $f[$k] ?: $cap[$k];
            }
            if ($f['short_bn'] === '' && $f['description_bn'] === '') {
                $cc = $f['collection_id'] ? db()->query('SELECT name,slug FROM collections WHERE id=' . (int)$f['collection_id'])->fetch() : null;
                $cap = caption_template(['name' => $name, 'collection' => $cc['name'] ?? '', 'collection_slug' => $cc['slug'] ?? '', 'brand' => $f['brand'], 'price' => $f['price'], 'old_price' => $f['old_price'], 'sizes' => $f['sizes'], 'colors' => $f['colors']]);
                foreach (['short_bn', 'description_bn', 'fb_post_en'] as $k) $f[$k] = $f[$k] ?: $cap[$k];
            }
            $f['slug'] = unique_slug('products', trim((string)($_POST['slug'] ?? '')) ?: $name, $id);
            if ($id) {
                $set = implode(',', array_map(fn($k) => "$k=?", array_keys($f)));
                $pdo->prepare("UPDATE products SET $set WHERE id=?")->execute([...array_values($f), $id]);
            } else {
                $f['created'] = $now;
                $pdo->prepare('INSERT INTO products(' . implode(',', array_keys($f)) . ') VALUES(' . implode(',', array_fill(0, count($f), '?')) . ')')->execute(array_values($f));
                $id = (int)$pdo->lastInsertId();
            }
            go(isset($_POST['and_new']) ? 'product' : 'products', isset($_POST['and_new']) ? ['col' => $f['collection_id']] : [], 'প্রোডাক্ট সেভ হয়েছে ✓');
        case 'product_quick':
            $id = (int)$_POST['id'];
            $op = $_POST['op'] ?? '';
            if ($op === 'delete') $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
            elseif ($op === 'active' || $op === 'featured') $pdo->prepare("UPDATE products SET $op=1-$op WHERE id=?")->execute([$id]);
            elseif ($op === 'dup') {
                $p = $pdo->query('SELECT * FROM products WHERE id=' . $id)->fetch();
                if ($p) { unset($p['id']); $p['name'] .= ' (কপি)'; $p['slug'] = unique_slug('products', $p['name']); $p['active'] = 0; $p['views'] = 0; $p['created'] = $p['updated'] = $now;
                    $pdo->prepare('INSERT INTO products(' . implode(',', array_keys($p)) . ') VALUES(' . implode(',', array_fill(0, count($p), '?')) . ')')->execute(array_values($p)); }
            } elseif ($op === 'stock') $pdo->prepare('UPDATE products SET stock=? WHERE id=?')->execute([max(0, (int)$_POST['stock']), $id]);
            elseif ($op === 'price') $pdo->prepare('UPDATE products SET price=?, old_price=? WHERE id=?')->execute([max(0, (int)$_POST['price']), max(0, (int)$_POST['old_price']), $id]);
            go('products', array_filter(['col' => $_POST['col'] ?? '', 'q' => $_POST['q'] ?? '']), $op === 'delete' ? 'ডিলিট হয়েছে' : 'আপডেট হয়েছে ✓');
        case 'col_save':
            $id = (int)($_POST['id'] ?? 0);
            $name = trim((string)$_POST['name']);
            if ($name === '') go('cols', [], '!নাম দিন।');
            $img = upload_field('image_file') ?? trim((string)($_POST['image'] ?? ''));
            $slugBase = trim((string)$_POST['slug']) ?: (trim((string)$_POST['name_en']) ?: $name);
            $vals = [$name, trim((string)$_POST['name_en']), unique_slug('collections', $slugBase, $id), trim((string)$_POST['tagline']), $img, (int)$_POST['sort'], isset($_POST['show_home']) ? 1 : 0, isset($_POST['active']) ? 1 : 0];
            $vals[] = trim((string)($_POST['tagline_bn'] ?? ''));
            if ($id) $pdo->prepare('UPDATE collections SET name=?,name_en=?,slug=?,tagline=?,image=?,sort=?,show_home=?,active=?,tagline_bn=? WHERE id=?')->execute([...$vals, $id]);
            else $pdo->prepare('INSERT INTO collections(name,name_en,slug,tagline,image,sort,show_home,active,tagline_bn) VALUES(?,?,?,?,?,?,?,?,?)')->execute($vals);
            go('cols', [], 'কালেকশন সেভ হয়েছে ✓');
        case 'col_delete':
            $id = (int)$_POST['id'];
            $pdo->prepare('UPDATE products SET collection_id=NULL WHERE collection_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM collections WHERE id=?')->execute([$id]);
            go('cols', [], 'কালেকশন ডিলিট হয়েছে (এর জুতাগুলো রয়ে গেছে, কালেকশন ছাড়া)');
        case 'order_save':
            $id = (int)$_POST['id'];
            $st = $_POST['status'] ?? 'new';
            if (!isset(ORDER_STATUS[$st])) $st = 'new';
            $old = $pdo->query('SELECT status,items FROM orders WHERE id=' . $id)->fetch();
            if ($old && in_array($st, ['cancelled', 'returned'], true) && !in_array($old['status'], ['cancelled', 'returned'], true)) {
                $inc = $pdo->prepare('UPDATE products SET stock=stock+? WHERE id=?');
                foreach (json_decode($old['items'], true) ?: [] as $it) $inc->execute([(int)$it['qty'], (int)$it['id']]);
            }
            $pdo->prepare('UPDATE orders SET status=?, admin_note=?, updated=?, seen=1 WHERE id=?')->execute([$st, trim((string)$_POST['admin_note']), $now, $id]);
            go('order', ['id' => $id], 'অর্ডার আপডেট হয়েছে ✓');
        case 'order_delete':
            $pdo->prepare('DELETE FROM orders WHERE id=?')->execute([(int)$_POST['id']]);
            go('orders', [], 'অর্ডার মুছে ফেলা হয়েছে');
        case 'settings_save':
            $allowed = array_keys(jb_defaults());
            foreach ($allowed as $k) if (isset($_POST[$k]) && !in_array($k, ['logo', 'hero_image', 'promo_image'], true)) set_setting($k, trim((string)$_POST[$k]));
            foreach (['logo', 'hero_image', 'promo_image'] as $k) { if ($p = upload_field($k . '_file')) set_setting($k, $p); elseif (!empty($_POST[$k . '_clear'])) set_setting($k, ''); }
            go('settings', ['tab' => $_POST['tab'] ?? ''], 'সেটিংস সেভ হয়েছে ✓');
        case 'cust':
            $id = (int)$_POST['id']; $op = $_POST['op'] ?? '';
            if ($op === 'block') $pdo->prepare('UPDATE customers SET blocked=1-blocked WHERE id=?')->execute([$id]);
            elseif ($op === 'pw') {
                $np = trim((string)$_POST['newpw']);
                if (mb_strlen($np) < 6) go('customers', [], '!নতুন পাসওয়ার্ড কমপক্ষে ৬ অক্ষর দিন।');
                $pdo->prepare('UPDATE customers SET pass=? WHERE id=?')->execute([password_hash($np, PASSWORD_DEFAULT), $id]);
                go('customers', [], 'পাসওয়ার্ড বদলানো হয়েছে। কাস্টমারকে নতুন পাসওয়ার্ডটা জানিয়ে দিন ✓');
            } elseif ($op === 'delete') $pdo->prepare('DELETE FROM customers WHERE id=?')->execute([$id]);
            go('customers', [], 'আপডেট হয়েছে ✓');
        case 'password':
            if (!password_verify((string)$_POST['old'], S('admin_hash'))) go('settings', ['tab' => 'pw'], '!পুরনো পাসওয়ার্ড ভুল।');
            if (mb_strlen((string)$_POST['new']) < 6 || $_POST['new'] !== $_POST['new2']) go('settings', ['tab' => 'pw'], '!নতুন পাসওয়ার্ড কমপক্ষে ৬ অক্ষর, দুইবার একই হতে হবে।');
            set_setting('admin_hash', password_hash((string)$_POST['new'], PASSWORD_DEFAULT));
            go('settings', ['tab' => 'pw'], 'পাসওয়ার্ড বদলানো হয়েছে ✓');
    }
}

/* ---------- downloads ---------- */
if ($a === 'export') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="orders-' . date('Y-m-d') . '.csv"');
    $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, ['Order', 'Date', 'Name', 'Phone', 'Address', 'Area', 'Items', 'Subtotal', 'Delivery', 'Total', 'Status', 'Note', 'Admin note']);
    foreach (db()->query('SELECT * FROM orders ORDER BY id DESC') as $r) {
        $items = implode(' | ', array_map(fn($i) => "{$i['name']} ({$i['size']}) x{$i['qty']}", json_decode($r['items'], true) ?: []));
        fputcsv($o, [$r['code'], $r['created'], $r['name'], $r['phone'], $r['address'], $r['area'] === 'in' ? 'Inside Dhaka' : 'Outside Dhaka', $items, $r['subtotal'], $r['delivery'], $r['total'], ORDER_STATUS[$r['status']] ?? $r['status'], $r['note'], $r['admin_note']]);
    }
    exit;
}
if ($a === 'backup') {
    if (!class_exists('ZipArchive')) exit('ZipArchive নেই।');
    $tmp = tempnam(sys_get_temp_dir(), 'jb');
    $snap = jb_data_dir() . '/backup-snap.sqlite';
    @unlink($snap); db()->exec("VACUUM INTO " . db()->quote($snap));
    $z = new ZipArchive(); $z->open($tmp, ZipArchive::OVERWRITE);
    $z->addFile($snap, 'shop.sqlite');
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(JB_ROOT . '/uploads', FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) if ($f->isFile()) $z->addFile($f->getPathname(), 'uploads/' . substr($f->getPathname(), strlen(JB_ROOT . '/uploads/')));
    $z->close(); @unlink($snap);
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="jutarbazar-backup-' . date('Y-m-d') . '.zip"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp); @unlink($tmp); exit;
}

/* ---------- pages ---------- */
$newCount = (int)db()->query("SELECT COUNT(*) FROM orders WHERE status='new'")->fetchColumn();

function admin_head(string $title, bool $bare = false): void {
    global $a, $newCount;
    ?><!doctype html><html lang="bn"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title><?= e($title) ?> | অ্যাডমিন</title>
<link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Anek+Bangla:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= e(url('admin/admin.css')) ?>?v=<?= JB_VERSION ?>">
</head><body class="<?= $bare ? 'bare' : '' ?>">
<?php if (!$bare): ?>
<header class="top">
  <button class="menu-btn" onclick="document.body.classList.toggle('nav-open')" aria-label="মেনু">☰</button>
  <a class="brand" href="<?= e(aurl()) ?>"><img src="<?= e(S('logo') ? img_url(S('logo')) : url('assets/img/favicon.svg')) ?>" alt=""><span><?= e(S('shop_name')) ?><small>অ্যাডমিন প্যানেল</small></span></a>
  <a class="view-site" href="<?= e(url()) ?>" target="_blank">সাইট দেখুন ↗</a>
</header>
<nav class="side">
  <?php foreach ([['dash', '🏠', 'ড্যাশবোর্ড'], ['orders', '📦', 'অর্ডার'], ['product', '➕', 'নতুন প্রোডাক্ট পোস্ট'], ['products', '👟', 'সব প্রোডাক্ট'], ['cols', '🗂️', 'কালেকশন'], ['settings', '⚙️', 'সাইট সেটিংস']] as [$k, $ic, $l]): ?>
    <a href="<?= e(aurl($k)) ?>" class="<?= $a === $k || ($k === 'orders' && $a === 'order') ? 'on' : '' ?>"><span><?= $ic ?></span><?= $l ?><?php if ($k === 'orders' && $newCount): ?><i class="pill"><?= bn_num($newCount) ?></i><?php endif; ?></a>
  <?php endforeach; ?>
  <a href="<?= e(aurl('logout')) ?>"><span>🚪</span>লগআউট</a>
</nav>
<div class="nav-bg" onclick="document.body.classList.remove('nav-open')"></div>
<main class="main">
<?php if (!empty($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?><div class="alert <?= $f[0] === '!' ? 'bad' : 'good' ?>"><?= e(ltrim($f, '!')) ?></div><?php endif; ?>
<?php endif;
}
function admin_foot(): void {
    echo '</main><script>window.CSRF=' . json_encode(csrf()) . ';window.AURL=' . json_encode(url('admin/')) . ';window.BASE=' . json_encode(base_url()) . ';</script><script src="' . e(url('admin/admin.js')) . '?v=' . JB_VERSION . '"></script></body></html>';
}
function status_pill(string $s): string { return '<span class="st st-' . e($s) . '">' . e(ORDER_STATUS[$s] ?? $s) . '</span>'; }

switch ($a) {
case 'dash':
    $pdo = db();
    $today = date('Y-m-d'); $month = date('Y-m');
    $stats = [
        ['নতুন অর্ডার', bn_num($pdo->query("SELECT COUNT(*) FROM orders WHERE status='new'")->fetchColumn()), 'orders', 'c1'],
        ['আজকের অর্ডার', bn_num($pdo->query("SELECT COUNT(*) FROM orders WHERE created LIKE '$today%'")->fetchColumn()), 'orders', 'c2'],
        ['এই মাসের বিক্রি', money($pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE created LIKE '$month%' AND status NOT IN ('cancelled','returned')")->fetchColumn()), 'orders', 'c3'],
        ['মোট প্রোডাক্ট', bn_num($pdo->query('SELECT COUNT(*) FROM products')->fetchColumn()), 'products', 'c4'],
    ];
    $low = $pdo->query('SELECT id,name,stock FROM products WHERE active=1 AND stock<=3 ORDER BY stock LIMIT 8')->fetchAll();
    $recent = $pdo->query('SELECT * FROM orders ORDER BY id DESC LIMIT 8')->fetchAll();
    $top = $pdo->query('SELECT id,name,views FROM products ORDER BY views DESC LIMIT 5')->fetchAll();
    $from = date('Y-m-d', strtotime('-29 days'));
    $st = $pdo->prepare("SELECT substr(created,1,10) d, COUNT(*) n, SUM(total) t FROM orders WHERE created>=? AND status NOT IN ('cancelled','returned') GROUP BY d");
    $st->execute([$from]); $byDay = [];
    foreach ($st as $r) $byDay[$r['d']] = $r;
    $days = []; for ($i = 29; $i >= 0; $i--) { $d = date('Y-m-d', strtotime("-$i days")); $days[] = [$d, (int)($byDay[$d]['t'] ?? 0), (int)($byDay[$d]['n'] ?? 0)]; }
    $maxT = max(1, max(array_column($days, 1)));
    $sumT = array_sum(array_column($days, 1)); $sumN = array_sum(array_column($days, 2));
    admin_head('ড্যাশবোর্ড');
    ?>
    <div class="welcome"><div><h1>স্বাগতম 👋</h1><p>আজ <?= bn_num(date('d/m/Y')) ?>। নতুন জুতা পোস্ট করতে নিচের বাটনে চাপুন।</p></div><a class="btn" href="<?= e(aurl('product')) ?>">➕ নতুন প্রোডাক্ট পোস্ট</a></div>
    <div class="stats"><?php foreach ($stats as [$l, $v, $to, $c]): ?><a class="stat <?= $c ?>" href="<?= e(aurl($to)) ?>"><small><?= $l ?></small><b><?= $v ?></b></a><?php endforeach; ?></div>
    <section class="box chart-box"><h2>গত ৩০ দিনের বিক্রি <small>মোট <?= money($sumT) ?> • <?= bn_num($sumN) ?>টি অর্ডার (বাতিল বাদে)</small></h2>
      <div class="chart" role="img" aria-label="গত ৩০ দিনের দৈনিক বিক্রি">
        <?php foreach ($days as [$d, $t, $n]): ?><div class="bar" style="--h:<?= round($t * 100 / $maxT, 1) ?>%" title="<?= e(bn_num(date('d/m', strtotime($d)))) ?>: <?= e(money($t)) ?> (<?= bn_num($n) ?>টি)"><i></i></div><?php endforeach; ?>
      </div>
      <div class="chart-x"><span><?= e(bn_num(date('d/m', strtotime($days[0][0])))) ?></span><span><?= e(bn_num(date('d/m', strtotime($days[14][0])))) ?></span><span>আজ</span></div>
    </section>
    <div class="cols2">
      <section class="box"><h2>সাম্প্রতিক অর্ডার <a href="<?= e(aurl('orders')) ?>" class="sm">সব দেখুন</a></h2>
        <?php if (!$recent): ?><p class="muted">এখনও কোনো অর্ডার আসেনি।</p><?php endif; ?>
        <div class="olist"><?php foreach ($recent as $o): ?><a href="<?= e(aurl('order', ['id' => $o['id']])) ?>" class="orow<?= $o['status'] === 'new' ? ' new' : '' ?>"><b><?= e($o['code']) ?></b><span><?= e($o['name']) ?><small><?= e(bn_num(date('d/m h:i A', strtotime($o['created'])))) ?></small></span><span><?= money($o['total']) ?></span><?= status_pill($o['status']) ?></a><?php endforeach; ?></div>
      </section>
      <div>
        <section class="box"><h2>স্টক কম ⚠️</h2><?php if (!$low): ?><p class="muted">সব প্রোডাক্টে যথেষ্ট স্টক আছে।</p><?php endif; ?>
          <?php foreach ($low as $p): ?><a class="mini" href="<?= e(aurl('product', ['id' => $p['id']])) ?>"><?= e($p['name']) ?><b class="<?= $p['stock'] ? '' : 'red' ?>"><?= bn_num($p['stock']) ?>টি</b></a><?php endforeach; ?></section>
        <section class="box"><h2>সবচেয়ে বেশি দেখা</h2><?php foreach ($top as $p): ?><a class="mini" href="<?= e(aurl('product', ['id' => $p['id']])) ?>"><?= e($p['name']) ?><b><?= bn_num($p['views']) ?> বার</b></a><?php endforeach; ?></section>
      </div>
    </div>
    <?php
    break;

case 'orders':
    $f = $_GET['s'] ?? ''; $q = trim((string)($_GET['q'] ?? ''));
    $w = []; $args = [];
    if (isset(ORDER_STATUS[$f])) { $w[] = 'status=?'; $args[] = $f; }
    if ($q !== '') { $w[] = '(code LIKE ? OR name LIKE ? OR phone LIKE ?)'; array_push($args, "%$q%", "%$q%", "%$q%"); }
    $page = max(1, (int)($_GET['p'] ?? 1));
    $st = db()->prepare('SELECT * FROM orders' . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . ' ORDER BY id DESC LIMIT 51 OFFSET ' . (($page - 1) * 50));
    $st->execute($args); $rows = $st->fetchAll();
    $counts = array_column(db()->query('SELECT status, COUNT(*) n FROM orders GROUP BY status')->fetchAll(), 'n', 'status');
    admin_head('অর্ডার');
    ?>
    <div class="ph"><h1>অর্ডার</h1><a class="btn ghost" href="<?= e(aurl('export')) ?>">⬇ CSV ডাউনলোড</a></div>
    <div class="tabs"><a href="<?= e(aurl('orders')) ?>" class="<?= $f === '' ? 'on' : '' ?>">সব</a><?php foreach (ORDER_STATUS as $k => $l): ?><a href="<?= e(aurl('orders', ['s' => $k])) ?>" class="<?= $f === $k ? 'on' : '' ?>"><?= $l ?> <i><?= bn_num($counts[$k] ?? 0) ?></i></a><?php endforeach; ?></div>
    <form class="searchbar"><input type="hidden" name="a" value="orders"><input type="hidden" name="s" value="<?= e($f) ?>"><input name="q" value="<?= e($q) ?>" placeholder="অর্ডার নাম্বার, নাম বা ফোন দিয়ে খুঁজুন"><button class="btn sm">খুঁজুন</button></form>
    <div class="box p0">
      <?php if (!$rows): ?><p class="muted pad">কোনো অর্ডার নেই।</p><?php endif; ?>
      <div class="olist"><?php foreach (array_slice($rows, 0, 50) as $o): $items = json_decode($o['items'], true) ?: []; ?>
        <a href="<?= e(aurl('order', ['id' => $o['id']])) ?>" class="orow<?= $o['status'] === 'new' ? ' new' : '' ?>"><b><?= e($o['code']) ?><?= !$o['seen'] ? ' <i class="pill">NEW</i>' : '' ?></b><span><?= e($o['name']) ?> • <?= e($o['phone']) ?><small><?= e(implode(', ', array_map(fn($i) => $i['name'] . ' (' . $i['size'] . ')', $items))) ?> • <?= e(bn_num(date('d/m h:i A', strtotime($o['created'])))) ?></small></span><span><?= money($o['total']) ?></span><?= status_pill($o['status']) ?></a>
      <?php endforeach; ?></div>
    </div>
    <div class="pager"><?php if ($page > 1): ?><a class="btn ghost sm" href="<?= e(aurl('orders', ['s' => $f, 'q' => $q, 'p' => $page - 1])) ?>">← আগের</a><?php endif; ?><?php if (count($rows) > 50): ?><a class="btn ghost sm" href="<?= e(aurl('orders', ['s' => $f, 'q' => $q, 'p' => $page + 1])) ?>">পরের →</a><?php endif; ?></div>
    <?php
    break;

case 'order':
    $id = (int)($_GET['id'] ?? 0);
    $o = db()->query('SELECT * FROM orders WHERE id=' . $id)->fetch();
    if (!$o) go('orders', [], '!অর্ডার পাওয়া যায়নি');
    if (!$o['seen']) db()->exec('UPDATE orders SET seen=1 WHERE id=' . $id);
    $items = json_decode($o['items'], true) ?: [];
    $wa = preg_replace('/\D/', '', $o['phone']); if (strlen($wa) === 11) $wa = '88' . $wa;
    $msg = "আসসালামু আলাইকুম {$o['name']}, " . S('shop_name') . " থেকে বলছি। আপনার অর্ডার {$o['code']} (মোট {$o['total']} টাকা) কনফার্ম করতে চাচ্ছি।";
    admin_head('অর্ডার ' . $o['code']);
    ?>
    <div class="ph"><h1>অর্ডার <?= e($o['code']) ?> <?= status_pill($o['status']) ?></h1><div class="row-btns"><button class="btn ghost" onclick="print()">🖨 প্রিন্ট / ইনভয়েস</button><a class="btn ghost" href="<?= e(aurl('orders')) ?>">← সব অর্ডার</a></div></div>
    <div class="cols2">
      <section class="box invoice">
        <div class="inv-head only-print"><b><?= e(S('shop_name')) ?></b><span><?= e(bn_num(S('phone'))) ?> • <?= e(S('address')) ?></span></div>
        <h2>কাস্টমার</h2>
        <p class="big"><b><?= e($o['name']) ?></b><br><?= e($o['phone']) ?><br><?= nl2br(e($o['address'])) ?><br><span class="muted"><?= $o['area'] === 'in' ? e(S('delivery_in_label')) : e(S('delivery_out_label')) ?> • <?= e(bn_num(date('d/m/Y h:i A', strtotime($o['created'])))) ?></span></p>
        <?php if ($o['note']): ?><p class="alert">📝 কাস্টমারের নোট: <?= e($o['note']) ?></p><?php endif; ?>
        <div class="row-btns no-print"><a class="btn sm" href="tel:<?= e($o['phone']) ?>">📞 কল করুন</a><a class="btn sm green" target="_blank" href="https://wa.me/<?= e($wa) ?>?text=<?= rawurlencode($msg) ?>">WhatsApp</a></div>
        <h2>প্রোডাক্ট</h2>
        <table class="tbl"><thead><tr><th>প্রোডাক্ট</th><th>সাইজ/রং</th><th>পরিমাণ</th><th>দাম</th></tr></thead><tbody>
        <?php foreach ($items as $it): ?><tr><td><a href="<?= e(aurl('product', ['id' => $it['id']])) ?>"><?= e($it['name']) ?></a></td><td><?= e($it['size']) ?> <?= e($it['color'] ?? '') ?></td><td><?= bn_num($it['qty']) ?></td><td><?= money($it['qty'] * $it['price']) ?></td></tr><?php endforeach; ?>
        <tr><td colspan="3">ডেলিভারি</td><td><?= money($o['delivery']) ?></td></tr>
        <tr class="tot"><td colspan="3">মোট (ক্যাশ অন ডেলিভারি)</td><td><?= money($o['total']) ?></td></tr></tbody></table>
      </section>
      <section class="box no-print">
        <h2>স্ট্যাটাস বদলান</h2>
        <form method="post" action="<?= e(aurl('order_save')) ?>" class="form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $o['id'] ?>">
          <div class="radios"><?php foreach (ORDER_STATUS as $k => $l): ?><label><input type="radio" name="status" value="<?= $k ?>"<?= $o['status'] === $k ? ' checked' : '' ?>><span><?= $l ?></span></label><?php endforeach; ?></div>
          <label>নিজের নোট (কাস্টমার দেখবে না)<textarea name="admin_note" rows="3" placeholder="যেমন: কুরিয়ার ট্র্যাকিং নাম্বার, কথা হয়েছে কিনা"><?= e($o['admin_note']) ?></textarea></label>
          <button class="btn full">সেভ করুন</button>
          <p class="muted sm">বাতিল বা ফেরত দিলে স্টক আবার যোগ হয়ে যাবে।</p>
        </form>
        <form method="post" action="<?= e(aurl('order_delete')) ?>" onsubmit="return confirm('অর্ডারটা একেবারে মুছে ফেলবেন?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $o['id'] ?>"><button class="link-btn red">অর্ডার মুছে ফেলুন</button></form>
      </section>
    </div>
    <?php
    break;

case 'products':
    $cols = collections(false); $cn = array_column($cols, 'name', 'id');
    $col = (int)($_GET['col'] ?? 0); $q = trim((string)($_GET['q'] ?? ''));
    $w = []; $args = [];
    if ($col) { $w[] = 'collection_id=?'; $args[] = $col; }
    if ($q !== '') { $w[] = '(name LIKE ? OR brand LIKE ?)'; array_push($args, "%$q%", "%$q%"); }
    $st = db()->prepare('SELECT * FROM products' . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . ' ORDER BY id DESC');
    $st->execute($args); $rows = $st->fetchAll();
    admin_head('সব প্রোডাক্ট');
    ?>
    <div class="ph"><h1>সব প্রোডাক্ট <small><?= bn_num(count($rows)) ?>টি</small></h1><a class="btn" href="<?= e(aurl('product', $col ? ['col' => $col] : [])) ?>">➕ নতুন প্রোডাক্ট পোস্ট</a></div>
    <div class="tabs"><a href="<?= e(aurl('products')) ?>" class="<?= !$col ? 'on' : '' ?>">সব</a><?php foreach ($cols as $c): ?><a href="<?= e(aurl('products', ['col' => $c['id']])) ?>" class="<?= $col == $c['id'] ? 'on' : '' ?>"><?= e($c['name']) ?></a><?php endforeach; ?></div>
    <form class="searchbar"><input type="hidden" name="a" value="products"><?php if ($col): ?><input type="hidden" name="col" value="<?= $col ?>"><?php endif; ?><input name="q" value="<?= e($q) ?>" placeholder="নাম দিয়ে খুঁজুন"><button class="btn sm">খুঁজুন</button></form>
    <div class="plist">
    <?php foreach ($rows as $p): ?>
      <div class="pcard<?= $p['active'] ? '' : ' off' ?>">
        <a href="<?= e(aurl('product', ['id' => $p['id']])) ?>" class="pimg"><img src="<?= e(p_first_image($p)) ?>" alt="" loading="lazy"><?php if ($p['featured']): ?><i class="star">★</i><?php endif; ?></a>
        <div class="pinfo">
          <a href="<?= e(aurl('product', ['id' => $p['id']])) ?>" class="pname"><?= e($p['name']) ?></a>
          <small><?= e($cn[$p['collection_id']] ?? 'কালেকশন নেই') ?><?= $p['active'] ? '' : ' • লুকানো' ?></small>
          <form method="post" action="<?= e(aurl('product_quick')) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="op" value="price"><input type="hidden" name="col" value="<?= $col ?: '' ?>">
            <label>দাম<input type="number" name="price" value="<?= (int)$p['price'] ?>" min="0"></label><label>আগের দাম<input type="number" name="old_price" value="<?= (int)$p['old_price'] ?>" min="0"></label><button class="btn xs">✓</button></form>
          <form method="post" action="<?= e(aurl('product_quick')) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="op" value="stock"><input type="hidden" name="col" value="<?= $col ?: '' ?>">
            <label>স্টক<input type="number" name="stock" value="<?= (int)$p['stock'] ?>" min="0" class="<?= $p['stock'] <= 3 ? 'warn' : '' ?>"></label><button class="btn xs">✓</button></form>
        </div>
        <div class="pact">
          <a class="btn xs ghost" href="<?= e(aurl('product', ['id' => $p['id']])) ?>">✏️ এডিট</a>
          <?php foreach (['active' => $p['active'] ? '🙈 লুকান' : '👁 দেখান', 'featured' => $p['featured'] ? '☆ জনপ্রিয় থেকে সরান' : '★ জনপ্রিয়তে দিন', 'dup' => '📄 কপি'] as $op => $l): ?>
            <form method="post" action="<?= e(aurl('product_quick')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="op" value="<?= $op ?>"><input type="hidden" name="col" value="<?= $col ?: '' ?>"><button class="btn xs ghost"><?= $l ?></button></form>
          <?php endforeach; ?>
          <form method="post" action="<?= e(aurl('product_quick')) ?>" onsubmit="return confirm('প্রোডাক্টটা ডিলিট করবেন?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="op" value="delete"><button class="btn xs ghost red">🗑 ডিলিট</button></form>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
    <?php
    break;

case 'product':
    $id = (int)($_GET['id'] ?? 0);
    $p = $id ? db()->query('SELECT * FROM products WHERE id=' . $id)->fetch() : null;
    if ($id && !$p) go('products', [], '!প্রোডাক্ট পাওয়া যায়নি');
    $p = $p ?: ['id' => 0, 'name' => '', 'slug' => '', 'collection_id' => (int)($_GET['col'] ?? 0), 'brand' => '', 'price' => '', 'old_price' => '', 'images' => '[]', 'sizes' => '39,40,41,42,43', 'colors' => '', 'stock' => 10, 'short' => '', 'description' => '', 'fb_post' => '', 'tags' => '', 'featured' => 0, 'is_new' => 1, 'active' => 1];
    $cols = collections(false);
    $sel = list_csv($p['sizes']);
    $std = array_map('strval', range(35, 46));
    admin_head($id ? 'প্রোডাক্ট এডিট' : 'নতুন প্রোডাক্ট');
    ?>
    <div class="ph"><h1><?= $id ? '✏️ প্রোডাক্ট এডিট' : '➕ নতুন প্রোডাক্ট পোস্ট' ?></h1><?php if ($id): ?><a class="btn ghost" target="_blank" href="<?= e(url('p/' . $p['slug'])) ?>">সাইটে দেখুন ↗</a><?php endif; ?></div>
    <form method="post" action="<?= e(aurl('product_save')) ?>" class="form pform" id="pform"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
    <div class="cols2 wide">
      <div>
        <section class="box">
          <h2>১. ছবি <small>প্রথম ছবিটাই মূল ছবি। একসাথে অনেক ছবি দিতে পারবেন।</small></h2>
          <div class="imgs" id="imgs">
            <?php foreach (p_images($p) as $im): ?><div class="im" draggable="true"><img src="<?= e(img_url($im)) ?>" alt=""><input type="hidden" name="images[]" value="<?= e($im) ?>"><button type="button" class="rm" title="সরান">×</button></div><?php endforeach; ?>
            <label class="im add"><input type="file" accept="image/*" multiple id="imgfile"><span>📷<br>ছবি যোগ করুন</span></label>
          </div>
          <p class="muted sm">টেনে এনে ছবির ক্রম বদলাতে পারবেন। বড় ছবি নিজে থেকেই ছোট হয়ে আপলোড হবে।</p>
        </section>
        <section class="box">
          <h2>২. প্রোডাক্টের তথ্য</h2>
          <label>প্রোডাক্টের নাম *<input name="name" id="f-name" value="<?= e($p['name']) ?>" required placeholder="যেমন: AeroFlex Runner"></label>
          <div class="g2">
            <label>কালেকশন *<select name="collection_id" id="f-col"><option value="0">কালেকশন বাছুন</option><?php foreach ($cols as $c): ?><option value="<?= $c['id'] ?>"<?= $p['collection_id'] == $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></label>
            <label>ব্র্যান্ড<input name="brand" id="f-brand" value="<?= e($p['brand']) ?>" placeholder="ঐচ্ছিক"></label>
          </div>
          <div class="g3">
            <label>বিক্রয় দাম (৳) *<input type="number" name="price" id="f-price" value="<?= e((string)$p['price']) ?>" min="0" required></label>
            <label>আগের দাম (৳)<input type="number" name="old_price" id="f-old" value="<?= e((string)$p['old_price']) ?>" min="0" placeholder="ছাড় দেখাতে"></label>
            <label>স্টক (কয়টা আছে)<input type="number" name="stock" value="<?= (int)$p['stock'] ?>" min="0"></label>
          </div>
          <div class="lbl">সাইজ <small>(যেগুলো আছে সেগুলোতে চাপুন)</small></div>
          <div class="chips" id="sizes"><?php foreach ($std as $z): ?><label><input type="checkbox" name="sizes[]" value="<?= $z ?>"<?= in_array($z, $sel, true) ? ' checked' : '' ?>><span><?= bn_num($z) ?></span></label><?php endforeach; ?></div>
          <label>অন্য সাইজ<input name="sizes_extra" value="<?= e(implode(',', array_diff($sel, $std))) ?>" placeholder="যেমন: 7, 8, 9 বা Free size"></label>
          <label>রং <small>(কমা দিয়ে আলাদা করুন)</small><input name="colors" id="f-colors" value="<?= e($p['colors']) ?>" placeholder="যেমন: কালো, সাদা"></label>
        </section>
      </div>
      <div>
        <section class="box ai-box">
          <h2>৩. ✨ অটো ক্যাপশন <small>নাম, কালেকশন, দাম আর ছবি দেখে নিজে থেকে লেখা তৈরি হবে। পরে ইচ্ছামতো বদলাতে পারবেন।</small></h2>
          <div class="lbl">লেখার ধরন</div>
          <div class="chips tone" id="tone">
            <label><input type="radio" name="tone_pick" value="premium" checked><span>👑 প্রিমিয়াম</span></label>
            <label><input type="radio" name="tone_pick" value="offer"><span>🔥 অফার</span></label>
            <label><input type="radio" name="tone_pick" value="festive"><span>🌙 উৎসব/ঈদ</span></label>
            <label><input type="radio" name="tone_pick" value="casual"><span>😊 বন্ধুসুলভ</span></label>
          </div>
          <label>বাড়তি কিছু বলতে চাইলে (ঐচ্ছিক)<input id="f-notes" placeholder="যেমন: চামড়ার, ঈদ অফার, হালকা ওজন"></label>
          <div class="row-btns"><button type="button" class="btn" id="gen">✨ অটো ক্যাপশন বানান</button><button type="button" class="btn ghost" id="gen2" title="আরেকটা নতুন লেখা">🔄 আরেকটা</button></div>
          <p class="muted sm" id="gen-note">ছবি আপলোড করলে নিজে থেকেই লেখা তৈরি হবে: ইংরেজি আর বাংলা দুই ভাষায়, সাথে ফেসবুক, ইনস্টাগ্রাম আর গুগলের লেখা। ফাঁকা রেখে সেভ করলেও অটো লেখা বসে যাবে। <?= S('gemini_key') ? '(ফ্রি Google Gemini AI চালু আছে, ছবি দেখেও লিখবে)' : '(এখন টেমপ্লেট দিয়ে লিখবে; আরও ভালো লেখার জন্য সেটিংসে ফ্রি Gemini key দিন)' ?></p>
          <div class="cap-tabs" role="tablist"><button type="button" class="on" data-captab="bn">🇧🇩 বাংলা</button><button type="button" data-captab="en">🇬🇧 English</button><button type="button" data-captab="social">📣 সোশ্যাল ও গুগল</button></div>
          <div class="cap-pane" data-pane="bn">
            <label>ছোট বিবরণ (বাংলা)<textarea name="short_bn" id="f-short-bn" rows="2"><?= e($p['short_bn'] ?? '') ?></textarea></label>
            <label>পুরো বিবরণ (বাংলা)<textarea name="description_bn" id="f-desc-bn" rows="8"><?= e($p['description_bn'] ?? '') ?></textarea></label>
          </div>
          <div class="cap-pane" data-pane="en" hidden>
            <label>ছোট বিবরণ (ইংরেজি)<textarea name="short" id="f-short" rows="2"><?= e($p['short']) ?></textarea></label>
            <label>পুরো বিবরণ (ইংরেজি)<textarea name="description" id="f-desc" rows="8"><?= e($p['description']) ?></textarea></label>
          </div>
          <div class="cap-pane" data-pane="social" hidden>
            <label>ফেসবুক পোস্ট (বাংলা) <button type="button" class="link-btn" data-copy="#f-fb">📋 কপি</button><textarea name="fb_post" id="f-fb" rows="9"><?= e($p['fb_post']) ?></textarea></label>
            <label>ফেসবুক পোস্ট (ইংরেজি) <button type="button" class="link-btn" data-copy="#f-fb-en">📋 কপি</button><textarea name="fb_post_en" id="f-fb-en" rows="9"><?= e($p['fb_post_en'] ?? '') ?></textarea></label>
            <label>ইনস্টাগ্রাম ক্যাপশন <button type="button" class="link-btn" data-copy="#f-insta">📋 কপি</button><textarea id="f-insta" rows="5" placeholder="অটো ক্যাপশন চাপলে এখানে আসবে (শুধু কপি করার জন্য)"></textarea></label>
            <label>গুগলের টাইটেল <button type="button" class="link-btn" data-copy="#f-seo-t">📋 কপি</button><input id="f-seo-t" placeholder="অটো ক্যাপশন চাপলে আসবে"></label>
            <label>গুগলের বিবরণ <button type="button" class="link-btn" data-copy="#f-seo-d">📋 কপি</button><textarea id="f-seo-d" rows="2" placeholder="অটো ক্যাপশন চাপলে আসবে"></textarea></label>
          </div>
          <label>ট্যাগ (সার্চের জন্য)<input name="tags" id="f-tags" value="<?= e($p['tags']) ?>"></label>
        </section>
        <section class="box">
          <h2>৪. দেখানো</h2>
          <div class="checks">
            <label><input type="checkbox" name="active"<?= $p['active'] ? ' checked' : '' ?>> সাইটে দেখাবে</label>
            <label><input type="checkbox" name="featured"<?= $p['featured'] ? ' checked' : '' ?>> "এখন সবচেয়ে জনপ্রিয়"-তে দেখাবে</label>
            <label><input type="checkbox" name="is_new"<?= $p['is_new'] ? ' checked' : '' ?>> "নতুন" ব্যাজ</label>
          </div>
          <label>লিংক (ঐচ্ছিক)<input name="slug" value="<?= e($p['slug']) ?>" placeholder="ফাঁকা রাখলে নাম থেকে বানাবে"></label>
        </section>
      </div>
    </div>
    <div class="savebar"><button class="btn">💾 সেভ করুন</button><button class="btn ghost" name="and_new" value="1">সেভ করে আরেকটা পোস্ট</button></div>
    </form>
    <?php
    break;

case 'cols':
    $cols = collections(false);
    $cnt = array_column(db()->query('SELECT collection_id, COUNT(*) n FROM products GROUP BY collection_id')->fetchAll(), 'n', 'collection_id');
    $edit = (int)($_GET['id'] ?? 0);
    admin_head('কালেকশন');
    ?>
    <div class="ph"><h1>কালেকশন</h1></div>
    <p class="muted">প্রতিটা কালেকশন হোমপেজে আলাদা সেকশন হিসেবে দেখায়। "ক্রম" নাম্বার ছোট হলে আগে দেখাবে।</p>
    <div class="clist">
    <?php foreach (array_merge($cols, [['id' => 0, 'name' => '', 'name_en' => '', 'slug' => '', 'tagline' => '', 'tagline_bn' => '', 'image' => '', 'sort' => count($cols) + 1, 'show_home' => 1, 'active' => 1]]) as $c): ?>
      <form method="post" enctype="multipart/form-data" action="<?= e(aurl('col_save')) ?>" class="box ccard<?= $c['id'] ? '' : ' new' ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>">
        <h2><?= $c['id'] ? e($c['name']) . ' <small>' . bn_num($cnt[$c['id']] ?? 0) . 'টি জুতা</small>' : '➕ নতুন কালেকশন' ?></h2>
        <div class="crow">
          <label class="cimg"><img src="<?= e(img_url($c['image'])) ?>" alt=""><input type="file" name="image_file" accept="image/*"><span>ছবি বদলান</span></label>
          <input type="hidden" name="image" value="<?= e($c['image']) ?>">
          <div class="cf">
            <div class="g2"><label>নাম (বাংলা)<input name="name" value="<?= e($c['name']) ?>" <?= $c['id'] ? 'required' : '' ?> placeholder="যেমন: লেডিস শু"></label><label>নাম (ইংরেজি)<input name="name_en" value="<?= e($c['name_en']) ?>" placeholder="Ladies Shoes"></label></div>
            <label>এক লাইনের বর্ণনা (ইংরেজি)<input name="tagline" value="<?= e($c['tagline']) ?>"></label>
            <label>এক লাইনের বর্ণনা (বাংলা)<input name="tagline_bn" value="<?= e($c['tagline_bn'] ?? '') ?>"></label>
            <div class="g3"><label>ক্রম<input type="number" name="sort" value="<?= (int)$c['sort'] ?>"></label><label>লিংক<input name="slug" value="<?= e($c['slug']) ?>" placeholder="অটো"></label><div class="checks"><label><input type="checkbox" name="show_home"<?= $c['show_home'] ? ' checked' : '' ?>> হোমপেজে</label><label><input type="checkbox" name="active"<?= $c['active'] ? ' checked' : '' ?>> চালু</label></div></div>
          </div>
        </div>
        <div class="row-btns"><button class="btn sm">সেভ</button><?php if ($c['id']): ?><a class="btn sm ghost" href="<?= e(aurl('products', ['col' => $c['id']])) ?>">জুতাগুলো দেখুন</a><button class="btn sm ghost red" formaction="<?= e(aurl('col_delete')) ?>" onclick="return confirm('কালেকশন ডিলিট করবেন? এর জুতাগুলো মুছবে না।')">ডিলিট</button><?php endif; ?></div>
      </form>
    <?php endforeach; ?>
    </div>
    <?php
    break;

case 'customers':
    $q = trim((string)($_GET['q'] ?? ''));
    $st = db()->prepare("SELECT c.*, (SELECT COUNT(*) FROM orders o WHERE o.customer_id=c.id) n, (SELECT COALESCE(SUM(total),0) FROM orders o WHERE o.customer_id=c.id AND o.status NOT IN ('cancelled','returned')) spent FROM customers c" . ($q !== '' ? ' WHERE c.name LIKE ? OR c.phone LIKE ?' : '') . ' ORDER BY c.id DESC LIMIT 300');
    $st->execute($q !== '' ? ["%$q%", "%$q%"] : []); $rows = $st->fetchAll();
    admin_head('কাস্টমার');
    ?>
    <div class="ph"><h1>কাস্টমার <small><?= bn_num(count($rows)) ?> জন</small></h1></div>
    <p class="muted">যারা সাইটে অ্যাকাউন্ট খুলেছেন। কেউ পাসওয়ার্ড ভুলে গেলে এখান থেকে নতুন পাসওয়ার্ড দিয়ে WhatsApp-এ জানিয়ে দিন। (নিরাপত্তার জন্য আসল পাসওয়ার্ড কেউ দেখতে পারে না।)</p>
    <form class="searchbar"><input type="hidden" name="a" value="customers"><input name="q" value="<?= e($q) ?>" placeholder="নাম বা ফোন দিয়ে খুঁজুন"><button class="btn sm">খুঁজুন</button></form>
    <?php if (!$rows): ?><div class="box"><p class="muted">এখনও কেউ অ্যাকাউন্ট খোলেনি।</p></div><?php endif; ?>
    <div class="plist">
    <?php foreach ($rows as $c): $wa = '88' . ltrim(preg_replace('/\D/', '', $c['phone']), '88'); ?>
      <div class="box cust<?= $c['blocked'] ? ' off' : '' ?>">
        <div class="cust-top"><b><?= e($c['name']) ?></b><?= $c['blocked'] ? ' <span class="st st-cancelled">বন্ধ</span>' : '' ?><span class="muted sm"><?= e($c['phone']) ?><?= $c['email'] ? ' • ' . e($c['email']) : '' ?></span></div>
        <div class="muted sm">অর্ডার <?= bn_num($c['n']) ?>টি • কেনাকাটা <?= money($c['spent']) ?> • যোগ দিয়েছেন <?= e(bn_num(date('d/m/Y', strtotime($c['created'])))) ?><?= $c['address'] ? ' • ' . e($c['address']) : '' ?></div>
        <div class="row-btns">
          <a class="btn xs ghost" href="tel:<?= e($c['phone']) ?>">📞 কল</a><a class="btn xs ghost" target="_blank" href="https://wa.me/<?= e($wa) ?>">WhatsApp</a>
          <form method="post" action="<?= e(aurl('cust')) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><input type="hidden" name="op" value="pw"><input name="newpw" placeholder="নতুন পাসওয়ার্ড" style="width:150px"><button class="btn xs">সেট</button></form>
          <form method="post" action="<?= e(aurl('cust')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><input type="hidden" name="op" value="block"><button class="btn xs ghost"><?= $c['blocked'] ? 'চালু করুন' : 'বন্ধ করুন' ?></button></form>
          <form method="post" action="<?= e(aurl('cust')) ?>" onsubmit="return confirm('অ্যাকাউন্ট মুছবেন? তার অর্ডারগুলো থেকে যাবে।')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><input type="hidden" name="op" value="delete"><button class="btn xs ghost red">মুছুন</button></form>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
    <?php
    break;

case 'settings':
    $s = settings();
    $tab = $_GET['tab'] ?? 'home';
    $T = ['home' => 'হোমপেজের লেখা', 'contact' => 'যোগাযোগ ও ডেলিভারি', 'brand' => 'লোগো ও রং', 'ai' => 'অটো ক্যাপশন (AI)', 'seo' => 'SEO ও ট্র্যাকিং', 'pw' => 'পাসওয়ার্ড ও ব্যাকআপ'];
    $fld = function (string $k, string $label, string $type = 'text', string $ph = '') use ($s) {
        if ($type === 'area') return '<label>' . $label . '<textarea name="' . $k . '" rows="3" placeholder="' . e($ph) . '">' . e($s[$k]) . '</textarea></label>';
        return '<label>' . $label . '<input type="' . $type . '" name="' . $k . '" value="' . e($s[$k]) . '" placeholder="' . e($ph) . '"></label>';
    };
    admin_head('সাইট সেটিংস');
    ?>
    <div class="ph"><h1>সাইট সেটিংস</h1></div>
    <div class="tabs"><?php foreach ($T as $k => $l): ?><a href="<?= e(aurl('settings', ['tab' => $k])) ?>" class="<?= $tab === $k ? 'on' : '' ?>"><?= $l ?></a><?php endforeach; ?></div>
    <?php if ($tab === 'pw'): ?>
      <div class="cols2">
      <form method="post" action="<?= e(aurl('password')) ?>" class="box form"><?= csrf_field() ?><h2>পাসওয়ার্ড বদলান</h2>
        <label>পুরনো পাসওয়ার্ড<input type="password" name="old" required></label><label>নতুন পাসওয়ার্ড<input type="password" name="new" required minlength="6"></label><label>আবার নতুন পাসওয়ার্ড<input type="password" name="new2" required></label><button class="btn">বদলান</button></form>
      <section class="box"><h2>ব্যাকআপ</h2><p>সব প্রোডাক্ট, অর্ডার, সেটিংস আর আপলোড করা ছবি এক ফাইলে ডাউনলোড হবে। মাঝে মাঝে নামিয়ে রাখুন।</p><a class="btn" href="<?= e(aurl('backup')) ?>">⬇ ব্যাকআপ ডাউনলোড</a></section>
      </div>
    <?php else: ?>
    <form method="post" enctype="multipart/form-data" action="<?= e(aurl('settings_save')) ?>" class="box form"><?= csrf_field() ?><input type="hidden" name="tab" value="<?= e($tab) ?>">
      <?php if ($tab === 'home'): ?>
        <p class="muted">সাইট দুই ভাষায় চলে: খুললে ইংরেজি, উপরের "বাং" বাটনে চাপলে বাংলা। তাই প্রতিটি লেখা ইংরেজি আর বাংলা দুটোই দিন।</p>
        <div class="g2"><?= $fld('hero_title_en', 'ব্যানারের বড় লেখা (ইংরেজি)') ?><?= $fld('hero_title', 'ব্যানারের বড় লেখা (বাংলা)') ?></div>
        <div class="g2"><?= $fld('hero_kicker', 'ব্যানারের উপরের ছোট লেখা (ইংরেজি)') ?><?= $fld('hero_kicker_bn', 'ব্যানারের উপরের ছোট লেখা (বাংলা)') ?></div>
        <div class="g2"><?= $fld('hero_btn', 'ব্যানারের বাটন (ইংরেজি)') ?><?= $fld('hero_btn_bn', 'ব্যানারের বাটন (বাংলা)') ?></div>
        <div class="g2"><?= $fld('hero_sub', 'ব্যানারের বর্ণনা (ইংরেজি)', 'area') ?><?= $fld('hero_sub_bn', 'ব্যানারের বর্ণনা (বাংলা)', 'area') ?></div>
        <div class="lbl">ব্যানারের বড় ছবি <small>(চওড়া, একটু অন্ধকার জুতার ছবি সবচেয়ে সুন্দর দেখায়)</small></div>
        <div class="file-row"><img src="<?= e($s['hero_image'] ? img_url($s['hero_image']) : url('assets/img/demo/hero-full.webp')) ?>" alt=""><input type="file" name="hero_image_file" accept="image/*"><?php if ($s['hero_image']): ?><label class="inline-check"><input type="checkbox" name="hero_image_clear" value="1"> ডেমো ছবিতে ফেরত যান</label><?php endif; ?></div>
        <div class="g2"><?= $fld('promo_title', 'অফার ব্যানারের শিরোনাম, ইংরেজি (ফাঁকা রাখলে দেখাবে না)') ?><?= $fld('promo_title_bn', 'অফার ব্যানারের শিরোনাম (বাংলা)') ?></div>
        <div class="g2"><?= $fld('promo_sub', 'অফারের বর্ণনা (ইংরেজি)') ?><?= $fld('promo_sub_bn', 'অফারের বর্ণনা (বাংলা)') ?></div>
        <div class="g2"><?= $fld('promo_btn', 'অফার বাটন (ইংরেজি)') ?><?= $fld('promo_btn_bn', 'অফার বাটন (বাংলা)') ?></div>
        <div class="lbl">অফার ব্যানারের পেছনের ছবি <small>(চওড়া, একটু অন্ধকার ছবি সবচেয়ে সুন্দর দেখায়)</small></div>
        <div class="file-row"><img src="<?= e($s['promo_image'] ? img_url($s['promo_image']) : url('assets/img/demo/real/frm-3.webp')) ?>" alt=""><input type="file" name="promo_image_file" accept="image/*"><?php if ($s['promo_image']): ?><label class="inline-check"><input type="checkbox" name="promo_image_clear" value="1"> ডেমো ছবিতে ফেরত যান</label><?php endif; ?></div>
        <div class="g2"><?= $fld('about', 'আমাদের সম্পর্কে (ইংরেজি)', 'area') ?><?= $fld('about_bn', 'আমাদের সম্পর্কে (বাংলা)', 'area') ?></div>
        <div class="g2"><?= $fld('footer_note', 'ফুটারের লেখা (ইংরেজি)') ?><?= $fld('footer_note_bn', 'ফুটারের লেখা (বাংলা)') ?></div>
      <?php elseif ($tab === 'contact'): ?>
        <div class="g2"><?= $fld('phone', 'ফোন নাম্বার') ?><?= $fld('whatsapp', 'WhatsApp নাম্বার (৮৮ দিয়ে শুরু)', 'text', '8801XXXXXXXXX') ?></div>
        <?= $fld('email', 'ইমেইল') ?>
        <div class="g2"><?= $fld('address', 'ঠিকানা (ইংরেজি)') ?><?= $fld('address_bn', 'ঠিকানা (বাংলা)') ?></div>
        <div class="g2"><?= $fld('facebook', 'Facebook পেজ লিংক') ?><?= $fld('instagram', 'Instagram লিংক') ?></div>
        <div class="g2"><?= $fld('tiktok', 'TikTok লিংক') ?><?= $fld('youtube', 'YouTube লিংক') ?></div>
        <h2>ডেলিভারি চার্জ</h2>
        <div class="g2"><?= $fld('delivery_in_label', 'এলাকা ১ এর নাম') ?><?= $fld('delivery_in', 'এলাকা ১ চার্জ (৳)', 'number') ?></div>
        <div class="g2"><?= $fld('delivery_out_label', 'এলাকা ২ এর নাম') ?><?= $fld('delivery_out', 'এলাকা ২ চার্জ (৳)', 'number') ?></div>
        <?= $fld('free_over', 'এত টাকার বেশি অর্ডারে ডেলিভারি ফ্রি (০ = বন্ধ)', 'number') ?>
        <?= $fld('notify_email', 'নতুন অর্ডার এলে এই ইমেইলে জানাবে (ঐচ্ছিক)', 'email') ?>
        <h2>WhatsApp পপ-আপ</h2>
        <div class="g2"><?= $fld('wa_popup', 'পপ-আপের লেখা (ইংরেজি)', 'area') ?><?= $fld('wa_popup_bn', 'পপ-আপের লেখা (বাংলা)', 'area') ?></div>
        <?= $fld('wa_popup_sec', 'কত সেকেন্ড পর দেখাবে (০ = পপ-আপ বন্ধ)', 'number') ?>
      <?php elseif ($tab === 'brand'): ?>
        <div class="g2"><?= $fld('shop_name', 'দোকানের নাম (বাংলা)') ?><?= $fld('shop_name_en', 'দোকানের নাম (ইংরেজি)') ?></div>
        <div class="lbl">লোগো <small>(না দিলে নামের লেখা লোগো দেখাবে)</small></div>
        <div class="file-row"><?php if ($s['logo']): ?><img src="<?= e(img_url($s['logo'])) ?>" alt=""><?php endif; ?><input type="file" name="logo_file" accept="image/*"><?php if ($s['logo']): ?><label class="inline-check"><input type="checkbox" name="logo_clear" value="1"> লোগো সরান</label><?php endif; ?></div>
        <label>মূল রং<input type="color" name="accent" value="<?= e($s['accent']) ?>"></label>
        <?= $fld('currency', 'টাকার চিহ্ন') ?>
      <?php elseif ($tab === 'ai'): ?>
        <p>অটো ক্যাপশন <b>Google Gemini</b> দিয়ে চলে, যেটা <b>ফ্রি</b>। key না দিলেও টেমপ্লেট দিয়ে ক্যাপশন হবে, কিন্তু AI দিলে ছবি দেখে আরও সুন্দর লেখা হয়।</p>
        <ol class="steps-help"><li><a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener">aistudio.google.com/app/apikey</a> এ Gmail দিয়ে ঢুকুন</li><li><b>Create API key</b> চাপুন, key কপি করুন</li><li>নিচের ঘরে পেস্ট করে সেভ করুন, তারপর "টেস্ট করুন" চাপুন</li></ol>
        <?= $fld('gemini_key', 'Gemini API key', 'password', 'AIza...') ?>
        <div class="row-btns"><button type="button" class="btn ghost" id="gtest">🧪 টেস্ট করুন</button><span id="gtest-out" class="muted"></span></div>
      <?php elseif ($tab === 'seo'): ?>
        <?= $fld('meta_desc', 'Google-এ দেখানো বর্ণনা (১৬০ অক্ষরের মধ্যে)', 'area') ?>
        <?= $fld('tagline', 'ট্যাগলাইন') ?>
        <div class="g2"><?= $fld('pixel_id', 'Facebook Pixel ID', 'text', 'যেমন 1234567890') ?><?= $fld('ga_id', 'Google Analytics ID', 'text', 'G-XXXXXXX') ?></div>
        <p class="muted sm">সাইটম্যাপ: <code><?= e(url('sitemap.xml')) ?></code> (Google Search Console-এ জমা দিন)</p>
      <?php endif; ?>
      <button class="btn">💾 সেভ করুন</button>
    </form>
    <?php endif; ?>
    <?php
    break;

default:
    go('dash');
}
admin_foot();
