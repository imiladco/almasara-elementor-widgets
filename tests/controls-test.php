<?php
/**
 * پایداری فهرست کنترل‌ها.
 *
 * نام کنترل در المنتور همان کلیدی است که تنظیمات کاربر زیرش ذخیره می‌شود.
 * عوض‌شدن یا حذف یک نام یعنی هر صفحه‌ای که از قبل این ویجت را دارد آن تنظیم
 * را بی‌صدا از دست می‌دهد. این تست فهرست فعلی را با یک عکس ثبت‌شده مقایسه
 * می‌کند تا چنین تغییری تصادفی رخ ندهد.
 *
 * اگر عمداً کنترلی اضافه یا حذف کردید، فایل fixtures-controls.txt را
 * به‌روزرسانی کنید و در commit توضیح دهید چرا.
 *
 * ضمناً چند خطای ساختاری که پنل المنتور را بی‌صدا خراب می‌کنند هم بررسی
 * می‌شود: نام تکراری، سکشن یا تب نامتوازن، و تب تودرتو.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/elementor-stub.php';

$root = dirname(__DIR__);

$controls = amw_collect_controls(
    \Almasara_Widgets\Widgets\Product_Section::class,
    [
        $root . '/includes/widgets/traits/intro-row.php',
        $root . '/includes/widgets/product-section/content-controls.php',
        $root . '/includes/widgets/product-section/style-controls.php',
        $root . '/includes/widgets/product-section.php',
    ]
);

Tests::group('کنترل‌ها › پایداری فهرست');

$expected = array_values(array_filter(
    array_map('trim', explode("\n", (string) file_get_contents(__DIR__ . '/fixtures-controls.txt'))),
    static fn($line) => '' !== $line
));

Tests::same('تعداد کل موارد', count($controls), count($expected));

$missing = array_diff($expected, $controls);
$added   = array_diff($controls, $expected);

Tests::ok(
    'هیچ کنترلی حذف یا تغییر نام نداده',
    [] === $missing,
    $missing ? implode(', ', array_slice($missing, 0, 8)) : ''
);

Tests::ok(
    'هیچ کنترل ثبت‌نشده‌ای اضافه نشده',
    [] === $added,
    $added ? implode(', ', array_slice($added, 0, 8)) : ''
);

Tests::ok('ترتیب ثبت هم یکسان است', $controls === $expected);

Tests::group('کنترل‌ها › سلامت ساختار');

$names = array_values(array_filter(
    $controls,
    static fn($entry) => !preg_match('#^(/|SECTION:|TABS:|TAB:)#', $entry)
));

$duplicates = array_values(array_unique(array_diff_assoc($names, array_unique($names))));
Tests::ok(
    'نام تکراری وجود ندارد',
    [] === $duplicates,
    $duplicates ? implode(', ', $duplicates) : ''
);

$ids = array_values(array_filter(
    $controls,
    static fn($entry) => (bool) preg_match('/^(SECTION|TABS|TAB):/', $entry)
));
$dupIds = array_values(array_unique(array_diff_assoc($ids, array_unique($ids))));
Tests::ok(
    'شناسهٔ سکشن یا تب تکراری وجود ندارد',
    [] === $dupIds,
    $dupIds ? implode(', ', $dupIds) : ''
);

/*
 * تب تودرتو در المنتور پشتیبانی نمی‌شود و پنل را بی‌صدا خراب می‌کند، و
 * سکشن/تبِ بسته‌نشده هم همین‌طور. عمق باید هیچ‌وقت از ۱ بیشتر نشود و در
 * پایان دقیقاً صفر برگردد.
 */
foreach ([
    ['گروه تب', 'TABS:', '/TABS'],
    ['تب', 'TAB:', '/TAB'],
    ['سکشن', 'SECTION:', '/SECTION'],
] as [$label, $open, $close]) {
    $depth = 0;
    $max   = 0;

    foreach ($controls as $entry) {
        if (str_starts_with($entry, $open)) {
            $max = max($max, ++$depth);
        } elseif ($entry === $close) {
            $depth--;
        }
    }

    Tests::same("{$label}: تودرتو نیست", $max, 1);
    Tests::same("{$label}: همه بسته شده‌اند", $depth, 0);
}
