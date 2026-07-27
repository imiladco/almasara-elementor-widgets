<?php
/**
 * تست یکپارچه با ووکامرس واقعی.
 *
 * چیزهایی که فقط با دیتابیس واقعی قابل سنجش‌اند: نمایانی کاتالوگ، فیلترها،
 * جدول جست‌وجوی ووکامرس و تکراری‌نشدن محصول متغیر، محاسبهٔ مالیات، و
 * انتخاب ارزان‌ترین گزینه.
 */

use Almasara_Widgets\Product_Card;
use Almasara_Widgets\Product_Section\Query;

/* ==========================================================================
 * نمایانی کاتالوگ
 * ======================================================================= */

Tests::group('یکپارچه › نمایانی کاتالوگ');

$visible = Fixture::simple(['name' => 'قابل نمایش', 'price' => 1000]);
$hidden  = Fixture::simple(['name' => 'پنهان', 'price' => 1000, 'visibility' => 'hidden']);
$noCat   = Fixture::simple(['name' => 'خارج از کاتالوگ', 'price' => 1000, 'visibility' => 'search']);

$html = Query::render(amw_query_args())['html'];

Tests::ok('محصول عادی نمایش داده می‌شود', amw_html_has($html, $visible));
Tests::ok('محصول پنهان نمایش داده نمی‌شود', !amw_html_has($html, $hidden));
Tests::ok('محصول «حذف از کاتالوگ» نمایش داده نمی‌شود', !amw_html_has($html, $noCat));

Fixture::cleanup();

/* ==========================================================================
 * فیلترها
 * ======================================================================= */

Tests::group('یکپارچه › فیلترها');

$priced   = Fixture::simple(['name' => 'دارای قیمت', 'price' => 5000]);
$unpriced = Fixture::simple(['name' => 'بدون قیمت', 'price' => null]);
$cheap    = Fixture::simple(['name' => 'ارزان', 'price' => 100]);
$oos      = Fixture::simple(['name' => 'ناموجود', 'price' => 3000, 'stock_status' => 'outofstock']);
$withImg  = Fixture::simple(['name' => 'با عکس', 'price' => 2000, 'image_id' => Fixture::image()]);
$free     = Fixture::simple(['name' => 'رایگان', 'price' => 0]);

$html = Query::render(amw_query_args(['has_price' => true]))['html'];
Tests::ok('فیلتر قیمت: دارای قیمت می‌ماند', amw_html_has($html, $priced));
Tests::ok('فیلتر قیمت: بدون قیمت حذف می‌شود', !amw_html_has($html, $unpriced));
Tests::ok('فیلتر قیمت: رایگان حذف نمی‌شود', amw_html_has($html, $free));

$html = Query::render(amw_query_args(['has_price' => true, 'min_price' => 1000.0]))['html'];
Tests::ok('حداقل قیمت: گران‌تر می‌ماند', amw_html_has($html, $priced));
Tests::ok('حداقل قیمت: ارزان‌تر حذف می‌شود', !amw_html_has($html, $cheap));

$html = Query::render(amw_query_args(['in_stock' => true]))['html'];
Tests::ok('فیلتر موجودی: موجود می‌ماند', amw_html_has($html, $priced));
Tests::ok('فیلتر موجودی: ناموجود حذف می‌شود', !amw_html_has($html, $oos));

$html = Query::render(amw_query_args(['has_image' => true]))['html'];
Tests::ok('فیلتر عکس: دارای عکس می‌ماند', amw_html_has($html, $withImg));
Tests::ok('فیلتر عکس: بدون عکس حذف می‌شود', !amw_html_has($html, $priced));

Fixture::cleanup();

/* ==========================================================================
 * محصول متغیر و جدول جست‌وجو
 * ======================================================================= */

Tests::group('یکپارچه › محصول متغیر');

// چند ردیف _price برای یک محصول: دقیقاً همان چیزی که قبلاً باعث می‌شد
// مرتب‌سازی با قیمت، کارت تکراری بسازد
$variable = Fixture::variable([
    ['regular' => 3000],
    ['regular' => 5000],
    ['regular' => 9000],
]);

$result = Query::render(amw_query_args(['orderby' => 'price', 'order' => 'ASC']));

Tests::same(
    'مرتب‌سازی با قیمت، محصول متغیر را تکراری نمی‌کند',
    amw_html_count($result['html'], $variable),
    1
);
Tests::same('فقط یک کارت رندر شده', amw_card_count($result['html']), 1);

$price = Product_Card::get_price($variable);
Tests::same('کارت، کمترین قیمت گزینه‌ها را نشان می‌دهد', $price['num'], wc_price_test_format(3000));

Fixture::cleanup();

/* ==========================================================================
 * تخفیف روی محصول متغیر
 * ======================================================================= */

Tests::group('یکپارچه › تخفیف محصول متغیر');

/*
 * دام واقعی: ارزان‌ترین قیمتِ فعال از یک گزینه می‌آید و ارزان‌ترین قیمتِ
 * عادی از گزینهٔ دیگر. مقایسهٔ آن دو درصدی می‌سازد که هیچ گزینه‌ای ندارد.
 *
 * اینجا گزینهٔ اول عادی ۱۰۰۰۰ با تخفیف ۹۰۰۰ است و گزینهٔ دوم عادی ۲۰۰۰
 * بدون تخفیف. کمترین قیمت فعال ۲۰۰۰ (گزینهٔ دوم) و کمترین قیمت عادی هم
 * ۲۰۰۰ است — یعنی هیچ تخفیفی نباید نمایش داده شود.
 */
$mixed = Fixture::variable([
    ['regular' => 10000, 'sale' => 9000],
    ['regular' => 2000],
]);

/*
 * سازنده عمداً صدا زده نمی‌شود: اگر المنتور واقعی نصب باشد، Widget_Base
 * آرگومان می‌خواهد و بدون کانتکست ویجت خطا می‌دهد. منطق قیمت به هیچ‌کدام
 * از آن‌ها وابسته نیست، پس نمونه‌سازی بدون سازنده هم درست کار می‌کند و هم
 * با استاب و هم با المنتور واقعی سازگار است.
 */
$widget = (new ReflectionClass(\Almasara_Widgets\Widgets\Product_Price::class))->newInstanceWithoutConstructor();
$method = new ReflectionMethod($widget, 'get_price_data');
$method->setAccessible(true);
$data = $method->invoke($widget, $mixed, 'min');

Tests::same('قیمت فعلی = ارزان‌ترین گزینه', (float) $data['current'], 2000.0);
Tests::ok(
    'برای گزینهٔ بدون تخفیف، قیمت پیشین نشان داده نمی‌شود',
    !$data['on_sale'],
    'old=' . $data['old']
);

// حالا گزینه‌ای که واقعاً ارزان‌ترین و تخفیف‌خورده است
$realSale = Fixture::variable([
    ['regular' => 10000, 'sale' => 1000],
    ['regular' => 8000],
]);
$data = $method->invoke($widget, $realSale, 'min');

Tests::same('قیمت فعلی = قیمت تخفیف‌خورده', (float) $data['current'], 1000.0);
Tests::same('قیمت پیشین از همان گزینه می‌آید', (float) $data['old'], 10000.0);

Fixture::cleanup();

/* ==========================================================================
 * گزینهٔ نامرئی
 * ======================================================================= */

Tests::group('یکپارچه › گزینهٔ نامرئی');

$withHidden = Fixture::variable([
    ['regular' => 500, 'visible' => false],
    ['regular' => 4000],
]);

$data = $method->invoke($widget, $withHidden, 'min');

Tests::ok(
    'گزینهٔ نامرئی مبنای قیمت قرار نمی‌گیرد',
    (float) $data['current'] !== 500.0,
    'قیمت نمایش‌داده‌شده: ' . $data['current']
);

Fixture::cleanup();

/* ==========================================================================
 * مالیات
 * ======================================================================= */

Tests::group('یکپارچه › مالیات');

$taxWasOn      = wc_tax_enabled();
$displayBefore = get_option('woocommerce_tax_display_shop');
$pricesInclude = get_option('woocommerce_prices_include_tax');

update_option('woocommerce_calc_taxes', 'yes');
update_option('woocommerce_prices_include_tax', 'no');
update_option('woocommerce_tax_display_shop', 'incl');

// نرخ ۱۰ درصد
$GLOBALS['wpdb']->insert($GLOBALS['wpdb']->prefix . 'woocommerce_tax_rates', [
    'tax_rate_country'  => '',
    'tax_rate'          => '10.0000',
    'tax_rate_name'     => 'TEST',
    'tax_rate_priority' => 1,
    'tax_rate_order'    => 1,
    'tax_rate_class'    => '',
]);
$rateId = (int) $GLOBALS['wpdb']->insert_id;
\WC_Cache_Helper::get_transient_version('taxes', true);

$taxed = Fixture::simple(['name' => 'مشمول مالیات', 'price' => 1000, 'tax_status' => 'taxable']);

// قیمت ذخیره‌شده ۱۰۰۰ است ولی فروشگاه با مالیات نمایش می‌دهد → باید ۱۱۰۰ شود
$displayed = (float) wc_get_price_to_display($taxed);
$card      = Product_Card::get_price($taxed);

Tests::ok(
    'قیمت نمایشی ووکامرس مالیات را اعمال می‌کند',
    $displayed > 1000.0,
    'displayed=' . $displayed
);
Tests::same(
    'کارت همان قیمت نمایشی ووکامرس را نشان می‌دهد',
    $card['num'],
    wc_price_test_format($displayed)
);

// بازگرداندن تنظیمات
$GLOBALS['wpdb']->delete($GLOBALS['wpdb']->prefix . 'woocommerce_tax_rates', ['tax_rate_id' => $rateId]);
update_option('woocommerce_calc_taxes', $taxWasOn ? 'yes' : 'no');
update_option('woocommerce_tax_display_shop', $displayBefore);
update_option('woocommerce_prices_include_tax', $pricesInclude);
\WC_Cache_Helper::get_transient_version('taxes', true);

Fixture::cleanup();

/* ==========================================================================
 * دسته‌بندی‌های غیرخالی
 * ======================================================================= */

Tests::group('یکپارچه › دسته‌بندی‌ها');

$full  = Fixture::category('پر');
$empty = Fixture::category('خالی');

Fixture::simple(['name' => 'در دستهٔ پر', 'price' => 1000, 'categories' => [$full]]);
Fixture::simple(['name' => 'در دستهٔ پر ولی بی‌قیمت', 'price' => null, 'categories' => [$empty]]);

$nonEmpty = Query::non_empty_categories([$full, $empty], ['has_price' => true]);

Tests::ok('دستهٔ دارای محصول می‌ماند', in_array($full, $nonEmpty, true));
Tests::ok('دسته‌ای که با فیلتر خالی می‌شود کنار می‌رود', !in_array($empty, $nonEmpty, true));

$all = Query::non_empty_categories([$full, $empty], []);
Tests::same('بدون فیلتر، همه می‌مانند', count($all), 2);

Fixture::cleanup();

/* ==========================================================================
 * کش
 * ======================================================================= */

Tests::group('یکپارچه › کش');

$cached = Fixture::simple(['name' => 'برای کش', 'price' => 1000]);

/**
 * تغییر بی‌سروصدای عنوان محصول.
 *
 * عمداً از ‎$product->save()‎ استفاده نمی‌شود: اگر افزونه در همان نصب فعال
 * باشد، هوک ‎woocommerce_update_product‎ نسخهٔ کش را بالا می‌برد و کش به‌درستی
 * باطل می‌شود — یعنی تست به‌جای «آیا کش کار می‌کند» به «آیا افزونه فعال
 * است» حساس می‌شد. با نوشتن مستقیم روی جدول، هیچ هوکی شلیک نمی‌شود و
 * تنها راه دیدنِ نام قدیمی، خوانده‌شدن از کش است.
 */
$rename = static function (int $id, string $title): void {
    global $wpdb;
    $wpdb->update($wpdb->posts, ['post_title' => $title], ['ID' => $id]);
    clean_post_cache($id);
};

// مهمان: کش فعال
wp_set_current_user(0);
$args  = amw_query_args(['cache' => 5]);
$first = Query::render($args);

$rename($cached->get_id(), 'نام عوض شد');
$second = Query::render($args);

Tests::same('مهمان: خروجی از کش می‌آید', $second['html'], $first['html']);

// و با بالا رفتن نسخه، همان کش باید کنار برود
Query::bump_cache_version();
$third = Query::render($args);

Tests::ok(
    'بالا رفتن نسخهٔ کش، خروجی را تازه می‌کند',
    false !== strpos($third['html'], 'نام عوض شد')
);

// کاربر واردشده: هرگز کش نمی‌شود
$userId = wp_insert_user(['user_login' => 'amwtest' . wp_generate_password(6, false), 'user_pass' => 'x', 'role' => 'customer']);
wp_set_current_user($userId);

$loggedFirst = Query::render($args);
$rename($cached->get_id(), 'نام سوم');
$loggedSecond = Query::render($args);

Tests::ok(
    'کاربر واردشده: خروجی کش نمی‌شود',
    $loggedFirst['html'] !== $loggedSecond['html'] || false !== strpos($loggedSecond['html'], 'نام سوم')
);

wp_set_current_user(0);
require_once ABSPATH . 'wp-admin/includes/user.php';
wp_delete_user($userId);
Fixture::cleanup();

/* ==========================================================================
 * پاک‌سازی SVG روی پیوست واقعی
 * ======================================================================= */

Tests::group('یکپارچه › SVG از پیوست');

$svgPath = wp_upload_dir()['path'] . '/amw-test.svg';
wp_mkdir_p(dirname($svgPath));
file_put_contents(
    $svgPath,
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">'
    . '<script>alert(1)</script><path d="M0 0h24" onload="alert(2)" fill="none" stroke="red"/></svg>'
);

$svgId = wp_insert_attachment([
    'post_title'     => 'svg تست',
    'post_mime_type' => 'image/svg+xml',
    'post_status'    => 'inherit',
], $svgPath);

$clean = \Almasara_Widgets\Svg::from_attachment($svgId);

Tests::ok('SVG واقعی از پیوست خوانده می‌شود', '' !== $clean);
Tests::ok('اسکریپت حذف شده', false === stripos($clean, 'script'));
Tests::ok('رویداد حذف شده', false === stripos($clean, 'onload'));
Tests::ok('مسیر سالم مانده', false !== strpos($clean, 'M0 0h24'));

$notSvg = Fixture::image();
Tests::same('پیوست غیر SVG رد می‌شود', \Almasara_Widgets\Svg::from_attachment($notSvg), '');

wp_delete_attachment($svgId, true);
Fixture::cleanup();
