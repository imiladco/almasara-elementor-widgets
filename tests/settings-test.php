<?php
/**
 * تنظیمات ویجت بخش محصولات و حل مقادیر ریسپانسیو.
 */

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/includes/responsive.php';
require_once dirname(__DIR__) . '/includes/product-section/settings.php';

use Almasara_Widgets\Responsive;
use Almasara_Widgets\Product_Section\Settings;

Tests::group('ریسپانسیو › ارث‌بری');

// المنتور مقدار تنظیم‌نشده را «رشتهٔ خالی» ذخیره می‌کند نه null، و همین
// باعث شده بود ?? هیچ‌وقت عمل نکند و (int) '' برابر صفر شود.
$gap = Responsive::resolve(
    ['space_between' => 20, 'space_between_tablet' => '', 'space_between_mobile' => ''],
    'space_between',
    Responsive::to_int()
);
Tests::same('تبلتِ خالی از دسکتاپ ارث می‌برد', $gap['tablet'], 20);
Tests::same('موبایلِ خالی از دسکتاپ ارث می‌برد', $gap['mobile'], 20);

$gap2 = Responsive::resolve(
    ['space_between' => 20, 'space_between_tablet' => 10, 'space_between_mobile' => ''],
    'space_between',
    Responsive::to_int()
);
Tests::same('موبایل از تبلت ارث می‌برد نه دسکتاپ', $gap2['mobile'], 10);

$slider = ['w' => ['size' => '', 'unit' => 'px'], 'w_mobile' => ['size' => 200, 'unit' => 'px']];
Tests::same('کنترل اسلایدری خالی = تنظیم‌نشده', Responsive::has($slider, 'w', 'desktop'), false);
Tests::same('کنترل اسلایدری پرشده = تنظیم‌شده', Responsive::has($slider, 'w', 'mobile'), true);
Tests::same('صفر یک مقدار معتبر است', Responsive::value(0), 0);
Tests::same('رشتهٔ خالی یعنی تنظیم‌نشده', Responsive::value(''), null);

Tests::group('اسلایدر › عرض دستی');

$cfg = Settings::slider([
    'slides_per_view' => 4,
    'speed'           => 600,
    'space_between'   => 20,
    'slide_width_mobile' => ['size' => 200, 'unit' => 'px'],
]);
Tests::same('عرض دستی موبایل → slidesPerView=auto', $cfg['slidesPerView'], 'auto');
Tests::same('دسکتاپ عددی می‌ماند', $cfg['breakpoints'][1025]['slidesPerView'], 4.0);

Tests::group('کوئری › اعتبارسنجی ورودی');

Tests::same('orderby ناشناخته امن می‌شود', Settings::query(['orderby' => '; DROP TABLE'])['orderby'], 'date');
Tests::same('order ناشناخته امن می‌شود', Settings::query(['order' => 'junk'])['order'], 'DESC');
Tests::same('تعداد محدود می‌شود', Settings::query(['products_count' => 9999])['count'], 48);
Tests::same('تعداد حداقل ۱', Settings::query(['products_count' => 0])['count'], 1);
Tests::same('کش در ادیتور خاموش', Settings::query(['cache_minutes' => 30], true)['cache'], 0);
Tests::same('کش در فرانت فعال', Settings::query(['cache_minutes' => 30], false)['cache'], 30);
Tests::same('کش سقف دارد', Settings::query(['cache_minutes' => 99999])['cache'], 1440);

Tests::group('فیلترها');

Tests::same(
    'حداقل قیمت بدون فیلتر قیمت نادیده گرفته می‌شود',
    Settings::filters(['filter_min_price' => 5000])['min_price'],
    0.0
);
Tests::same(
    'حداقل قیمت با فیلتر قیمت اعمال می‌شود',
    Settings::filters(['filter_has_price' => 'yes', 'filter_min_price' => 5000])['min_price'],
    5000.0
);
Tests::same(
    'حداقل قیمت منفی صفر می‌شود',
    Settings::filters(['filter_has_price' => 'yes', 'filter_min_price' => -5])['min_price'],
    0.0
);

Tests::group('منبع کارت');

Tests::same('نبودِ کلید = جت‌انجین (سازگاری عقب‌رو)', Settings::source([]), 'jetengine');
Tests::same('مقدار builtin', Settings::source(['card_source' => 'builtin']), 'builtin');
Tests::same('مقدار ناشناخته = جت‌انجین', Settings::source(['card_source' => 'nonsense']), 'jetengine');

Tests::group('REST › محدودیت دسته‌بندی');

// بدون این محدودیت می‌شد با پیمایش شناسه‌ها برای هر ترمِ سایت یک کوئری و
// یک کلید کش تازه ساخت
$widget = ['categories' => [['category' => 12], ['category' => 34], ['category' => 0]]];

Tests::same('دستهٔ تعریف‌شده مجاز است', Settings::allows_category($widget, 12), true);
Tests::same('دستهٔ دوم مجاز است', Settings::allows_category($widget, 34), true);
Tests::same('صفر یعنی «همه» و مجاز است', Settings::allows_category($widget, 0), true);
Tests::same('دستهٔ تعریف‌نشده رد می‌شود', Settings::allows_category($widget, 99), false);
Tests::same('ویجت بدون دسته فقط «همه» را می‌پذیرد', Settings::allows_category([], 5), false);
Tests::same('شناسه‌های تکراری و صفر پاک می‌شوند', Settings::categories($widget), [12, 34]);
