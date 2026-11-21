<?php
// First-run demo data. Everything here can be edited or deleted from the admin panel.
function jb_seed(PDO $pdo): void {
    $cols = [
        ['রানিং শু', 'Running Shoes', 'running', 'Light, cushioned, built for every run', 'assets/img/demo/real/run-1.webp', 'হালকা, নরম কুশন, প্রতিদিনের দৌড়ের জন্য'],
        ['স্নিকার্স', 'Sneakers', 'sneakers', 'Everyday style that goes with anything', 'assets/img/demo/real/snk-1.webp', 'প্রতিদিনের স্টাইল, যেকোনো পোশাকে মানানসই'],
        ['লোফার', 'Loafers', 'loafers', 'Lace-free comfort, classic look', 'assets/img/demo/real/col-loafers.webp', 'ফিতা ছাড়া আরাম, ক্লাসিক লুক'],
        ['ফরমাল শু', 'Formal Shoes', 'formal', 'For the office, weddings and big days', 'assets/img/demo/real/col-formal.webp', 'অফিস, বিয়ে আর বিশেষ দিনের জন্য'],
        ['স্যান্ডেল', 'Sandals', 'sandals', 'Easy comfort, indoors and out', 'assets/img/demo/real/snd-2.webp', 'গরমে আরাম, ঘরে-বাইরে সবখানে'],
    ];
    $ci = $pdo->prepare('INSERT INTO collections(name,name_en,slug,tagline,image,sort,tagline_bn) VALUES(?,?,?,?,?,?,?)');
    foreach ($cols as $i => $c) $ci->execute([$c[0], $c[1], $c[2], $c[3], $c[4], $i + 1, $c[5]]);

    // [collection slug, name, brand, price, old price, image, sizes, colors, featured]
    $P = [
        ['sneakers', 'Denim Chukka', 'Mode', 2750, 3200, 'snk-4', '39,40,41,42,43,44', 'Denim Grey', 1],
        ['running', 'Color Pop Runner', 'Stride', 3450, 4200, 'run-1', '39,40,41,42,43,44', 'White/Multicolor', 1],
        ['running', 'Gel Cushion Trainer', 'Pacer', 3990, 4800, 'run-2', '39,40,41,42,43', 'Silver/Grey', 1],
        ['running', 'Night Sprint', 'Stride', 2850, 3400, 'run-3', '38,39,40,41,42', 'Black', 0],
        ['sneakers', 'Urban Court Classic', 'Kicks', 2450, 2990, 'snk-1', '38,39,40,41,42,43', 'White/Beige', 1],
        ['sneakers', 'Canvas High Top', 'Kicks', 2150, 2600, 'snk-2', '39,40,41,42,43', 'Grey', 0],
        ['sneakers', 'Red Canvas Low', 'Mode', 1690, 0, 'snk-3', '38,39,40,41,42', 'Red', 0],
        ['loafers', 'Suede Horsebit Loafer', 'Crafted', 3250, 3900, 'lof-1', '39,40,41,42,43', 'Tan', 1],
        ['loafers', 'Embroidered Slip-On', 'Crafted', 2950, 0, 'lof-2', '39,40,41,42,43', 'Sky Blue', 0],
        ['loafers', 'Velvet Studded Loafer', 'Heritage', 3450, 4100, 'lof-3', '40,41,42,43', 'Black', 0],
        ['loafers', 'Classic Bit Loafer', 'Heritage', 3790, 0, 'lof-4', '39,40,41,42,43,44', 'Brown', 0],
        ['formal', 'Woven Leather Slip-On', 'Heritage', 4250, 4990, 'frm-1', '39,40,41,42,43,44', 'Brown', 1],
        ['formal', 'Wingtip Oxford', 'Heritage', 4490, 0, 'frm-2', '39,40,41,42,43', 'Burgundy', 0],
        ['formal', 'Tan Driving Loafer', 'Crafted', 3990, 4600, 'frm-3', '40,41,42,43,44', 'Tan', 0],
        ['formal', 'Midnight Derby', 'Crafted', 4150, 0, 'frm-4', '39,40,41,42,43', 'Navy', 0],
        ['sandals', 'Buckle Comfort Slide', 'Breeze', 1290, 1550, 'snd-1', '38,39,40,41,42,43,44', 'Black', 1],
        ['sandals', 'Leather Thong Sandal', 'Breeze', 1450, 1750, 'snd-2', '39,40,41,42,43', 'Black/Brown', 0],
        ['sandals', 'Double Strap Sandal', 'Breeze', 1690, 0, 'snd-3', '39,40,41,42,43,44', 'Olive', 0],
        ['sandals', 'Cork Buckle Sandal', 'Breeze', 1190, 1400, 'snd-4', '38,39,40,41,42,43', 'Brown', 0],
        ['running', 'Air Glide Runner', 'Stride', 3790, 4500, 'run-4', '39,40,41,42,43,44', 'Charcoal/White', 1],
        ['running', 'Pulse Knit Runner', 'Pacer', 3590, 4200, 'run-5', '39,40,41,42,43', 'Black/White', 0],
        ['sneakers', 'Clean White Court', 'Kicks', 2290, 2750, 'snk-5', '38,39,40,41,42,43', 'White', 1],
        ['loafers', 'Pinstripe Velvet Slipper', 'Heritage', 2650, 0, 'lof-5', '39,40,41,42,43', 'Navy Stripe', 0],
        ['formal', 'Patina Cap-Toe Oxford', 'Heritage', 5290, 6200, 'frm-5', '40,41,42,43,44', 'Midnight Blue', 1],
        ['formal', 'Classic Tan Derby', 'Crafted', 3690, 4300, 'frm-6', '39,40,41,42,43,44', 'Tan', 0],
        ['sandals', 'Crochet Fringe Slide', 'Breeze', 1350, 1600, 'snd-5', '36,37,38,39,40,41', 'Cream', 0],
        ['sandals', 'Cross-Strap Platform Sandal', 'Breeze', 1890, 2250, 'snd-6', '36,37,38,39,40', 'Maroon', 1],
        ['sneakers', 'Little Bloom Baby Sneaker', 'Tiny Steps', 990, 1250, 'kid-1', '19,20,21,22,23', 'Teal', 1],
        ['sneakers', 'Tiny Red Canvas', 'Tiny Steps', 890, 1100, 'kid-2', '19,20,21,22,23,24', 'Red', 0],
    ];
    require_once __DIR__ . '/caption.php';
    $colId = [];
    foreach ($pdo->query('SELECT id,slug,name FROM collections') as $r) $colId[$r['slug']] = $r;
    $pi = $pdo->prepare('INSERT INTO products(name,slug,collection_id,brand,price,old_price,images,sizes,colors,stock,short,description,fb_post,tags,featured,is_new,sort,created,updated,short_bn,description_bn,fb_post_en) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $now = date('Y-m-d H:i:s');
    foreach ($P as $i => $p) {
        $c = $colId[$p[0]];
        $cap = caption_template(['name' => $p[1], 'collection' => $c['name'], 'collection_slug' => $p[0], 'brand' => $p[2], 'price' => $p[3], 'old_price' => $p[4], 'sizes' => $p[6], 'colors' => $p[7]], $i);
        $pi->execute([$p[1], slugify($p[1]), $c['id'], $p[2], $p[3], $p[4], json_encode(["assets/img/demo/p/{$p[5]}.webp"]), $p[6], $p[7], $i % 5 === 1 ? 3 : 15,
            $cap['short'], $cap['description'], $cap['fb_post'], $cap['tags'], $p[8], $i % 4 === 0 ? 1 : 0, $i, $now, $now, $cap['short_bn'], $cap['description_bn'], $cap['fb_post_en']]);
    }
    jb_seed_gender($pdo);
}

// Men / Women / Kids for the demo products (everything else stays 'men')
function jb_seed_gender(PDO $pdo): void {
    $pdo->exec("UPDATE products SET gender='women' WHERE slug IN ('crochet-fringe-slide','cross-strap-platform-sandal','embroidered-slip-on')");
    $pdo->exec("UPDATE products SET gender='unisex' WHERE slug IN ('red-canvas-low','clean-white-court','canvas-high-top','color-pop-runner','urban-court-classic')");
    $pdo->exec("UPDATE products SET gender='kids' WHERE slug IN ('little-bloom-baby-sneaker','tiny-red-canvas')");
}
