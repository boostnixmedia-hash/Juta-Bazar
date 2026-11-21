<?php
// Auto caption: Gemini (free key, set in Admin > Settings) with a built-in template fallback that always works.

function caption_kind(string $text): string {
    $t = mb_strtolower($text);
    $map = [
        'running' => ['run', 'রানিং', 'দৌড়', 'jog', 'sport', 'স্পোর্টস', 'trail', 'gym', 'জিম', 'training'],
        'sandals' => ['sandal', 'স্যান্ডেল', 'slide', 'স্লাইড', 'slipper', 'স্লিপার', 'চপ্পল', 'kolhapuri', 'flip'],
        'loafers' => ['loafer', 'লোফার', 'moccasin', 'driver', 'tassel', 'penny', 'horsebit', 'slip-on', 'slip on'],
        'formal' => ['formal', 'ফরমাল', 'oxford', 'derby', 'brogue', 'monk', 'chelsea', 'boot', 'বুট', 'office', 'অফিস', 'leather', 'চামড়া'],
        'sneakers' => ['sneaker', 'স্নিকার', 'court', 'canvas', 'high top', 'casual', 'ক্যাজুয়াল', 'street'],
        'kids' => ['kid', 'বাচ্চা', 'শিশু', 'child', 'baby'],
        'ladies' => ['ladies', 'women', 'মহিলা', 'লেডিস', 'heel', 'হিল', 'girl'],
    ];
    foreach ($map as $k => $words) foreach ($words as $w) if (mb_strpos($t, $w) !== false) return $k;
    return 'general';
}

function caption_template(array $in, ?int $seed = null, string $tone = 'premium'): array {
    $name = trim($in['name'] ?? '') ?: 'নতুন জুতা';
    $col = trim($in['collection'] ?? '');
    $brand = trim($in['brand'] ?? '');
    $price = (int)($in['price'] ?? 0);
    $old = (int)($in['old_price'] ?? 0);
    $sizes = list_csv((string)($in['sizes'] ?? ''));
    $colors = list_csv((string)($in['colors'] ?? ''));
    $kind = caption_kind(($in['collection_slug'] ?? '') . ' ' . $col . ' ' . $name);
    $seed = $seed ?? random_int(0, 999);
    $pick = fn(array $a, int $o = 0) => $a[($seed + $o) % count($a)];

    $K = [
        'running' => [
            'hooks' => ['প্রতিটা দৌড়ে নতুন রেকর্ড গড়ার সঙ্গী', 'হালকা পায়ে আরও দূরে যান', 'মর্নিং ওয়াক থেকে ম্যারাথন, সবখানে আরাম'],
            'feat' => ['শ্বাস নিতে পারা মেশ আপার, পা থাকে ঠান্ডা ও শুকনো', 'নরম কুশনিং সোল, হাঁটু ও গোড়ালিতে কম চাপ', 'হালকা ওজন, সারাদিন পরলেও ক্লান্তি নেই', 'মজবুত গ্রিপ, ভেজা রাস্তাতেও পিছলায় না'],
            'use' => 'দৌড়, জিম, হাঁটা আর প্রতিদিনের ব্যবহারের জন্য একদম পারফেক্ট।',
            'tags' => ['RunningShoes', 'রানিংশু', 'SportsShoes', 'GymShoes'],
        ],
        'sneakers' => [
            'hooks' => ['স্টাইল আর আরামের পারফেক্ট কম্বিনেশন', 'যেকোনো আউটফিটে স্মার্ট লুক', 'প্রতিদিনের ফেভারিট স্নিকার্স'],
            'feat' => ['প্রিমিয়াম ফিনিশিং আপার, দেখতে একদম ঝকঝকে', 'নরম ইনসোল, সারাদিন আরামে হাঁটুন', 'মজবুত রাবার সোল, দীর্ঘদিন টেকসই', 'জিন্স, প্যান্ট বা শর্টস সবকিছুর সাথে মানায়'],
            'use' => 'ক্যাম্পাস, আড্ডা, ঘোরাঘুরি বা অফিসের ক্যাজুয়াল দিনে নিশ্চিন্তে পরুন।',
            'tags' => ['Sneakers', 'স্নিকার্স', 'CasualShoes', 'StreetStyle'],
        ],
        'loafers' => [
            'hooks' => ['ফিতা ছাড়াই ক্লাসিক জেন্টলম্যান লুক', 'এক সেকেন্ডে পরুন, সারাদিন আরামে থাকুন', 'স্মার্ট ক্যাজুয়ালের সেরা পছন্দ'],
            'feat' => ['স্লিপ-অন ডিজাইন, পরা ও খোলা খুব সহজ', 'নরম ও ফ্লেক্সিবল সোল', 'প্রিমিয়াম ফিনিশিং, ক্লাসিক স্টিচিং', 'প্যান্ট, চিনো বা পাঞ্জাবির সাথে দারুণ মানায়'],
            'use' => 'অফিস, দাওয়াত, ঈদ বা প্রতিদিনের স্মার্ট লুকের জন্য আদর্শ।',
            'tags' => ['Loafers', 'লোফার', 'MensFashion', 'SmartCasual'],
        ],
        'formal' => [
            'hooks' => ['প্রথম দেখাতেই ভালো ইমপ্রেশন', 'অফিস থেকে বিয়ের অনুষ্ঠান, সবখানে আত্মবিশ্বাস', 'ক্লাসিক ফরমাল, যা কখনো পুরনো হয় না'],
            'feat' => ['প্রিমিয়াম লেদার লুক, ঝকঝকে পালিশ ফিনিশ', 'কুশনড ইনসোল, লম্বা সময় পরলেও আরাম', 'মজবুত সোল ও নিখুঁত সেলাই', 'স্যুট, ব্লেজার বা ফরমাল প্যান্টের সাথে পারফেক্ট'],
            'use' => 'অফিস, মিটিং, বিয়ে আর যেকোনো বিশেষ অনুষ্ঠানের জন্য।',
            'tags' => ['FormalShoes', 'ফরমালশু', 'OfficeShoes', 'LeatherShoes'],
        ],
        'sandals' => [
            'hooks' => ['গরমে পায়ের জন্য সবচেয়ে আরামের সঙ্গী', 'হালকা, নরম আর টেকসই', 'ঘরে-বাইরে প্রতিদিনের আরাম'],
            'feat' => ['নরম ফুটবেড, পায়ে কোনো চাপ লাগে না', 'হালকা ওজন, সহজে পরা যায়', 'পানিতে নষ্ট হয় না এমন টেকসই ম্যাটেরিয়াল', 'নন-স্লিপ সোল, পিছলে পড়ার ভয় নেই'],
            'use' => 'বাসা, বাজার, ভ্রমণ, নামাজ বা সমুদ্র সৈকত, সবখানে পরতে পারবেন।',
            'tags' => ['Sandals', 'স্যান্ডেল', 'SummerShoes', 'ComfortWear'],
        ],
        'kids' => [
            'hooks' => ['ছোট্ট পায়ের জন্য বড় আরাম', 'খেলাধুলায় দুরন্ত, পায়ে নিরাপদ', 'বাচ্চাদের ফেভারিট রঙিন জুতা'],
            'feat' => ['নরম ও হালকা, বাচ্চার পায়ে আরামদায়ক', 'সহজে পরা ও খোলা যায়', 'নন-স্লিপ সোল', 'টেকসই ও সহজে পরিষ্কার করা যায়'],
            'use' => 'স্কুল, খেলা আর বেড়াতে যাওয়ার জন্য।',
            'tags' => ['KidsShoes', 'বাচ্চাদেরজুতা'],
        ],
        'ladies' => [
            'hooks' => ['আপনার স্টাইলে আনুন নতুন মাত্রা', 'আরাম আর এলিগেন্স একসাথে', 'প্রতিটা অনুষ্ঠানে নজর কাড়ুন'],
            'feat' => ['এলিগেন্ট ডিজাইন, প্রিমিয়াম ফিনিশ', 'নরম কুশন ইনসোল', 'হালকা ও আরামদায়ক', 'শাড়ি, সালোয়ার বা ওয়েস্টার্ন সব পোশাকে মানায়'],
            'use' => 'দাওয়াত, অফিস আর প্রতিদিনের ব্যবহারের জন্য।',
            'tags' => ['LadiesShoes', 'লেডিসজুতা'],
        ],
        'general' => [
            'hooks' => ['আরাম আর স্টাইলের নতুন ঠিকানা', 'প্রতিটা পদক্ষেপে আত্মবিশ্বাস', 'আপনার পায়ের জন্য সেরা পছন্দ'],
            'feat' => ['প্রিমিয়াম কোয়ালিটি ম্যাটেরিয়াল', 'নরম ও আরামদায়ক ইনসোল', 'মজবুত ও টেকসই সোল', 'আধুনিক ডিজাইন, সব পোশাকে মানানসই'],
            'use' => 'প্রতিদিনের ব্যবহার আর বিশেষ দিনের জন্য।',
            'tags' => ['Shoes', 'জুতা'],
        ],
    ];
    $k = $K[$kind];
    $hook = $pick($k['hooks']);
    $title = $brand && stripos($name, $brand) === false ? "$brand $name" : $name;

    $short = "{$hook}। " . ($col ? "আমাদের $col কালেকশনের " : '') . "$title, " . ($colors ? implode(', ', $colors) . ' রঙে' : 'আকর্ষণীয় রঙে') . ", " . ($sizes ? 'সাইজ ' . bn_num($sizes[0]) . '–' . bn_num(end($sizes)) : 'সব সাইজে') . ' পাওয়া যাচ্ছে।';

    $desc = "$title: {$hook}।\n\n";
    foreach ($k['feat'] as $f) $desc .= "✔ $f\n";
    if ($colors) $desc .= '✔ রং: ' . implode(', ', $colors) . "\n";
    if ($sizes) $desc .= '✔ সাইজ: ' . bn_num(implode(', ', $sizes)) . "\n";
    $desc .= "\n" . $k['use'] . "\n\nসাইজ নিয়ে দ্বিধা থাকলে অর্ডারের সময় নোটে আপনার পায়ের মাপ (ইঞ্চি/সেমি) লিখে দিন, আমরা মিলিয়ে পাঠাবো। ক্যাশ অন ডেলিভারি, পণ্য হাতে পেয়ে টাকা দিন।";

    $cur = S('currency');
    $off = $old > $price && $price ? (int)round(100 - $price * 100 / $old) : 0;
    $priceLine = $price ? ($off ? '💰 দাম: ~' . bn_num(number_format($old)) . '~ এখন মাত্র ' . $cur . bn_num(number_format($price)) . ' (' . bn_num((string)$off) . '% ছাড়)' : '💰 দাম: ' . $cur . bn_num(number_format($price))) : '';
    $priceEn = $price ? ($off ? "💰 Price: ~{$cur}" . number_format($old) . "~ now {$cur}" . number_format($price) . " ($off% off)" : "💰 Price: {$cur}" . number_format($price)) : '';
    $shop = S('shop_name');
    $tags = array_merge($k['tags'], [preg_replace('/\s+/u', '', $shop), preg_replace('/\s+/u', '', S('shop_name_en')), 'ShoesBD', 'BangladeshShopping', 'CashOnDelivery']);
    $tagStr = implode(' ', array_map(fn($t) => '#' . $t, array_unique($tags)));
    // Opening and closing lines change with the chosen tone
    $open = [
        'premium' => ["✨ $title ✨", "{$hook}।"],
        'offer' => [$off ? "🔥 " . bn_num($off) . "% ছাড়! $title 🔥" : "🔥 সেরা দামে $title 🔥", $off ? 'অফার চলাকালীন অর্ডার করুন, দাম বাড়ার আগেই!' : "$hook!"],
        'festive' => ["🌙 উৎসবের লুক হোক আরও স্পেশাল 🌙", "{$title}: {$hook}।"],
        'casual' => ["👟 নতুন জোড়া এসে গেছে: $title", "$hook, একবার পরলেই বুঝবেন 😍"],
    ][$tone] ?? ["✨ $title ✨", "{$hook}।"];
    $close = ['premium' => '✔ প্রতিটি জোড়া পাঠানোর আগে হাতে চেক করা হয়', 'offer' => '⏳ স্টক সীমিত, এখনই অর্ডার করুন', 'festive' => '🎁 প্রিয়জনের জন্য সেরা উপহার', 'casual' => '💬 সাইজ নিয়ে প্রশ্ন? ইনবক্সে জানান'][$tone] ?? '';
    $fb = $open[0] . "\n" . $open[1] . "\n\n";
    foreach (array_slice($k['feat'], 0, 3) as $f) $fb .= "👟 $f\n";
    $fb .= "\n" . ($colors ? '🎨 রং: ' . implode(', ', $colors) . "\n" : '') . ($sizes ? '📏 সাইজ: ' . bn_num(implode(', ', $sizes)) . "\n" : '') . ($priceLine ? "$priceLine\n" : '');
    $fb .= "🚚 সারা বাংলাদেশে ক্যাশ অন ডেলিভারি\n" . ($close ? "$close\n" : '') . "📦 অর্ডার করতে ইনবক্স করুন অথবা ওয়েবসাইটে অর্ডার দিন" . (S('phone') ? "\n📞 " . bn_num(S('phone')) : '') . "\n\n" . $tagStr;

    $en = caption_template_en($in, $seed);
    $openEn = ['premium' => ["✨ $title ✨", $en['hook'] . '.'], 'offer' => [$off ? "🔥 {$off}% OFF: $title 🔥" : "🔥 Best price: $title 🔥", $off ? 'Grab it while the offer lasts!' : $en['hook'] . '!'],
        'festive' => ['🌙 Make this festive season special 🌙', "$title: " . $en['hook'] . '.'], 'casual' => ["👟 New drop: $title", $en['hook'] . '. You will feel it from the first step.']][$tone] ?? ["✨ $title ✨", $en['hook'] . '.'];
    $fbEn = $openEn[0] . "\n" . $openEn[1] . "\n\n";
    foreach (array_slice($en['feat'], 0, 3) as $f) $fbEn .= "👟 $f\n";
    $fbEn .= "\n" . ($colors ? '🎨 Color: ' . implode(', ', $colors) . "\n" : '') . ($sizes ? '📏 Sizes: ' . implode(', ', $sizes) . "\n" : '') . ($priceEn ? "$priceEn\n" : '');
    $fbEn .= "🚚 Cash on delivery all over Bangladesh\n📦 DM us or order on our website" . (S('phone') ? "\n📞 " . S('phone') : '') . "\n\n" . $tagStr;
    $insta = $open[0] . "\n" . $en['hook'] . ".\n" . ($priceLine ? "$priceLine\n" : '') . "🚚 COD সারা দেশে\n\n" . $tagStr;
    $seoTitle = mb_substr("$title" . ($col ? " | $col" : '') . ' | ' . S('shop_name_en'), 0, 65);
    $seoDesc = mb_substr($en['short'] . ' Cash on delivery all over Bangladesh.', 0, 158);

    return ['short' => $en['short'], 'description' => $en['description'], 'short_bn' => $short, 'description_bn' => $desc,
        'fb_post' => $fb, 'fb_post_en' => $fbEn, 'insta' => $insta, 'seo_title' => $seoTitle, 'seo_desc' => $seoDesc,
        'tags' => implode(', ', $k['tags']), 'source' => 'template'];
}

/** English product copy for the website (short line + description). The Facebook post stays in Bangla. */
function caption_template_en(array $in, int $seed = 0): array {
    $name = trim($in['name'] ?? '') ?: 'New pair';
    $brand = trim($in['brand'] ?? '');
    $sizes = list_csv((string)($in['sizes'] ?? ''));
    $colors = list_csv((string)($in['colors'] ?? ''));
    $kind = caption_kind(($in['collection_slug'] ?? '') . ' ' . ($in['collection'] ?? '') . ' ' . $name);
    $K = [
        'running' => [['Built to keep up with every run', 'Go lighter, go further', 'From morning walks to long runs'], ['Breathable mesh upper keeps feet cool and dry', 'Soft cushioned sole takes the load off knees and ankles', 'Lightweight, so you can wear it all day', 'Grippy outsole for wet roads'], 'Made for running, the gym, walking and everyday wear.'],
        'sneakers' => [['Style and comfort in one pair', 'A sharp look with any outfit', 'Your everyday favourite sneaker'], ['Clean, premium-finish upper', 'Soft insole for all-day comfort', 'Durable rubber sole', 'Goes with jeans, chinos or shorts'], 'Perfect for campus, hangouts, travel and casual office days.'],
        'loafers' => [['Classic gentleman look, no laces needed', 'Slip on in a second, stay comfortable all day', 'The smart-casual essential'], ['Easy slip-on design', 'Soft, flexible sole', 'Neat stitching and a premium finish', 'Pairs well with trousers, chinos or panjabi'], 'Ideal for the office, dinners, Eid and everyday smart looks.'],
        'formal' => [['Make a strong first impression', 'Confidence from the office to the wedding hall', 'A formal classic that never dates'], ['Polished, premium leather look', 'Cushioned insole for long hours', 'Sturdy sole and precise stitching', 'Made for suits, blazers and formal trousers'], 'For the office, meetings, weddings and special occasions.'],
        'sandals' => [['Easy comfort for warm days', 'Light, soft and made to last', 'Everyday comfort, indoors and out'], ['Soft footbed with no pressure points', 'Lightweight and easy to slip on', 'Durable, water-friendly material', 'Non-slip sole'], 'Wear it at home, to the market, on trips or at the beach.'],
        'kids' => [['Big comfort for little feet', 'Ready for play, safe on the feet', 'A colourful kids favourite'], ['Soft and light', 'Easy on, easy off', 'Non-slip sole', 'Tough and easy to clean'], 'For school, play and days out.'],
        'ladies' => [['Add a new touch to your style', 'Comfort and elegance together', 'Turn heads at every occasion'], ['Elegant design with a premium finish', 'Soft cushioned insole', 'Light and comfortable', 'Works with sari, salwar or western wear'], 'For dinners, the office and every day.'],
        'general' => [['Comfort and style, made simple', 'Confidence in every step', 'A great pick for your feet'], ['Quality materials', 'Soft, comfortable insole', 'Strong, durable sole', 'Modern design that goes with everything'], 'For everyday wear and special days.'],
    ][$kind];
    $hook = $K[0][$seed % 3];
    $title = $brand && stripos($name, $brand) === false ? "$brand $name" : $name;
    $short = "$hook. $title" . ($colors ? ' in ' . implode(', ', $colors) : '') . ($sizes ? ', sizes ' . $sizes[0] . '–' . end($sizes) : '') . '.';
    $desc = "$title: $hook.\n\n";
    foreach ($K[1] as $f) $desc .= "✔ $f\n";
    if ($colors) $desc .= '✔ Color: ' . implode(', ', $colors) . "\n";
    if ($sizes) $desc .= '✔ Sizes: ' . implode(', ', $sizes) . "\n";
    $desc .= "\n" . $K[2] . "\n\nNot sure about your size? Add your foot length to the order note and we will match it. Cash on delivery: pay when it reaches you.";
    return ['short' => $short, 'description' => $desc, 'hook' => $hook, 'feat' => $K[1]];
}

/** Ask Gemini. Returns null on any failure so the caller can fall back to the template. */
function caption_gemini(array $in, string $imageB64 = '', string $mime = 'image/jpeg', string &$err = '', string $tone = 'premium'): ?array {
    $key = trim(S('gemini_key'));
    if ($key === '' || !function_exists('curl_init')) { $err = 'no key'; return null; }
    $facts = "Product name: {$in['name']}\nCollection/category: {$in['collection']}\nBrand: {$in['brand']}\nPrice: {$in['price']} BDT" .
        (($in['old_price'] ?? 0) > ($in['price'] ?? 0) ? "\nRegular price (before discount): {$in['old_price']} BDT" : '') .
        "\nSizes: {$in['sizes']}\nColors: {$in['colors']}\nShop name: " . S('shop_name') . "\nExtra notes from shop owner: " . ($in['notes'] ?? '');
    $toneTxt = ['premium' => 'premium and elegant, like a luxury fashion brand', 'offer' => 'an exciting sale/offer post that pushes people to order now (only mention a discount if a regular price is given)', 'festive' => 'festive (Eid / wedding / celebration season), warm and gift-friendly', 'casual' => 'friendly and casual, like talking to a friend'][$tone] ?? 'premium';
    $prompt = "You write product copy for a premium Bangladeshi online shoe shop. Tone: $toneTxt. "
        . "Use ONLY the facts given (and what is clearly visible in the photo if one is attached: shape, colour, material look, sole, style). Never invent things like 'genuine leather', warranties, reviews or discounts that are not given.\n\n$facts\n\n"
        . "Return JSON with these keys:\n"
        . "short: 1-2 sentence selling line in polished English, max 200 chars.\n"
        . "description: English product page text: 1 intro line, then 4-6 lines each starting with ✔ (features/benefits, mention what you see in the photo), then 1-2 lines on where to wear it, then one line that it is cash on delivery.\n"
        . "short_bn: the same as short, in natural, elegant Bengali (Bangla script).\n"
        . "description_bn: the same as description, in natural, elegant Bengali, same ✔ format.\n"
        . "fb_post: Facebook post in warm, simple Bengali the way top Bangladeshi fashion pages write: catchy first line with emoji, 3-4 emoji bullet lines, colors, sizes, price line, cash on delivery line, order line, then 8-12 hashtags mixing English and Bangla.\n"
        . "fb_post_en: the same Facebook post in English.\n"
        . "insta: a short Instagram caption (max 4 lines + hashtags), mixing Bengali and English naturally.\n"
        . "seo_title: Google title, max 60 chars, English. seo_desc: Google description, max 155 chars, English.\n"
        . "tags: comma separated 4-6 short English keywords.";
    $parts = [['text' => $prompt]];
    if ($imageB64 !== '') $parts[] = ['inline_data' => ['mime_type' => $mime, 'data' => $imageB64]];
    $body = json_encode(['contents' => [['parts' => $parts]], 'generationConfig' => ['temperature' => 0.9, 'responseMimeType' => 'application/json']], JSON_UNESCAPED_UNICODE);
    foreach (['gemini-flash-latest', 'gemini-flash-lite-latest', 'gemini-2.5-flash'] as $model) {
        $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent");
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 40,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-goog-api-key: ' . $key]]);
        $res = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $cerr = curl_error($ch); curl_close($ch);
        if ($res === false || $code !== 200) { $err = $cerr ?: "HTTP $code " . substr((string)$res, 0, 200); if ($code === 400 || $code === 403) break; continue; }
        $j = json_decode($res, true);
        $txt = $j['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $txt = trim(preg_replace('/^```(json)?|```$/m', '', $txt));
        $out = json_decode($txt, true);
        if (is_array($out) && !empty($out['description'])) {
            foreach (['short', 'description', 'short_bn', 'description_bn', 'fb_post', 'fb_post_en', 'insta', 'seo_title', 'seo_desc', 'tags'] as $f) $out[$f] = is_array($out[$f] ?? '') ? implode(', ', $out[$f]) : trim((string)($out[$f] ?? ''));
            $out['source'] = 'ai';
            return $out;
        }
        $err = 'bad AI reply';
    }
    return null;
}
