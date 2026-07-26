<?php
namespace Almasara_Widgets\Product_Section;

use Almasara_Widgets\Product_Card;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * endpoint فیلتر دسته‌بندی ویجت «بخش محصولات» و بی‌اعتبارسازی کش.
 *
 * خروجی فقط محصولات منتشرشده است (داده عمومی)، پس نیازی به احراز هویت
 * ندارد؛ در عوض همهٔ ورودی‌ها اینجا whitelist و محدود می‌شوند.
 */
final class Rest {

    private const NAMESPACE = 'almasara/v1';
    private const ROUTE     = '/product-section';

    public static function init(): void {
        add_action('rest_api_init', [self::class, 'register_route']);

        // با هر تغییر محصول یا موجودی، خروجی کش‌شده کهنه می‌شود
        $bump = [Query::class, 'bump_cache_version'];
        add_action('save_post_product', $bump);
        add_action('woocommerce_update_product', $bump);
        add_action('woocommerce_product_set_stock', $bump);
        add_action('woocommerce_variation_set_stock', $bump);
        add_action('woocommerce_product_set_stock_status', $bump);

        // و با ذخیرهٔ صفحه در المنتور. بدون این، بعد از تغییر تنظیمات ویجت
        // سایت تا انقضای ترنزینت (پیش‌فرض نیم‌ساعت) خروجی قدیمی را نشان
        // می‌داد، در حالی که ادیتور تازه را — یعنی دقیقاً همان اختلافی که
        // بین پیش‌نمایش و سایت دیده می‌شد.
        add_action('elementor/document/after_save', $bump);
        add_action('elementor/core/files/clear_cache', $bump);
    }

    public static function register_route(): void {
        register_rest_route(self::NAMESPACE, self::ROUTE, [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => [self::class, 'handle'],
        ]);
    }

    public static function url(): string {
        return rest_url(self::NAMESPACE . self::ROUTE);
    }

    public static function handle(\WP_REST_Request $request) {
        if (!function_exists('wc_get_product')) {
            return new \WP_Error('woocommerce_missing', __('ووکامرس فعال نیست.', 'almasara-widgets'), ['status' => 500]);
        }

        $result = Query::render([
            'source'     => sanitize_key((string) $request->get_param('source')),
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
            'card'       => Product_Card::sanitize_args((string) $request->get_param('card')),
        ]);

        $response = rest_ensure_response($result);
        $response->header('Cache-Control', 'public, max-age=120');

        return $response;
    }
}
