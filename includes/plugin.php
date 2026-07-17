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
        add_action('elementor/elements/categories_registered', [$this, 'register_category']);
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
        add_action('elementor/frontend/after_register_styles', [$this, 'register_styles']);
        add_action('elementor/editor/after_enqueue_styles', [$this, 'enqueue_editor_styles']);
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

        $widgets_manager->register(new Widgets\Product_Attributes());
        $widgets_manager->register(new Widgets\Product_Description());
    }

    /**
     * ثبت استایل‌ها؛ هر ویجت با get_style_depends فقط در صورت استفاده لودشان می‌کند
     */
    public function register_styles(): void {
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
