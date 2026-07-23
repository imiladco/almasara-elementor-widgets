<?php
namespace Almasara_Widgets\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ویجت «بخش محصولات» — عنوان + فیلتر پیلی دسته‌بندی (AJAX زنده) + دکمه
 * مشاهده همه + اسلایدر کارت‌های محصول (کارت از قالب Listing جت‌انجین).
 *
 * کوئری و رندر کارت‌ها با Product_Section_Ajax مشترک است تا رندر اولیه
 * (این کلاس) و فیلتر AJAX همیشه دقیقاً یک خروجی تولید کنند.
 */
class Product_Section extends Widget_Base {

    use Traits\Intro_Row; // برای گزینه‌های چیدمان مشترک

    public function get_name(): string {
        return 'almasara-product-section';
    }

    public function get_title(): string {
        return __('بخش محصولات الماسارا', 'almasara-widgets');
    }

    public function get_icon(): string {
        return 'eicon-product-related';
    }

    public function get_categories(): array {
        return ['almasara'];
    }

    public function get_keywords(): array {
        return ['محصولات', 'دسته‌بندی', 'اسلایدر', 'products', 'category', 'jetengine', 'الماسارا'];
    }

    public function get_style_depends(): array {
        return ['almasara-swiper', 'almasara-widgets'];
    }

    public function get_script_depends(): array {
        return ['almasara-swiper', 'almasara-product-section'];
    }

    /* =====================================================================
     * کنترل‌ها
     * =================================================================== */

    protected function register_controls(): void {
        $this->register_header_content_controls();
        $this->register_categories_content_controls();
        $this->register_source_content_controls();
        $this->register_slider_content_controls();

        $this->register_header_style_controls();
        $this->register_button_style_controls();
        $this->register_pills_style_controls();
        $this->register_card_style_controls();
        $this->register_nav_style_controls();
        $this->register_pagination_style_controls();
    }

    /* ---------------- محتوا: عنوان و دکمه ---------------- */

    private function register_header_content_controls(): void {
        $this->start_controls_section('section_header', [
            'label' => __('عنوان', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('title', [
            'label'       => __('عنوان', 'almasara-widgets'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('محصولات', 'almasara-widgets'),
            'dynamic'     => ['active' => true],
            'label_block' => true,
        ]);

        $this->add_control('show_view_all', [
            'label'   => __('دکمه «مشاهده همه»', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('view_all_text', [
            'label'     => __('متن دکمه', 'almasara-widgets'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('مشاهده همه', 'almasara-widgets'),
            'condition' => ['show_view_all' => 'yes'],
        ]);

        $this->add_control('view_all_note', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('لینک این دکمه خودکار است: اگر پیل «همه» فعال باشد به فروشگاه، وگرنه به آرشیو همان دسته‌بندی می‌رود.', 'almasara-widgets'),
            'content_classes' => 'elementor-descriptor',
            'condition'       => ['show_view_all' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- محتوا: دسته‌بندی‌ها ---------------- */

    private function register_categories_content_controls(): void {
        $this->start_controls_section('section_categories', [
            'label' => __('فیلتر دسته‌بندی‌ها', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('all_label', [
            'label'   => __('متن گزینه «همه»', 'almasara-widgets'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('همه', 'almasara-widgets'),
        ]);

        $repeater = new Repeater();

        $repeater->add_control('category', [
            'label'   => __('دسته‌بندی', 'almasara-widgets'),
            'type'    => Controls_Manager::SELECT2,
            'options' => $this->get_product_category_options(),
        ]);

        $repeater->add_control('label', [
            'label'       => __('برچسب سفارشی', 'almasara-widgets'),
            'type'        => Controls_Manager::TEXT,
            'placeholder' => __('خالی = نام خودِ دسته‌بندی', 'almasara-widgets'),
        ]);

        $this->add_control('categories', [
            'label'       => __('دسته‌بندی‌ها', 'almasara-widgets'),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $repeater->get_controls(),
            'title_field' => '{{{ label || "دسته‌بندی" }}}',
        ]);

        $this->end_controls_section();
    }

    /* ---------------- محتوا: منبع محصولات ---------------- */

    private function register_source_content_controls(): void {
        $this->start_controls_section('section_source', [
            'label' => __('کارت محصول و کوئری', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('listing_id', [
            'label'       => __('قالب Listing جت‌انجین', 'almasara-widgets'),
            'type'        => Controls_Manager::SELECT2,
            'options'     => $this->get_jetengine_listing_options(),
            'description' => __('قالب کارت محصولی که قبلاً در جت‌انجین ساخته‌اید.', 'almasara-widgets'),
        ]);

        $this->add_control('products_count', [
            'label'   => __('تعداد محصول در اسلایدر', 'almasara-widgets'),
            'type'    => Controls_Manager::NUMBER,
            'default' => 12,
            'min'     => 1,
            'max'     => 48,
        ]);

        $this->add_control('orderby', [
            'label'   => __('مرتب‌سازی بر اساس', 'almasara-widgets'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'date',
            'options' => [
                'date'       => __('جدیدترین', 'almasara-widgets'),
                'price'      => __('قیمت', 'almasara-widgets'),
                'popularity' => __('پرفروش‌ترین', 'almasara-widgets'),
                'title'      => __('نام محصول', 'almasara-widgets'),
                'menu_order' => __('ترتیب دستی محصول', 'almasara-widgets'),
                'rand'       => __('تصادفی', 'almasara-widgets'),
            ],
        ]);

        $this->add_control('order', [
            'label'     => __('جهت مرتب‌سازی', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'DESC',
            'options'   => [
                'DESC' => __('نزولی', 'almasara-widgets'),
                'ASC'  => __('صعودی', 'almasara-widgets'),
            ],
            'condition' => ['orderby!' => 'rand'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- محتوا: اسلایدر ---------------- */

    private function register_slider_content_controls(): void {
        $this->start_controls_section('section_slider', [
            'label' => __('اسلایدر', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_responsive_control('slides_per_view', [
            'label'          => __('تعداد کارت هم‌زمان', 'almasara-widgets'),
            'type'           => Controls_Manager::NUMBER,
            'default'        => 4,
            'tablet_default' => 2.2,
            'mobile_default' => 1.2,
            'min'            => 1,
            'step'           => 0.1,
        ]);

        $this->add_responsive_control('space_between', [
            'label'   => __('فاصله بین کارت‌ها (px)', 'almasara-widgets'),
            'type'    => Controls_Manager::NUMBER,
            'default' => 20,
            'min'     => 0,
        ]);

        $this->add_responsive_control('speed', [
            'label'          => __('سرعت گذار (میلی‌ثانیه)', 'almasara-widgets'),
            'type'           => Controls_Manager::NUMBER,
            'default'        => 600,
            'tablet_default' => 500,
            'mobile_default' => 400,
            'min'            => 100,
            'step'           => 50,
        ]);

        $this->add_control('rewind', [
            'label'   => __('بازگشت به کارت اول', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('rtl', [
            'label'   => __('جهت راست‌به‌چپ', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => is_rtl() ? 'yes' : '',
        ]);

        $this->add_control('heading_autoplay', [
            'label'     => __('پخش خودکار', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('autoplay', [
            'label'   => __('پخش خودکار', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
        ]);

        $this->add_control('autoplay_delay', [
            'label'     => __('تأخیر (میلی‌ثانیه)', 'almasara-widgets'),
            'type'      => Controls_Manager::NUMBER,
            'default'   => 3500,
            'min'       => 1000,
            'step'      => 100,
            'condition' => ['autoplay' => 'yes'],
        ]);

        $this->add_control('pause_on_interaction', [
            'label'     => __('توقف بعد از تعامل کاربر', 'almasara-widgets'),
            'type'      => Controls_Manager::SWITCHER,
            'condition' => ['autoplay' => 'yes'],
        ]);

        $this->add_control('heading_nav', [
            'label'     => __('ناوبری', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('show_navigation', [
            'label'   => __('دکمه‌های قبلی/بعدی', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('nav_icon', [
            'label'       => __('آیکون فلش', 'almasara-widgets'),
            'type'        => Controls_Manager::MEDIA,
            'media_types' => ['image', 'svg'],
            'description' => __('خالی = فلش پیش‌فرض inline و رنگ‌پذیر.', 'almasara-widgets'),
            'condition'   => ['show_navigation' => 'yes'],
        ]);

        $this->add_control('show_pagination', [
            'label'     => __('نقطه‌های پیجینیشن', 'almasara-widgets'),
            'type'      => Controls_Manager::SWITCHER,
            'separator' => 'before',
        ]);

        $this->add_control('pagination_clickable', [
            'label'     => __('کلیک‌پذیر بودن نقطه‌ها', 'almasara-widgets'),
            'type'      => Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'condition' => ['show_pagination' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: هدر (عنوان + پیل‌ها + دکمه) ---------------- */

    private function register_header_style_controls(): void {
        $this->start_controls_section('section_style_header', [
            'label' => __('هدر', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('header_direction', [
            'label'                => __('جهت چیدمان', 'almasara-widgets'),
            'type'                 => Controls_Manager::CHOOSE,
            'default'              => 'row',
            'options'              => $this->layout_direction_options(),
            'selectors'            => ['{{WRAPPER}} .amw-ps__header' => 'flex-direction: {{VALUE}};'],
        ]);

        $this->add_responsive_control('header_justify', [
            'label'     => __('توزیع', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'default'   => 'space-between',
            'options'   => $this->layout_justify_options(),
            'selectors' => ['{{WRAPPER}} .amw-ps__header' => 'justify-content: {{VALUE}};'],
        ]);

        $this->add_responsive_control('header_align', [
            'label'     => __('تراز', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'default'   => 'center',
            'options'   => $this->layout_align_options(),
            'selectors' => ['{{WRAPPER}} .amw-ps__header' => 'align-items: {{VALUE}};'],
        ]);

        $this->add_responsive_control('header_wrap', [
            'label'     => __('شکستن به چند خط', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'default'   => 'wrap',
            'options'   => $this->layout_wrap_options(),
            'selectors' => ['{{WRAPPER}} .amw-ps__header' => 'flex-wrap: {{VALUE}};'],
        ]);

        $this->add_responsive_control('header_gap', [
            'label'      => __('فاصله بین اجزا', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-ps__header' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('header_margin_bottom', [
            'label'      => __('فاصله تا اسلایدر', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 80]],
            'default'    => ['size' => 24, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-ps__header' => 'margin-bottom: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('heading_title_style', [
            'label'     => __('عنوان', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'title_typo',
            'selector' => '{{WRAPPER}} .amw-ps__title',
        ]);

        $this->add_control('title_color', [
            'label'     => __('رنگ', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-ps__title' => 'color: {{VALUE}};'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: دکمه مشاهده همه ---------------- */

    private function register_button_style_controls(): void {
        $this->start_controls_section('section_style_button', [
            'label'     => __('دکمه مشاهده همه', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_view_all' => 'yes'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'btn_typo',
            'selector' => '{{WRAPPER}} .amw-ps__viewall',
        ]);

        $this->add_responsive_control('btn_padding', [
            'label'      => __('پدینگ', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .amw-ps__viewall' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->add_control('btn_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ['{{WRAPPER}} .amw-ps__viewall' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('heading_btn_normal', [
            'label'     => __('حالت عادی', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('btn_color', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-ps__viewall' => 'color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => 'btn_bg',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .amw-ps__viewall',
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'btn_border',
            'selector' => '{{WRAPPER}} .amw-ps__viewall',
        ]);

        $this->add_control('heading_btn_hover', [
            'label'     => __('حالت هاور', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('btn_color_hover', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-ps__viewall:hover' => 'color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => 'btn_bg_hover',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .amw-ps__viewall:hover',
        ]);

        $this->add_control('btn_border_color_hover', [
            'label'     => __('رنگ حاشیه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-ps__viewall:hover' => 'border-color: {{VALUE}};'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: پیل‌های دسته‌بندی ---------------- */

    private function register_pills_style_controls(): void {
        $this->start_controls_section('section_style_pills', [
            'label' => __('پیل‌های دسته‌بندی', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'pill_typo',
            'selector' => '{{WRAPPER}} .amw-ps__pill',
        ]);

        $this->add_responsive_control('pill_padding', [
            'label'      => __('پدینگ', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .amw-ps__pill' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->add_control('pill_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 20, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-ps__pill' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('pills_gap', [
            'label'      => __('فاصله بین پیل‌ها', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'default'    => ['size' => 8, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-ps__pills' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('heading_pill_normal', [
            'label'     => __('حالت عادی', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('pill_color', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-ps__pill' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('pill_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-ps__pill' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'pill_border',
            'selector' => '{{WRAPPER}} .amw-ps__pill',
        ]);

        $this->add_control('heading_pill_hover', [
            'label'     => __('حالت هاور', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('pill_color_hover', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-ps__pill:hover' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('pill_bg_hover', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-ps__pill:hover' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('heading_pill_active', [
            'label'     => __('حالت فعال (انتخاب‌شده)', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('pill_color_active', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => ['{{WRAPPER}} .amw-ps__pill.is-active' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('pill_bg_active', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#16265c',
            'selectors' => ['{{WRAPPER}} .amw-ps__pill.is-active' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('pill_border_color_active', [
            'label'     => __('رنگ حاشیه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-ps__pill.is-active' => 'border-color: {{VALUE}};'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: کارت (بسته‌بندی دور کارت جت‌انجین) ---------------- */

    private function register_card_style_controls(): void {
        $this->start_controls_section('section_style_card', [
            'label' => __('بسته‌بندی کارت', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('card_note', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('طراحی خودِ کارت از قالب Listing جت‌انجین می‌آید؛ این کنترل‌ها فقط دور آن را احاطه می‌کنند (مثلاً برای سایه/رادیوس یکسان روی همه کارت‌ها بدون دست‌زدن به قالب).', 'almasara-widgets'),
            'content_classes' => 'elementor-descriptor',
        ]);

        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'      => 'card_bg',
            'types'     => ['classic', 'gradient'],
            'selector'  => '{{WRAPPER}} .amw-ps__card',
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'card_border',
            'selector' => '{{WRAPPER}} .amw-ps__card',
        ]);

        $this->add_control('card_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .amw-ps__card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;'],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'card_shadow',
            'selector' => '{{WRAPPER}} .amw-ps__card',
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: دکمه‌های قبلی/بعدی ---------------- */

    private function register_nav_style_controls(): void {
        $this->start_controls_section('section_style_nav', [
            'label'     => __('دکمه‌های قبلی/بعدی', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_navigation' => 'yes'],
        ]);

        $this->add_responsive_control('nav_size', [
            'label'      => __('اندازه آیکون', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 10, 'max' => 40]],
            'default'    => ['size' => 18, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-ps__btn' => '--amw-ps-nav-size: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('nav_box_size', [
            'label'      => __('اندازه دکمه', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 24, 'max' => 80]],
            'default'    => ['size' => 40, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-ps__btn' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('nav_offset_x', [
            'label'      => __('فاصله از کناره‌های بخش', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => -40, 'max' => 40]],
            'default'    => ['size' => 0, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .amw-ps__btn--prev' => 'left: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .amw-ps__btn--next' => 'right: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('heading_nav_normal', [
            'label'     => __('حالت عادی', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('nav_color', [
            'label'     => __('رنگ آیکون', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#16265c',
            'selectors' => ['{{WRAPPER}} .amw-ps__btn' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('nav_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => ['{{WRAPPER}} .amw-ps__btn' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'nav_shadow',
            'selector' => '{{WRAPPER}} .amw-ps__btn',
        ]);

        $this->add_control('heading_nav_hover', [
            'label'     => __('حالت هاور', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('nav_color_hover', [
            'label'     => __('رنگ آیکون', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-ps__btn:hover' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('nav_bg_hover', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-ps__btn:hover' => 'background-color: {{VALUE}};'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: پیجینیشن ---------------- */

    private function register_pagination_style_controls(): void {
        $this->start_controls_section('section_style_pagination', [
            'label'     => __('پیجینیشن (نقطه‌ها)', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_pagination' => 'yes'],
        ]);

        $this->add_control('dot_size', [
            'label'      => __('اندازه نقطه', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 2, 'max' => 20]],
            'default'    => ['size' => 6, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-ps__pagination .swiper-pagination-bullet' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('dot_color', [
            'label'     => __('رنگ نقطه غیرفعال', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#cad2de',
            'selectors' => ['{{WRAPPER}} .amw-ps__pagination .swiper-pagination-bullet' => 'background: {{VALUE}};'],
        ]);

        $this->add_control('dot_color_active', [
            'label'     => __('رنگ نقطه فعال', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#16265c',
            'selectors' => ['{{WRAPPER}} .amw-ps__pagination .swiper-pagination-bullet-active' => 'background: {{VALUE}};'],
        ]);

        $this->end_controls_section();
    }

    /* =====================================================================
     * کمکی‌های کنترل
     * =================================================================== */

    private function get_product_category_options(): array {
        if (!function_exists('get_terms')) {
            return [];
        }
        $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
        if (is_wp_error($terms)) {
            return [];
        }
        $options = [];
        foreach ($terms as $term) {
            $options[$term->term_id] = $term->name;
        }
        return $options;
    }

    private function get_jetengine_listing_options(): array {
        $posts = get_posts([
            'post_type'      => 'jet-engine',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
        $options = [];
        foreach ($posts as $post) {
            $options[$post->ID] = $post->post_title;
        }
        return $options;
    }

    /* =====================================================================
     * رندر
     * =================================================================== */

    protected function render(): void {
        $settings = $this->get_settings_for_display();

        $listing_id = absint($settings['listing_id'] ?? 0);
        $is_editor  = \Elementor\Plugin::$instance->editor->is_edit_mode();

        if (!$listing_id) {
            if ($is_editor) {
                echo '<div class="amw-ps__notice">' . esc_html__('یک قالب Listing جت‌انجین برای کارت محصول انتخاب کنید.', 'almasara-widgets') . '</div>';
            }
            return;
        }

        $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');

        // المنتور دسکتاپ‌محور (پایه=دسکتاپ) → breakpoints موبایل‌محور Swiper
        // (پایه=کوچک‌ترین)، عیناً مثل ویجت اسلایدر هیرو.
        $responsive = static function (array $settings, string $key, $cast) {
            return [
                'mobile'  => $cast($settings[$key . '_mobile'] ?? $settings[$key]),
                'tablet'  => $cast($settings[$key . '_tablet'] ?? $settings[$key]),
                'desktop' => $cast($settings[$key]),
            ];
        };
        $to_int   = static fn($v) => (int) $v;
        $to_float = static fn($v) => (float) $v;

        $speed = $responsive($settings, 'speed', $to_int);
        $spv   = $responsive($settings, 'slides_per_view', $to_float);
        $space = $responsive($settings, 'space_between', $to_int);

        $cfg = [
            'restUrl'              => esc_url_raw(rest_url('almasara/v1/product-section')),
            'listingId'            => $listing_id,
            'count'                => max(1, (int) $settings['products_count']),
            'orderby'              => $settings['orderby'],
            'order'                => $settings['order'],
            'speed'                => $speed['mobile'],
            'slidesPerView'        => $spv['mobile'],
            'spaceBetween'         => $space['mobile'],
            'breakpoints'          => [
                768  => ['speed' => $speed['tablet'], 'slidesPerView' => $spv['tablet'], 'spaceBetween' => $space['tablet']],
                1025 => ['speed' => $speed['desktop'], 'slidesPerView' => $spv['desktop'], 'spaceBetween' => $space['desktop']],
            ],
            'rewind'               => 'yes' === $settings['rewind'],
            'rtl'                  => 'yes' === $settings['rtl'],
            'autoplay'             => 'yes' === $settings['autoplay'],
            'delay'                => max(1000, (int) $settings['autoplay_delay']),
            'disableOnInteraction' => 'yes' === ($settings['pause_on_interaction'] ?? ''),
            'navigation'           => 'yes' === $settings['show_navigation'],
            'pagination'           => 'yes' === $settings['show_pagination'],
            'paginationClickable'  => 'yes' === ($settings['pagination_clickable'] ?? ''),
        ];

        printf('<div class="amw-ps" data-cfg="%s">', esc_attr(wp_json_encode($cfg)));

        $this->render_header($settings, $shop_url);

        echo '<div class="amw-ps__slider-wrap">';
        echo '<div class="amw-ps__slider swiper">';
        echo '<div class="swiper-wrapper">';

        $result = \Almasara_Widgets\Product_Section_Ajax::query_and_render([
            'listing_id' => $listing_id,
            'category'   => 0,
            'count'      => $cfg['count'],
            'orderby'    => $cfg['orderby'],
            'order'      => $cfg['order'],
        ]);
        echo $result['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- رندرشده از قالب Listing، محتوایش مسئولیت خودِ جت‌انجین است

        echo '</div>'; // .swiper-wrapper

        if ('yes' === $settings['show_pagination']) {
            echo '<div class="swiper-pagination amw-ps__pagination"></div>';
        }

        echo '</div>'; // .swiper

        if ('yes' === $settings['show_navigation']) {
            echo '<button type="button" class="amw-ps__btn amw-ps__btn--prev" aria-label="' . esc_attr__('قبلی', 'almasara-widgets') . '">';
            $this->render_nav_icon($settings, true);
            echo '</button>';
            echo '<button type="button" class="amw-ps__btn amw-ps__btn--next" aria-label="' . esc_attr__('بعدی', 'almasara-widgets') . '">';
            $this->render_nav_icon($settings, false);
            echo '</button>';
        }

        echo '</div>'; // .amw-ps__slider-wrap
        echo '</div>'; // .amw-ps
    }

    /** هدر: عنوان + پیل‌های دسته‌بندی + دکمه مشاهده همه */
    private function render_header(array $settings, string $shop_url): void {
        echo '<div class="amw-ps__header">';

        if ('' !== trim((string) $settings['title'])) {
            echo '<h2 class="amw-ps__title">' . esc_html($settings['title']) . '</h2>';
        }

        echo '<div class="amw-ps__pills" role="tablist">';
        printf(
            '<button type="button" class="amw-ps__pill is-active" data-term="0" data-link="%s" role="tab" aria-selected="true">%s</button>',
            esc_url($shop_url),
            esc_html($settings['all_label'])
        );

        foreach ((array) ($settings['categories'] ?? []) as $row) {
            $term_id = absint($row['category'] ?? 0);
            if (!$term_id) {
                continue;
            }
            $term = get_term($term_id, 'product_cat');
            if (!$term || is_wp_error($term)) {
                continue;
            }
            $label = '' !== trim((string) ($row['label'] ?? '')) ? $row['label'] : $term->name;
            $link  = get_term_link($term, 'product_cat');
            printf(
                '<button type="button" class="amw-ps__pill" data-term="%d" data-link="%s" role="tab" aria-selected="false">%s</button>',
                $term_id,
                esc_url(is_wp_error($link) ? $shop_url : $link),
                esc_html($label)
            );
        }
        echo '</div>';

        if ('yes' === $settings['show_view_all']) {
            printf(
                '<a class="amw-ps__viewall" href="%s">%s</a>',
                esc_url($shop_url),
                esc_html($settings['view_all_text'])
            );
        }

        echo '</div>'; // .amw-ps__header
    }

    /** آیکون فلش سفارشی یا شورون پیش‌فرض inline (رنگ‌پذیر با currentColor) */
    private function render_nav_icon(array $settings, bool $is_prev): void {
        if (!empty($settings['nav_icon']['url'])) {
            $url    = $settings['nav_icon']['url'];
            $is_svg = 'svg' === strtolower(pathinfo(wp_parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
            if ($is_svg && !empty($settings['nav_icon']['id'])) {
                $svg = $this->get_inline_svg((int) $settings['nav_icon']['id']);
                if ($svg) {
                    echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput -- sanitized in get_inline_svg()
                    return;
                }
            }
            printf('<img src="%s" alt="">', esc_url($url));
            return;
        }

        $path = $is_prev ? 'm15 6-6 6 6 6' : 'm9 6 6 6-6 6';
        printf(
            '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="%s"/></svg>',
            $path
        );
    }
}
