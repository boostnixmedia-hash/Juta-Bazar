<?php
// Jutar Bazar core: database, settings, helpers. PHP 8 + SQLite (no setup needed).
declare(strict_types=1);
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Dhaka');
mb_internal_encoding('UTF-8');

define('JB_ROOT', dirname(__DIR__));
define('JB_VERSION', '1.7.2');

/** Private data folder: ~/jutarbazar-data (outside public_html) if possible, else ./data (blocked by .htaccess). */
function jb_data_dir(): string {
    static $d = null;
    if ($d !== null) return $d;
    $home = getenv('HOME') ?: (function_exists('posix_getpwuid') ? (posix_getpwuid(posix_geteuid())['dir'] ?? '') : '');
    $cands = [];
    if ($home && is_dir($home) && strpos(JB_ROOT, $home) === 0 && realpath($home) !== realpath(JB_ROOT)) $cands[] = rtrim($home, '/') . '/jutarbazar-data';
    $cands[] = JB_ROOT . '/data';
    foreach ($cands as $c) {
        if (!is_dir($c)) @mkdir($c, 0755, true);
        if (is_dir($c) && is_writable($c)) {
            if (!file_exists("$c/.htaccess")) @file_put_contents("$c/.htaccess", "Require all denied\nDeny from all\n");
            return $d = $c;
        }
    }
    return $d = JB_ROOT . '/data';
}

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $file = jb_data_dir() . '/shop.sqlite';
    $new = !file_exists($file);
    $pdo = new PDO('sqlite:' . $file, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON; PRAGMA busy_timeout=4000;');
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (k TEXT PRIMARY KEY, v TEXT);
    CREATE TABLE IF NOT EXISTS collections (
        id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, name_en TEXT DEFAULT '', slug TEXT UNIQUE NOT NULL,
        tagline TEXT DEFAULT '', image TEXT DEFAULT '', sort INTEGER DEFAULT 0, show_home INTEGER DEFAULT 1, active INTEGER DEFAULT 1);
    CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, slug TEXT UNIQUE NOT NULL, collection_id INTEGER,
        brand TEXT DEFAULT '', price INTEGER DEFAULT 0, old_price INTEGER DEFAULT 0, images TEXT DEFAULT '[]',
        sizes TEXT DEFAULT '', colors TEXT DEFAULT '', stock INTEGER DEFAULT 10, short TEXT DEFAULT '', description TEXT DEFAULT '',
        fb_post TEXT DEFAULT '', tags TEXT DEFAULT '', featured INTEGER DEFAULT 0, is_new INTEGER DEFAULT 1, active INTEGER DEFAULT 1,
        sort INTEGER DEFAULT 0, views INTEGER DEFAULT 0, created TEXT, updated TEXT);
    CREATE INDEX IF NOT EXISTS ix_prod_col ON products(collection_id, active);
    CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT UNIQUE, name TEXT, phone TEXT, address TEXT, area TEXT, note TEXT,
        items TEXT, subtotal INTEGER, delivery INTEGER, total INTEGER, status TEXT DEFAULT 'new', admin_note TEXT DEFAULT '',
        seen INTEGER DEFAULT 0, ip TEXT, created TEXT, updated TEXT);
    CREATE INDEX IF NOT EXISTS ix_ord_status ON orders(status);
    CREATE TABLE IF NOT EXISTS hits (ip TEXT, kind TEXT, t INTEGER);
    CREATE TABLE IF NOT EXISTS customers (
        id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, phone TEXT UNIQUE, email TEXT DEFAULT '', pass TEXT,
        address TEXT DEFAULT '', area TEXT DEFAULT 'in', blocked INTEGER DEFAULT 0, created TEXT, last_login TEXT);
    ");
    $cols = array_column($pdo->query('PRAGMA table_info(orders)')->fetchAll(), 'name');
    if (!in_array('customer_id', $cols, true)) $pdo->exec('ALTER TABLE orders ADD COLUMN customer_id INTEGER');
    // v1.3: Bangla copies for the two-language storefront
    $pc = array_column($pdo->query('PRAGMA table_info(products)')->fetchAll(), 'name');
    if (!in_array('short_bn', $pc, true)) $pdo->exec("ALTER TABLE products ADD COLUMN short_bn TEXT DEFAULT ''");
    if (!in_array('description_bn', $pc, true)) $pdo->exec("ALTER TABLE products ADD COLUMN description_bn TEXT DEFAULT ''");
    if (!in_array('fb_post_en', $pc, true)) $pdo->exec("ALTER TABLE products ADD COLUMN fb_post_en TEXT DEFAULT ''");
    $cc = array_column($pdo->query('PRAGMA table_info(collections)')->fetchAll(), 'name');
    if (!in_array('tagline_bn', $cc, true)) $pdo->exec("ALTER TABLE collections ADD COLUMN tagline_bn TEXT DEFAULT ''");
    if ($new) { require_once __DIR__ . '/seed.php'; jb_seed($pdo); }
    return $pdo;
}

/* ---------- settings ---------- */
function jb_defaults(): array {
    return [
        'shop_name' => 'জুতার বাজার',
        'shop_name_en' => 'JUTAR BAZAR',
        'logo' => '',
        'tagline' => 'Step into more',
        'announce' => 'Cash on delivery all over Bangladesh  •  Easy size exchange  •  Every pair checked before dispatch',
        'hero_kicker' => 'NEW SEASON 2026',
        'hero_title' => 'প্রতিটি পদক্ষেপে আভিজাত্য',
        'hero_title_en' => 'Walk in Style',
        'hero_sub' => 'Running, sneakers, loafers, formal and sandals. Handpicked pairs that look sharp and feel right, delivered to your door.',
        'hero_btn' => 'Shop now',
        'hero_image' => '',
        'promo_image' => '',
        'promo_title' => 'The new collection is here',
        'promo_sub' => 'From running to formal, new designs land every week. Find your pair today.',
        'promo_btn' => 'Explore new arrivals',
        'free_over' => '0',
        'about' => 'We believe a good pair of shoes starts a good day. So we pick every pair for quality, help you get the right size, and keep our prices honest.',
        'phone' => '01700000000',
        'whatsapp' => '8801700000000',
        'email' => '',
        'address' => 'Dhaka, Bangladesh',
        'facebook' => '',
        'instagram' => '',
        'tiktok' => '',
        'youtube' => '',
        'delivery_in' => '70',
        'delivery_out' => '130',
        'delivery_in_label' => 'ঢাকা সিটির ভেতরে',
        'delivery_out_label' => 'ঢাকা সিটির বাইরে',
        'currency' => '৳',
        'accent' => '#8c6a3f',
        'gemini_key' => '',
        'notify_email' => '',
        'meta_desc' => 'Jutar Bazar: running shoes, sneakers, loafers, formal shoes and sandals. Cash on delivery all over Bangladesh.',
        'pixel_id' => '',
        'ga_id' => '',
        'wa_popup' => 'Looking for a pair? Ask us anything about size, price or your order on WhatsApp.',
        'wa_popup_sec' => '6',
        'footer_note' => 'Comfort, style and trust in every pair.',
        // Bangla versions (used when the visitor switches to বাংলা)
        'hero_kicker_bn' => 'নতুন সিজন ২০২৬',
        'hero_sub_bn' => 'রানিং, স্নিকার্স, লোফার, ফরমাল আর স্যান্ডেল। বাছাই করা প্রতিটি জোড়া দেখতে দারুণ, পরতে আরাম, পৌঁছে যাবে আপনার দরজায়।',
        'hero_btn_bn' => 'এখনই কিনুন',
        'promo_title_bn' => 'নতুন কালেকশন চলে এসেছে',
        'promo_sub_bn' => 'রানিং থেকে ফরমাল, প্রতি সপ্তাহে নতুন ডিজাইন। আজই বেছে নিন আপনার জোড়া।',
        'promo_btn_bn' => 'নতুন জুতা দেখুন',
        'about_bn' => 'আমরা বিশ্বাস করি, ভালো একজোড়া জুতা দিয়েই ভালো দিনের শুরু। তাই প্রতিটি জোড়া মান দেখে বাছাই করি, সঠিক সাইজ পেতে সাহায্য করি, আর দাম রাখি সৎ।',
        'address_bn' => 'ঢাকা, বাংলাদেশ',
        'meta_desc_bn' => 'জুতার বাজার: রানিং শু, স্নিকার্স, লোফার, ফরমাল শু আর স্যান্ডেল। সারা বাংলাদেশে ক্যাশ অন ডেলিভারি।',
        'wa_popup_bn' => 'জুতা খুঁজছেন? সাইজ, দাম বা অর্ডার নিয়ে যেকোনো প্রশ্ন হোয়াটসঅ্যাপে করুন।',
        'footer_note_bn' => 'প্রতিটি জোড়ায় আরাম, স্টাইল আর আস্থা।',
        'size_guide' => '1',
        'of1_on' => '1',
        'of1_l1' => 'WALK IN',
        'of1_l2' => 'STYLE',
        'of1_top' => 'NEW SEASON',
        'of1_big' => '2026',
        'of1_end' => 'SHOP',
        'of1_theme' => 'red',
        'of1_img' => 'assets/img/demo/offer/snk-3.webp',
        'of1_link' => 'shop',
        'of2_on' => '1',
        'of2_l1' => 'CASH ON',
        'of2_l2' => 'DELIVERY',
        'of2_top' => 'ALL OVER',
        'of2_big' => 'BD',
        'of2_end' => 'COD',
        'of2_theme' => 'navy',
        'of2_img' => 'assets/img/demo/offer/run-2.webp',
        'of2_link' => 'shop',
        'of3_on' => '1',
        'of3_l1' => 'DHAKA',
        'of3_l2' => 'DELIVERY',
        'of3_top' => 'ONLY',
        'of3_big' => '৳70',
        'of3_end' => 'FAST',
        'of3_theme' => 'green',
        'of3_img' => 'assets/img/demo/offer/lof-3.webp',
        'of3_link' => 'shop',
        'of4_on' => '1',
        'of4_l1' => 'EASY SIZE',
        'of4_l2' => 'EXCHANGE',
        'of4_top' => 'WRONG SIZE?',
        'of4_big' => 'SWAP',
        'of4_end' => 'EASY',
        'of4_theme' => 'maroon',
        'of4_img' => 'assets/img/demo/offer/snd-1.webp',
        'of4_link' => 'shop',
        'of5_on' => '1',
        'of5_l1' => 'SNEAKERS',
        'of5_l2' => 'COLLECTION',
        'of5_top' => 'UP TO',
        'of5_big' => 'auto',
        'of5_end' => 'OFF!',
        'of5_theme' => 'red',
        'of5_img' => 'assets/img/demo/offer/snk-4.webp',
        'of5_link' => 'c/sneakers',
        'of6_on' => '1',
        'of6_l1' => 'FORMAL',
        'of6_l2' => 'SHOES',
        'of6_top' => 'UP TO',
        'of6_big' => 'auto',
        'of6_end' => 'OFF!',
        'of6_theme' => 'dark',
        'of6_img' => 'assets/img/demo/offer/frm-3.webp',
        'of6_link' => 'c/formal',
        'of7_on' => '1',
        'of7_l1' => 'LOAFERS',
        'of7_l2' => 'COLLECTION',
        'of7_top' => 'UP TO',
        'of7_big' => 'auto',
        'of7_end' => 'OFF!',
        'of7_theme' => 'maroon',
        'of7_img' => 'assets/img/demo/offer/lof-4.webp',
        'of7_link' => 'c/loafers',
        'of_note' => '',
    ];
}
function settings(): array {
    static $s = null;
    if ($s !== null) return $s;
    $s = jb_defaults();
    foreach (db()->query('SELECT k,v FROM settings') as $r) $s[$r['k']] = $r['v'];
    return $s;
}
function set_setting(string $k, string $v): void {
    db()->prepare('INSERT INTO settings(k,v) VALUES(?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v')->execute([$k, $v]);
}
function S(string $k): string { return (string)(settings()[$k] ?? ''); }

/* ---------- helpers ---------- */
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function base_url(): string {
    static $b = null;
    if ($b !== null) return $b;
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (basename($dir) === 'admin') $dir = dirname($dir);
    return $b = rtrim($dir, '/') . '/';
}
function url(string $path = ''): string { return base_url() . ltrim($path, '/'); }
/* ---------- language (storefront: English by default, বাংলা on switch; admin is always Bangla) ---------- */
function lang(): string {
    static $l = null;
    if ($l !== null) return $l;
    if (!defined('JB_EN')) return $l = 'bn';
    $q = (string)($_GET['lang'] ?? '');
    if ($q === 'en' || $q === 'bn') { if (!headers_sent()) setcookie('jb_lang', $q, ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax']); return $l = $q; }
    $c = (string)($_COOKIE['jb_lang'] ?? '');
    return $l = ($c === 'bn' ? 'bn' : 'en');
}
function is_bn(): bool { return lang() === 'bn'; }
/** Pick the English or Bangla text for the current visitor. */
function t(string $en, string $bn): string { return is_bn() ? $bn : $en; }
/** Setting in the visitor's language: uses KEY_bn when Bangla is on and it is filled in. */
function SL(string $k): string { if (is_bn()) { $v = S($k . '_bn'); if (trim($v) !== '') return $v; } return S($k); }
function col_name(array $c): string { return is_bn() ? ($c['name'] ?: $c['name_en']) : (($c['name_en'] ?? '') ?: $c['name']); }
function col_tag(array $c): string { return is_bn() && trim((string)($c['tagline_bn'] ?? '')) !== '' ? $c['tagline_bn'] : (string)($c['tagline'] ?? ''); }
function p_short(array $p): string { return is_bn() && trim((string)($p['short_bn'] ?? '')) !== '' ? $p['short_bn'] : (string)($p['short'] ?? ''); }
function p_desc(array $p): string { return is_bn() && trim((string)($p['description_bn'] ?? '')) !== '' ? $p['description_bn'] : (string)($p['description'] ?? ''); }
/** Same page in the other language. */
function lang_url(string $to): string {
    $u = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    $q = $_GET; unset($q['lang'], $q['route'], $q['r']); $q['lang'] = $to;
    return $u . '?' . http_build_query($q);
}
function bn_num($n): string { if (defined('JB_EN') && !is_bn()) return (string)$n; return strtr((string)$n, ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯']); }
function money($n): string { return S('currency') . bn_num(number_format((int)$n)); }
function slugify(string $s): string {
    $s = mb_strtolower(trim($s));
    $s = preg_replace('/[^\p{L}\p{N}]+/u', '-', $s);
    $s = trim((string)$s, '-');
    return $s !== '' ? mb_substr($s, 0, 80) : 'item-' . substr(md5((string)microtime(true)), 0, 6);
}
function unique_slug(string $table, string $base, int $ignoreId = 0): string {
    $slug = slugify($base); $try = $slug; $i = 2;
    $st = db()->prepare("SELECT id FROM $table WHERE slug=? AND id<>?");
    while (true) { $st->execute([$try, $ignoreId]); if (!$st->fetch()) return $try; $try = $slug . '-' . $i++; }
}
function img_url(string $p): string {
    if ($p === '') return url('assets/img/placeholder.svg');
    if (preg_match('~^https?://~', $p)) return $p;
    return url($p);
}
function p_images(array $p): array { $a = json_decode($p['images'] ?: '[]', true); return is_array($a) ? array_values(array_filter($a)) : []; }
function p_first_image(array $p): string { $a = p_images($p); return img_url($a[0] ?? ''); }
function list_csv(string $s): array { return array_values(array_filter(array_map('trim', preg_split('/[,،\n]+/u', $s)), 'strlen')); }
function discount_pct(array $p): int { return ($p['old_price'] > $p['price'] && $p['old_price'] > 0) ? (int)round(100 - $p['price'] * 100 / $p['old_price']) : 0; }
function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45); }
function rate_ok(string $kind, int $max, int $secs): bool {
    $pdo = db(); $now = time();
    $pdo->prepare('DELETE FROM hits WHERE t<?')->execute([$now - 86400]);
    $st = $pdo->prepare('SELECT COUNT(*) FROM hits WHERE ip=? AND kind=? AND t>?');
    $st->execute([client_ip(), $kind, $now - $secs]);
    if ((int)$st->fetchColumn() >= $max) return false;
    $pdo->prepare('INSERT INTO hits(ip,kind,t) VALUES(?,?,?)')->execute([client_ip(), $kind, $now]);
    return true;
}
function collections(bool $activeOnly = true): array {
    return db()->query('SELECT * FROM collections' . ($activeOnly ? ' WHERE active=1' : '') . ' ORDER BY sort, id')->fetchAll();
}
function json_out($data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
const ORDER_STATUS = [
    'new' => 'নতুন', 'confirmed' => 'কনফার্মড', 'shipped' => 'পাঠানো হয়েছে',
    'delivered' => 'ডেলিভারি সম্পন্ন', 'cancelled' => 'বাতিল', 'returned' => 'ফেরত',
];

/* ---------- customer accounts ---------- */
function shop_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('jbshop');
    session_set_cookie_params(['lifetime' => 86400 * 30, 'path' => base_url(), 'httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    ini_set('session.gc_maxlifetime', (string)(86400 * 30));
    session_start();
}
function customer(): ?array {
    static $c = false;
    if ($c !== false) return $c;
    if (empty($_COOKIE['jbshop'])) return $c = null;
    shop_session();
    $id = (int)($_SESSION['cid'] ?? 0);
    if (!$id) return $c = null;
    $st = db()->prepare('SELECT * FROM customers WHERE id=? AND blocked=0'); $st->execute([$id]);
    return $c = ($st->fetch() ?: null);
}
function norm_phone(string $p): string {
    $p = strtr($p, ['০'=>'0','১'=>'1','২'=>'2','৩'=>'3','৪'=>'4','৫'=>'5','৬'=>'6','৭'=>'7','৮'=>'8','৯'=>'9']);
    $d = preg_replace('/\D/', '', $p);
    if (strlen($d) === 13 && str_starts_with($d, '880')) $d = substr($d, 2);
    return $d;
}
function shop_csrf(): string { shop_session(); if (empty($_SESSION['scsrf'])) $_SESSION['scsrf'] = bin2hex(random_bytes(16)); return $_SESSION['scsrf']; }
function shop_csrf_ok(): bool { shop_session(); $t = $_POST['csrf'] ?? ''; return is_string($t) && !empty($_SESSION['scsrf']) && hash_equals($_SESSION['scsrf'], $t); }
