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

    public static function init(): void {
        add_action('rest_api_init', [self::class, 'register_endpoint']);
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
        ]);

        $response = rest_ensure_response($result);
        $response->header('Cache-Control', 'public, max-age=120');

        return $response;
    }

    /**
     * کوئری محصولات + رندر کارت هرکدام؛ هم رندر اولیه ویجت هم endpoint
     * فیلتر AJAX از همین یک تابع استفاده می‌کنند تا همیشه یکسان بمانند.
     *
     * @param array $args listing_id, category (0 = همه), count, orderby, order
     * @return array{html: string, count: int}
     */
    public static function query_and_render(array $args): array {
        $listing_id = absint($args['listing_id'] ?? 0);
        $category   = absint($args['category'] ?? 0);
        $count      = max(1, min(48, absint($args['count'] ?? 12)));
        $orderby    = $args['orderby'] ?? 'date';
        $order      = 'asc' === strtolower((string) ($args['order'] ?? 'desc')) ? 'ASC' : 'DESC';

        $allowed_orderby = ['date', 'title', 'price', 'popularity', 'rand', 'menu_order'];
        if (!in_array($orderby, $allowed_orderby, true)) {
            $orderby = 'date';
        }

        $query_args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $count,
            'orderby'        => 'popularity' === $orderby ? 'meta_value_num' : $orderby,
            'order'          => $order,
            'no_found_rows'  => true,
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
            $html .= '<div class="swiper-slide"><div class="amw-ps__card">' . self::render_jetengine_card($listing_id, (int) $post->ID) . '</div></div>';
        }

        wp_reset_postdata();

        return ['html' => $html, 'count' => $query->post_count];
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
    private static function render_jetengine_card(int $listing_id, int $post_id): string {
        if (!$listing_id || !$post_id) {
            return '';
        }

        if (!function_exists('jet_engine') || !class_exists('\Elementor\Plugin')) {
            return '';
        }

        $product = get_post($post_id);
        if (!$product) {
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
