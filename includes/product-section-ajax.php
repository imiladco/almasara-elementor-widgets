<?php
namespace Almasara_Widgets;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * منطق مشترک ویجت «بخش محصولات» — کوئری + رندر کارت — بین رندر اولیه
 * (سمت PHP ویجت) و فیلتر AJAX دسته‌بندی (این کلاس) به اشتراک گذاشته می‌شود.
 */
final class Product_Section_Ajax {

    const CACHE_VERSION_OPTION = 'amw_ps_cache_ver';

    public static function init(): void {
        add_action('rest_api_init', [self::class, 'register_endpoint']);

        // با هر تغییر محصول/موجودی، نسخه کش بالا می‌رود تا کلید ترنزینت‌های
        // قدیمی دیگر hit نشوند (نیازی به پاک‌سازی دستی نیست؛ خودشان با TTL
        // منقضی می‌شوند). این مانع نمایش قیمت/موجودی کهنه بعد از ویرایش است.
        $bump = [self::class, 'bump_cache_version'];
        add_action('save_post_product', $bump);
        add_action('woocommerce_update_product', $bump);
        add_action('woocommerce_product_set_stock', $bump);
        add_action('woocommerce_variation_set_stock', $bump);
        add_action('woocommerce_product_set_stock_status', $bump);
    }

    public static function bump_cache_version(): void {
        update_option(self::CACHE_VERSION_OPTION, (int) get_option(self::CACHE_VERSION_OPTION, 0) + 1, false);
    }

    public static function register_endpoint(): void {
        register_rest_route('almasara/v1', '/product-section', [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => [self::class, 'handle_request'],
        ]);
    }

    public static function handle_request(\WP_REST_Request $request) {
        if (!function_exists('wc_get_product')) {
            return new \WP_Error('woocommerce_missing', __('ووکامرس فعال نیست.', 'almasara-widgets'), ['status' => 500]);
        }

        $result = self::query_and_render([
            'listing_id' => absint($request->get_param('listing_id')),
            'category'   => absint($request->get_param('category')),
            'count'      => absint($request->get_param('count')),
            'orderby'    => sanitize_key((string) $request->get_param('orderby')),
            'order'      => sanitize_key((string) $request->get_param('order')),
            'cache'      => absint($request->get_param('cache')),
            'has_price'  => (bool) absint($request->get_param('has_price')),
            'in_stock'   => (bool) absint($request->get_param('in_stock')),
            'has_image'  => (bool) absint($request->get_param('has_image')),
            'min_price'  => (float) $request->get_param('min_price'),
        ]);

        $response = rest_ensure_response($result);
        $response->header('Cache-Control', 'public, max-age=120');

        return $response;
    }

    /**
     * کوئری محصولات + رندر کارت هرکدام؛ هم رندر اولیه ویجت هم endpoint
     * فیلتر AJAX از همین یک تابع استفاده می‌کنند تا همیشه یکسان بمانند.
     *
     * @param array $args listing_id, category (0 = همه), count, orderby, order,
     *                     cache (دقیقه؛ 0=خاموش), has_price, in_stock, has_image (bool)
     * @return array{html: string, count: int}
     */
    public static function query_and_render(array $args): array {
        $listing_id = absint($args['listing_id'] ?? 0);
        $category   = absint($args['category'] ?? 0);
        $count      = max(1, min(48, absint($args['count'] ?? 12)));
        $orderby    = $args['orderby'] ?? 'date';
        $order      = 'asc' === strtolower((string) ($args['order'] ?? 'desc')) ? 'ASC' : 'DESC';
        $cache_min  = max(0, min(1440, absint($args['cache'] ?? 0)));
        $has_price  = !empty($args['has_price']);
        $in_stock   = !empty($args['in_stock']);
        $has_image  = !empty($args['has_image']);
        $min_price  = $has_price ? max(0.0, (float) ($args['min_price'] ?? 0)) : 0.0;

        $allowed_orderby = ['date', 'title', 'price', 'popularity', 'rand', 'menu_order'];
        if (!in_array($orderby, $allowed_orderby, true)) {
            $orderby = 'date';
        }

        // کش موقت خروجی: سنگین‌ترین کار این ویجت رندرِ N قالب المنتوری در
        // هر بارگذاری صفحه است. با کش، این هزینه فقط یک‌بار در هر بازه
        // پرداخت می‌شود. مرتب‌سازی تصادفی هرگز کش نمی‌شود (بی‌معنی است).
        $use_cache = $cache_min > 0 && 'rand' !== $orderby;
        $cache_key = '';
        if ($use_cache) {
            $ver       = (int) get_option(self::CACHE_VERSION_OPTION, 0);
            $cache_key = 'amw_ps_' . md5(wp_json_encode([$ver, $listing_id, $category, $count, $orderby, $order, $has_price, $in_stock, $has_image, $min_price]));
            $cached    = get_transient($cache_key);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $query_args = [
            'post_type'           => 'product',
            'post_status'         => 'publish',
            'posts_per_page'      => $count,
            'orderby'             => 'popularity' === $orderby ? 'meta_value_num' : $orderby,
            'order'               => $order,
            'no_found_rows'       => true,
            'ignore_sticky_posts' => true,
        ];

        $query_args = self::apply_filters_to_query_args($query_args, $category, $in_stock, $has_image);

        $lookup = self::build_lookup_clauses($orderby, $order, $has_price, $min_price);

        if ($lookup) {
            if (self::orders_by_lookup($orderby)) {
                // orderby را خودمان مستقیم در clauses می‌نویسیم
                $query_args['orderby'] = 'none';
            }
            add_filter('posts_clauses', $lookup);
        } elseif ('popularity' === $orderby) {
            // fallback وقتی جدول جست‌وجوی ووکامرس در دسترس نیست
            $query_args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery
        } elseif ('price' === $orderby) {
            $query_args['orderby']  = 'meta_value_num';
            $query_args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery
        }

        $query = new \WP_Query($query_args);

        if ($lookup) {
            remove_filter('posts_clauses', $lookup);
        }

        $html = '';
        foreach ($query->posts as $post) {
            // WP_Query خودش آبجکت پست را در کش گذاشته؛ مستقیم پاسش می‌دهیم
            // تا render دوباره get_post صدا نزند.
            $html .= '<div class="swiper-slide"><div class="amw-ps__card">' . self::render_jetengine_card($listing_id, $post) . '</div></div>';
        }

        wp_reset_postdata();

        $result = ['html' => $html, 'count' => $query->post_count];

        if ($use_cache) {
            set_transient($cache_key, $result, $cache_min * MINUTE_IN_SECONDS);
        }

        return $result;
    }

    /** آیا مرتب‌سازی خواسته‌شده از ستون‌های جدول جست‌وجو خوانده می‌شود؟ */
    private static function orders_by_lookup(string $orderby): bool {
        return in_array($orderby, ['price', 'popularity'], true);
    }

    /**
     * فیلتر posts_clauses برای استفاده از جدول wc_product_meta_lookup ووکامرس.
     *
     * این جدول به‌ازای هر محصول دقیقاً یک ردیف دارد و ستون‌هایش ایندکس‌شده‌اند،
     * پس دو مشکل را هم‌زمان حل می‌کند:
     *   • تکراری‌شدن کارت‌ها: متای _price برای محصول متغیر چند ردیف دارد، و
     *     هر JOIN روی آن (چه برای مرتب‌سازی، چه برای فیلتر «دارای قیمت»)
     *     همان محصول را چند بار برمی‌گرداند.
     *   • کندی: مرتب‌سازی با meta_value_num روی جدول postmeta به‌مراتب از
     *     خواندن یک ستون ایندکس‌شده گران‌تر است.
     * min_price فقط برای محصول بدون قیمت NULL است؛ محصول رایگان مقدار ۰
     * دارد، پس فیلتر «دارای قیمت» رایگان‌ها را حذف نمی‌کند. همین ستون مبنای
     * «حداقل قیمت» هم هست: برای محصول متغیر، ارزان‌ترین گزینه سنجیده می‌شود.
     *
     * @return callable|null null یعنی این کوئری به جدول نیازی ندارد، یا جدول
     *                      در دسترس نیست. حالت دوم عملاً رخ نمی‌دهد (ووکامرس
     *                      از ۳.۶ همیشه آن را می‌سازد) و اگر رخ دهد مرتب‌سازی
     *                      به مسیر متایی برمی‌گردد و فیلتر قیمت اعمال نمی‌شود.
     */
    private static function build_lookup_clauses(string $orderby, string $order, bool $has_price, float $min_price = 0.0): ?callable {
        global $wpdb;

        if (empty($wpdb->wc_product_meta_lookup)) {
            return null;
        }

        $order_column = '';
        if ('price' === $orderby) {
            $order_column = 'min_price';
        } elseif ('popularity' === $orderby) {
            $order_column = 'total_sales';
        }

        if ('' === $order_column && !$has_price && $min_price <= 0) {
            return null;
        }

        // ستون و جهت از مجموعه‌ای ثابت و داخلی می‌آیند، نه از ورودی کاربر؛
        // حداقل قیمت هم که از کاربر می‌آید با prepare بایند می‌شود.
        return static function (array $clauses) use ($wpdb, $order_column, $order, $has_price, $min_price): array {
            $clauses['join'] .= " INNER JOIN {$wpdb->wc_product_meta_lookup} amw_pml ON {$wpdb->posts}.ID = amw_pml.product_id ";

            if ($has_price) {
                $clauses['where'] .= ' AND amw_pml.min_price IS NOT NULL ';
            }

            if ($min_price > 0) {
                $clauses['where'] .= $wpdb->prepare(' AND amw_pml.min_price >= %f ', $min_price);
            }

            if ('' !== $order_column) {
                $clauses['orderby'] = "amw_pml.{$order_column} {$order}, {$wpdb->posts}.ID DESC";
            }

            return $clauses;
        };
    }

    /**
     * افزودن دسته‌بندی و فیلترهای «موجود» و «دارای عکس» به آرگومان‌های کوئری.
     *
     * هر دو فیلتر عمداً با شرط‌هایی بیان شده‌اند که ردیف تکراری تولید نکنند:
     *   • موجودی از تکسونومی product_visibility خوانده می‌شود (نه متای
     *     _stock_status) — هم خودِ ووکامرس در حلقهٔ فروشگاه از همین استفاده
     *     می‌کند، هم WP روی tax_query خودکار DISTINCT می‌زند. محصولات بدون
     *     مدیریت موجودی هم ترم outofstock نمی‌گیرند، پس حذف نمی‌شوند.
     *   • _thumbnail_id تک‌مقداری است، پس JOIN آن هیچ‌وقت تکراری نمی‌سازد.
     * فیلتر «دارای قیمت» اینجا نیست چون به جدول جست‌وجو نیاز دارد؛
     * build_lookup_clauses() آن را اعمال می‌کند.
     */
    private static function apply_filters_to_query_args(array $query_args, int $category, bool $in_stock, bool $has_image): array {
        $tax_query = [];

        if ($category > 0) {
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => [$category],
            ];
        }

        if ($in_stock) {
            $tax_query[] = [
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => ['outofstock'],
                'operator' => 'NOT IN',
            ];
        }

        if (count($tax_query) > 1) {
            $tax_query = array_merge(['relation' => 'AND'], $tax_query);
        }

        if ($tax_query) {
            $query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery
        }

        if ($has_image) {
            $query_args['meta_query'] = [[ // phpcs:ignore WordPress.DB.SlowDBQuery
                'key'     => '_thumbnail_id',
                'compare' => 'EXISTS',
            ]];
        }

        return $query_args;
    }

    /**
     * از میان شناسه‌های دسته‌بندی داده‌شده، آن‌هایی را برمی‌گرداند که با
     * فیلترهای فعال دست‌کم یک محصول دارند (برای مخفی‌کردن پیل‌های خالی).
     *
     * نتیجه یکجا در یک ترنزینت کش می‌شود: بدون آن، هر بارگذاری صفحه به‌ازای
     * هر پیل یک کوئری جدا می‌زد. کلید کش به همان نسخهٔ سراسری گره خورده که
     * با ذخیرهٔ محصول یا تغییر موجودی بالا می‌رود، پس کهنه نمی‌ماند.
     *
     * دقیقاً همان منطق فیلترِ کوئری اصلی را به کار می‌گیرد تا پیلی که نمایش
     * داده می‌شود، حتماً بعد از کلیک هم کارت داشته باشد.
     *
     * @param int[] $term_ids
     * @param array $filters has_price, in_stock, has_image (bool), min_price (float)
     * @return int[]
     */
    public static function filter_non_empty_categories(array $term_ids, array $filters): array {
        $term_ids = array_values(array_unique(array_filter(array_map('absint', $term_ids))));

        $has_price = !empty($filters['has_price']);
        $in_stock  = !empty($filters['in_stock']);
        $has_image = !empty($filters['has_image']);
        $min_price = $has_price ? max(0.0, (float) ($filters['min_price'] ?? 0)) : 0.0;

        if (!$term_ids || (!$has_price && !$in_stock && !$has_image)) {
            return $term_ids;
        }

        $ver       = (int) get_option(self::CACHE_VERSION_OPTION, 0);
        $cache_key = 'amw_ps_cats_' . md5(wp_json_encode([$ver, $term_ids, $has_price, $in_stock, $has_image, $min_price]));
        $cached    = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $visible = [];
        foreach ($term_ids as $term_id) {
            $query_args = [
                'post_type'              => 'product',
                'post_status'            => 'publish',
                'posts_per_page'         => 1,
                'fields'                 => 'ids',
                'no_found_rows'          => true,
                'ignore_sticky_posts'    => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ];

            $query_args = self::apply_filters_to_query_args($query_args, $term_id, $in_stock, $has_image);

            $lookup = self::build_lookup_clauses('date', 'DESC', $has_price, $min_price);
            if ($lookup) {
                add_filter('posts_clauses', $lookup);
            }

            $query = new \WP_Query($query_args);

            if ($lookup) {
                remove_filter('posts_clauses', $lookup);
            }

            if ($query->post_count > 0) {
                $visible[] = $term_id;
            }
        }

        set_transient($cache_key, $visible, HOUR_IN_SECONDS);

        return $visible;
    }

    /**
     * رندر یک آیتم از قالب Listing جت‌انجین برای یک محصول مشخص.
     *
     * ترکیب دو API پایدار و مستند: کانتکست‌دهی به ماکروهای جت‌انجین
     * (jet_engine()->listings->data->set_current_object) + رندر محتوای
     * قالب المنتوری با API خودِ المنتور (get_builder_content_for_display).
     * چون قالب‌های Listing این سایت با المنتور ساخته می‌شوند، این ترکیب
     * باید مقادیر داینامیک جت‌انجین را درست روی محصول resolve کند.
     *
     * $post سراسری هم موقتاً عوض می‌شود: تگ‌های داینامیک خودِ المنتور
     * (نه فقط ماکروهای جت‌انجین) — مثل «تصویر شاخص» یا «عنوان نوشته» —
     * از global $post می‌خوانند، نه از کانتکست جت‌انجین.
     *
     * این تنها نقطه‌ای از افزونه است که مستقیماً به API داخلی جت‌انجین
     * وابسته است — اگر کارت خالی درآمد یا محصول اشتباه رندر شد، مشکل
     * دقیقاً همین‌جاست.
     */
    private static function render_jetengine_card(int $listing_id, \WP_Post $product): string {
        if (!$listing_id) {
            return '';
        }

        if (!function_exists('jet_engine') || !class_exists('\Elementor\Plugin')) {
            return '';
        }

        global $post;
        $original_post = $post;
        $post = $product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
        setup_postdata($post);

        $listings = jet_engine()->listings ?? null;
        if ($listings && isset($listings->data) && method_exists($listings->data, 'set_current_object')) {
            $listings->data->set_current_object($product);
        }

        $content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display($listing_id);

        if ($listings && isset($listings->data) && method_exists($listings->data, 'reset_current_object')) {
            $listings->data->reset_current_object();
        }

        $post = $original_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
        if ($post) {
            setup_postdata($post);
        }

        if ('' === trim((string) $content)) {
            // fallback اگر قالب با المنتور ساخته نشده باشد
            $content = apply_filters('the_content', get_post_field('post_content', $listing_id));
        }

        return $content;
    }
}
