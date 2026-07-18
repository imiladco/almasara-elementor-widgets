<?php
namespace Almasara_Widgets\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ویجت «توضیحات محصول»
 *
 * سطر معرفی: عنوان + خط جداکننده + آیکون (مشترک — Traits\Intro_Row)
 * سطر بعد: توضیحات محصول (کامل/کوتاه) یا متن دلخواه.
 */
class Product_Description extends Widget_Base {

    use Traits\Intro_Row;

    public function get_name(): string {
        return 'almasara-product-description';
    }

    public function get_title(): string {
        return __('توضیحات محصول الماسارا', 'almasara-widgets');
    }

    public function get_icon(): string {
        return 'eicon-product-description';
    }

    public function get_categories(): array {
        return ['almasara', 'woocommerce-elements'];
    }

    public function get_keywords(): array {
        return ['توضیحات', 'محصول', 'description', 'product', 'woocommerce', 'الماسارا'];
    }

    public function get_style_depends(): array {
        return ['almasara-widgets'];
    }

    /* ---------------------------------------------------------------------
     * کنترل‌ها
     * ------------------------------------------------------------------- */

    protected function register_controls(): void {
        // تب محتوا
        $this->register_intro_content_controls(__('مشاهده توضیحات محصول', 'almasara-widgets'));
        $this->register_layout_section();
        $this->register_description_content_controls();
        $this->register_link_section();

        // تب استایل
        $this->register_intro_style_controls();
        $this->register_description_style_controls();
        $this->register_headings_style_controls();
        $this->register_inline_style_controls();
        $this->register_lists_style_controls();
        $this->register_table_style_controls();
        $this->register_blockquote_style_controls();
    }

    /** محتوا — چیدمان: فقط سطر معرفی (از Trait) */
    private function register_layout_section(): void {
        $this->start_controls_section('section_layout', [
            'label' => __('چیدمان', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->register_header_layout_controls();

        $this->end_controls_section();
    }

    /** محتوا — بخش توضیحات */
    private function register_description_content_controls(): void {
        $this->start_controls_section('section_description', [
            'label' => __('توضیحات', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('description_source', [
            'label'   => __('منبع توضیحات', 'almasara-widgets'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'full',
            'options' => [
                'full'   => __('توضیحات کامل محصول', 'almasara-widgets'),
                'short'  => __('توضیحات کوتاه محصول', 'almasara-widgets'),
                'custom' => __('متن دلخواه', 'almasara-widgets'),
            ],
        ]);

        $this->add_control('custom_description', [
            'label'     => __('متن', 'almasara-widgets'),
            'type'      => Controls_Manager::WYSIWYG,
            'dynamic'   => ['active' => true],
            'condition' => ['description_source' => 'custom'],
        ]);

        $this->add_control('words_limit', [
            'label'       => __('حداکثر تعداد کلمات', 'almasara-widgets'),
            'type'        => Controls_Manager::NUMBER,
            'min'         => 0,
            'default'     => 0,
            'description' => __('۰ یعنی نمایش کامل. با محدود کردن، قالب‌بندی HTML حذف و متن ساده با «...» کوتاه می‌شود.', 'almasara-widgets'),
        ]);

        $this->end_controls_section();
    }

    /** محتوا — لینک (بدون حالت آیتم‌ها) */
    private function register_link_section(): void {
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
                'title'  => __('عنوان', 'almasara-widgets'),
                'header' => __('کل سطر معرفی', 'almasara-widgets'),
                'widget' => __('کل ویجت', 'almasara-widgets'),
            ],
        ]);

        $this->add_control('link', [
            'label'     => __('لینک', 'almasara-widgets'),
            'type'      => Controls_Manager::URL,
            'dynamic'   => ['active' => true],
            'condition' => ['link_scope!' => 'none'],
        ]);

        $this->end_controls_section();
    }

    /** استایل — تب توضیحات */
    private function register_description_style_controls(): void {
        $this->start_controls_section('section_style_description', [
            'label' => __('توضیحات', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'description_typography',
            'selector' => '{{WRAPPER}} .amw-pd__content',
        ]);

        $this->add_responsive_control('paragraph_spacing', [
            'label'      => __('فاصله بین پاراگراف‌ها', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 80]],
            'selectors'  => [
                '{{WRAPPER}} .amw-pd__content p' => 'margin-block-start: 0; margin-block-end: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('description_align', [
            'label'     => __('چینش متن', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'right'   => ['title' => __('راست', 'almasara-widgets'), 'icon' => 'eicon-text-align-right'],
                'center'  => ['title' => __('وسط', 'almasara-widgets'), 'icon' => 'eicon-text-align-center'],
                'left'    => ['title' => __('چپ', 'almasara-widgets'), 'icon' => 'eicon-text-align-left'],
                'justify' => ['title' => __('هم‌تراز', 'almasara-widgets'), 'icon' => 'eicon-text-align-justify'],
            ],
            'selectors' => [
                '{{WRAPPER}} .amw-pd__content' => 'text-align: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('description_padding', [
            'label'      => __('پدینگ', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', '%'],
            'selectors'  => [
                '{{WRAPPER}} .amw-pd__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_control('description_colors_divider', [
            'type' => Controls_Manager::DIVIDER,
        ]);

        $this->start_controls_tabs('description_color_tabs');

        $this->start_controls_tab('description_colors_normal', ['label' => __('عادی', 'almasara-widgets')]);

        $this->add_control('description_color', [
            'label'     => __('رنگ', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pd__content' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Text_Shadow::get_type(), [
            'name'     => 'description_shadow',
            'selector' => '{{WRAPPER}} .amw-pd__content',
        ]);

        $this->end_controls_tab();

        $this->start_controls_tab('description_colors_hover', ['label' => __('هاور', 'almasara-widgets')]);

        $this->add_control('description_color_hover', [
            'label'     => __('رنگ', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pd__content:hover' => 'color: {{VALUE}};',
            ],
        ]);

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    /** استایل — عنوان‌های داخل متن */
    private function register_headings_style_controls(): void {
        $this->start_controls_section('section_style_headings', [
            'label' => __('عنوان‌های متن', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'headings_typography',
            'label'    => __('تایپوگرافی (همه عنوان‌ها)', 'almasara-widgets'),
            'selector' => '{{WRAPPER}} .amw-pd__content h1, {{WRAPPER}} .amw-pd__content h2, {{WRAPPER}} .amw-pd__content h3, {{WRAPPER}} .amw-pd__content h4, {{WRAPPER}} .amw-pd__content h5, {{WRAPPER}} .amw-pd__content h6',
        ]);

        $this->add_control('headings_color', [
            'label'     => __('رنگ (همه عنوان‌ها)', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pd__content h1, {{WRAPPER}} .amw-pd__content h2, {{WRAPPER}} .amw-pd__content h3, {{WRAPPER}} .amw-pd__content h4, {{WRAPPER}} .amw-pd__content h5, {{WRAPPER}} .amw-pd__content h6' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('headings_spacing_note', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('فاصله بالا و پایین هر تگ عنوان را جداگانه تنظیم کنید:', 'almasara-widgets'),
            'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            'separator'       => 'before',
        ]);

        foreach (['h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
            $this->add_responsive_control('heading_margin_' . $tag, [
                'label'              => sprintf(__('فاصله %s (بالا / پایین)', 'almasara-widgets'), strtoupper($tag)),
                'type'               => Controls_Manager::DIMENSIONS,
                'size_units'         => ['px', 'em'],
                'allowed_dimensions' => 'vertical',
                'selectors'          => [
                    '{{WRAPPER}} .amw-pd__content ' . $tag => 'margin-block-start: {{TOP}}{{UNIT}}; margin-block-end: {{BOTTOM}}{{UNIT}};',
                ],
            ]);
        }

        $this->end_controls_section();
    }

    /** استایل — لینک‌ها و متن بولد */
    private function register_inline_style_controls(): void {
        $this->start_controls_section('section_style_inline', [
            'label' => __('لینک‌ها و بولد', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('heading_links', [
            'label' => __('لینک‌ها', 'almasara-widgets'),
            'type'  => Controls_Manager::HEADING,
        ]);

        $this->start_controls_tabs('link_tabs');

        $this->start_controls_tab('link_tab_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->add_control('link_color', [
            'label'     => __('رنگ', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-pd__content a' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('link_decoration', [
            'label'     => __('خط زیر', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => '',
            'options'   => [
                ''          => __('پیش‌فرض', 'almasara-widgets'),
                'underline' => __('داشته باشد', 'almasara-widgets'),
                'none'      => __('نداشته باشد', 'almasara-widgets'),
            ],
            'selectors' => ['{{WRAPPER}} .amw-pd__content a' => 'text-decoration: {{VALUE}};'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('link_tab_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->add_control('link_color_hover', [
            'label'     => __('رنگ', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-pd__content a:hover' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('link_decoration_hover', [
            'label'     => __('خط زیر', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => '',
            'options'   => [
                ''          => __('پیش‌فرض', 'almasara-widgets'),
                'underline' => __('داشته باشد', 'almasara-widgets'),
                'none'      => __('نداشته باشد', 'almasara-widgets'),
            ],
            'selectors' => ['{{WRAPPER}} .amw-pd__content a:hover' => 'text-decoration: {{VALUE}};'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control('heading_bold', [
            'label'     => __('متن بولد', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('bold_color', [
            'label'     => __('رنگ', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pd__content strong, {{WRAPPER}} .amw-pd__content b' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('bold_weight', [
            'label'     => __('وزن فونت', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => '',
            'options'   => [
                ''    => __('پیش‌فرض', 'almasara-widgets'),
                '500' => '500',
                '600' => '600',
                '700' => '700',
                '800' => '800',
                '900' => '900',
            ],
            'selectors' => [
                '{{WRAPPER}} .amw-pd__content strong, {{WRAPPER}} .amw-pd__content b' => 'font-weight: {{VALUE}};',
            ],
        ]);

        $this->end_controls_section();
    }

    /** استایل — لیست‌ها و بولت‌ها */
    private function register_lists_style_controls(): void {
        $this->start_controls_section('section_style_lists', [
            'label' => __('لیست‌ها (بولت‌ها)', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('list_marker_style', [
            'label'     => __('شکل بولت', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => '',
            'options'   => [
                ''       => __('پیش‌فرض', 'almasara-widgets'),
                'disc'   => __('دایره توپر', 'almasara-widgets'),
                'circle' => __('دایره توخالی', 'almasara-widgets'),
                'square' => __('مربع', 'almasara-widgets'),
                'none'   => __('بدون بولت', 'almasara-widgets'),
            ],
            'selectors' => ['{{WRAPPER}} .amw-pd__content ul' => 'list-style-type: {{VALUE}};'],
        ]);

        $this->add_control('marker_color', [
            'label'     => __('رنگ بولت/شماره', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pd__content li::marker' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('marker_size', [
            'label'      => __('اندازه بولت/شماره', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 8, 'max' => 40]],
            'selectors'  => [
                '{{WRAPPER}} .amw-pd__content li::marker' => 'font-size: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('list_indent', [
            'label'      => __('تورفتگی لیست', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 80]],
            'selectors'  => [
                '{{WRAPPER}} .amw-pd__content ul, {{WRAPPER}} .amw-pd__content ol' => 'padding-inline-start: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('list_item_gap', [
            'label'      => __('فاصله بین آیتم‌ها', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => [
                '{{WRAPPER}} .amw-pd__content li' => 'margin-block-end: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('list_block_gap', [
            'label'      => __('فاصله لیست از متن اطراف', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 80]],
            'selectors'  => [
                '{{WRAPPER}} .amw-pd__content ul, {{WRAPPER}} .amw-pd__content ol' => 'margin-block: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('list_text_color', [
            'label'     => __('رنگ متن آیتم‌ها', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'separator' => 'before',
            'selectors' => ['{{WRAPPER}} .amw-pd__content li' => 'color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'list_typography',
            'label'    => __('تایپوگرافی آیتم‌ها', 'almasara-widgets'),
            'selector' => '{{WRAPPER}} .amw-pd__content li',
        ]);

        $this->end_controls_section();
    }

    /** استایل — جدول‌ها */
    private function register_table_style_controls(): void {
        $this->start_controls_section('section_style_table', [
            'label' => __('جدول‌ها', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('table_border_color', [
            'label'     => __('رنگ خطوط جدول', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pd__content table, {{WRAPPER}} .amw-pd__content th, {{WRAPPER}} .amw-pd__content td' => 'border-color: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('table_border_width', [
            'label'      => __('ضخامت خطوط', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 6]],
            'selectors'  => [
                '{{WRAPPER}} .amw-pd__content th, {{WRAPPER}} .amw-pd__content td' => 'border-width: {{SIZE}}{{UNIT}}; border-style: solid;',
            ],
        ]);

        $this->add_responsive_control('table_radius', [
            'label'      => __('رادیوس جدول', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'selectors'  => [
                '{{WRAPPER}} .amw-pd__content table' => 'border-radius: {{SIZE}}{{UNIT}}; overflow: hidden; border-collapse: separate; border-spacing: 0;',
            ],
        ]);

        $this->add_responsive_control('table_cell_padding', [
            'label'      => __('پدینگ سلول‌ها', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .amw-pd__content th, {{WRAPPER}} .amw-pd__content td' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('table_align', [
            'label'     => __('چینش متن سلول‌ها', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'right'  => ['title' => __('راست', 'almasara-widgets'), 'icon' => 'eicon-text-align-right'],
                'center' => ['title' => __('وسط', 'almasara-widgets'), 'icon' => 'eicon-text-align-center'],
                'left'   => ['title' => __('چپ', 'almasara-widgets'), 'icon' => 'eicon-text-align-left'],
            ],
            'selectors' => [
                '{{WRAPPER}} .amw-pd__content th, {{WRAPPER}} .amw-pd__content td' => 'text-align: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('table_block_gap', [
            'label'      => __('فاصله جدول از متن اطراف', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 80]],
            'selectors'  => [
                '{{WRAPPER}} .amw-pd__content table' => 'margin-block: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('heading_table_head', [
            'label'     => __('سطر عنوان (th)', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('th_bg', [
            'label'     => __('پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-pd__content th' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('th_color', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-pd__content th' => 'color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'th_typography',
            'selector' => '{{WRAPPER}} .amw-pd__content th',
        ]);

        $this->add_control('heading_table_cells', [
            'label'     => __('سلول‌ها (td)', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('td_bg', [
            'label'     => __('پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-pd__content td' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('td_stripe_bg', [
            'label'       => __('پس‌زمینه سطرهای زوج (زبرا)', 'almasara-widgets'),
            'type'        => Controls_Manager::COLOR,
            'description' => __('برای خوانایی جدول‌های بلند، یکی‌درمیون رنگ می‌گیرند.', 'almasara-widgets'),
            'selectors'   => ['{{WRAPPER}} .amw-pd__content tr:nth-child(even) td' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('td_color', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-pd__content td' => 'color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'td_typography',
            'selector' => '{{WRAPPER}} .amw-pd__content td',
        ]);

        $this->end_controls_section();
    }

    /** استایل — بلاک‌کوت */
    private function register_blockquote_style_controls(): void {
        $this->start_controls_section('section_style_blockquote', [
            'label' => __('بلاک‌کوت (نقل‌قول)', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('bq_bg', [
            'label'     => __('پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-pd__content blockquote' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('bq_border_color', [
            'label'     => __('رنگ خط کنار', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pd__content blockquote' => 'border-inline-start-style: solid; border-inline-start-color: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('bq_border_width', [
            'label'      => __('ضخامت خط کنار', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 12]],
            'selectors'  => [
                '{{WRAPPER}} .amw-pd__content blockquote' => 'border-inline-start-width: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('bq_padding', [
            'label'      => __('پدینگ', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .amw-pd__content blockquote' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('bq_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .amw-pd__content blockquote' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('bq_block_gap', [
            'label'      => __('فاصله از متن اطراف', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 80]],
            'selectors'  => [
                '{{WRAPPER}} .amw-pd__content blockquote' => 'margin-block: {{SIZE}}{{UNIT}}; margin-inline: 0;',
            ],
        ]);

        $this->add_control('bq_color', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'separator' => 'before',
            'selectors' => ['{{WRAPPER}} .amw-pd__content blockquote' => 'color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'bq_typography',
            'selector' => '{{WRAPPER}} .amw-pd__content blockquote',
        ]);

        $this->end_controls_section();
    }

    /* ---------------------------------------------------------------------
     * رندر
     * ------------------------------------------------------------------- */

    protected function render(): void {
        $settings = $this->get_settings_for_display();

        $content = $this->resolve_description($settings);
        if ('' === trim(wp_strip_all_tags($content))) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="amw-paw__notice">' . esc_html__('توضیحاتی برای نمایش پیدا نشد. این ویجت را در قالب صفحه محصول استفاده کنید یا «متن دلخواه» را انتخاب کنید.', 'almasara-widgets') . '</div>';
            }
            return;
        }

        $scope       = $settings['link_scope'];
        $global_link = !empty($settings['link']['url']) ? $settings['link'] : null;

        $wrapper_tag = 'div';
        $this->add_render_attribute('wrapper', 'class', ['amw-paw', 'amw-pd']);
        if ('widget' === $scope && $global_link) {
            $wrapper_tag = 'a';
            $this->add_link_attributes('wrapper', $global_link);
        }

        ?>
        <<?php echo $wrapper_tag; // phpcs:ignore ?> <?php $this->print_render_attribute_string('wrapper'); ?>>

            <?php $this->render_intro_row($settings, $scope, $global_link); ?>

            <div class="amw-pd__content">
                <?php echo wp_kses_post($content); // phpcs:ignore ?>
            </div>

        </<?php echo $wrapper_tag; // phpcs:ignore ?>>
        <?php
    }

    /** خواندن توضیحات از منبع انتخابی و آماده‌سازی خروجی */
    private function resolve_description(array $settings): string {
        if ('custom' === $settings['description_source']) {
            $content = (string) $settings['custom_description'];
            $content = $this->parse_text_editor($content);
        } else {
            $product = $this->resolve_product();
            if (!$product) {
                return '';
            }

            $content = 'short' === $settings['description_source']
                ? $product->get_short_description()
                : $product->get_description();

            if ('' !== trim($content)) {
                $content = function_exists('wc_format_content') ? wc_format_content($content) : wpautop(do_shortcode($content));
            }
        }

        $limit = (int) ($settings['words_limit'] ?? 0);
        if ($limit > 0 && '' !== $content) {
            $content = wpautop(wp_trim_words($content, $limit, '...'));
        }

        return $content;
    }
}
