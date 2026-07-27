<?php
/**
 * حداقلِ المنتور برای تست.
 *
 * هدف: بشود کنترل‌های یک ویجت را ثبت کرد و فهرستشان را برداشت، یا منطق
 * داخلی ویجت را صدا زد، بدون نصب المنتور.
 *
 * همه‌چیز مشروط تعریف می‌شود تا این فایل کنار وردپرسِ واقعی هم قابل
 * بارگذاری باشد (تست‌های یکپارچه) و چیزی را دوباره اعلام نکند.
 */

namespace Elementor {

if (!class_exists('Elementor\Controls_Manager')) {

class Controls_Manager {
    const TAB_CONTENT='content'; const TAB_STYLE='style';
    const TEXT='text'; const NUMBER='number'; const SELECT='select'; const SELECT2='select2';
    const SWITCHER='switcher'; const COLOR='color'; const SLIDER='slider'; const CHOOSE='choose';
    const DIMENSIONS='dimensions'; const HEADING='heading'; const RAW_HTML='raw'; const MEDIA='media';
    const REPEATER='repeater'; const GAPS='gaps'; const DIVIDER='divider';
}
abstract class Group_Control_Base { public static function get_type(){ return static::class; } }
class Group_Control_Typography extends Group_Control_Base {}
class Group_Control_Border extends Group_Control_Base {}
class Group_Control_Box_Shadow extends Group_Control_Base {}
class Group_Control_Background extends Group_Control_Base {}
class Group_Control_Text_Shadow extends Group_Control_Base {}
class Group_Control_Css_Filter extends Group_Control_Base {}
class Repeater { public function add_control($n,$a=[]){ $GLOBALS['AMW'][]="repeater:$n"; } public function get_controls(){ return []; } }
class Widget_Base {
    public function add_control($n,$a=[]){ $GLOBALS['AMW'][]=$n; }
    public function add_responsive_control($n,$a=[]){ $GLOBALS['AMW'][]=$n; }
    public function add_group_control($t,$a=[]){ $GLOBALS['AMW'][]='group:'.($a['name']??'?'); }
    public function start_controls_section($n,$a=[]){ $GLOBALS['AMW'][]="SECTION:$n"; }
    public function end_controls_section(){ $GLOBALS['AMW'][]='/SECTION'; }
    public function start_controls_tabs($n,$a=[]){ $GLOBALS['AMW'][]="TABS:$n"; }
    public function end_controls_tabs(){ $GLOBALS['AMW'][]='/TABS'; }
    public function start_controls_tab($n,$a=[]){ $GLOBALS['AMW'][]="TAB:$n"; }
    public function end_controls_tab(){ $GLOBALS['AMW'][]='/TAB'; }
    public function get_id(){ return 'testid'; }
}
class Utils { public static function validate_html_tag($t){ return $t; } }

}

}

namespace {

    if (!defined('ABSPATH')) {
        define('ABSPATH', true);
    }

    // همه مشروط، تا کنار وردپرس واقعی دوباره اعلام نشوند
    if (!function_exists('__')) {
        function __($s, $d = null) { return $s; }
    }
    if (!function_exists('esc_html__')) {
        function esc_html__($s, $d = null) { return $s; }
    }
    if (!function_exists('esc_attr__')) {
        function esc_attr__($s, $d = null) { return $s; }
    }
    if (!function_exists('is_rtl')) {
        function is_rtl() { return true; }
    }
    if (!function_exists('get_terms')) {
        function get_terms($a = []) { return []; }
    }
    if (!function_exists('is_wp_error')) {
        function is_wp_error($x) { return false; }
    }
    if (!function_exists('get_posts')) {
        function get_posts($a = []) { return []; }
    }
    if (!function_exists('get_intermediate_image_sizes')) {
        function get_intermediate_image_sizes() {
            return ['thumbnail', 'medium', 'large', 'woocommerce_thumbnail'];
        }
    }
    if (!function_exists('get_woocommerce_currency_symbol')) {
        function get_woocommerce_currency_symbol($c = '') { return 'تومان'; }
    }

    $GLOBALS['AMW'] = $GLOBALS['AMW'] ?? [];

    /**
     * فهرست کامل کنترل‌ها، سکشن‌ها و تب‌های یک ویجت، به ترتیب ثبت.
     *
     * @return string[]
     */
    function amw_collect_controls(string $class, array $files): array {
        foreach ($files as $file) {
            require_once $file;
        }

        $GLOBALS['AMW'] = [];
        $widget = new $class();
        $method = new ReflectionMethod($widget, 'register_controls');
        $method->setAccessible(true);
        $method->invoke($widget);

        return $GLOBALS['AMW'];
    }
}
