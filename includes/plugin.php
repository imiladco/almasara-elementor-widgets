<?php
namespace Almasara_Widgets;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * کلاس اصلی افزونه: ثبت دسته‌بندی، ویجت‌ها و استایل‌ها
 */
final class Plugin {

    private static $instance = null;

    public static function instance(): Plugin {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        require_once ALMASARA_WIDGETS_PATH . 'includes/product-extras.php';

        add_action('elementor/elements/categories_registered', [$this, 'register_category']);
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
        add_action('elementor/frontend/after_register_styles', [$this, 'register_styles']);
        add_action('elementor/frontend/after_register_scripts', [$this, 'register_scripts']);
        add_action('elementor/editor/after_enqueue_styles', [$this, 'enqueue_editor_styles']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * دسته‌بندی اختصاصی «الماسارا» در پنل ویجت‌های المنتور
     */
    public function register_category($elements_manager): void {
        $elements_manager->add_category('almasara', [
            'title' => __('الماسارا', 'almasara-widgets'),
            'icon'  => 'eicon-woocommerce',
        ]);
    }

    /**
     * ثبت ویجت‌ها با API جدید المنتور (3.5+)
     */
    public function register_widgets($widgets_manager): void {
        require_once ALMASARA_WIDGETS_PATH . 'includes/widgets/traits/intro-row.php';
        require_once ALMASARA_WIDGETS_PATH . 'includes/widgets/product-attributes.php';
        require_once ALMASARA_WIDGETS_PATH . 'includes/widgets/product-description.php';
        require_once ALMASARA_WIDGETS_PATH . 'includes/widgets/product-gallery.php';
        require_once ALMASARA_WIDGETS_PATH . 'includes/widgets/anchor-nav.php';
        require_once ALMASARA_WIDGETS_PATH . 'includes/widgets/product-faq.php';
        require_once ALMASARA_WIDGETS_PATH . 'includes/widgets/product-reviews.php';
        require_once ALMASARA_WIDGETS_PATH . 'includes/widgets/hero-slider.php';

        $widgets_manager->register(new Widgets\Product_Attributes());
        $widgets_manager->register(new Widgets\Product_Description());
        $widgets_manager->register(new Widgets\Product_Gallery());
        $widgets_manager->register(new Widgets\Anchor_Nav());
        $widgets_manager->register(new Widgets\Product_Faq());
        $widgets_manager->register(new Widgets\Product_Reviews());
        $widgets_manager->register(new Widgets\Hero_Slider());
    }

    /**
     * اسکریپت مودال گالری؛ فقط وقتی ویجت گالری در صفحه باشد لود می‌شود
     */
    public function register_scripts(): void {
        // Swiper فقط با get_script_depends ویجت اسلایدر لود می‌شود — روی
        // بقیه صفحات هیچ هزینه‌ای ندارد.
        wp_register_script(
            'swiper',
            ALMASARA_WIDGETS_URL . 'assets/vendor/swiper/swiper-bundle.min.js',
            [],
            '11.2.10',
            true
        );

        $scripts = [
            'almasara-gallery'     => 'gallery-modal.js',
            'almasara-nav'         => 'anchor-nav.js',
            'almasara-faq'         => 'faq.js',
            'almasara-reviews'     => 'reviews.js',
            'almasara-hero-slider' => 'hero-slider.js',
        ];

        foreach ($scripts as $handle => $file) {
            wp_register_script(
                $handle,
                ALMASARA_WIDGETS_URL . 'assets/js/' . $file,
                'almasara-hero-slider' === $handle ? ['swiper'] : [],
                ALMASARA_WIDGETS_VERSION,
                true
            );
        }
    }

    /**
     * REST endpoint عمومی برای لود ایجکسی تصاویر گالری محصول.
     * خروجی فقط URL تصاویر است (داده عمومی)، پس کش صفحه/CDN هم می‌تواند کشش کند.
     */
    public function register_rest_routes(): void {
        register_rest_route('almasara/v1', '/product-gallery/(?P<id>\d+)', [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'args'                => [
                'id' => ['sanitize_callback' => 'absint'],
            ],
            'callback'            => [$this, 'rest_product_gallery'],
        ]);
    }

    public function rest_product_gallery($request) {
        if (!function_exists('wc_get_product')) {
            return new \WP_Error('woocommerce_missing', 'WooCommerce is not active.', ['status' => 500]);
        }

        $product = wc_get_product((int) $request['id']);
        if (!$product || 'publish' !== $product->get_status()) {
            return new \WP_Error('not_found', 'Product not found.', ['status' => 404]);
        }

        $ids = [];
        if ($product->get_image_id()) {
            $ids[] = (int) $product->get_image_id();
        }
        foreach ($product->get_gallery_image_ids() as $gallery_id) {
            $ids[] = (int) $gallery_id;
        }

        $images = [];
        foreach ($ids as $attachment_id) {
            $full = wp_get_attachment_image_src($attachment_id, 'large');
            if (!$full) {
                $full = wp_get_attachment_image_src($attachment_id, 'full');
            }
            if (!$full) {
                continue;
            }

            $thumb = wp_get_attachment_image_src($attachment_id, 'medium');

            $images[] = [
                'full'  => $full[0],
                'thumb' => $thumb ? $thumb[0] : $full[0],
                'alt'   => (string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            ];
        }

        $response = rest_ensure_response($images);
        $response->header('Cache-Control', 'public, max-age=3600');

        return $response;
    }

    /**
     * ثبت استایل‌ها؛ هر ویجت با get_style_depends فقط در صورت استفاده لودشان می‌کند
     */
    public function register_styles(): void {
        wp_register_style(
            'swiper',
            ALMASARA_WIDGETS_URL . 'assets/vendor/swiper/swiper-bundle.min.css',
            [],
            '11.2.10'
        );

        wp_register_style(
            'almasara-widgets',
            ALMASARA_WIDGETS_URL . 'assets/css/almasara-widgets.css',
            [],
            ALMASARA_WIDGETS_VERSION
        );
    }

    /**
     * در ادیتور همیشه استایل لود شود تا پیش‌نمایش درست باشد
     */
    public function enqueue_editor_styles(): void {
        $this->register_styles();
        wp_enqueue_style('almasara-widgets');
    }
}
