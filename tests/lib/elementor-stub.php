<?php
namespace Elementor {
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
}
class Utils { public static function validate_html_tag($t){ return $t; } }
}
namespace {
    if(!defined('ABSPATH')) define('ABSPATH', true);
    function __($s,$d=null){ return $s; }
    function esc_html__($s,$d=null){ return $s; }
    function esc_attr__($s,$d=null){ return $s; }
    function is_rtl(){ return true; }
    function get_terms($a=[]){ return []; }
    function is_wp_error($x){ return false; }
    function get_posts($a=[]){ return []; }
    function get_intermediate_image_sizes(){ return ['thumbnail','medium','large','woocommerce_thumbnail']; }
    function get_woocommerce_currency_symbol(){ return 'تومان'; }
    $GLOBALS['AMW']=[];

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
