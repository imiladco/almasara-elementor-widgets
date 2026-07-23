<?php
/**
 * Plugin Name:       Almasara Elementor Widgets
 * Description:       ویجت‌های اختصاصی المنتور برای فروشگاه الماسارا (جدا از پوسته فرزند)
 * Version:           2.6.1
 * Author:            Almasara
 * Text Domain:       almasara-widgets
 * Requires PHP:      7.4
 * Requires at least: 6.0
 * Elementor tested up to: 3.30
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ALMASARA_WIDGETS_VERSION', '2.6.1');
define('ALMASARA_WIDGETS_FILE', __FILE__);
define('ALMASARA_WIDGETS_PATH', plugin_dir_path(__FILE__));
define('ALMASARA_WIDGETS_URL', plugin_dir_url(__FILE__));

// حداقل نسخه‌های مورد نیاز
define('ALMASARA_WIDGETS_MIN_ELEMENTOR', '3.13.0');
define('ALMASARA_WIDGETS_MIN_PHP', '7.4');

/**
 * بارگذاری افزونه بعد از لود شدن همه افزونه‌ها تا بتوانیم وجود المنتور و ووکامرس را بررسی کنیم.
 */
function almasara_widgets_init() {

    // ترجمه‌ها روی init لود می‌شوند (نه زودتر) تا نوتیس _load_textdomain_just_in_time ندهد
    add_action('init', static function () {
        load_plugin_textdomain('almasara-widgets', false, dirname(plugin_basename(ALMASARA_WIDGETS_FILE)) . '/languages');
    });

    if (version_compare(PHP_VERSION, ALMASARA_WIDGETS_MIN_PHP, '<')) {
        add_action('admin_notices', 'almasara_widgets_notice_php');
        return;
    }

    if (!did_action('elementor/loaded')) {
        add_action('admin_notices', 'almasara_widgets_notice_elementor');
        return;
    }

    if (defined('ELEMENTOR_VERSION') && version_compare(ELEMENTOR_VERSION, ALMASARA_WIDGETS_MIN_ELEMENTOR, '<')) {
        add_action('admin_notices', 'almasara_widgets_notice_elementor_version');
        return;
    }

    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'almasara_widgets_notice_woocommerce');
        return;
    }

    require_once ALMASARA_WIDGETS_PATH . 'includes/plugin.php';
    \Almasara_Widgets\Plugin::instance();
}
add_action('plugins_loaded', 'almasara_widgets_init');

function almasara_widgets_notice_php() {
    printf(
        '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
        sprintf(
            /* translators: %s: required PHP version */
            esc_html__('افزونه «ویجت‌های الماسارا» به PHP نسخه %s یا بالاتر نیاز دارد.', 'almasara-widgets'),
            ALMASARA_WIDGETS_MIN_PHP
        )
    );
}

function almasara_widgets_notice_elementor() {
    printf(
        '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
        esc_html__('افزونه «ویجت‌های الماسارا» برای کار کردن به افزونه المنتور نیاز دارد. لطفاً المنتور را نصب و فعال کنید.', 'almasara-widgets')
    );
}

function almasara_widgets_notice_elementor_version() {
    printf(
        '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
        sprintf(
            /* translators: %s: required Elementor version */
            esc_html__('افزونه «ویجت‌های الماسارا» به المنتور نسخه %s یا بالاتر نیاز دارد. لطفاً المنتور را به‌روزرسانی کنید.', 'almasara-widgets'),
            ALMASARA_WIDGETS_MIN_ELEMENTOR
        )
    );
}

function almasara_widgets_notice_woocommerce() {
    printf(
        '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
        esc_html__('افزونه «ویجت‌های الماسارا» برای ویجت‌های فروشگاهی به ووکامرس نیاز دارد. لطفاً ووکامرس را نصب و فعال کنید.', 'almasara-widgets')
    );
}
