<?php
namespace Almasara_Widgets\Widgets\Product_Section;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Css_Filter;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * کنترل‌های تب «استایل» ویجت بخش محصولات.
 *
 * قرارداد مهم: در همهٔ بخش‌های کارت، «هاور» یعنی موس روی هر جای کارت
 * باشد، نه فقط روی خودِ آن بخش — به همین دلیل سلکتور هاور همیشه از ریشهٔ
 * ‎.amw-card شروع می‌شود (card_hover_selector).
 */
trait Style_Controls {

    /* ---------------- استایل: هدر (عنوان + پیل‌ها + دکمه) ---------------- */

    /**
     * ثبت مجموعه کامل کنترل‌های فلکس‌باکس (جهت / تراز کردن محتوا / تراز
     * موارد / شکاف‌ها / wrap) برای یک سلکتور — همه ریسپانسیو.
     */
    /* ---------------- استایل: چیدمان (هدر / ردیف فیلتر / پیل‌ها) ---------------- */

    private function register_layout_style_controls(): void {
        $this->start_controls_section('section_style_layout', [
            'label' => __('چیدمان', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->start_controls_tabs('layout_tabs');

        $this->start_controls_tab('layout_tab_header', ['label' => __('هدر', 'almasara-widgets')]);
        $this->add_full_flex_controls('header', '{{WRAPPER}} .amw-ps__header', [
            'direction' => 'row',
            'justify'   => 'space-between',
            'align'     => 'center',
            'wrap'      => 'wrap',
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('layout_tab_filter', ['label' => __('ردیف فیلتر', 'almasara-widgets')]);
        $this->add_control('filter_row_note', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('پیل‌های دسته‌بندی و دکمه «مشاهده همه» با هم در یک ردیف‌اند؛ با «جهت = افقی معکوس» می‌توانید جای دکمه و پیل‌ها را عوض کنید.', 'almasara-widgets'),
            'content_classes' => 'elementor-descriptor',
        ]);
        $this->add_responsive_control('filter_row_width', [
            'label'                => __('عرض ردیف فیلتر', 'almasara-widgets'),
            'type'                 => Controls_Manager::CHOOSE,
            'default'              => 'auto',
            'options'              => [
                'auto' => ['title' => __('خودکار (اندازه محتوا)', 'almasara-widgets'), 'icon' => 'eicon-h-align-left'],
                'grow' => ['title' => __('پرکردن فضای باقی‌مانده', 'almasara-widgets'), 'icon' => 'eicon-grow'],
                'full' => ['title' => __('تمام عرض (خط جدا)', 'almasara-widgets'), 'icon' => 'eicon-h-align-stretch'],
            ],
            'selectors_dictionary' => [
                'auto' => 'flex: 0 1 auto; width: auto;',
                'grow' => 'flex: 1 1 auto; width: auto;',
                'full' => 'flex: 1 0 100%; width: 100%;',
            ],
            'selectors'            => ['{{WRAPPER}} .amw-ps__filter-row' => '{{VALUE}}'],
            'description'          => __('«پرکردن» ردیف را کنار عنوان کش می‌آورد؛ «تمام عرض» آن را به خط بعدی زیر عنوان می‌برد.', 'almasara-widgets'),
        ]);
        $this->add_responsive_control('button_joins_title', [
            'label'       => __('دکمه در ردیف عنوان', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'description' => __('دکمه «مشاهده همه» را از کنار پیل‌ها به ردیف عنوان می‌برد و پیل‌ها را به خط بعد می‌فرستد. معمولاً فقط روی موبایل لازم است — همین کنترل را در حالت موبایل روشن کنید تا دسکتاپ دست‌نخورده بماند.', 'almasara-widgets'),
            'selectors'   => [
                // ردیف فیلتر از چیدمان خارج می‌شود تا پیل‌ها و دکمه مستقیماً
                // آیتم‌های فلکسِ خودِ هدر شوند و بشود با order جابه‌جایشان کرد
                '{{WRAPPER}} .amw-ps__filter-row' => 'display: contents;',
                '{{WRAPPER}} .amw-ps__title'      => 'order: 1;',
                '{{WRAPPER}} .amw-ps__viewall'    => 'order: 2;',
                '{{WRAPPER}} .amw-ps__pills'      => 'order: 3; flex: 1 0 100%;',
            ],
        ]);

        $this->add_full_flex_controls('filter_row', '{{WRAPPER}} .amw-ps__filter-row', [
            'direction' => 'row',
            'justify'   => 'flex-start',
            'align'     => 'center',
            'wrap'      => 'wrap',
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('layout_tab_pills', ['label' => __('پیل‌ها', 'almasara-widgets')]);
        $this->add_control('pills_grow', [
            'label'        => __('پیل‌ها فضای باقی‌مانده را بگیرند', 'almasara-widgets'),
            'type'         => Controls_Manager::SWITCHER,
            'description'  => __('روشن = پیل‌ها کش می‌آیند و دکمه «مشاهده همه» به انتهای ردیف رانده می‌شود.', 'almasara-widgets'),
            'return_value' => '1 1 auto',
            'selectors'    => ['{{WRAPPER}} .amw-ps__pills' => 'flex: {{VALUE}};'],
        ]);
        $this->add_full_flex_controls('pills', '{{WRAPPER}} .amw-ps__pills', [
            'direction' => 'row',
            'justify'   => 'flex-start',
            'align'     => 'center',
            'wrap'      => 'wrap',
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control('header_margin_bottom', [
            'label'      => __('فاصله هدر تا اسلایدر', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 80]],
            'default'    => ['size' => 24, 'unit' => 'px'],
            'separator'  => 'before',
            'selectors'  => ['{{WRAPPER}} .amw-ps__header' => 'margin-bottom: {{SIZE}}{{UNIT}};'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: عنوان ---------------- */

    private function register_header_style_controls(): void {
        $this->start_controls_section('section_style_title', [
            'label' => __('عنوان', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
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

        $this->add_responsive_control('viewall_hide', [
            'label'       => __('مخفی کردن دکمه', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'description' => __('برای پنهان‌کردن فقط در موبایل، همین کنترل را در حالت موبایل روشن کنید تا دسکتاپ دست‌نخورده بماند.', 'almasara-widgets'),
            'selectors'   => ['{{WRAPPER}} .amw-ps__viewall' => 'display: none;'],
        ]);

        $this->add_responsive_control('btn_width', [
            'label'                => __('عرض دکمه', 'almasara-widgets'),
            'type'                 => Controls_Manager::CHOOSE,
            'default'              => 'auto',
            'options'              => [
                'auto' => ['title' => __('خودکار (اندازه محتوا)', 'almasara-widgets'), 'icon' => 'eicon-h-align-left'],
                'grow' => ['title' => __('پرکردن فضای باقی‌مانده', 'almasara-widgets'), 'icon' => 'eicon-grow'],
                'full' => ['title' => __('تمام عرض ردیف', 'almasara-widgets'), 'icon' => 'eicon-h-align-stretch'],
            ],
            'selectors_dictionary' => [
                'auto' => 'flex: 0 0 auto; width: auto;',
                'grow' => 'flex: 1 1 auto; width: auto;',
                'full' => 'flex: 1 0 100%; width: 100%; justify-content: center;',
            ],
            'selectors'            => ['{{WRAPPER}} .amw-ps__viewall' => '{{VALUE}}'],
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

    /**
     * مجموعه کامل کنترل‌های یک حالت پیل: تایپوگرافی، رنگ، پس‌زمینه،
     * حاشیه، پدینگ، رادیوس — روی یک سلکتور مشخص (حالت عادی/هاور/فعال).
     */
    private function register_pill_state_controls(string $prefix, string $selector, array $defaults = []): void {
        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => $prefix . '_typo',
            'label'    => __('تایپوگرافی', 'almasara-widgets'),
            'selector' => $selector,
        ]);

        $this->add_control($prefix . '_color', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => $defaults['color'] ?? '',
            'selectors' => [$selector => 'color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => $prefix . '_bg',
            'label'    => __('پس‌زمینه', 'almasara-widgets'),
            'types'    => ['classic', 'gradient'],
            'selector' => $selector,
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => $prefix . '_border',
            'label'    => __('حاشیه', 'almasara-widgets'),
            'selector' => $selector,
        ]);

        $this->add_responsive_control($prefix . '_padding', [
            'label'      => __('پدینگ', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->add_responsive_control($prefix . '_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);
    }

    private function register_pills_style_controls(): void {
        $this->start_controls_section('section_style_pills', [
            'label' => __('پیل‌های دسته‌بندی', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('pill_states_note', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('برای جلوگیری از پرش چیدمان هنگام هاور یا انتخاب، بهتر است پدینگ و ضخامت حاشیه را در هر سه حالت یکسان نگه دارید و فقط رنگ‌ها را تغییر دهید.', 'almasara-widgets'),
            'content_classes' => 'elementor-descriptor',
        ]);

        $this->start_controls_tabs('pill_state_tabs');

        $this->start_controls_tab('pill_tab_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->register_pill_state_controls('pill', '{{WRAPPER}} .amw-ps__pill');
        $this->end_controls_tab();

        $this->start_controls_tab('pill_tab_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->register_pill_state_controls('pill_hover', '{{WRAPPER}} .amw-ps__pill:hover');
        $this->end_controls_tab();

        $this->start_controls_tab('pill_tab_active', ['label' => __('فعال', 'almasara-widgets')]);
        $this->register_pill_state_controls('pill_active', '{{WRAPPER}} .amw-ps__pill.is-active', ['color' => '#ffffff']);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    /* ---------------- استایل: کارت (بسته‌بندی دور کارت جت‌انجین) ---------------- */

    private function register_card_style_controls(): void {
        // فقط برای مسیر جت‌انجین. این بخش پوستهٔ بیرونی (.amw-ps__card) را
        // استایل می‌دهد و دلیل وجودش این است که به قالب Listing دسترسی
        // نداریم. برای کارت داخلی، بخش «کارت: آیتم» خودِ کارت را استایل
        // می‌دهد؛ اگر هر دو باز بمانند، کاربر پس‌زمینه را روی یک المان و
        // رادیوس را روی المان دیگر می‌گذارد و نتیجه بهم‌ریخته می‌شود.
        $this->start_controls_section('section_style_card', [
            'label'     => __('بسته‌بندی کارت', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['card_source' => 'jetengine'],
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
            'selectors'  => ['{{WRAPPER}} .amw-ps__card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'card_shadow',
            'selector' => '{{WRAPPER}} .amw-ps__card',
        ]);

        $this->add_control('card_clip_corners', [
            'label'       => __('قطع محتوای اضافی روی گوشه‌های گرد', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'description' => __('فقط اگر محتوای داخل کارت (مثلاً تصویر) گوشهٔ گرد ندارد و از رادیوس بالا بیرون می‌زند روشن کنید. توجه: با روشن‌بودن این گزینه، هر سایه‌ای — حتی سایه هاوری که خودِ قالب Listing روی کارت گذاشته — روی لبه‌های کارت قطع می‌شود؛ به همین دلیل پیش‌فرض خاموش است.', 'almasara-widgets'),
            'selectors'   => ['{{WRAPPER}} .amw-ps__card' => 'overflow: hidden;'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: کارت داخلی ---------------- */

    /**
     * مجموعهٔ استایل «جعبه‌ای» یک حالت: پس‌زمینه، حاشیه، رادیوس، سایه، پدینگ.
     * در همهٔ تب‌های عادی/هاورِ کارت تکرار می‌شود.
     */
    private function card_box_controls(string $prefix, string $selector, bool $with_padding = true): void {
        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => $prefix . '_bg',
            'label'    => __('پس‌زمینه', 'almasara-widgets'),
            'types'    => ['classic', 'gradient'],
            'selector' => $selector,
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => $prefix . '_border',
            'label'    => __('حاشیه', 'almasara-widgets'),
            'selector' => $selector,
        ]);

        $this->add_responsive_control($prefix . '_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => $prefix . '_shadow',
            'selector' => $selector,
        ]);

        if ($with_padding) {
            $this->add_responsive_control($prefix . '_padding', [
                'label'      => __('پدینگ', 'almasara-widgets'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
            ]);
        }
    }

    /**
     * مجموعهٔ استایل «متنی» یک حالت: تایپوگرافی، رنگ، سایهٔ متن، پدینگ و
     * (در صورت معنادار بودن) ترازبندی.
     *
     * $with_align برای شعار و عدد و واحد پول false است: آن‌ها آیتم‌های
     * inline-flex داخل یک ردیف فلکس‌اند و text-align رویشان هیچ اثری ندارد؛
     * جایگاه‌شان با کنترل‌های فلکسِ همان ردیف تعیین می‌شود. کنترلی که کاری
     * نمی‌کند فقط این حس را می‌سازد که «پیش‌نمایش درست کار نمی‌کند».
     */
    private function card_text_controls(string $prefix, string $selector, bool $with_align = true): void {
        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => $prefix . '_typo',
            'label'    => __('تایپوگرافی', 'almasara-widgets'),
            'selector' => $selector,
        ]);

        $this->add_control($prefix . '_color', [
            'label'     => __('رنگ', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$selector => 'color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Text_Shadow::get_type(), [
            'name'     => $prefix . '_tshadow',
            'selector' => $selector,
        ]);

        $this->add_responsive_control($prefix . '_padding', [
            'label'      => __('پدینگ', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        if ($with_align) {
            $this->add_responsive_control($prefix . '_align', [
                'label'     => __('ترازبندی', 'almasara-widgets'),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'start'  => ['title' => __('ابتدا', 'almasara-widgets'), 'icon' => 'eicon-text-align-right'],
                    'center' => ['title' => __('وسط', 'almasara-widgets'), 'icon' => 'eicon-text-align-center'],
                    'end'    => ['title' => __('انتها', 'almasara-widgets'), 'icon' => 'eicon-text-align-left'],
                ],
                'selectors' => [$selector => 'text-align: {{VALUE}};'],
            ]);
        }
    }

    /**
     * «هاور» در همهٔ بخش‌های داخلی یعنی هاور روی خودِ کارت، نه روی آن بخش —
     * پس سلکتور هاور همیشه از ریشهٔ .amw-card شروع می‌شود.
     */
    private function card_hover_selector(string $child = ''): string {
        return '{{WRAPPER}} .amw-card:hover' . ('' === $child ? '' : ' ' . $child);
    }

    private function register_card_item_style_controls(): void {
        $this->start_controls_section('section_style_card_item', [
            'label'     => __('کارت: آیتم', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['card_source' => 'builtin'],
        ]);

        $this->add_control('card_item_hover_note', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('در همهٔ بخش‌های کارت، «هاور» یعنی موس روی هر جای کارت باشد — نه فقط روی خودِ آن بخش.', 'almasara-widgets'),
            'content_classes' => 'elementor-descriptor',
        ]);

        $this->add_responsive_control('card_item_transition', [
            'label'      => __('مدت انیمیشن حالت هاور (ثانیه)', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['s'],
            'range'      => ['s' => ['min' => 0, 'max' => 2, 'step' => 0.05]],
            'default'    => ['size' => 0.25, 'unit' => 's'],
            'selectors'  => ['{{WRAPPER}} .amw-card' => '--amw-card-transition: {{SIZE}}s;'],
        ]);

        $this->add_responsive_control('card_item_height', [
            'label'       => __('ارتفاع کارت', 'almasara-widgets'),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => ['px', '%', 'vh'],
            'range'       => ['px' => ['min' => 100, 'max' => 900]],
            'description' => __('خالی = ارتفاع از محتوا. چون کارت‌ها کشیده می‌شوند، معمولاً لازم نیست.', 'almasara-widgets'),
            'selectors'   => ['{{WRAPPER}} .amw-card' => 'height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->start_controls_tabs('card_item_tabs');

        $this->start_controls_tab('card_item_tab_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->card_box_controls('card_item', '{{WRAPPER}} .amw-card');
        $this->end_controls_tab();

        $this->start_controls_tab('card_item_tab_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->card_box_controls('card_item_hover', $this->card_hover_selector());
        $this->add_responsive_control('card_item_hover_move', [
            'label'      => __('جابه‌جایی عمودی', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => -40, 'max' => 40]],
            'selectors'  => [$this->card_hover_selector() => 'transform: translateY({{SIZE}}{{UNIT}});'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control('heading_card_body', [
            'label'     => __('بدنه (زیر تصویر)', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_full_flex_controls('card_body', '{{WRAPPER}} .amw-card__body', [
            'direction' => 'column',
            'align'     => 'stretch',
        ]);

        $this->add_responsive_control('card_body_padding', [
            'label'      => __('پدینگ بدنه', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .amw-card__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->end_controls_section();
    }

    private function register_card_image_style_controls(): void {
        $this->start_controls_section('section_style_card_image', [
            'label'     => __('کارت: تصویر', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['card_source' => 'builtin', 'card_show_image' => 'yes'],
        ]);

        $this->add_responsive_control('card_img_width', [
            'label'      => __('عرض', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'default'    => ['size' => 100, 'unit' => '%'],
            'range'      => ['px' => ['min' => 20, 'max' => 600]],
            'selectors'  => ['{{WRAPPER}} .amw-card__img' => 'width: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('card_img_max_width', [
            'label'      => __('حداکثر عرض', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => ['px' => ['min' => 20, 'max' => 800]],
            'selectors'  => ['{{WRAPPER}} .amw-card__img' => 'max-width: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('card_img_height', [
            'label'      => __('ارتفاع', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%', 'vh'],
            'range'      => ['px' => ['min' => 40, 'max' => 700]],
            'selectors'  => ['{{WRAPPER}} .amw-card__img' => 'height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('card_img_ratio', [
            'label'       => __('نسبت ابعاد', 'almasara-widgets'),
            'type'        => Controls_Manager::SELECT,
            'default'     => '',
            'options'     => [
                ''      => __('پیش‌فرض (از خودِ تصویر)', 'almasara-widgets'),
                '1/1'   => __('۱:۱ مربع', 'almasara-widgets'),
                '4/3'   => '۴:۳',
                '3/4'   => '۳:۴',
                '16/9'  => '۱۶:۹',
                '3/2'   => '۳:۲',
                '2/3'   => '۲:۳',
            ],
            'description' => __('با تعیین نسبت، همهٔ تصویرها هم‌اندازه می‌شوند و کارت‌ها هم‌قد می‌مانند.', 'almasara-widgets'),
            'selectors'   => ['{{WRAPPER}} .amw-card__img' => 'aspect-ratio: {{VALUE}};'],
        ]);

        $this->add_control('card_img_fit', [
            'label'     => __('نحوهٔ جاگیری تصویر (object-fit)', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'cover',
            'options'   => [
                'cover'      => __('پوشاندن کامل (برش لبه‌ها)', 'almasara-widgets'),
                'contain'    => __('جاشدن کامل (بدون برش)', 'almasara-widgets'),
                'fill'       => __('کشیده‌شدن', 'almasara-widgets'),
                'none'       => __('اندازهٔ اصلی', 'almasara-widgets'),
                'scale-down' => __('کوچک‌شدن در صورت نیاز', 'almasara-widgets'),
            ],
            'selectors' => ['{{WRAPPER}} .amw-card__img' => 'object-fit: {{VALUE}};'],
        ]);

        $this->add_control('card_img_position', [
            'label'     => __('نقطهٔ تمرکز تصویر', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'center center',
            'options'   => [
                'center center' => __('وسط', 'almasara-widgets'),
                'top center'    => __('بالا', 'almasara-widgets'),
                'bottom center' => __('پایین', 'almasara-widgets'),
                'center left'   => __('چپ', 'almasara-widgets'),
                'center right'  => __('راست', 'almasara-widgets'),
            ],
            'selectors' => ['{{WRAPPER}} .amw-card__img' => 'object-position: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'      => 'card_img_border',
            'label'     => __('حاشیه', 'almasara-widgets'),
            'selector'  => '{{WRAPPER}} .amw-card__img',
            'separator' => 'before',
        ]);

        $this->add_responsive_control('card_img_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .amw-card__img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('card_media_padding', [
            'label'      => __('پدینگ کادر تصویر', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .amw-card__media' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('card_media_align', [
            'label'     => __('ترازبندی افقی تصویر', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'flex-start' => ['title' => __('ابتدا', 'almasara-widgets'), 'icon' => 'eicon-h-align-right'],
                'center'     => ['title' => __('وسط', 'almasara-widgets'), 'icon' => 'eicon-h-align-center'],
                'flex-end'   => ['title' => __('انتها', 'almasara-widgets'), 'icon' => 'eicon-h-align-left'],
            ],
            'selectors' => ['{{WRAPPER}} .amw-card__media' => 'justify-content: {{VALUE}};'],
        ]);

        $this->start_controls_tabs('card_img_tabs');

        $this->start_controls_tab('card_img_tab_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->card_image_state_controls('card_img', '{{WRAPPER}} .amw-card__img', 1, 1);
        $this->end_controls_tab();

        $this->start_controls_tab('card_img_tab_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->card_image_state_controls('card_img_hover', $this->card_hover_selector('.amw-card__img'), 1, 1.05);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    /** شفافیت و مقیاس تصویر در یک حالت */
    private function card_image_state_controls(string $prefix, string $selector, float $opacity, float $scale): void {
        $this->add_responsive_control($prefix . '_opacity', [
            'label'      => __('شفافیت', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 1, 'step' => 0.01]],
            'default'    => ['size' => $opacity],
            'selectors'  => [$selector => 'opacity: {{SIZE}};'],
        ]);

        $this->add_responsive_control($prefix . '_scale', [
            'label'      => __('مقیاس', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0.5, 'max' => 2, 'step' => 0.01]],
            'default'    => ['size' => $scale],
            'selectors'  => [$selector => 'transform: scale({{SIZE}});'],
        ]);

        $this->add_group_control(Group_Control_Css_Filter::get_type(), [
            'name'     => $prefix . '_filter',
            'selector' => $selector,
        ]);
    }

    private function register_card_title_style_controls(): void {
        $this->start_controls_section('section_style_card_title', [
            'label'     => __('کارت: عنوان', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['card_source' => 'builtin', 'card_show_title' => 'yes'],
        ]);

        $this->add_responsive_control('card_title_margin', [
            'label'      => __('مارجین', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .amw-card__title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->start_controls_tabs('card_title_tabs');

        $this->start_controls_tab('card_title_tab_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->card_text_controls('card_title', '{{WRAPPER}} .amw-card__title');
        $this->end_controls_tab();

        $this->start_controls_tab('card_title_tab_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->card_text_controls('card_title_hover', $this->card_hover_selector('.amw-card__title'));
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    private function register_card_price_style_controls(): void {
        $this->start_controls_section('section_style_card_price', [
            'label'     => __('کارت: بخش قیمت', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['card_source' => 'builtin'],
        ]);

        /* --- ۱) خودِ دیواید بخش قیمت --- */

        $this->add_control('heading_card_pricerow', [
            'label' => __('کادر بخش قیمت', 'almasara-widgets'),
            'type'  => Controls_Manager::HEADING,
        ]);

        $this->add_full_flex_controls('card_pricerow', '{{WRAPPER}} .amw-card__price', [
            'direction' => 'row',
            'justify'   => 'space-between',
            'align'     => 'center',
            'wrap'      => 'wrap',
        ]);

        $this->add_responsive_control('card_pricerow_margin', [
            'label'      => __('مارجین', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .amw-card__price' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->start_controls_tabs('card_pricerow_tabs');

        $this->start_controls_tab('card_pricerow_tab_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->card_box_controls('card_pricerow', '{{WRAPPER}} .amw-card__price');
        $this->end_controls_tab();

        $this->start_controls_tab('card_pricerow_tab_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->card_box_controls('card_pricerow_hover', $this->card_hover_selector('.amw-card__price'));
        $this->end_controls_tab();

        $this->end_controls_tabs();

        /* --- ۲) شعار --- */

        $this->add_control('heading_card_slogan', [
            'label'     => __('شعار', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['card_show_slogan' => 'yes'],
        ]);

        $this->add_responsive_control('card_slogan_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .amw-card__slogan' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
            'condition'  => ['card_show_slogan' => 'yes'],
        ]);

        $this->start_controls_tabs('card_slogan_tabs', ['condition' => ['card_show_slogan' => 'yes']]);

        $this->start_controls_tab('card_slogan_tab_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->card_text_controls('card_slogan', '{{WRAPPER}} .amw-card__slogan', false);
        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => 'card_slogan_bg',
            'label'    => __('پس‌زمینه', 'almasara-widgets'),
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .amw-card__slogan',
        ]);
        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'card_slogan_border',
            'label'    => __('حاشیه', 'almasara-widgets'),
            'selector' => '{{WRAPPER}} .amw-card__slogan',
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('card_slogan_tab_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->card_text_controls('card_slogan_hover', $this->card_hover_selector('.amw-card__slogan'), false);
        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => 'card_slogan_bg_hover',
            'label'    => __('پس‌زمینه', 'almasara-widgets'),
            'types'    => ['classic', 'gradient'],
            'selector' => $this->card_hover_selector('.amw-card__slogan'),
        ]);
        $this->add_control('card_slogan_border_color_hover', [
            'label'     => __('رنگ حاشیه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$this->card_hover_selector('.amw-card__slogan') => 'border-color: {{VALUE}};'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        /* --- ۳) قیمت: بستهٔ عدد+واحد، و هرکدام جداگانه --- */

        $this->add_control('heading_card_amount', [
            'label'     => __('قیمت', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['card_show_price' => 'yes'],
        ]);

        $this->add_full_flex_controls('card_amount', '{{WRAPPER}} .amw-card__amount', [
            'direction' => 'row',
            'align'     => 'baseline',
        ]);

        $this->start_controls_tabs('card_amount_tabs', ['condition' => ['card_show_price' => 'yes']]);

        $this->start_controls_tab('card_amount_tab_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->add_control('heading_card_num_normal', [
            'label' => __('عدد', 'almasara-widgets'),
            'type'  => Controls_Manager::HEADING,
        ]);
        $this->card_text_controls('card_num', '{{WRAPPER}} .amw-card__num', false);
        $this->add_control('heading_card_unit_normal', [
            'label'     => __('واحد پول', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);
        $this->card_text_controls('card_unit', '{{WRAPPER}} .amw-card__unit', false);
        $this->end_controls_tab();

        $this->start_controls_tab('card_amount_tab_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->add_control('heading_card_num_hover', [
            'label' => __('عدد', 'almasara-widgets'),
            'type'  => Controls_Manager::HEADING,
        ]);
        $this->card_text_controls('card_num_hover', $this->card_hover_selector('.amw-card__num'), false);
        $this->add_control('heading_card_unit_hover', [
            'label'     => __('واحد پول', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);
        $this->card_text_controls('card_unit_hover', $this->card_hover_selector('.amw-card__unit'), false);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    /* ---------------- استایل: دکمه‌های قبلی/بعدی ---------------- */

    /**
     * یک حالت آیکون ناوبری (عادی/هاور/غیرفعال): رنگ آیکون، پس‌زمینه، سایه،
     * شفافیت.
     *
     * دربارهٔ رنگ‌کردن SVG — دو نکته که نسخهٔ قبل را ناکار می‌کرد:
     *   • «svg [fill]» سلکتور نواده است و به خودِ تگ ریشهٔ <svg fill="..">
     *     نمی‌خورد، درحالی‌که بسیاری از فایل‌های آیکون رنگ را همان‌جا
     *     می‌گذارند. پس ریشه جداگانه هدف گرفته می‌شود.
     *   • فایل‌های خروجی ایلوستریتور/فیگما معمولاً style="fill:.." اینلاین
     *     دارند و استایل اینلاین از قاعدهٔ معمولی قوی‌تر است؛ تنها راه غلبه
     *     !important است. چون سلکتورها فقط به svgِ همین دکمه محدودند، دامنهٔ
     *     اثرش بسته است.
     * حالت‌های هاور/غیرفعال هم به‌خاطر ویژگیِ بالاترشان روی حالت عادی
     * می‌نشینند، حتی با وجود !important در هر دو.
     */
    private function register_nav_state_controls(string $prefix, string $selector, array $defaults = []): void {
        $paintable = static function (string $attr) use ($selector): array {
            $skip = ':not([' . $attr . '="none"]):not([' . $attr . '="transparent"])';

            return [
                // ریشهٔ svg
                $selector . ' svg' . $skip,
                // فرزندان دارای همان ویژگی
                $selector . ' svg [' . $attr . ']' . $skip,
            ];
        };

        $fill_targets   = $paintable('fill');
        $stroke_targets = $paintable('stroke');

        $this->add_control($prefix . '_color', [
            'label'       => __('رنگ آیکون', 'almasara-widgets'),
            'type'        => Controls_Manager::COLOR,
            'default'     => $defaults['color'] ?? '',
            'description' => __('هم روی آیکون پیش‌فرض و هم روی SVG سفارشی اثر می‌گذارد؛ بخش‌هایی که عمداً بی‌رنگ‌اند (none/transparent) دست‌نخورده می‌مانند.', 'almasara-widgets'),
            'selectors'   => [
                $selector                     => 'color: {{VALUE}};',
                implode(', ', $fill_targets)  => 'fill: {{VALUE}} !important;',
                implode(', ', $stroke_targets) => 'stroke: {{VALUE}} !important;',
            ],
        ]);

        $this->add_control($prefix . '_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => $defaults['bg'] ?? '',
            'selectors' => [$selector => 'background-color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => $prefix . '_shadow',
            'selector' => $selector,
        ]);

        $this->add_responsive_control($prefix . '_opacity', [
            'label'      => __('شفافیت', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 1, 'step' => 0.01]],
            'default'    => ['size' => $defaults['opacity'] ?? 1],
            'selectors'  => [$selector => 'opacity: {{SIZE}};'],
        ]);
    }

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

        $this->add_control('heading_nav_rotate', [
            'label'       => __('زاویه آیکون', 'almasara-widgets'),
            'type'        => Controls_Manager::HEADING,
            'separator'   => 'before',
        ]);

        $this->add_control('nav_rotate_note', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('چون فقط یک فایل آیکون آپلود می‌شود، اگر جهت پیش‌فرض آن با دکمه بعدی هم‌خوان نیست، اینجا زاویه چرخش هرکدام را جدا تنظیم کنید.', 'almasara-widgets'),
            'content_classes' => 'elementor-descriptor',
        ]);

        $this->add_responsive_control('nav_rotate_prev', [
            'label'      => __('زاویه آیکون قبلی', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['deg'],
            'range'      => ['deg' => ['min' => -180, 'max' => 180]],
            'default'    => ['size' => 0, 'unit' => 'deg'],
            'selectors'  => ['{{WRAPPER}} .amw-ps__btn--prev svg, {{WRAPPER}} .amw-ps__btn--prev img' => 'transform: rotate({{SIZE}}{{UNIT}});'],
        ]);

        $this->add_responsive_control('nav_rotate_next', [
            'label'      => __('زاویه آیکون بعدی', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['deg'],
            'range'      => ['deg' => ['min' => -180, 'max' => 180]],
            'default'    => ['size' => 0, 'unit' => 'deg'],
            'selectors'  => ['{{WRAPPER}} .amw-ps__btn--next svg, {{WRAPPER}} .amw-ps__btn--next img' => 'transform: rotate({{SIZE}}{{UNIT}});'],
        ]);

        $this->start_controls_tabs('nav_state_tabs');

        $this->start_controls_tab('nav_state_tab_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->register_nav_state_controls('nav', '{{WRAPPER}} .amw-ps__btn', ['color' => '#16265c', 'bg' => '#ffffff']);
        $this->end_controls_tab();

        $this->start_controls_tab('nav_state_tab_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->register_nav_state_controls('nav_hover', '{{WRAPPER}} .amw-ps__btn:hover');
        $this->end_controls_tab();

        $this->start_controls_tab('nav_state_tab_disabled', ['label' => __('غیرفعال', 'almasara-widgets')]);
        $this->add_control('nav_disabled_note', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('این حالت وقتی دیده می‌شود که اسلایدر «بازگشت به کارت اول» خاموش باشد و کاربر به ابتدا/انتهای لیست برسد.', 'almasara-widgets'),
            'content_classes' => 'elementor-descriptor',
        ]);
        $this->register_nav_state_controls('nav_disabled', '{{WRAPPER}} .amw-ps__btn.swiper-button-disabled', ['opacity' => 0.35]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

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
}
