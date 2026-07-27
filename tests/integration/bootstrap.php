<?php
/**
 * راه‌انداز تست‌های یکپارچه.
 *
 * برخلاف تست‌های واحد، این‌ها روی یک نصب واقعی وردپرس + ووکامرس اجرا
 * می‌شوند تا چیزهایی سنجیده شود که فقط با دیتابیس واقعی معنا دارند:
 * کوئری محصولات، نمایانی کاتالوگ، جدول جست‌وجوی ووکامرس، محاسبهٔ مالیات،
 * و رفتار محصول متغیر.
 *
 * مسیر نصب از متغیر محیطی WP_ROOT خوانده می‌شود. برای ساختنش:
 *
 *     bash tests/integration/setup.sh /tmp/wpint
 *     WP_ROOT=/tmp/wpint/wp php tests/integration/run.php
 */

$wp_root = getenv('WP_ROOT');

if (!$wp_root || !file_exists($wp_root . '/wp-load.php')) {
    fwrite(STDERR, <<<TXT

    تست‌های یکپارچه رد شدند: نصب وردپرس پیدا نشد.

    متغیر WP_ROOT را به ریشهٔ یک وردپرسِ نصب‌شده با ووکامرس فعال بدهید:

        bash tests/integration/setup.sh /tmp/wpint
        WP_ROOT=/tmp/wpint/wp php tests/integration/run.php

    TXT);
    exit(0); // نبودِ محیط یعنی «اجرا نشد»، نه «شکست خورد»
}

// خطاهای PHP نباید با خروجی تست قاطی شوند
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);

$plugin_dir = dirname(__DIR__, 2);

/*
 * افزونه باید از همین پوشهٔ کاری خوانده شود، نه از نسخه‌ای که شاید داخل
 * wp-content/plugins کپی شده باشد (tests/e2e/setup.sh دقیقاً همین کار را
 * می‌کند). وگرنه تست بی‌آنکه چیزی بگوید کدِ کهنه را می‌سنجد و هر تغییری در
 * پوشهٔ کاری نامرئی می‌ماند — بدترین حالتِ ممکن برای یک تست.
 *
 * چون wp-load.php اولین چیزی است که اجرا می‌شود، جایی برای هوک زدن نمانده؛
 * تنها نقطهٔ ورودِ زودهنگام، mu-plugin است که وردپرس پیش از افزونه‌های
 * معمولی لود می‌کند. آنجا فهرست افزونه‌های فعال فیلتر می‌شود.
 *
 * فایل بی‌اثر است مگر آنکه AMW_INTEGRATION ست باشد، پس e2e و مرورگر
 * همچنان افزونه را فعال می‌بینند.
 */
$mu_code = <<<'PHP'
<?php
/**
 * Plugin Name: AMW integration isolation
 *
 * فقط در جریان tests/integration اثر دارد: کپیِ افزونه داخل این نصب را از
 * بارگذاری کنار می‌گذارد تا تست، کدِ پوشهٔ کاری را بسنجد نه کپی را.
 */
if (getenv('AMW_INTEGRATION')) {
    add_filter('option_active_plugins', static function ($plugins) {
        return array_values(array_filter((array) $plugins, static function ($p) {
            return false === strpos((string) $p, 'almasara-elementor-widgets/');
        }));
    });
}
PHP;

$mu_dir  = $wp_root . '/wp-content/mu-plugins';
$mu_file = $mu_dir . '/amw-integration-isolation.php';

if (!is_dir($mu_dir)) {
    mkdir($mu_dir, 0777, true);
}
if (!is_file($mu_file) || file_get_contents($mu_file) !== $mu_code) {
    file_put_contents($mu_file, $mu_code);
}

putenv('AMW_INTEGRATION=1');

require_once $wp_root . '/wp-load.php';

if (!class_exists('WooCommerce')) {
    fwrite(STDERR, "ووکامرس در این نصب فعال نیست.\n");
    exit(1);
}

// چارچوب assert مشترک با تست‌های واحد. توابع پوششیِ آنجا همه با
// function_exists محافظت شده‌اند، پس با وردپرس واقعی تداخل نمی‌کنند.
require_once dirname(__DIR__) . '/bootstrap.php';

/*
 * کلاس‌ها فقط اگر از قبل نباشند بارگذاری می‌شوند: اگر با وجود جداسازیِ
 * بالا باز هم کسی آن‌ها را لود کرده باشد، require دوباره «Cannot redeclare»
 * می‌دهد.
 */
foreach ([
    'Almasara_Widgets\Svg'                       => '/includes/svg.php',
    'Almasara_Widgets\Responsive'                => '/includes/responsive.php',
    'Almasara_Widgets\Product_Card'              => '/includes/product-card.php',
    'Almasara_Widgets\Product_Section\Settings'  => '/includes/product-section/settings.php',
    'Almasara_Widgets\Product_Section\Query'     => '/includes/product-section/query.php',
] as $class => $file) {
    if (!class_exists($class)) {
        require_once $plugin_dir . $file;
    }
}

/*
 * تور ایمنی: اگر جداسازی به هر دلیلی نگرفته باشد، تست باید سر و صدا کند نه
 * اینکه در سکوت کدِ کهنه را سبز اعلام کند.
 */
$loaded_from = (new ReflectionClass(\Almasara_Widgets\Product_Section\Query::class))->getFileName();

if (0 !== strpos((string) realpath($loaded_from), (string) realpath($plugin_dir))) {
    fwrite(STDERR, sprintf(
        "\nکلاس‌های افزونه از مسیر دیگری لود شده‌اند:\n  %s\nانتظار می‌رفت از:\n  %s\n"
        . "یعنی تست نسخهٔ دیگری از کد را می‌سنجد. mu-plugin جداسازی را بررسی کنید.\n\n",
        $loaded_from,
        $plugin_dir
    ));
    exit(1);
}

/* --------------------------------------------------------------------------
 * جداسازی از محتوای از پیش موجود
 * ----------------------------------------------------------------------- */

/**
 * محصولاتی که خودِ تست نساخته را موقتاً از دید کوئری پنهان می‌کند.
 *
 * چرا لازم است: بخش زیادی از assertها دربارهٔ «چه چیزی رندر شده» است —
 * «فقط یک کارت»، «این محصول نباید باشد»، «خروجی از کش می‌آید». همهٔ این‌ها
 * فرض می‌کنند تنها محصولات موجود، همان‌هایی‌اند که Fixture ساخته. کافی است
 * چیز دیگری در همان نصب باشد (مثلاً هشت محصولی که tests/e2e/setup.sh
 * می‌سازد) تا تست‌ها بی‌آنکه افزونه ایرادی داشته باشد قرمز شوند.
 *
 * به‌جای دست‌کاری کوئریِ زیر آزمایش، وضعیت آن پست‌ها به draft تغییر می‌کند
 * و در پایان دقیقاً به حالت قبل برمی‌گردد. این‌طور کوئری همان مسیر واقعیِ
 * تولید را طی می‌کند و چیزی از منطقش دور زده نمی‌شود.
 */
final class Isolation {

    /**
     * سقف احتیاطی: نصب یک‌بارمصرفِ setup.sh چند محصول بیشتر ندارد. اگر
     * WP_ROOT اشتباهی به سایت واقعی اشاره کند، تست به‌جای دست زدن به
     * کاتالوگ، متوقف می‌شود.
     */
    private const MAX_FOREIGN = 200;

    /** @var array<int,string> شناسه => وضعیت اصلی */
    private static array $parked = [];

    private static bool $shutdown_registered = false;

    public static function begin(): void {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT ID, post_status FROM {$wpdb->posts}
             WHERE post_type IN ('product', 'product_variation')
               AND post_status = 'publish'"
        );

        if (count($rows) > self::MAX_FOREIGN) {
            fwrite(STDERR, sprintf(
                "\nاین نصب %d محصول منتشرشده دارد؛ به‌نظر یک سایت واقعی می‌آید.\n"
                . "تست‌های یکپارچه محتوا را تغییر می‌دهند، پس روی نصب یک‌بارمصرفِ\n"
                . "tests/integration/setup.sh اجرایشان کنید.\n\n",
                count($rows)
            ));
            exit(1);
        }

        foreach ($rows as $row) {
            $id = (int) $row->ID;
            self::$parked[$id] = $row->post_status;

            // مستقیم روی جدول: wp_update_post روی محصول، همگام‌سازی و
            // هوک‌های ووکامرس را راه می‌اندازد که اینجا نه لازم است نه
            // بی‌خطر. وضعیت، تنها چیزی است که کوئری به آن نگاه می‌کند.
            $wpdb->update($wpdb->posts, ['post_status' => 'draft'], ['ID' => $id]);
            clean_post_cache($id);
        }

        if (self::$parked && !self::$shutdown_registered) {
            self::$shutdown_registered = true;
            // اگر تستی وسط کار fatal داد، کاتالوگ نباید draft بماند
            register_shutdown_function([self::class, 'end']);
        }
    }

    public static function end(): void {
        global $wpdb;

        foreach (self::$parked as $id => $status) {
            $wpdb->update($wpdb->posts, ['post_status' => $status], ['ID' => $id]);
            clean_post_cache($id);
        }

        self::$parked = [];
    }
}

/* --------------------------------------------------------------------------
 * کمکی‌های ساخت داده
 * ----------------------------------------------------------------------- */

final class Fixture {

    /** @var int[] هر چیزی که ساخته‌ایم، تا در پایان پاک شود */
    private static array $posts = [];
    private static array $terms = [];

    public static function simple(array $args = []): \WC_Product_Simple {
        $product = new \WC_Product_Simple();
        $product->set_name($args['name'] ?? 'محصول تست');
        $product->set_status('publish');

        if (array_key_exists('price', $args)) {
            if (null !== $args['price']) {
                $product->set_regular_price((string) $args['price']);
            }
        } else {
            $product->set_regular_price('1000');
        }

        if (isset($args['sale'])) {
            $product->set_sale_price((string) $args['sale']);
        }
        if (isset($args['stock_status'])) {
            $product->set_stock_status($args['stock_status']);
        }
        if (isset($args['visibility'])) {
            $product->set_catalog_visibility($args['visibility']);
        }
        if (isset($args['image_id'])) {
            $product->set_image_id($args['image_id']);
        }
        if (isset($args['tax_status'])) {
            $product->set_tax_status($args['tax_status']);
        }
        if (isset($args['categories'])) {
            $product->set_category_ids($args['categories']);
        }

        $id = $product->save();
        self::$posts[] = $id;

        return wc_get_product($id);
    }

    /**
     * محصول متغیر با گزینه‌های داده‌شده.
     * هر گزینه: ['regular' => .., 'sale' => .., 'visible' => bool]
     */
    public static function variable(array $variations, string $name = 'محصول متغیر'): \WC_Product_Variable {
        $product = new \WC_Product_Variable();
        $product->set_name($name);
        $product->set_status('publish');

        $attribute = new \WC_Product_Attribute();
        $attribute->set_name('اندازه');
        $attribute->set_options(array_map(static fn($i) => 'v' . $i, array_keys($variations)));
        $attribute->set_visible(true);
        $attribute->set_variation(true);
        $product->set_attributes([$attribute]);

        $id = $product->save();
        self::$posts[] = $id;

        foreach ($variations as $i => $spec) {
            $variation = new \WC_Product_Variation();
            $variation->set_parent_id($id);
            $variation->set_attributes(['اندازه' => 'v' . $i]);
            $variation->set_regular_price((string) $spec['regular']);

            if (isset($spec['sale'])) {
                $variation->set_sale_price((string) $spec['sale']);
            }

            // گزینهٔ نامرئی: ووکامرس آن را از انتخاب مشتری کنار می‌گذارد
            $variation->set_status(($spec['visible'] ?? true) ? 'publish' : 'private');

            self::$posts[] = $variation->save();
        }

        \WC_Product_Variable::sync($id);

        return wc_get_product($id);
    }

    public static function category(string $name): int {
        $term = wp_insert_term($name . '-' . wp_generate_password(6, false), 'product_cat');
        $id   = is_wp_error($term) ? 0 : (int) $term['term_id'];

        if ($id) {
            self::$terms[] = $id;
        }

        return $id;
    }

    /** پیوست تصویری بدون فایل واقعی — فقط برای اینکه _thumbnail_id وجود داشته باشد */
    public static function image(): int {
        $id = wp_insert_post([
            'post_title'     => 'تصویر تست',
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image/png',
        ]);

        self::$posts[] = $id;

        return (int) $id;
    }

    public static function cleanup(): void {
        foreach (array_reverse(self::$posts) as $id) {
            wp_delete_post($id, true);
        }
        foreach (self::$terms as $id) {
            wp_delete_term($id, 'product_cat');
        }

        self::$posts = [];
        self::$terms = [];
    }
}

/** آرگومان‌های پایهٔ کوئری، با کارت داخلی و بدون کش */
function amw_query_args(array $overrides = []): array {
    return array_merge([
        'source'     => 'builtin',
        'listing_id' => 0,
        'category'   => 0,
        'count'      => 20,
        'orderby'    => 'date',
        'order'      => 'DESC',
        'cache'      => 0,
        'card'       => [],
        'has_price'  => false,
        'in_stock'   => false,
        'has_image'  => false,
        'min_price'  => 0.0,
    ], $overrides);
}

/**
 * آیا این محصول در خروجی رندرشده هست؟
 *
 * عمداً بر پایهٔ عنوان است نه لینک: استخراج شناسه از href به پیوند یکتای
 * فعال وابسته است و در نصب تازه با ساختار پیش‌فرض، url_to_postid چیزی
 * برنمی‌گرداند. آن‌وقت assertهای منفی («فلان محصول نباید باشد») روی آرایهٔ
 * خالی الکی سبز می‌شدند — یعنی تست هیچ‌چیز را نمی‌سنجید.
 */
function amw_html_has(string $html, \WC_Product $product): bool {
    return false !== strpos($html, esc_html($product->get_name()));
}

/**
 * چند کارت برای این محصول رندر شده (برای تشخیص کارت تکراری).
 *
 * عمداً فقط عنوانِ کارت شمرده می‌شود، نه هر جای متن: نام محصول در alt
 * تصویر هم می‌آید، پس شمارش ساده هر کارت را دو بار حساب می‌کرد.
 */
function amw_html_count(string $html, \WC_Product $product): int {
    $name = preg_quote(esc_html($product->get_name()), '/');

    return (int) preg_match_all('/class="amw-card__title"[^>]*>\s*' . $name . '/u', $html);
}

/** تعداد کارت‌های رندرشده */
function amw_card_count(string $html): int {
    return substr_count($html, 'class="amw-card"') + substr_count($html, "class='amw-card'");
}
