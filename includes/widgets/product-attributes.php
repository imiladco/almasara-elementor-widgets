<?php
namespace Almasara_Widgets\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ویجت «ویژگی‌های محصول»
 *
 * سطر معرفی: عنوان + خط جداکننده + آیکون
 * سطر آیتم‌ها: باکس‌های ویژگی محصول که به‌صورت داینامیک از محصول جاری خوانده می‌شوند.
 */
class Product_Attributes extends Widget_Base {

    public function get_name(): string {
        return 'almasara-product-attributes';
    }

    public function get_title(): string {
        return __('ویژگی‌های محصول الماسارا', 'almasara-widgets');
    }

    public function get_icon(): string {
        return 'eicon-product-info';
    }

    public function get_categories(): array {
        return ['almasara', 'woocommerce-elements'];
    }

    public function get_keywords(): array {
        return ['ویژگی', 'محصول', 'attributes', 'product', 'woocommerce', 'الماسارا'];
    }

    public function get_style_depends(): array {
        return ['almasara-product-attributes'];
    }

    /* ---------------------------------------------------------------------
     * کنترل‌ها
     * ------------------------------------------------------------------- */

    protected function register_controls(): void {
        // تب محتوا
        $this->register_intro_content_controls();
        $this->register_layout_controls();
        $this->register_items_content_controls();
        $this->register_link_controls();

        // تب استایل
        $this->register_intro_style_controls();
        $this->register_item_style_controls();
    }

    /* =====================================================================
     * محتوا — بخش اول: معرفی
     * =================================================================== */

    private function register_intro_content_controls(): void {
        $this->start_controls_section('section_intro', [
            'label' => __('معرفی', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('title', [
            'label'       => __('عنوان', 'almasara-widgets'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('مشاهده ویژگی‌های محصول', 'almasara-widgets'),
            'dynamic'     => ['active' => true],
            'label_block' => true,
        ]);

        $this->add_control('title_tag', [
            'label'   => __('تگ', 'almasara-widgets'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'h3',
            'options' => [
                'h2'   => 'H2',
                'h3'   => 'H3',
                'h4'   => 'H4',
                'h5'   => 'H5',
                'h6'   => 'H6',
                'div'  => 'div',
                'span' => 'span',
            ],
        ]);

        $this->add_control('show_divider', [
            'label'   => __('خط جداکننده', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('icon_image', [
            'label'       => __('آیکون', 'almasara-widgets'),
            'type'        => Controls_Manager::MEDIA,
            'media_types' => ['image', 'svg'],
            'dynamic'     => ['active' => true],
            'description' => __('تصویر یا SVG انتخاب کنید. SVG به‌صورت inline رندر می‌شود و رنگش از تب استایل قابل تغییر است.', 'almasara-widgets'),
        ]);

        $this->end_controls_section();
    }

    /* =====================================================================
     * محتوا — بخش دوم: چیدمان (فلکس/گرید کامل برای هر دو سطر)
     * =================================================================== */

    private function register_layout_controls(): void {
        $start = is_rtl() ? 'right' : 'left';
        $end   = is_rtl() ? 'left' : 'right';

        $direction_options = [
            'row' => [
                'title' => __('سطری', 'almasara-widgets'),
                'icon'  => 'eicon-arrow-' . $end,
            ],
            'column' => [
                'title' => __('ستونی', 'almasara-widgets'),
                'icon'  => 'eicon-arrow-down',
            ],
            'row-reverse' => [
                'title' => __('سطری معکوس', 'almasara-widgets'),
                'icon'  => 'eicon-arrow-' . $start,
            ],
            'column-reverse' => [
                'title' => __('ستونی معکوس', 'almasara-widgets'),
                'icon'  => 'eicon-arrow-up',
            ],
        ];

        $justify_options = [
            'flex-start' => [
                'title' => __('ابتدا', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-justify-start-h',
            ],
            'center' => [
                'title' => __('وسط', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-justify-center-h',
            ],
            'flex-end' => [
                'title' => __('انتها', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-justify-end-h',
            ],
            'space-between' => [
                'title' => __('فاصله بین', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-justify-space-between-h',
            ],
            'space-around' => [
                'title' => __('فاصله اطراف', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-justify-space-around-h',
            ],
            'space-evenly' => [
                'title' => __('فاصله مساوی', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-justify-space-evenly-h',
            ],
        ];

        $align_options = [
            'flex-start' => [
                'title' => __('ابتدا', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-align-start-v',
            ],
            'center' => [
                'title' => __('وسط', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-align-center-v',
            ],
            'flex-end' => [
                'title' => __('انتها', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-align-end-v',
            ],
            'stretch' => [
                'title' => __('کشیده', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-align-stretch-v',
            ],
        ];

        $wrap_options = [
            'nowrap' => [
                'title' => __('در یک خط بماند', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-nowrap',
            ],
            'wrap' => [
                'title' => __('به چند خط تقسیم شود', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-wrap',
            ],
        ];

        $this->start_controls_section('section_layout', [
            'label' => __('چیدمان', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        // ---------------- سطر معرفی ----------------
        $this->add_control('heading_header_layout', [
            'label' => __('سطر معرفی', 'almasara-widgets'),
            'type'  => Controls_Manager::HEADING,
        ]);

        $this->add_responsive_control('header_direction', [
            'label'     => __('جهت', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => $direction_options,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__header' => 'flex-direction: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('header_justify', [
            'label'     => __('تراز کردن محتوا', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => $justify_options,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__header' => 'justify-content: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('header_align', [
            'label'     => __('تراز موارد', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => $align_options,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__header' => 'align-items: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('header_gaps', [
            'label'      => __('شکاف‌ها', 'almasara-widgets'),
            'type'       => Controls_Manager::GAPS,
            'size_units' => ['px', '%', 'em', 'rem'],
            'default'    => ['unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .amw-paw__header' => 'gap: {{ROW}}{{UNIT}} {{COLUMN}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('header_wrap', [
            'label'       => __('Wrap', 'almasara-widgets'),
            'type'        => Controls_Manager::CHOOSE,
            'options'     => $wrap_options,
            'description' => __('اقلام داخل سطر می‌توانند در یک خط باقی بمانند (No wrap)، یا به چند خط تقسیم شوند (Wrap).', 'almasara-widgets'),
            'selectors'   => [
                '{{WRAPPER}} .amw-paw__header' => 'flex-wrap: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('header_spacing', [
            'label'      => __('فاصله تا آیتم‌ها', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 100]],
            'selectors'  => [
                '{{WRAPPER}} .amw-paw__header' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ]);

        // ---------------- سطر آیتم‌ها ----------------
        $this->add_control('heading_items_layout', [
            'label'     => __('سطر آیتم‌ها', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('items_display', [
            'label'   => __('نوع چیدمان', 'almasara-widgets'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'grid',
            'options' => [
                'grid' => __('گرید', 'almasara-widgets'),
                'flex' => __('فلکس', 'almasara-widgets'),
            ],
            'selectors' => [
                '{{WRAPPER}} .amw-paw__items' => 'display: {{VALUE}};',
            ],
        ]);

        // گرید
        $this->add_responsive_control('grid_columns', [
            'label'          => __('ستون‌ها', 'almasara-widgets'),
            'type'           => Controls_Manager::SLIDER,
            'size_units'     => ['fr'],
            'range'          => ['fr' => ['min' => 1, 'max' => 12]],
            'default'        => ['size' => 3, 'unit' => 'fr'],
            'tablet_default' => ['size' => 2, 'unit' => 'fr'],
            'mobile_default' => ['size' => 1, 'unit' => 'fr'],
            'selectors'      => [
                '{{WRAPPER}} .amw-paw__items' => 'grid-template-columns: repeat({{SIZE}}, minmax(0, 1fr));',
            ],
            'condition'      => ['items_display' => 'grid'],
        ]);

        $this->add_responsive_control('grid_rows', [
            'label'      => __('ردیف‌ها', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['fr'],
            'range'      => ['fr' => ['min' => 1, 'max' => 12]],
            'selectors'  => [
                '{{WRAPPER}} .amw-paw__items' => 'grid-template-rows: repeat({{SIZE}}, minmax(0, 1fr));',
            ],
            'condition'  => ['items_display' => 'grid'],
        ]);

        $this->add_responsive_control('grid_template', [
            'label'       => __('قالب ستون سفارشی', 'almasara-widgets'),
            'type'        => Controls_Manager::TEXT,
            'placeholder' => '2fr 1fr 1fr',
            'description' => __('grid-template-columns دلخواه؛ روی «ستون‌ها» اولویت دارد.', 'almasara-widgets'),
            'selectors'   => [
                '{{WRAPPER}} .amw-paw__items' => 'grid-template-columns: {{VALUE}};',
            ],
            'condition'   => ['items_display' => 'grid'],
        ]);

        $this->add_responsive_control('grid_auto_flow', [
            'label'     => __('جهت چینش گرید', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => '',
            'options'   => [
                ''          => __('پیش‌فرض', 'almasara-widgets'),
                'row'       => __('سطری', 'almasara-widgets'),
                'column'    => __('ستونی', 'almasara-widgets'),
                'row dense' => __('سطری فشرده', 'almasara-widgets'),
            ],
            'selectors' => [
                '{{WRAPPER}} .amw-paw__items' => 'grid-auto-flow: {{VALUE}};',
            ],
            'condition' => ['items_display' => 'grid'],
        ]);

        // فلکس
        $this->add_responsive_control('items_direction', [
            'label'     => __('جهت', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => $direction_options,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__items' => 'flex-direction: {{VALUE}};',
            ],
            'condition' => ['items_display' => 'flex'],
        ]);

        // مشترک
        $this->add_responsive_control('items_justify', [
            'label'     => __('تراز کردن محتوا', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => $justify_options,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__items' => 'justify-content: {{VALUE}}; justify-items: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('items_align', [
            'label'     => __('تراز موارد', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => $align_options,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__items' => 'align-items: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('items_gaps', [
            'label'      => __('شکاف‌ها', 'almasara-widgets'),
            'type'       => Controls_Manager::GAPS,
            'size_units' => ['px', '%', 'em', 'rem'],
            'default'    => [
                'row'      => '16',
                'column'   => '16',
                'unit'     => 'px',
                'isLinked' => true,
            ],
            'selectors'  => [
                '{{WRAPPER}} .amw-paw__items' => 'gap: {{ROW}}{{UNIT}} {{COLUMN}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('items_wrap', [
            'label'       => __('Wrap', 'almasara-widgets'),
            'type'        => Controls_Manager::CHOOSE,
            'options'     => $wrap_options,
            'default'     => 'wrap',
            'description' => __('اقلام داخل سطر می‌توانند در یک خط باقی بمانند (No wrap)، یا به چند خط تقسیم شوند (Wrap).', 'almasara-widgets'),
            'selectors'   => [
                '{{WRAPPER}} .amw-paw__items' => 'flex-wrap: {{VALUE}};',
            ],
            'condition'   => ['items_display' => 'flex'],
        ]);

        $this->add_responsive_control('item_flex_basis', [
            'label'      => __('عرض هر آیتم', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => [
                'px' => ['min' => 50, 'max' => 800],
                '%'  => ['min' => 5, 'max' => 100],
            ],
            'selectors'  => [
                '{{WRAPPER}} .amw-paw__items' => '--amw-item-basis: {{SIZE}}{{UNIT}};',
            ],
            'condition'  => ['items_display' => 'flex'],
        ]);

        $this->add_control('item_flex_grow', [
            'label'        => __('آیتم‌ها فضای خالی را پر کنند', 'almasara-widgets'),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => '1',
            'selectors'    => [
                '{{WRAPPER}} .amw-paw__items .amw-paw__item' => 'flex-grow: {{VALUE}};',
            ],
            'condition'    => ['items_display' => 'flex'],
        ]);

        $this->end_controls_section();
    }

    /* =====================================================================
     * محتوا — بخش سوم: ویژگی‌ها
     * =================================================================== */

    private function register_items_content_controls(): void {
        $this->start_controls_section('section_items', [
            'label' => __('ویژگی‌ها', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('items_count', [
            'label'       => __('تعداد آیتم‌های نمایشی', 'almasara-widgets'),
            'type'        => Controls_Manager::NUMBER,
            'min'         => 0,
            'max'         => 30,
            'default'     => 6,
            'description' => __('اول آیتم‌های انتخابی شما نمایش داده می‌شوند؛ اگر محصول آن‌ها را نداشت، به‌طور خودکار از سایر ویژگی‌های خود محصول تا رسیدن به این تعداد پر می‌شود. ۰ یعنی فقط آیتم‌های انتخابی.', 'almasara-widgets'),
        ]);

        $repeater = new Repeater();

        $repeater->add_control('source', [
            'label'   => __('نوع داده', 'almasara-widgets'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'attribute',
            'options' => [
                'attribute'   => __('ویژگی محصول', 'almasara-widgets'),
                'category'    => __('دسته‌بندی', 'almasara-widgets'),
                'tag'         => __('برچسب‌ها', 'almasara-widgets'),
                'sku'         => __('شناسه محصول (SKU)', 'almasara-widgets'),
                'rating'      => __('امتیاز خریداران', 'almasara-widgets'),
                'stock'       => __('وضعیت موجودی', 'almasara-widgets'),
                'weight'      => __('وزن', 'almasara-widgets'),
                'dimensions'  => __('ابعاد', 'almasara-widgets'),
                'custom_meta' => __('فیلد دلخواه (متا)', 'almasara-widgets'),
            ],
        ]);

        $repeater->add_control('attribute', [
            'label'     => __('ویژگی', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'options'   => $this->get_attribute_options(),
            'default'   => 'custom',
            'condition' => ['source' => 'attribute'],
        ]);

        $repeater->add_control('custom_attribute', [
            'label'       => __('نام ویژگی سفارشی', 'almasara-widgets'),
            'type'        => Controls_Manager::TEXT,
            'description' => __('نام ویژگی غیرسراسری همان‌طور که در تب «ویژگی‌ها»ی محصول نوشته شده (مثلاً: رنگ).', 'almasara-widgets'),
            'condition'   => [
                'source'    => 'attribute',
                'attribute' => 'custom',
            ],
        ]);

        $repeater->add_control('meta_key', [
            'label'     => __('کلید متا', 'almasara-widgets'),
            'type'      => Controls_Manager::TEXT,
            'condition' => ['source' => 'custom_meta'],
        ]);

        $repeater->add_control('label', [
            'label'       => __('عنوان دلخواه', 'almasara-widgets'),
            'type'        => Controls_Manager::TEXT,
            'description' => __('خالی بماند تا عنوان پیش‌فرض نمایش داده شود.', 'almasara-widgets'),
        ]);

        $repeater->add_control('item_link', [
            'label'       => __('لینک این آیتم', 'almasara-widgets'),
            'type'        => Controls_Manager::URL,
            'dynamic'     => ['active' => true],
            'description' => __('فقط وقتی در بخش «لینک»، حالت «آیتم‌ها» انتخاب و «لینک یکسان» خاموش باشد اعمال می‌شود.', 'almasara-widgets'),
        ]);

        $this->add_control('items', [
            'label'       => __('آیتم‌های انتخابی', 'almasara-widgets'),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $repeater->get_controls(),
            'title_field' => '<# var map = {attribute: "ویژگی", category: "دسته‌بندی", tag: "برچسب‌ها", sku: "شناسه محصول", rating: "امتیاز خریداران", stock: "موجودی", weight: "وزن", dimensions: "ابعاد", custom_meta: "فیلد دلخواه"}; #>{{{ label || map[source] || source }}}',
            'default'     => [
                ['source' => 'category'],
                ['source' => 'sku'],
                ['source' => 'attribute'],
                ['source' => 'rating'],
            ],
        ]);

        $this->add_control('hide_empty', [
            'label'     => __('مخفی کردن آیتم‌های خالی', 'almasara-widgets'),
            'type'      => Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'separator' => 'before',
        ]);

        $this->add_control('value_separator', [
            'label'   => __('جداکننده مقادیر چندتایی', 'almasara-widgets'),
            'type'    => Controls_Manager::TEXT,
            'default' => '، ',
        ]);

        $this->add_control('value_limit', [
            'label'       => __('حداکثر تعداد مقادیر', 'almasara-widgets'),
            'type'        => Controls_Manager::NUMBER,
            'min'         => 0,
            'default'     => 0,
            'description' => __('۰ یعنی بدون محدودیت. مقادیر اضافه با «...» نمایش داده می‌شوند.', 'almasara-widgets'),
        ]);

        $this->end_controls_section();
    }

    /* =====================================================================
     * محتوا — بخش چهارم: لینک
     * =================================================================== */

    private function register_link_controls(): void {
        $this->start_controls_section('section_link', [
            'label' => __('لینک', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('link_scope', [
            'label'   => __('چه چیزی لینک شود؟', 'almasara-widgets'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'none',
            'options' => [
                'none'   => __('هیچ‌کدام', 'almasara-widgets'),
                'items'  => __('آیتم‌ها', 'almasara-widgets'),
                'title'  => __('عنوان', 'almasara-widgets'),
                'header' => __('کل سطر معرفی', 'almasara-widgets'),
                'widget' => __('کل ویجت', 'almasara-widgets'),
            ],
        ]);

        $this->add_control('same_link', [
            'label'     => __('لینک یکسان برای همه آیتم‌ها', 'almasara-widgets'),
            'type'      => Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'condition' => ['link_scope' => 'items'],
        ]);

        $this->add_control('link', [
            'label'      => __('لینک', 'almasara-widgets'),
            'type'       => Controls_Manager::URL,
            'dynamic'    => ['active' => true],
            'conditions' => [
                'relation' => 'or',
                'terms'    => [
                    [
                        'name'     => 'link_scope',
                        'operator' => 'in',
                        'value'    => ['title', 'header', 'widget'],
                    ],
                    [
                        'relation' => 'and',
                        'terms'    => [
                            ['name' => 'link_scope', 'operator' => '==', 'value' => 'items'],
                            ['name' => 'same_link', 'operator' => '==', 'value' => 'yes'],
                        ],
                    ],
                ],
            ],
        ]);

        $this->end_controls_section();
    }

    /* =====================================================================
     * استایل — تب اول: معرفی
     * =================================================================== */

    private function register_intro_style_controls(): void {
        $this->start_controls_section('section_style_intro', [
            'label' => __('معرفی', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'title_typography',
            'label'    => __('تایپوگرافی عنوان', 'almasara-widgets'),
            'selector' => '{{WRAPPER}} .amw-paw__title',
        ]);

        $this->add_control('divider_style', [
            'label'     => __('نوع خط جداکننده', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'dashed',
            'options'   => [
                'solid'  => __('یک‌دست', 'almasara-widgets'),
                'dashed' => __('خط‌چین', 'almasara-widgets'),
                'dotted' => __('نقطه‌چین', 'almasara-widgets'),
                'double' => __('دوخطی', 'almasara-widgets'),
            ],
            'selectors' => [
                '{{WRAPPER}} .amw-paw__divider' => 'border-top-style: {{VALUE}};',
            ],
            'condition' => ['show_divider' => 'yes'],
        ]);

        $this->add_responsive_control('divider_weight', [
            'label'      => __('ضخامت', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 1, 'max' => 10]],
            'selectors'  => [
                '{{WRAPPER}} .amw-paw__divider' => 'border-top-width: {{SIZE}}{{UNIT}};',
            ],
            'condition'  => ['show_divider' => 'yes'],
        ]);

        $this->add_responsive_control('icon_size', [
            'label'      => __('سایز آیکون', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 8, 'max' => 200]],
            'default'    => ['size' => 28, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .amw-paw' => '--amw-icon-size: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('icon_padding', [
            'label'      => __('پدینگ آیکون', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .amw-paw__icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        /*
         * رنگ‌های سطر معرفی — دو تب عادی/هاور.
         * رنگ SVG خودکار اعمال می‌شود: کنترل «رنگ آیکون» فقط بخش‌هایی را که
         * در فایل fill دارند بازرنگ می‌کند و «رنگ خطوط آیکون» فقط بخش‌های
         * stroke را. اگر خالی بمانند، رنگ اصلی فایل حفظ می‌شود.
         */
        $this->add_control('intro_colors_divider', [
            'type' => Controls_Manager::DIVIDER,
        ]);

        $this->start_controls_tabs('intro_color_tabs');

        $this->start_controls_tab('intro_colors_normal', ['label' => __('عادی', 'almasara-widgets')]);

        $this->add_control('title_color', [
            'label'     => __('رنگ عنوان', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__title' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('divider_color', [
            'label'     => __('رنگ خط جداکننده', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__divider' => 'border-top-color: {{VALUE}};',
            ],
            'condition' => ['show_divider' => 'yes'],
        ]);

        $this->add_control('icon_fill_color', [
            'label'       => __('رنگ آیکون', 'almasara-widgets'),
            'type'        => Controls_Manager::COLOR,
            'description' => __('بخش‌های توپُر (fill) را بازرنگ می‌کند؛ خالی بماند تا رنگ اصلی فایل حفظ شود.', 'almasara-widgets'),
            'selectors'   => [
                '{{WRAPPER}} .amw-paw__icon' => 'color: {{VALUE}};',
                '{{WRAPPER}} .amw-paw__icon svg [fill]:not([fill="none"]):not([fill="transparent"])' => 'fill: {{VALUE}};',
                '{{WRAPPER}} .amw-paw__icon svg :is(path,circle,rect,ellipse,polygon,polyline,line):not([fill]):not([stroke])' => 'fill: {{VALUE}};',
            ],
        ]);

        $this->add_control('icon_stroke_color', [
            'label'       => __('رنگ خطوط آیکون', 'almasara-widgets'),
            'type'        => Controls_Manager::COLOR,
            'description' => __('بخش‌های خطی (stroke) را بازرنگ می‌کند؛ خالی بماند تا رنگ اصلی فایل حفظ شود.', 'almasara-widgets'),
            'selectors'   => [
                '{{WRAPPER}} .amw-paw__icon svg [stroke]:not([stroke="none"]):not([stroke="transparent"])' => 'stroke: {{VALUE}};',
            ],
        ]);

        $this->end_controls_tab();

        $this->start_controls_tab('intro_colors_hover', ['label' => __('هاور', 'almasara-widgets')]);

        $this->add_control('title_color_hover', [
            'label'     => __('رنگ عنوان', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__header:hover .amw-paw__title' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('divider_color_hover', [
            'label'     => __('رنگ خط جداکننده', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__header:hover .amw-paw__divider' => 'border-top-color: {{VALUE}};',
            ],
            'condition' => ['show_divider' => 'yes'],
        ]);

        $this->add_control('icon_fill_color_hover', [
            'label'     => __('رنگ آیکون', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__header:hover .amw-paw__icon' => 'color: {{VALUE}};',
                '{{WRAPPER}} .amw-paw__header:hover .amw-paw__icon svg [fill]:not([fill="none"]):not([fill="transparent"])' => 'fill: {{VALUE}};',
                '{{WRAPPER}} .amw-paw__header:hover .amw-paw__icon svg :is(path,circle,rect,ellipse,polygon,polyline,line):not([fill]):not([stroke])' => 'fill: {{VALUE}};',
            ],
        ]);

        $this->add_control('icon_stroke_color_hover', [
            'label'     => __('رنگ خطوط آیکون', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__header:hover .amw-paw__icon svg [stroke]:not([stroke="none"]):not([stroke="transparent"])' => 'stroke: {{VALUE}};',
            ],
        ]);

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    /* =====================================================================
     * استایل — تب دوم: آیتم
     * =================================================================== */

    private function register_item_style_controls(): void {
        $this->start_controls_section('section_style_item', [
            'label' => __('آیتم', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        // ---------------- تب‌های عنوان / مقدار ----------------
        $this->start_controls_tabs('item_text_tabs');

        $this->start_controls_tab('item_tab_label', ['label' => __('عنوان', 'almasara-widgets')]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'label_typography',
            'selector' => '{{WRAPPER}} .amw-paw__label',
        ]);

        $this->add_control('label_color', [
            'label'     => __('رنگ', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__label' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Text_Shadow::get_type(), [
            'name'     => 'label_shadow',
            'selector' => '{{WRAPPER}} .amw-paw__label',
        ]);

        $this->add_control('label_color_hover', [
            'label'     => __('رنگ هاور', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__item:hover .amw-paw__label' => 'color: {{VALUE}};',
            ],
        ]);

        $this->end_controls_tab();

        $this->start_controls_tab('item_tab_value', ['label' => __('مقدار', 'almasara-widgets')]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'value_typography',
            'selector' => '{{WRAPPER}} .amw-paw__value',
        ]);

        $this->add_control('value_color', [
            'label'     => __('رنگ', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__value' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Text_Shadow::get_type(), [
            'name'     => 'value_shadow',
            'selector' => '{{WRAPPER}} .amw-paw__value',
        ]);

        $this->add_control('value_color_hover', [
            'label'     => __('رنگ هاور', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__item:hover .amw-paw__value' => 'color: {{VALUE}};',
            ],
        ]);

        $this->end_controls_tab();
        $this->end_controls_tabs();

        // ---------------- باکس آیتم ----------------
        $this->add_control('item_box_divider', [
            'type' => Controls_Manager::DIVIDER,
        ]);

        $this->add_control('item_layout', [
            'label'   => __('چیدمان داخلی آیتم', 'almasara-widgets'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'column',
            'options' => [
                'column'      => __('عنوان بالا، مقدار پایین', 'almasara-widgets'),
                'row'         => __('کنار هم', 'almasara-widgets'),
                'row-between' => __('کنار هم با فاصله', 'almasara-widgets'),
            ],
        ]);

        $this->add_responsive_control('item_align', [
            'label'     => __('چینش متن', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'right'  => ['title' => __('راست', 'almasara-widgets'), 'icon' => 'eicon-text-align-right'],
                'center' => ['title' => __('وسط', 'almasara-widgets'), 'icon' => 'eicon-text-align-center'],
                'left'   => ['title' => __('چپ', 'almasara-widgets'), 'icon' => 'eicon-text-align-left'],
            ],
            'selectors' => [
                '{{WRAPPER}} .amw-paw__item' => 'text-align: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('item_inner_gap', [
            'label'      => __('فاصله عنوان و مقدار', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'selectors'  => [
                '{{WRAPPER}} .amw-paw__item' => 'gap: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('item_padding', [
            'label'      => __('پدینگ', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', '%'],
            'selectors'  => [
                '{{WRAPPER}} .amw-paw__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        // ---------------- تب‌های عادی / هاور ----------------
        $this->start_controls_tabs('item_box_tabs');

        $this->start_controls_tab('item_box_normal', ['label' => __('عادی', 'almasara-widgets')]);

        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => 'item_background',
            'label'    => __('پس‌زمینه', 'almasara-widgets'),
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .amw-paw__item',
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'item_border',
            'label'    => __('حاشیه', 'almasara-widgets'),
            'selector' => '{{WRAPPER}} .amw-paw__item',
        ]);

        $this->add_responsive_control('item_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .amw-paw__item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'item_shadow',
            'label'    => __('سایه', 'almasara-widgets'),
            'selector' => '{{WRAPPER}} .amw-paw__item',
        ]);

        $this->end_controls_tab();

        $this->start_controls_tab('item_box_hover', ['label' => __('هاور', 'almasara-widgets')]);

        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => 'item_background_hover',
            'label'    => __('پس‌زمینه', 'almasara-widgets'),
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .amw-paw__item:hover',
        ]);

        $this->add_control('item_border_color_hover', [
            'label'     => __('رنگ حاشیه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__item:hover' => 'border-color: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('item_radius_hover', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .amw-paw__item:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'item_shadow_hover',
            'label'    => __('سایه', 'almasara-widgets'),
            'selector' => '{{WRAPPER}} .amw-paw__item:hover',
        ]);

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_control('item_transition_divider', [
            'type' => Controls_Manager::DIVIDER,
        ]);

        $this->add_control('item_transition', [
            'label'     => __('مدت زمان انیمیشن (میلی‌ثانیه)', 'almasara-widgets'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 0, 'max' => 2000]],
            'default'   => ['size' => 300],
            'selectors' => [
                '{{WRAPPER}} .amw-paw__item, {{WRAPPER}} .amw-paw__icon, {{WRAPPER}} .amw-paw__icon svg *, {{WRAPPER}} .amw-paw__title, {{WRAPPER}} .amw-paw__divider' => 'transition: all {{SIZE}}ms ease;',
            ],
        ]);

        $this->end_controls_section();
    }

    /* ---------------------------------------------------------------------
     * رندر
     * ------------------------------------------------------------------- */

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $product  = $this->resolve_product();

        if (!$product) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="amw-paw__notice">' . esc_html__('محصولی برای پیش‌نمایش پیدا نشد. این ویجت را در قالب صفحه محصول استفاده کنید.', 'almasara-widgets') . '</div>';
            }
            return;
        }

        $items = $this->build_items($settings, $product);
        if (empty($items) && 'yes' === $settings['hide_empty']) {
            return;
        }

        $scope       = $settings['link_scope'];
        $global_link = !empty($settings['link']['url']) ? $settings['link'] : null;

        $wrapper_tag = 'div';
        $this->add_render_attribute('wrapper', 'class', 'amw-paw');
        if ('widget' === $scope && $global_link) {
            $wrapper_tag = 'a';
            $this->add_link_attributes('wrapper', $global_link);
        }

        $header_tag = 'div';
        $this->add_render_attribute('header', 'class', 'amw-paw__header');
        if ('header' === $scope && $global_link) {
            $header_tag = 'a';
            $this->add_link_attributes('header', $global_link);
        }

        $title_tag = Utils::validate_html_tag($settings['title_tag']);

        ?>
        <<?php echo $wrapper_tag; // phpcs:ignore ?> <?php $this->print_render_attribute_string('wrapper'); ?>>

            <<?php echo $header_tag; // phpcs:ignore ?> <?php $this->print_render_attribute_string('header'); ?>>
                <?php if ('' !== $settings['title']) : ?>
                    <<?php echo $title_tag; // phpcs:ignore ?> class="amw-paw__title">
                        <?php if ('title' === $scope && $global_link) : ?>
                            <?php $this->add_link_attributes('title-link', $global_link); ?>
                            <a <?php $this->print_render_attribute_string('title-link'); ?>><?php echo esc_html($settings['title']); ?></a>
                        <?php else : ?>
                            <?php echo esc_html($settings['title']); ?>
                        <?php endif; ?>
                    </<?php echo $title_tag; // phpcs:ignore ?>>
                <?php endif; ?>

                <?php if ('yes' === $settings['show_divider']) : ?>
                    <span class="amw-paw__divider" aria-hidden="true"></span>
                <?php endif; ?>

                <?php $this->render_icon($settings); ?>
            </<?php echo $header_tag; // phpcs:ignore ?>>

            <div class="amw-paw__items<?php echo 'flex' === $settings['items_display'] ? ' amw-flex' : ''; ?>">
                <?php foreach ($items as $index => $item) :
                    $item_key = 'item-' . $index;
                    $item_tag = 'div';
                    $this->add_render_attribute($item_key, 'class', ['amw-paw__item', 'amw-item-' . $settings['item_layout']]);

                    if ('items' === $scope) {
                        $item_link = ('yes' === $settings['same_link']) ? $global_link : ($item['link'] ?: null);
                        if ($item_link && !empty($item_link['url'])) {
                            $item_tag = 'a';
                            $this->add_link_attributes($item_key, $item_link);
                        }
                    }
                    ?>
                    <<?php echo $item_tag; // phpcs:ignore ?> <?php $this->print_render_attribute_string($item_key); ?>>
                        <span class="amw-paw__label"><?php echo esc_html($item['label']); ?></span>
                        <span class="amw-paw__value"><?php echo esc_html($item['value']); ?></span>
                    </<?php echo $item_tag; // phpcs:ignore ?>>
                <?php endforeach; ?>
            </div>

        </<?php echo $wrapper_tag; // phpcs:ignore ?>>
        <?php
    }

    /** رندر آیکون؛ SVG همیشه inline و پاک‌سازی‌شده درج می‌شود */
    private function render_icon(array $settings): void {
        if (empty($settings['icon_image']['url'])) {
            return;
        }

        $url    = $settings['icon_image']['url'];
        $is_svg = 'svg' === strtolower(pathinfo(wp_parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

        echo '<span class="amw-paw__icon">';

        $svg = '';
        if ($is_svg && !empty($settings['icon_image']['id'])) {
            $svg = $this->get_inline_svg((int) $settings['icon_image']['id']);
        }

        if ($svg) {
            echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput -- sanitized in get_inline_svg()
        } else {
            printf('<img src="%s" alt="">', esc_url($url));
        }

        echo '</span>';
    }

    /** خواندن و پاک‌سازی فایل SVG برای درج inline */
    private function get_inline_svg(int $attachment_id): string {
        $path = get_attached_file($attachment_id);
        if (!$path || !file_exists($path)) {
            return '';
        }

        $svg = file_get_contents($path);
        if (false === $svg || false === stripos($svg, '<svg')) {
            return '';
        }

        // پاک‌سازی امنیتی: حذف اسکریپت‌ها، رویدادها و لینک‌های خطرناک
        $svg = preg_replace('/<\?xml.*?\?>/is', '', $svg);
        $svg = preg_replace('/<!DOCTYPE.*?>/is', '', $svg);
        $svg = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $svg);
        $svg = preg_replace('/<foreignObject\b[^>]*>.*?<\/foreignObject>/is', '', $svg);
        $svg = preg_replace('/\son\w+\s*=\s*(["\']).*?\1/is', '', $svg);
        $svg = preg_replace('/\s(?:xlink:)?href\s*=\s*(["\'])\s*(?:javascript|data):.*?\1/is', '', $svg);

        return trim($svg);
    }

    /** پیدا کردن محصول: صفحه محصول جاری، وگرنه آخرین محصول برای پیش‌نمایش ادیتور */
    private function resolve_product() {
        if (!function_exists('wc_get_product')) {
            return false;
        }

        $post_id = get_the_ID();
        if ($post_id && 'product' === get_post_type($post_id)) {
            return wc_get_product($post_id);
        }

        $is_preview = \Elementor\Plugin::$instance->editor->is_edit_mode() || is_preview()
            || (\Elementor\Plugin::$instance->preview && \Elementor\Plugin::$instance->preview->is_preview_mode());

        if (!$is_preview) {
            return false;
        }

        $latest = wc_get_products(['limit' => 1, 'orderby' => 'date', 'order' => 'DESC', 'status' => 'publish']);
        return $latest ? $latest[0] : false;
    }

    /**
     * ساخت آرایه آیتم‌ها:
     * اول آیتم‌های انتخابی؛ اگر «تعداد» بیشتر بود، به‌طور خودکار از سایر
     * ویژگی‌های خود محصول تکمیل می‌شود.
     */
    private function build_items(array $settings, $product): array {
        $items = [];
        $used_attributes = [];

        foreach ((array) $settings['items'] as $row) {
            $value = $this->resolve_value($row, $product, $settings);
            $label = '' !== trim((string) ($row['label'] ?? '')) ? $row['label'] : $this->default_label($row, $product);

            // ثبت ویژگی‌های استفاده‌شده تا در تکمیل خودکار تکرار نشوند
            if ('attribute' === $row['source']) {
                $attr = $row['attribute'] ?? '';
                $used_attributes[] = ('custom' === $attr)
                    ? sanitize_title((string) ($row['custom_attribute'] ?? ''))
                    : $attr;
            }

            if ('' === $value && 'yes' === $settings['hide_empty']) {
                continue;
            }

            $items[] = [
                'label' => $label,
                'value' => $value,
                'link'  => $row['item_link'] ?? null,
            ];
        }

        $count = (int) ($settings['items_count'] ?? 0);

        if ($count > 0) {
            if (count($items) >= $count) {
                return array_slice($items, 0, $count);
            }
            $items = $this->autofill_from_attributes($items, $used_attributes, $product, $settings, $count);
        }

        return $items;
    }

    /** تکمیل خودکار آیتم‌ها از ویژگی‌های خود محصول */
    private function autofill_from_attributes(array $items, array $used, $product, array $settings, int $count): array {
        foreach ($product->get_attributes() as $attribute) {
            if (count($items) >= $count) {
                break;
            }

            if (!$attribute instanceof \WC_Product_Attribute || !$attribute->get_visible()) {
                continue;
            }

            $name = $attribute->get_name();
            $key  = $attribute->is_taxonomy() ? $name : sanitize_title($name);
            if (in_array($key, $used, true)) {
                continue;
            }

            if ($attribute->is_taxonomy()) {
                $values = wc_get_product_terms($product->get_id(), $name, ['fields' => 'names']);
                $values = is_wp_error($values) ? [] : $values;
            } else {
                $values = $attribute->get_options();
            }

            $value = $this->join_values((array) $values, $settings);
            if ('' === $value) {
                continue;
            }

            $items[] = [
                'label' => wc_attribute_label($name, $product),
                'value' => $value,
                'link'  => null,
            ];
        }

        return $items;
    }

    /** عنوان پیش‌فرض هر نوع داده */
    private function default_label(array $row, $product): string {
        if ('attribute' === $row['source']) {
            $attr = $row['attribute'] ?? '';
            if ('custom' === $attr) {
                return (string) ($row['custom_attribute'] ?? '');
            }
            return wc_attribute_label($attr);
        }

        $labels = [
            'category'    => __('دسته‌بندی', 'almasara-widgets'),
            'tag'         => __('برچسب‌ها', 'almasara-widgets'),
            'sku'         => __('شناسه محصول', 'almasara-widgets'),
            'rating'      => __('امتیاز خریداران', 'almasara-widgets'),
            'stock'       => __('وضعیت موجودی', 'almasara-widgets'),
            'weight'      => __('وزن', 'almasara-widgets'),
            'dimensions'  => __('ابعاد', 'almasara-widgets'),
            'custom_meta' => (string) ($row['meta_key'] ?? ''),
        ];

        return $labels[$row['source']] ?? '';
    }

    /** چسباندن مقادیر چندتایی با جداکننده و سقف تعداد */
    private function join_values(array $values, array $settings): string {
        $separator = (string) ($settings['value_separator'] ?? '، ');
        $limit     = (int) ($settings['value_limit'] ?? 0);

        $values    = array_filter(array_map('trim', array_map('strval', $values)), 'strlen');
        $truncated = false;

        if ($limit > 0 && count($values) > $limit) {
            $values    = array_slice($values, 0, $limit);
            $truncated = true;
        }

        $out = implode($separator, $values);
        return $truncated ? $out . '، ...' : $out;
    }

    /** مقدار داینامیک هر آیتم از محصول جاری */
    private function resolve_value(array $row, $product, array $settings): string {
        switch ($row['source']) {
            case 'attribute':
                $attr = $row['attribute'] ?? '';
                if ('custom' === $attr) {
                    $name = (string) ($row['custom_attribute'] ?? '');
                    if ('' === $name) {
                        return '';
                    }
                    $value = $product->get_attribute($name);
                    return $this->join_values(explode(',', $value), $settings);
                }
                $terms = wc_get_product_terms($product->get_id(), $attr, ['fields' => 'names']);
                return is_wp_error($terms) ? '' : $this->join_values($terms, $settings);

            case 'category':
                $terms = wc_get_product_terms($product->get_id(), 'product_cat', ['fields' => 'names']);
                return is_wp_error($terms) ? '' : $this->join_values($terms, $settings);

            case 'tag':
                $terms = wc_get_product_terms($product->get_id(), 'product_tag', ['fields' => 'names']);
                return is_wp_error($terms) ? '' : $this->join_values($terms, $settings);

            case 'sku':
                return (string) $product->get_sku();

            case 'rating':
                $rating = (float) $product->get_average_rating();
                return $rating > 0 ? (string) round($rating, 1) : '';

            case 'stock':
                if (!$product->is_in_stock()) {
                    return __('ناموجود', 'almasara-widgets');
                }
                $qty = $product->get_stock_quantity();
                return $qty ? sprintf(__('موجود (%d عدد)', 'almasara-widgets'), $qty) : __('موجود', 'almasara-widgets');

            case 'weight':
                $weight = $product->get_weight();
                return $weight ? wc_format_weight($weight) : '';

            case 'dimensions':
                $dimensions = $product->get_dimensions(false);
                return array_filter($dimensions) ? wc_format_dimensions($dimensions) : '';

            case 'custom_meta':
                $key = (string) ($row['meta_key'] ?? '');
                if ('' === $key) {
                    return '';
                }
                $meta = get_post_meta($product->get_id(), $key, true);
                return is_scalar($meta) ? (string) $meta : '';
        }

        return '';
    }

    /** گزینه‌های دراپ‌داون ویژگی‌ها: ویژگی‌های سراسری ووکامرس + سفارشی */
    private function get_attribute_options(): array {
        $options = [
            'custom' => __('ویژگی سفارشی (غیرسراسری)', 'almasara-widgets'),
        ];

        if (function_exists('wc_get_attribute_taxonomies')) {
            foreach (wc_get_attribute_taxonomies() as $taxonomy) {
                $options['pa_' . $taxonomy->attribute_name] = $taxonomy->attribute_label;
            }
        }

        return $options;
    }
}
