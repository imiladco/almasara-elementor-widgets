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
        ]);

        $response = rest_ensure_response($result);
        $response->header('Cache-Control', 'public, max-age=120');

        return $response;
    }

    /**
     * کوئری محصولات + رندر کارت هرکدام؛ هم رندر اولیه ویجت هم endpoint
     * فیلتر AJAX از همین یک تابع استفاده می‌کنند تا همیشه یکسان بمانند.
     *
     * @param array $args listing_id, category (0 = همه), count, orderby, order, cache (دقیقه؛ 0=خاموش)
     * @return array{html: string, count: int}
     */
    public static function query_and_render(array $args): array {
        $listing_id = absint($args['listing_id'] ?? 0);
        $category   = absint($args['category'] ?? 0);
        $count      = max(1, min(48, absint($args['count'] ?? 12)));
        $orderby    = $args['orderby'] ?? 'date';
        $order      = 'asc' === strtolower((string) ($args['order'] ?? 'desc')) ? 'ASC' : 'DESC';
        $cache_min  = max(0, min(1440, absint($args['cache'] ?? 0)));

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
            $cache_key = 'amw_ps_' . md5(wp_json_encode([$ver, $listing_id, $category, $count, $orderby, $order]));
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

        if ('popularity' === $orderby) {
            $query_args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery
        } elseif ('price' === $orderby) {
            $query_args['orderby']  = 'meta_value_num';
            $query_args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery
        }

        if ($category > 0) {
            $query_args['tax_query'] = [[ // phpcs:ignore WordPress.DB.SlowDBQuery
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => [$category],
            ]];
        }

        $query = new \WP_Query($query_args);

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
