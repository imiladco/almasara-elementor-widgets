<?php
namespace Almasara_Widgets\Product_Section;

use Almasara_Widgets\Product_Card;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * کوئری محصولات و رندر کارت‌ها برای ویجت «بخش محصولات».
 *
 * تنها نقطه‌ای است که محصولات از دیتابیس خوانده و به کارت تبدیل می‌شوند —
 * هم رندر اولیهٔ ویجت و هم endpoint فیلتر AJAX از همین‌جا می‌گذرند، پس
 * خروجی دو مسیر نمی‌تواند از هم جدا بیفتد.
 */
final class Query {

    public const CACHE_VERSION_OPTION = 'amw_ps_cache_ver';

    private const CATEGORY_CACHE_TTL = HOUR_IN_SECONDS;

    /* =====================================================================
     * کش
     * =================================================================== */

    /**
     * نسخهٔ کش را بالا می‌برد تا کلید همهٔ ترنزینت‌های قدیمی دیگر hit نشود.
     * نیازی به پاک‌سازی دستی نیست؛ خودشان با TTL منقضی می‌شوند.
     */
    public static function bump_cache_version(): void {
        update_option(self::CACHE_VERSION_OPTION, (int) get_option(self::CACHE_VERSION_OPTION, 0) + 1, false);
    }

    private static function cache_version(): int {
        return (int) get_option(self::CACHE_VERSION_OPTION, 0);
    }

    private static function cache_key(string $prefix, array $parts): string {
        return $prefix . md5(wp_json_encode(array_merge([self::cache_version(), self::cache_bucket()], $parts)));
    }

    /**
     * «سبد» کش: هر چیزی که خروجی را بین بازدیدکننده‌ها متفاوت می‌کند.
     *
     * خروجی این ویجت می‌تواند از یک قالب Listing بیاید که داخلش نام کاربر،
     * قیمت نقش‌محور، قیمت عمده، واحد پول انتخاب‌شده یا وضعیت ورود باشد. با
     * یک کلید مشترک، خروجیِ ساخته‌شده برای یک کاربر به بقیه هم داده می‌شد.
     *
     * پایه‌اش نقش‌های کاربر است، چون رایج‌ترین منبع تفاوت قیمت همین است. هر
     * چیز دیگری — مثلاً واحد پول افزونهٔ Currency Switcher یا سطح مشتری —
     * را می‌توان با همین فیلتر به کلید اضافه کرد:
     *
     *     add_filter('almasara_ps_cache_bucket', fn($b) => $b + ['cur' => my_currency()]);
     */
    private static function cache_bucket(): array {
        $bucket = ['roles' => []];

        if (is_user_logged_in()) {
            $user             = wp_get_current_user();
            $roles            = (array) $user->roles;
            sort($roles);
            $bucket['roles'] = $roles;
        }

        return (array) apply_filters('almasara_ps_cache_bucket', $bucket);
    }

    /**
     * آیا اجازه داریم این خروجی را کش کنیم؟
     *
     * برای کاربر وارد‌شده پیش‌فرض «نه» است. سبد نقش‌محور بالا بخش زیادی از
     * تفاوت‌ها را می‌پوشاند، ولی مواردی مثل سبد خرید، لیست علاقه‌مندی یا
     * nonce داخل کارت به خودِ کاربر گره خورده‌اند و با هیچ سبدی امن نمی‌شوند.
     */
    private static function may_cache(string $orderby, int $cache_min): bool {
        if ($cache_min <= 0) {
            return false;
        }

        // مرتب‌سازی تصادفی کش‌کردنش بی‌معنی است
        if ('rand' === $orderby) {
            return false;
        }

        return (bool) apply_filters('almasara_ps_may_cache', !is_user_logged_in());
    }

    /* =====================================================================
     * فیلترها
     * =================================================================== */

    /**
     * دسته‌بندی و فیلترهای «موجود» و «دارای عکس» روی آرگومان‌های کوئری.
     *
     * هر دو فیلتر عمداً طوری بیان شده‌اند که ردیف تکراری تولید نکنند:
     *   • موجودی از تکسونومی product_visibility خوانده می‌شود، نه متای
     *     _stock_status — هم خودِ ووکامرس در حلقهٔ فروشگاه همین را به کار
     *     می‌برد، هم WP روی tax_query خودکار DISTINCT می‌زند. محصولاتی که
     *     موجودی‌شان مدیریت نمی‌شود ترم outofstock نمی‌گیرند، پس حذف
     *     نمی‌شوند.
     *   • ‎_thumbnail_id تک‌مقداری است، پس JOIN آن هیچ‌وقت تکراری نمی‌سازد.
     *
     * فیلتر قیمت اینجا نیست: به جدول جست‌وجو نیاز دارد و lookup_clauses()
     * آن را اعمال می‌کند.
     */
    private static function apply_filters(array $args, int $category, array $filters): array {
        $tax = [];

        if ($category > 0) {
            $tax[] = [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => [$category],
            ];
        }

        // نمایانی کاتالوگ ووکامرس همیشه اعمال می‌شود، نه فقط وقتی فیلتری
        // فعال است: محصولی که مدیر «پنهان» یا «حذف از کاتالوگ» کرده نباید در
        // فهرست محصولات دیده شود. قبلاً این شرط به فیلتر «فقط موجود» گره
        // خورده بود، پس با خاموش بودن آن فیلتر، محصول پنهان هم نمایش
        // داده می‌شد.
        $hidden = ['exclude-from-catalog'];

        if (!empty($filters['in_stock'])) {
            $hidden[] = 'outofstock';
        }

        $tax[] = [
            'taxonomy' => 'product_visibility',
            'field'    => 'name',
            'terms'    => $hidden,
            'operator' => 'NOT IN',
        ];

        if (count($tax) > 1) {
            $tax = array_merge(['relation' => 'AND'], $tax);
        }

        if ($tax) {
            $args['tax_query'] = $tax; // phpcs:ignore WordPress.DB.SlowDBQuery
        }

        if (!empty($filters['has_image'])) {
            $args['meta_query'] = [[ // phpcs:ignore WordPress.DB.SlowDBQuery
                'key'     => '_thumbnail_id',
                'compare' => 'EXISTS',
            ]];
        }

        return $args;
    }

    private static function orders_by_lookup(string $orderby): bool {
        return in_array($orderby, ['price', 'popularity'], true);
    }

    /**
     * فیلتر posts_clauses روی جدول wc_product_meta_lookup ووکامرس.
     *
     * این جدول به‌ازای هر محصول دقیقاً یک ردیف دارد و ستون‌هایش ایندکس‌شده‌اند،
     * پس دو مشکل را هم‌زمان حل می‌کند:
     *   • کارت تکراری: متای _price برای محصول متغیر چند ردیف دارد و هر JOIN
     *     روی آن — چه برای مرتب‌سازی، چه برای فیلتر قیمت — همان محصول را
     *     چند بار برمی‌گرداند.
     *   • کندی: مرتب‌سازی با meta_value_num روی postmeta به‌مراتب گران‌تر از
     *     خواندن یک ستون ایندکس‌شده است.
     *
     * min_price فقط برای محصول بدون قیمت NULL است؛ محصول رایگان مقدار صفر
     * دارد، پس فیلتر «دارای قیمت» رایگان‌ها را حذف نمی‌کند. همین ستون مبنای
     * «حداقل قیمت» هم هست: برای محصول متغیر، ارزان‌ترین گزینه.
     *
     * @return callable|null null یعنی این کوئری به جدول نیاز ندارد یا جدول
     *                      در دسترس نیست (ووکامرس از ۳.۶ همیشه می‌سازدش).
     */
    private static function lookup_clauses(string $orderby, string $order, array $filters): ?callable {
        global $wpdb;

        if (empty($wpdb->wc_product_meta_lookup)) {
            return null;
        }

        $column    = ['price' => 'min_price', 'popularity' => 'total_sales'][$orderby] ?? '';
        $has_price = !empty($filters['has_price']);
        $min_price = (float) ($filters['min_price'] ?? 0);

        if ('' === $column && !$has_price && $min_price <= 0) {
            return null;
        }

        // ستون و جهت از مجموعه‌ای ثابت و داخلی می‌آیند؛ حداقل قیمت که از
        // کاربر می‌آید با prepare بایند می‌شود.
        return static function (array $clauses) use ($wpdb, $column, $order, $has_price, $min_price): array {
            $clauses['join'] .= " INNER JOIN {$wpdb->wc_product_meta_lookup} amw_pml ON {$wpdb->posts}.ID = amw_pml.product_id ";

            if ($has_price) {
                $clauses['where'] .= ' AND amw_pml.min_price IS NOT NULL ';
            }

            if ($min_price > 0) {
                $clauses['where'] .= $wpdb->prepare(' AND amw_pml.min_price >= %f ', $min_price);
            }

            if ('' !== $column) {
                $clauses['orderby'] = "amw_pml.{$column} {$order}, {$wpdb->posts}.ID DESC";
            }

            return $clauses;
        };
    }

    /** اجرای یک WP_Query با clauses اختیاری، و برداشتن مطمئنِ فیلتر بعدش */
    private static function run(array $args, ?callable $clauses): \WP_Query {
        if ($clauses) {
            add_filter('posts_clauses', $clauses);
        }

        try {
            return new \WP_Query($args);
        } finally {
            if ($clauses) {
                remove_filter('posts_clauses', $clauses);
            }
        }
    }

    /* =====================================================================
     * کوئری اصلی
     * =================================================================== */

    /**
     * کوئری محصولات + رندر کارت هرکدام.
     *
     * @param array $args خروجی Settings::query()، به‌علاوهٔ category
     * @return array{html:string,count:int}
     */
    public static function render(array $args): array {
        $source     = Settings::SOURCE_BUILTIN === ($args['source'] ?? '') ? Settings::SOURCE_BUILTIN : Settings::SOURCE_JETENGINE;
        $listing_id = absint($args['listing_id'] ?? 0);
        $category   = absint($args['category'] ?? 0);
        $count      = max(1, min(48, absint($args['count'] ?? 12)));
        $orderby    = (string) ($args['orderby'] ?? 'date');
        $order      = 'ASC' === strtoupper((string) ($args['order'] ?? 'DESC')) ? 'ASC' : 'DESC';
        $cache_min  = max(0, min(1440, absint($args['cache'] ?? 0)));
        $card       = Product_Card::sanitize_args($args['card'] ?? []);
        $filters    = [
            'has_price' => !empty($args['has_price']),
            'in_stock'  => !empty($args['in_stock']),
            'has_image' => !empty($args['has_image']),
            'min_price' => max(0.0, (float) ($args['min_price'] ?? 0)),
        ];

        // سنگین‌ترین کار این ویجت رندرِ N قالب در هر بارگذاری صفحه است؛ کش
        // این هزینه را به یک‌بار در هر بازه کاهش می‌دهد.
        $use_cache = self::may_cache($orderby, $cache_min);
        $key       = '';

        if ($use_cache) {
            $key    = self::cache_key('amw_ps_', [$source, $listing_id, $category, $count, $orderby, $order, $filters, $card]);
            $cached = get_transient($key);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $args_q = [
            'post_type'           => 'product',
            'post_status'         => 'publish',
            'posts_per_page'      => $count,
            'orderby'             => $orderby,
            'order'               => $order,
            'no_found_rows'       => true,
            'ignore_sticky_posts' => true,
        ];

        $args_q  = self::apply_filters($args_q, $category, $filters);
        $clauses = self::lookup_clauses($orderby, $order, $filters);

        if ($clauses && self::orders_by_lookup($orderby)) {
            // ترتیب را خودمان مستقیم در clauses می‌نویسیم
            $args_q['orderby'] = 'none';
        } elseif (!$clauses && self::orders_by_lookup($orderby)) {
            // مسیر جایگزین وقتی جدول جست‌وجو در دسترس نیست
            $args_q['orderby']  = 'meta_value_num';
            $args_q['meta_key'] = 'price' === $orderby ? '_price' : 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery
        }

        $query = self::run($args_q, $clauses);

        $html = '';
        foreach ($query->posts as $index => $post) {
            $html .= '<div class="swiper-slide"><div class="amw-ps__card">'
                . self::render_card($source, $listing_id, $post, $card, $index)
                . '</div></div>';
        }

        wp_reset_postdata();

        $result = ['html' => $html, 'count' => $query->post_count];

        if ($use_cache) {
            set_transient($key, $result, $cache_min * MINUTE_IN_SECONDS);
        }

        return $result;
    }

    private static function render_card(string $source, int $listing_id, \WP_Post $post, array $card, int $index): string {
        if (Settings::SOURCE_BUILTIN === $source) {
            // کارت‌های ابتدای اسلایدر معمولاً بالای صفحه دیده می‌شوند، پس
            // lazy کردنشان فقط نمایش را عقب می‌اندازد
            return Product_Card::render($post, ['eager' => $index < 4] + $card);
        }

        return self::render_jetengine_card($listing_id, $post);
    }

    /* =====================================================================
     * پیل‌های دسته‌بندی
     * =================================================================== */

    /**
     * از میان دسته‌بندی‌های داده‌شده، آن‌هایی که با فیلترهای فعال دست‌کم یک
     * محصول دارند. پیلی که بعد از کلیک خالی دربیاید تجربهٔ بدی است.
     *
     * نتیجه یکجا کش می‌شود: بدون آن، هر بارگذاری صفحه به‌ازای هر پیل یک
     * کوئری جدا می‌زد. کلید کش به همان نسخهٔ سراسری گره خورده که با ذخیرهٔ
     * محصول یا تغییر موجودی بالا می‌رود، پس کهنه نمی‌ماند.
     *
     * @param int[] $term_ids
     * @return int[]
     */
    public static function non_empty_categories(array $term_ids, array $filters): array {
        $term_ids = array_values(array_unique(array_filter(array_map('absint', $term_ids))));

        $active = !empty($filters['has_price']) || !empty($filters['in_stock']) || !empty($filters['has_image']);
        if (!$term_ids || !$active) {
            return $term_ids;
        }

        $key    = self::cache_key('amw_ps_cats_', [$term_ids, $filters]);
        $cached = get_transient($key);
        if (is_array($cached)) {
            return $cached;
        }

        $visible = [];
        foreach ($term_ids as $term_id) {
            $args = self::apply_filters([
                'post_type'              => 'product',
                'post_status'            => 'publish',
                'posts_per_page'         => 1,
                'fields'                 => 'ids',
                'no_found_rows'          => true,
                'ignore_sticky_posts'    => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ], $term_id, $filters);

            if (self::run($args, self::lookup_clauses('date', 'DESC', $filters))->post_count > 0) {
                $visible[] = $term_id;
            }
        }

        set_transient($key, $visible, self::CATEGORY_CACHE_TTL);

        return $visible;
    }

    /* =====================================================================
     * کارت جت‌انجین
     * =================================================================== */

    /**
     * آیا این شناسه واقعاً یک قالب Listing منتشرشده است؟
     *
     * حیاتی از نظر امنیتی: listing_id از یک endpoint عمومی و بدون احراز هویت
     * می‌آید و مستقیم به get_builder_content_for_display (و در مسیر جایگزین
     * به the_content) داده می‌شود. بدون این بررسی، هرکسی می‌توانست شناسهٔ هر
     * نوشته‌ای — از جمله پیش‌نویس، خصوصی یا رمزدار — را بفرستد و محتوای
     * رندرشده‌اش را بگیرد.
     */
    public static function is_renderable_listing(int $listing_id): bool {
        if ($listing_id <= 0) {
            return false;
        }

        $post = get_post($listing_id);

        return $post instanceof \WP_Post
            && 'jet-engine' === $post->post_type
            && 'publish' === $post->post_status
            && '' === $post->post_password;
    }

    /**
     * رندر یک آیتم از قالب Listing جت‌انجین برای یک محصول مشخص.
     *
     * ترکیب دو API مستند: کانتکست‌دهی به ماکروهای جت‌انجین
     * (listings->data->set_current_object) و رندر قالب المنتوری با
     * get_builder_content_for_display. متغیر سراسری $post هم موقتاً عوض
     * می‌شود، چون تگ‌های داینامیک خودِ المنتور — مثل تصویر شاخص یا عنوان
     * نوشته — از آن می‌خوانند، نه از کانتکست جت‌انجین.
     *
     * تنها نقطهٔ وابسته به API داخلی جت‌انجین در کل افزونه؛ اگر کارت خالی
     * درآمد یا محصول اشتباه رندر شد، مشکل دقیقاً همین‌جاست.
     */
    private static function render_jetengine_card(int $listing_id, \WP_Post $product): string {
        if (!self::is_renderable_listing($listing_id)) {
            return '';
        }

        if (!function_exists('jet_engine') || !class_exists('\Elementor\Plugin')) {
            return '';
        }

        global $post;
        $original = $post;
        $post     = $product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
        setup_postdata($post);

        $listings = jet_engine()->listings ?? null;
        $has_data = $listings && isset($listings->data);

        if ($has_data && method_exists($listings->data, 'set_current_object')) {
            $listings->data->set_current_object($product);
        }

        $content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display($listing_id);

        if ($has_data && method_exists($listings->data, 'reset_current_object')) {
            $listings->data->reset_current_object();
        }

        $post = $original; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
        if ($post) {
            setup_postdata($post);
        }

        if ('' === trim((string) $content)) {
            // قالبی که با المنتور ساخته نشده باشد
            $content = apply_filters('the_content', get_post_field('post_content', $listing_id));
        }

        return $content;
    }
}
