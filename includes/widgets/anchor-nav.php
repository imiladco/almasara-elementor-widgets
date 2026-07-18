<?php
namespace Almasara_Widgets\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ویجت «نوار تب چسبان» — ناوبری لنگری (scrollspy) بخش‌های صفحه محصول.
 * تب واقعی نیست: کلیک، اسکرول نرم به بخش مقصد است و هنگام اسکرول
 * تبِ بخشِ در حال مشاهده خودکار هایلایت می‌شود (بهتر برای سئو و UX).
 */
class Anchor_Nav extends Widget_Base {

    use Traits\Intro_Row; // برای گزینه‌های فلکس مشترک و رندر SVG

    public function get_name(): string {
        return 'almasara-anchor-nav';
    }

    public function get_title(): string {
        return __('نوار تب چسبان الماسارا', 'almasara-widgets');
    }

    public function get_icon(): string {
        return 'eicon-navigation-horizontal';
    }

    public function get_categories(): array {
        return ['almasara'];
    }

    public function get_keywords(): array {
        return ['تب', 'نوار', 'لنگر', 'nav', 'anchor', 'scrollspy', 'الماسارا'];
    }

    public function get_style_depends(): array {
        return ['almasara-widgets'];
    }

    public function get_script_depends(): array {
        return ['almasara-nav'];
    }

    protected function register_controls(): void {

        /* ---------------- محتوا ---------------- */
        $this->start_controls_section('section_items', [
            'label' => __('تب‌ها', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('side_title', [
            'label'   => __('عنوان کنار نوار', 'almasara-widgets'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('توضیحات', 'almasara-widgets'),
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('side_title_mode', [
            'label'       => __('رفتار عنوان', 'almasara-widgets'),
            'type'        => Controls_Manager::SELECT,
            'default'     => 'dynamic',
            'options'     => [
                'dynamic' => __('داینامیک: عنوان تبِ فعال (با انیمیشن رول)', 'almasara-widgets'),
                'static'  => __('ثابت: همیشه همین متن', 'almasara-widgets'),
            ],
            'description' => __('در حالت داینامیک، وقتی کاربر وارد هر بخش می‌شود عنوان با چرخش ۳۶۰ درجه به متن همان تب تغییر می‌کند؛ جهت چرخش هم‌جهت با حرکت کاربر است.', 'almasara-widgets'),
        ]);

        $this->add_control('show_steps', [
            'label'       => __('فلش‌های پیمایش بخش قبلی/بعدی', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => 'yes',
            'description' => __('شورون بالا/پایین کنار عنوان — مثل دیزاین — برای پرش به بخش قبلی یا بعدی.', 'almasara-widgets'),
        ]);

        $this->add_control('step_icon_up', [
            'label'       => __('آیکون فلش بالا', 'almasara-widgets'),
            'type'        => Controls_Manager::MEDIA,
            'media_types' => ['image', 'svg'],
            'description' => __('خالی = شورون پیش‌فرض. SVG به‌صورت inline رندر و رنگ‌پذیر می‌شود.', 'almasara-widgets'),
            'condition'   => ['show_steps' => 'yes'],
        ]);

        $this->add_control('step_icon_down', [
            'label'       => __('آیکون فلش پایین', 'almasara-widgets'),
            'type'        => Controls_Manager::MEDIA,
            'media_types' => ['image', 'svg'],
            'condition'   => ['show_steps' => 'yes'],
        ]);

        $repeater = new Repeater();

        $repeater->add_control('label', [
            'label'   => __('متن تب', 'almasara-widgets'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('توضیحات', 'almasara-widgets'),
        ]);

        $repeater->add_control('anchor', [
            'label'       => __('شناسه بخش مقصد (بدون #)', 'almasara-widgets'),
            'type'        => Controls_Manager::TEXT,
            'placeholder' => 'specs',
            'description' => __('همان CSS ID که در تب پیشرفته ویجت/کانتینر بخش مقصد وارد کرده‌اید.', 'almasara-widgets'),
        ]);

        $this->add_control('items', [
            'label'       => __('تب‌ها', 'almasara-widgets'),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $repeater->get_controls(),
            'title_field' => '{{{ label }}}',
            'default'     => [
                ['label' => __('توضیحات تکمیلی', 'almasara-widgets'), 'anchor' => 'specs'],
                ['label' => __('توضیحات', 'almasara-widgets'), 'anchor' => 'description'],
                ['label' => __('نظرات', 'almasara-widgets'), 'anchor' => 'reviews'],
                ['label' => __('پرسش‌های متداول', 'almasara-widgets'), 'anchor' => 'faq'],
            ],
        ]);

        $this->end_controls_section();

        $this->start_controls_section('section_behavior', [
            'label' => __('رفتار', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_responsive_control('sticky_mode', [
            'label'          => __('چسبیدن به بالای نمایشگر', 'almasara-widgets'),
            'type'           => Controls_Manager::SELECT,
            'default'        => 'sticky',
            'tablet_default' => 'sticky',
            'mobile_default' => 'sticky',
            'options'        => [
                'sticky' => __('چسبان (sticky)', 'almasara-widgets'),
                'static' => __('عادی (بدون چسبیدن)', 'almasara-widgets'),
            ],
            'description'    => __('برای دسکتاپ/تبلت/موبایل جداگانه تنظیم می‌شود (آیکون نمایشگر کنار عنوان کنترل).', 'almasara-widgets'),
            'selectors'      => [
                '{{WRAPPER}} .amw-nav' => 'position: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('sticky_offset', [
            'label'       => __('فاصله از بالای صفحه هنگام چسبیدن', 'almasara-widgets'),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => ['px'],
            'range'       => ['px' => ['min' => 0, 'max' => 200]],
            'default'     => ['size' => 0, 'unit' => 'px'],
            'description' => __('اگر هدر چسبان دارید، ارتفاع هدر را بدهید.', 'almasara-widgets'),
            'selectors'   => [
                '{{WRAPPER}} .amw-nav' => 'top: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('sticky_zindex', [
            'label'     => __('z-index', 'almasara-widgets'),
            'type'      => Controls_Manager::NUMBER,
            'default'   => 20,
            'selectors' => ['{{WRAPPER}} .amw-nav' => 'z-index: {{VALUE}};'],
        ]);

        $this->add_responsive_control('scroll_offset', [
            'label'       => __('فاصله توقف اسکرول از بالای بخش', 'almasara-widgets'),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => ['px'],
            'range'       => ['px' => ['min' => 0, 'max' => 300]],
            'default'     => ['size' => 90, 'unit' => 'px'],
            'description' => __('معمولاً ارتفاع نوار + هدر چسبان؛ تا عنوان بخش زیر نوار نرود.', 'almasara-widgets'),
        ]);

        $this->end_controls_section();

        /* ---------------- استایل: نوار ---------------- */
        $this->start_controls_section('section_style_bar', [
            'label' => __('نوار', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('bar_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-nav' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_responsive_control('bar_padding', [
            'label'      => __('پدینگ', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .amw-nav' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'bar_border',
            'selector' => '{{WRAPPER}} .amw-nav',
        ]);

        $this->add_responsive_control('bar_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .amw-nav' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'bar_shadow',
            'selector' => '{{WRAPPER}} .amw-nav',
        ]);

        // ---------------- چیدمان فلکس نوار ----------------
        $this->add_control('heading_bar_flex', [
            'label'     => __('چیدمان نوار', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_responsive_control('bar_direction', [
            'label'     => __('جهت', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => $this->layout_direction_options(),
            'selectors' => ['{{WRAPPER}} .amw-nav' => 'flex-direction: {{VALUE}};'],
        ]);

        $this->add_responsive_control('bar_justify', [
            'label'     => __('تراز کردن محتوا', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => $this->layout_justify_options(),
            'selectors' => ['{{WRAPPER}} .amw-nav' => 'justify-content: {{VALUE}};'],
        ]);

        $this->add_responsive_control('bar_align', [
            'label'     => __('تراز موارد', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => $this->layout_align_options(),
            'selectors' => ['{{WRAPPER}} .amw-nav' => 'align-items: {{VALUE}};'],
        ]);

        $this->add_responsive_control('bar_gaps', [
            'label'      => __('شکاف‌ها', 'almasara-widgets'),
            'type'       => Controls_Manager::GAPS,
            'size_units' => ['px', 'em'],
            'default'    => ['unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-nav' => 'gap: {{ROW}}{{UNIT}} {{COLUMN}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('bar_wrap', [
            'label'     => __('Wrap', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => $this->layout_wrap_options(),
            'selectors' => ['{{WRAPPER}} .amw-nav' => 'flex-wrap: {{VALUE}};'],
        ]);

        $this->add_responsive_control('lead_gap', [
            'label'       => __('فاصله فلش‌ها و عنوان', 'almasara-widgets'),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => ['px', 'em'],
            'range'       => ['px' => ['min' => 0, 'max' => 40]],
            'description' => __('فلش‌ها و عنوان در یک گروه‌اند؛ این فاصله داخل گروه است. فاصله گروه تا تب‌ها با «تراز کردن محتوا» (مثلاً فاصله بین) و «شکاف‌ها» کنترل می‌شود.', 'almasara-widgets'),
            'selectors'   => ['{{WRAPPER}} .amw-nav__lead' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('heading_items_flex', [
            'label'     => __('چیدمان تب‌ها', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_responsive_control('items_justify', [
            'label'     => __('تراز کردن تب‌ها', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => $this->layout_justify_options(),
            'selectors' => ['{{WRAPPER}} .amw-nav__items' => 'justify-content: {{VALUE}};'],
        ]);

        $this->add_responsive_control('items_wrap', [
            'label'     => __('Wrap تب‌ها', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => $this->layout_wrap_options(),
            'selectors' => ['{{WRAPPER}} .amw-nav__items' => 'flex-wrap: {{VALUE}};'],
        ]);

        $this->add_control('items_grow', [
            'label'        => __('تب‌ها عرض نوار را پر کنند', 'almasara-widgets'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => '1',
            'selectors'    => ['{{WRAPPER}} .amw-nav__items' => 'flex-grow: {{VALUE}};'],
        ]);

        // ---------------- هنگام چسبیدن ----------------
        $this->add_control('heading_stuck', [
            'label'       => __('هنگام چسبیدن به بالا', 'almasara-widgets'),
            'type'        => Controls_Manager::HEADING,
            'separator'   => 'before',
            'description' => __('وقتی نوار به بالای صفحه می‌چسبد کلاس is-stuck می‌گیرد.', 'almasara-widgets'),
        ]);

        $this->add_control('stuck_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-nav.is-stuck' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'stuck_shadow',
            'label'    => __('سایه', 'almasara-widgets'),
            'selector' => '{{WRAPPER}} .amw-nav.is-stuck',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'      => 'side_title_typography',
            'label'     => __('تایپوگرافی عنوان کنار', 'almasara-widgets'),
            'selector'  => '{{WRAPPER}} .amw-nav__title',
            'condition' => ['side_title!' => ''],
        ]);

        $this->add_control('side_title_color', [
            'label'     => __('رنگ عنوان کنار', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-nav__title' => 'color: {{VALUE}};'],
            'condition' => ['side_title!' => ''],
        ]);

        $this->end_controls_section();

        /* ---------------- استایل: تب‌ها ---------------- */
        $this->start_controls_section('section_style_items', [
            'label' => __('تب‌ها', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'item_typography',
            'selector' => '{{WRAPPER}} .amw-nav__item',
        ]);

        $this->add_responsive_control('items_gap', [
            'label'      => __('فاصله بین تب‌ها', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 80]],
            'selectors'  => ['{{WRAPPER}} .amw-nav__items' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('item_padding', [
            'label'      => __('پدینگ هر تب', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .amw-nav__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->start_controls_tabs('item_tabs');

        $this->start_controls_tab('item_tab_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->add_control('item_color', [
            'label'     => __('رنگ', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-nav__item' => 'color: {{VALUE}};'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('item_tab_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->add_control('item_color_hover', [
            'label'     => __('رنگ', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-nav__item:hover' => 'color: {{VALUE}};'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('item_tab_active', ['label' => __('فعال', 'almasara-widgets')]);
        $this->add_control('item_color_active', [
            'label'     => __('رنگ', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-nav__item.is-active' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('indicator_color', [
            'label'     => __('رنگ خط زیر تب فعال', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-nav__item::after' => 'border-top-color: {{VALUE}};'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control('indicator_style', [
            'label'     => __('نوع خط تب فعال', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'dotted',
            'options'   => [
                'dotted' => __('نقطه‌چین (مثل دیزاین)', 'almasara-widgets'),
                'dashed' => __('خط‌چین', 'almasara-widgets'),
                'solid'  => __('توپر', 'almasara-widgets'),
            ],
            'separator' => 'before',
            'selectors' => ['{{WRAPPER}} .amw-nav__item::after' => 'border-top-style: {{VALUE}};'],
        ]);

        $this->add_responsive_control('indicator_height', [
            'label'      => __('ضخامت خط تب فعال', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 1, 'max' => 8]],
            'selectors'  => ['{{WRAPPER}} .amw-nav__item::after' => 'border-top-width: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('indicator_gap', [
            'label'      => __('فاصله خط از متن', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 20]],
            'selectors'  => ['{{WRAPPER}} .amw-nav__item::after' => 'bottom: -{{SIZE}}{{UNIT}};'],
        ]);

        $this->end_controls_section();

        /* ---------------- استایل: فلش‌های پیمایش ---------------- */
        $this->start_controls_section('section_style_steps', [
            'label'     => __('فلش‌های پیمایش', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_steps' => 'yes'],
        ]);

        $this->add_responsive_control('step_size', [
            'label'      => __('اندازه آیکون', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 8, 'max' => 40]],
            'selectors'  => [
                '{{WRAPPER}} .amw-nav__step svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .amw-nav__step img' => 'width: {{SIZE}}{{UNIT}}; height: auto;',
            ],
        ]);

        $this->add_responsive_control('step_padding', [
            'label'      => __('پدینگ دکمه', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 20]],
            'selectors'  => ['{{WRAPPER}} .amw-nav__step' => 'padding: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('step_radius', [
            'label'      => __('رادیوس دکمه', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => ['px' => ['min' => 0, 'max' => 30], '%' => ['min' => 0, 'max' => 50]],
            'selectors'  => ['{{WRAPPER}} .amw-nav__step' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);

        $this->start_controls_tabs('step_tabs');

        $this->start_controls_tab('step_tab_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->add_control('step_color', [
            'label'     => __('رنگ آیکون', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-nav__step' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('step_bg', [
            'label'     => __('پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-nav__step' => 'background-color: {{VALUE}};'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('step_tab_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->add_control('step_color_hover', [
            'label'     => __('رنگ آیکون', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-nav__step:hover' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('step_bg_hover', [
            'label'       => __('پس‌زمینه', 'almasara-widgets'),
            'type'        => Controls_Manager::COLOR,
            'description' => __('مثلاً قرمز، تا فلش هنگام هاور بک‌گراند بگیرد.', 'almasara-widgets'),
            'selectors'   => ['{{WRAPPER}} .amw-nav__step:hover' => 'background-color: {{VALUE}};'],
        ]);
        $this->add_control('step_scale_hover', [
            'label'     => __('بزرگ‌نمایی', 'almasara-widgets'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 1, 'max' => 1.6, 'step' => 0.05]],
            'selectors' => ['{{WRAPPER}} .amw-nav__step:hover' => 'transform: scale({{SIZE}});'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        // ---------------- جداکننده بین فلش‌ها ----------------
        $this->add_control('heading_dash', [
            'label'     => __('جداکننده بین فلش‌ها', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('dash_show', [
            'label'     => __('نمایش جداکننده', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'block',
            'options'   => [
                'block' => __('نمایش', 'almasara-widgets'),
                'none'  => __('مخفی', 'almasara-widgets'),
            ],
            'selectors' => ['{{WRAPPER}} .amw-nav__step-dash' => 'display: {{VALUE}};'],
        ]);

        $this->add_control('dash_color', [
            'label'     => __('رنگ', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-nav__step-dash' => 'background-color: {{VALUE}};'],
            'condition' => ['dash_show' => 'block'],
        ]);

        $this->add_responsive_control('dash_width', [
            'label'      => __('طول', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 4, 'max' => 40]],
            'selectors'  => ['{{WRAPPER}} .amw-nav__step-dash' => 'width: {{SIZE}}{{UNIT}};'],
            'condition'  => ['dash_show' => 'block'],
        ]);

        $this->add_responsive_control('dash_height', [
            'label'      => __('ضخامت', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 1, 'max' => 8]],
            'selectors'  => ['{{WRAPPER}} .amw-nav__step-dash' => 'height: {{SIZE}}{{UNIT}};'],
            'condition'  => ['dash_show' => 'block'],
        ]);

        $this->add_responsive_control('dash_gap', [
            'label'      => __('فاصله از فلش‌ها', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 16]],
            'selectors'  => ['{{WRAPPER}} .amw-nav__steps' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);

        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $items    = (array) $settings['items'];
        if (empty($items)) {
            return;
        }

        $offset = isset($settings['scroll_offset']['size']) ? (int) $settings['scroll_offset']['size'] : 90;

        $this->add_render_attribute('nav', [
            'class'         => 'amw-nav',
            'data-offset'   => (string) $offset,
            'data-dyntitle' => 'dynamic' === $settings['side_title_mode'] ? '1' : '0',
        ]);

        ?>
        <div <?php $this->print_render_attribute_string('nav'); ?>>
            <div class="amw-nav__lead">
                <?php if ('yes' === $settings['show_steps']) : ?>
                    <span class="amw-nav__steps">
                        <button type="button" class="amw-nav__step amw-nav__step--up" aria-label="<?php echo esc_attr__('بخش قبلی', 'almasara-widgets'); ?>">
                            <?php $this->render_step_icon($settings, 'up'); ?>
                        </button>
                        <span class="amw-nav__step-dash" aria-hidden="true"></span>
                        <button type="button" class="amw-nav__step amw-nav__step--down" aria-label="<?php echo esc_attr__('بخش بعدی', 'almasara-widgets'); ?>">
                            <?php $this->render_step_icon($settings, 'down'); ?>
                        </button>
                    </span>
                <?php endif; ?>

                <?php if ('' !== $settings['side_title']) : ?>
                    <span class="amw-nav__title"><span class="amw-nav__title-in"><?php echo esc_html($settings['side_title']); ?></span></span>
                <?php endif; ?>
            </div>

            <nav class="amw-nav__items" aria-label="<?php echo esc_attr__('دسترسی سریع به بخش‌های محصول', 'almasara-widgets'); ?>">
                <?php foreach ($items as $item) :
                    $anchor = sanitize_title((string) ($item['anchor'] ?? ''));
                    if ('' === $anchor || '' === (string) ($item['label'] ?? '')) {
                        continue;
                    }
                    ?>
                    <a class="amw-nav__item" href="#<?php echo esc_attr($anchor); ?>"><?php echo esc_html($item['label']); ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
        <?php
    }

    /** آیکون فلش پیمایش: کاستوم (تصویر/SVG inline) یا شورون پیش‌فرض */
    private function render_step_icon(array $settings, string $which): void {
        $setting = $settings['step_icon_' . $which] ?? [];

        if (!empty($setting['url'])) {
            $url    = $setting['url'];
            $is_svg = 'svg' === strtolower(pathinfo(wp_parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

            if ($is_svg && !empty($setting['id'])) {
                $svg = $this->get_inline_svg((int) $setting['id']);
                if ($svg) {
                    echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput -- sanitized in get_inline_svg()
                    return;
                }
            }

            printf('<img src="%s" alt="">', esc_url($url));
            return;
        }

        $path = 'up' === $which ? 'm18 15-6-6-6 6' : 'm6 9 6 6 6-6';
        printf(
            '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="%s"/></svg>',
            esc_attr($path)
        );
    }
}
