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
 * ویجت «اسلایدر هیرو» — بنر اسلایدشو بالای صفحه (Swiper).
 *
 * هر اسلاید فقط تصویر + لینک است (تصویر موبایل اختیاری، خالی = همان تصویر
 * دسکتاپ). چندنمونه‌ای بودن با instantiate کردن Swiper روی هر عنصر پیدا‌شده
 * حل می‌شود (نه با ID دستی)، پس چند ویجت در یک صفحه بدون تداخل کار می‌کنند.
 */
class Hero_Slider extends Widget_Base {

    use Traits\Intro_Row; // برای گزینه‌های فلکس مشترک و رندر SVG اینلاین

    public function get_name(): string {
        return 'almasara-hero-slider';
    }

    public function get_title(): string {
        return __('اسلایدر هیرو الماسارا', 'almasara-widgets');
    }

    public function get_icon(): string {
        return 'eicon-slider-push';
    }

    public function get_categories(): array {
        return ['almasara'];
    }

    public function get_keywords(): array {
        return ['اسلایدر', 'بنر', 'اسلایدشو', 'slider', 'banner', 'swiper', 'الماسارا'];
    }

    public function get_style_depends(): array {
        return ['almasara-swiper', 'almasara-widgets'];
    }

    public function get_script_depends(): array {
        return ['almasara-swiper', 'almasara-hero-slider'];
    }

    /* =====================================================================
     * کنترل‌ها
     * =================================================================== */

    protected function register_controls(): void {
        $this->register_slides_content_controls();
        $this->register_behavior_content_controls();
        $this->register_navigation_content_controls();

        $this->register_panel_style_controls();
        $this->register_skeleton_style_controls();
        $this->register_slide_style_controls();
        $this->register_nav_arrows_style_controls();
        $this->register_controls_bar_style_controls();
        $this->register_pagination_style_controls();
    }

    /* ---------------- محتوا: اسلایدها ---------------- */

    private function register_slides_content_controls(): void {
        $this->start_controls_section('section_slides', [
            'label' => __('اسلایدها', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $repeater = new Repeater();

        $repeater->add_control('image', [
            'label'   => __('تصویر (دسکتاپ)', 'almasara-widgets'),
            'type'    => Controls_Manager::MEDIA,
            'default' => ['url' => \Elementor\Utils::get_placeholder_image_src()],
        ]);

        $repeater->add_control('image_mobile', [
            'label'       => __('تصویر موبایل', 'almasara-widgets'),
            'type'        => Controls_Manager::MEDIA,
            'description' => __('خالی = همان تصویر دسکتاپ در موبایل هم نمایش داده می‌شود.', 'almasara-widgets'),
        ]);

        $repeater->add_control('link', [
            'label'       => __('لینک', 'almasara-widgets'),
            'type'        => Controls_Manager::URL,
            'placeholder' => 'https://your-link.com',
            'dynamic'     => ['active' => true],
        ]);

        $this->add_control('slides', [
            'label'       => __('اسلایدها', 'almasara-widgets'),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $repeater->get_controls(),
            'title_field' => '{{{ image.url ? "اسلاید" : "اسلاید خالی" }}}',
            'default'     => [
                ['image' => ['url' => \Elementor\Utils::get_placeholder_image_src()]],
            ],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- محتوا: رفتار اسلایدر ---------------- */

    private function register_behavior_content_controls(): void {
        $this->start_controls_section('section_behavior', [
            'label' => __('رفتار اسلایدر', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('autoplay', [
            'label'   => __('پخش خودکار', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('autoplay_delay', [
            'label'     => __('تأخیر بین اسلایدها (میلی‌ثانیه)', 'almasara-widgets'),
            'type'      => Controls_Manager::NUMBER,
            'default'   => 3000,
            'min'       => 1000,
            'step'      => 100,
            'condition' => ['autoplay' => 'yes'],
        ]);

        $this->add_control('pause_on_interaction', [
            'label'       => __('توقف پخش خودکار بعد از تعامل کاربر', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'description' => __('خاموش = بعد از اینکه کاربر دستی اسلاید را عوض کرد، پخش خودکار باز هم ادامه پیدا می‌کند.', 'almasara-widgets'),
            'condition'   => ['autoplay' => 'yes'],
        ]);

        $this->add_control('heading_motion', [
            'label'     => __('حرکت', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_responsive_control('speed', [
            'label'          => __('سرعت گذار (میلی‌ثانیه)', 'almasara-widgets'),
            'type'           => Controls_Manager::NUMBER,
            'default'        => 1000,
            'tablet_default' => 800,
            'mobile_default' => 600,
            'min'            => 100,
            'step'           => 50,
        ]);

        $this->add_responsive_control('slides_per_view', [
            'label'   => __('تعداد اسلاید هم‌زمان', 'almasara-widgets'),
            'type'    => Controls_Manager::NUMBER,
            'default' => 1,
            'min'     => 1,
            'step'    => 0.1,
        ]);

        $this->add_responsive_control('space_between', [
            'label'   => __('فاصله بین اسلایدها (px)', 'almasara-widgets'),
            'type'    => Controls_Manager::NUMBER,
            'default' => 0,
            'min'     => 0,
        ]);

        $this->add_control('resistance_ratio', [
            'label'       => __('مقاومت در انتهای پیمایش', 'almasara-widgets'),
            'type'        => Controls_Manager::NUMBER,
            'default'     => 0,
            'min'         => 0,
            'max'         => 1,
            'step'        => 0.1,
            'description' => __('۰ = بدون کش‌آمدن؛ عدد بزرگ‌تر یعنی کشش لاستیکی بیشتر هنگام رسیدن به اولین/آخرین اسلاید.', 'almasara-widgets'),
        ]);

        $this->add_control('rewind', [
            'label'       => __('بازگشت به اسلاید اول', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => 'yes',
            'description' => __('بعد از آخرین اسلاید، به اولی برمی‌گردد (بدون لوپ واقعی و دوبله‌شدن اسلایدها).', 'almasara-widgets'),
        ]);

        $this->add_control('rtl', [
            'label'   => __('جهت راست‌به‌چپ', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => is_rtl() ? 'yes' : '',
        ]);

        $this->add_control('heading_parallax', [
            'label'     => __('پارالاکس', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('parallax', [
            'label'   => __('پارالاکس ملایم تصویر', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('parallax_amount', [
            'label'       => __('میزان جابه‌جایی', 'almasara-widgets'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '70%',
            'description' => __('مقدار data-swiper-parallax؛ درصد یا عدد پیکسل.', 'almasara-widgets'),
            'condition'   => ['parallax' => 'yes'],
        ]);

        $this->add_control('parallax_opacity', [
            'label'     => __('محو شدن تصویر هنگام گذار', 'almasara-widgets'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 0, 'max' => 1, 'step' => 0.05]],
            'default'   => ['size' => 0.2],
            'condition' => ['parallax' => 'yes'],
        ]);

        $this->add_control('heading_loading', [
            'label'     => __('بارگذاری', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('first_slide_priority', [
            'label'       => __('اولویت بارگذاری اسلاید اول (LCP)', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => 'yes',
            'description' => __('تصویر اولین اسلاید fetchpriority="high" و بدون lazy می‌گیرد؛ بقیه اسلایدها lazy لود می‌شوند.', 'almasara-widgets'),
        ]);

        $this->add_control('show_skeleton', [
            'label'       => __('اسکلتون در حال بارگذاری', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => 'yes',
            'description' => __('تا وقتی اسلایدر آماده نشده، یک پس‌زمینه شیمر روی جای آن نمایش داده می‌شود.', 'almasara-widgets'),
        ]);

        $this->end_controls_section();
    }

    /* ---------------- محتوا: ناوبری و پیجینیشن ---------------- */

    private function register_navigation_content_controls(): void {
        $this->start_controls_section('section_navigation', [
            'label' => __('ناوبری و پیجینیشن', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
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
            'description' => __('خالی = فلش پیش‌فرض. یک آیکون برای هر دو دکمه استفاده و برای «قبلی» ۱۸۰ درجه چرخانده می‌شود. SVG به‌صورت inline و رنگ‌پذیر رندر می‌شود.', 'almasara-widgets'),
            'condition'   => ['show_navigation' => 'yes'],
        ]);

        $this->add_control('show_pagination', [
            'label'     => __('نقطه‌های پیجینیشن', 'almasara-widgets'),
            'type'      => Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'separator' => 'before',
        ]);

        $this->add_control('pagination_clickable', [
            'label'     => __('کلیک‌پذیر بودن نقطه‌ها', 'almasara-widgets'),
            'type'      => Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'condition' => ['show_pagination' => 'yes'],
        ]);

        $this->add_control('heading_notch', [
            'label'     => __('برش تزئینی زیر نوار', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('show_notch', [
            'label'       => __('نمایش برش تزئینی', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => 'yes',
            'description' => __('شکل بریده گرد در دو طرف نوار کنترل‌ها، هم‌رنگ نوار — مثل دیزاین. خاموش‌کردن این گزینه، نوار را به‌صورت یک کادر ساده گردگوشه در می‌آورد.', 'almasara-widgets'),
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: پنل ---------------- */

    private function register_panel_style_controls(): void {
        $this->start_controls_section('section_style_panel', [
            'label' => __('پنل', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('panel_height', [
            'label'          => __('ارتفاع', 'almasara-widgets'),
            'type'           => Controls_Manager::SLIDER,
            'size_units'     => ['px', 'vh'],
            'range'          => ['px' => ['min' => 150, 'max' => 800]],
            'default'        => ['size' => 400, 'unit' => 'px'],
            'tablet_default' => ['size' => 360, 'unit' => 'px'],
            'mobile_default' => ['size' => 252, 'unit' => 'px'],
            'selectors'      => ['{{WRAPPER}} .amw-hero__wrap' => 'height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('panel_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'default'    => ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .amw-hero__wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;',
            ],
        ]);

        $this->add_control('panel_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#f5f5f5',
            'selectors' => ['{{WRAPPER}} .amw-hero__wrap' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'panel_shadow',
            'selector' => '{{WRAPPER}} .amw-hero__wrap',
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: اسکلتون ---------------- */

    private function register_skeleton_style_controls(): void {
        $this->start_controls_section('section_style_skeleton', [
            'label'     => __('اسکلتون لودینگ', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_skeleton' => 'yes'],
        ]);

        $this->add_control('skeleton_base_color', [
            'label'     => __('رنگ پایه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#f2f2f2',
            'selectors' => ['{{WRAPPER}} .amw-hero__skeleton' => '--amw-hero-skel-a: {{VALUE}};'],
        ]);

        $this->add_control('skeleton_highlight_color', [
            'label'     => __('رنگ درخشش متحرک', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#e2e2e2',
            'selectors' => ['{{WRAPPER}} .amw-hero__skeleton' => '--amw-hero-skel-b: {{VALUE}};'],
        ]);

        $this->add_control('skeleton_duration', [
            'label'     => __('سرعت انیمیشن (ثانیه)', 'almasara-widgets'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 0.5, 'max' => 5, 'step' => 0.1]],
            'default'   => ['size' => 1.6],
            'selectors' => ['{{WRAPPER}} .amw-hero__skeleton' => '--amw-hero-skel-dur: {{SIZE}}s;'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: تصویر اسلاید ---------------- */

    private function register_slide_style_controls(): void {
        $this->start_controls_section('section_style_slide', [
            'label' => __('تصویر اسلاید', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('img_object_fit', [
            'label'     => __('نوع جای‌گیری تصویر', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'cover',
            'options'   => [
                'cover'   => __('پر کردن قاب (cover)', 'almasara-widgets'),
                'contain' => __('کامل داخل قاب (contain)', 'almasara-widgets'),
            ],
            'selectors' => ['{{WRAPPER}} .amw-hero__img' => 'object-fit: {{VALUE}};'],
        ]);

        $this->add_control('img_transition', [
            'label'     => __('مدت گذار (میلی‌ثانیه)', 'almasara-widgets'),
            'type'      => Controls_Manager::NUMBER,
            'default'   => 1000,
            'min'       => 0,
            'selectors' => ['{{WRAPPER}} .amw-hero__img' => 'transition-duration: {{VALUE}}ms;'],
        ]);

        $this->add_control('heading_inactive', [
            'label'     => __('اسلایدهای مجاور (قبل/بعد)', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('dim_inactive', [
            'label'       => __('کم‌رنگ کردن اسلاید مجاور', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => 'yes',
            'description' => __('وقتی بیش از یک اسلاید هم‌زمان دیده می‌شود، اسلایدهای غیرفعال کم‌رنگ‌تر می‌شوند.', 'almasara-widgets'),
        ]);

        $this->add_control('inactive_opacity', [
            'label'     => __('میزان شفافیت', 'almasara-widgets'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 0, 'max' => 1, 'step' => 0.05]],
            'default'   => ['size' => 0.2],
            'condition' => ['dim_inactive' => 'yes'],
            'selectors' => [
                '{{WRAPPER}} .swiper-slide-prev .amw-hero__img, {{WRAPPER}} .swiper-slide-next .amw-hero__img' => 'opacity: {{SIZE}} !important;',
            ],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: دکمه‌های قبلی/بعدی ---------------- */

    private function register_nav_arrows_style_controls(): void {
        $this->start_controls_section('section_style_nav_arrows', [
            'label'     => __('دکمه‌های قبلی/بعدی', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_navigation' => 'yes'],
        ]);

        $this->add_responsive_control('nav_size', [
            'label'      => __('اندازه آیکون', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 12, 'max' => 60]],
            'default'    => ['size' => 24, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-hero__btn' => '--amw-hero-nav-size: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('nav_padding', [
            'label'      => __('پدینگ دکمه', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors'  => [
                '{{WRAPPER}} .amw-hero__btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_control('nav_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ['{{WRAPPER}} .amw-hero__btn' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('heading_nav_normal', [
            'label'     => __('حالت عادی', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('nav_color', [
            'label'     => __('رنگ آیکون', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#002864',
            'selectors' => ['{{WRAPPER}} .amw-hero__btn' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('nav_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-hero__btn' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('heading_nav_hover', [
            'label'     => __('حالت هاور', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('nav_color_hover', [
            'label'     => __('رنگ آیکون', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-hero__btn:hover' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('nav_bg_hover', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-hero__btn:hover' => 'background-color: {{VALUE}};'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: نوار کنترل‌ها ---------------- */

    private function register_controls_bar_style_controls(): void {
        $this->start_controls_section('section_style_bar', [
            'label' => __('نوار کنترل‌ها', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('bar_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => ['{{WRAPPER}} .amw-hero__controls' => '--amw-hero-bar-bg: {{VALUE}};'],
        ]);

        $this->add_responsive_control('bar_gap', [
            'label'      => __('فاصله بین آیتم‌ها', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 50]],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-hero__controls' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('bar_padding', [
            'label'      => __('پدینگ افقی', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'default'    => ['size' => 20, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-hero__controls' => 'padding-inline: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('bar_height', [
            'label'      => __('ارتفاع', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 30, 'max' => 90]],
            'default'    => ['size' => 45, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-hero__controls' => 'height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('bar_radius_top', [
            'label'      => __('رادیوس بالا', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 20, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-hero__controls' => '--amw-hero-bar-radius: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('bar_offset_bottom', [
            'label'      => __('فاصله از پایین پنل', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => -20, 'max' => 60]],
            'default'    => ['size' => 0, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-hero__controls' => 'bottom: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'bar_shadow',
            'selector' => '{{WRAPPER}} .amw-hero__controls',
        ]);

        $this->add_control('heading_notch_style', [
            'label'     => __('برش تزئینی', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['show_notch' => 'yes'],
        ]);

        $this->add_control('notch_color', [
            'label'       => __('رنگ برش', 'almasara-widgets'),
            'type'        => Controls_Manager::COLOR,
            'description' => __('خالی = هم‌رنگ پس‌زمینه نوار.', 'almasara-widgets'),
            'selectors'   => ['{{WRAPPER}} .amw-hero__controls' => '--amw-hero-notch-color: {{VALUE}};'],
            'condition'   => ['show_notch' => 'yes'],
        ]);

        $this->add_control('notch_size', [
            'label'      => __('اندازه برش', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 8, 'max' => 60]],
            'default'    => ['size' => 26, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-hero__controls' => '--amw-hero-notch-size: {{SIZE}}{{UNIT}};'],
            'condition'  => ['show_notch' => 'yes'],
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
            'selectors'  => ['{{WRAPPER}} .amw-hero__pagination' => '--amw-hero-dot: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('dot_gap', [
            'label'      => __('فاصله بین نقطه‌ها', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'default'    => ['size' => 6, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-hero__pagination' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('dot_color', [
            'label'     => __('رنگ نقطه غیرفعال', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#cad2de',
            'selectors' => ['{{WRAPPER}} .swiper-pagination-bullet' => 'background: {{VALUE}};'],
        ]);

        $this->add_control('dot_color_active', [
            'label'     => __('رنگ نقطه فعال', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#002864',
            'selectors' => ['{{WRAPPER}} .swiper-pagination-bullet-active' => 'background: {{VALUE}};'],
        ]);

        $this->add_control('dot_width_active', [
            'label'      => __('عرض نقطه فعال', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 6, 'max' => 60]],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .swiper-pagination-bullet-active' => 'width: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('dot_radius_active', [
            'label'      => __('رادیوس نقطه فعال', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 20]],
            'default'    => ['size' => 4, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .swiper-pagination-bullet-active' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);

        $this->end_controls_section();
    }

    /* =====================================================================
     * رندر
     * =================================================================== */

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $slides   = array_filter((array) ($settings['slides'] ?? []), static function ($slide) {
            return !empty($slide['image']['url']);
        });

        if (empty($slides)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="amw-hero__notice">' . esc_html__('حداقل یک اسلاید با تصویر اضافه کنید.', 'almasara-widgets') . '</div>';
            }
            return;
        }

        // المنتور دسکتاپ‌محور است (پایه = دسکتاپ، override برای کوچک‌تر)؛ Swiper
        // موبایل‌محور است (پایه = کوچک‌ترین، breakpoints برای بزرگ‌تر). این تابع
        // مقادیر دسکتاپ/تبلت/موبایل المنتور را به ساختار breakpoints سوایپر می‌برد.
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
            'autoplay'             => 'yes' === $settings['autoplay'],
            'delay'                => max(1000, (int) $settings['autoplay_delay']),
            'disableOnInteraction' => 'yes' === ($settings['pause_on_interaction'] ?? ''),
            'speed'                => $speed['mobile'],
            'slidesPerView'        => $spv['mobile'],
            'spaceBetween'         => $space['mobile'],
            'breakpoints'          => [
                768  => ['speed' => $speed['tablet'], 'slidesPerView' => $spv['tablet'], 'spaceBetween' => $space['tablet']],
                1025 => ['speed' => $speed['desktop'], 'slidesPerView' => $spv['desktop'], 'spaceBetween' => $space['desktop']],
            ],
            'resistanceRatio'      => (float) $settings['resistance_ratio'],
            'rewind'               => 'yes' === $settings['rewind'],
            'rtl'                  => 'yes' === $settings['rtl'],
            'parallax'             => 'yes' === $settings['parallax'],
            'navigation'           => 'yes' === $settings['show_navigation'],
            'pagination'           => 'yes' === $settings['show_pagination'],
            'paginationClickable'  => 'yes' === ($settings['pagination_clickable'] ?? ''),
        ];

        printf('<div class="amw-hero" data-cfg="%s">', esc_attr(wp_json_encode($cfg)));

        echo '<div class="amw-hero__wrap">';

        if ('yes' === $settings['show_skeleton']) {
            echo '<div class="amw-hero__skeleton"></div>';
        }

        echo '<div class="swiper amw-hero__swiper">';
        echo '<div class="swiper-wrapper">';

        $index = 0;
        foreach ($slides as $slide) {
            $this->render_slide($slide, $settings, 0 === $index);
            $index++;
        }

        echo '</div>'; // .swiper-wrapper

        $this->render_controls_bar($settings);

        echo '</div>'; // .swiper
        echo '</div>'; // .amw-hero__wrap
        echo '</div>'; // .amw-hero
    }

    /** رندر یک اسلاید: picture (تصویر موبایل اختیاری) داخل لینک یا div */
    private function render_slide(array $slide, array $settings, bool $is_first): void {
        $desktop_url = $slide['image']['url'] ?? '';
        $mobile_url  = $slide['image_mobile']['url'] ?? '';
        $alt         = !empty($slide['image']['id'])
            ? get_post_meta((int) $slide['image']['id'], '_wp_attachment_image_alt', true)
            : '';

        $priority = 'yes' === ($settings['first_slide_priority'] ?? '') && $is_first;
        $img_attrs = sprintf(
            'class="amw-hero__img" alt="%s" loading="%s"%s',
            esc_attr((string) $alt),
            $priority ? 'eager' : 'lazy',
            $priority ? ' fetchpriority="high"' : ''
        );

        if ('yes' === ($settings['parallax'] ?? '')) {
            $img_attrs .= sprintf(
                ' data-swiper-parallax="%s" data-swiper-parallax-opacity="%s"',
                esc_attr($settings['parallax_amount'] ?: '70%'),
                esc_attr((string) ($settings['parallax_opacity']['size'] ?? 0.2))
            );
        }

        $has_link = !empty($slide['link']['url']);

        echo '<div class="swiper-slide">';

        if ($has_link) {
            $target = !empty($slide['link']['is_external']) ? ' target="_blank"' : '';
            $nofollow = !empty($slide['link']['nofollow']) ? ' rel="nofollow"' : '';
            printf('<a class="amw-hero__link" href="%s"%s%s>', esc_url($slide['link']['url']), $target, $nofollow);
        } else {
            echo '<div class="amw-hero__link">';
        }

        if ($mobile_url) {
            printf('<picture>');
            printf('<source media="(max-width: 767px)" srcset="%s">', esc_url($mobile_url));
            printf('<img src="%s" %s>', esc_url($desktop_url), $img_attrs); // phpcs:ignore
            echo '</picture>';
        } else {
            printf('<img src="%s" %s>', esc_url($desktop_url), $img_attrs); // phpcs:ignore
        }

        echo $has_link ? '</a>' : '</div>';
        echo '</div>'; // .swiper-slide
    }

    /** نوار کنترل‌ها: دکمه قبلی، پیجینیشن، دکمه بعدی */
    private function render_controls_bar(array $settings): void {
        $show_nav   = 'yes' === $settings['show_navigation'];
        $show_pager = 'yes' === $settings['show_pagination'];

        if (!$show_nav && !$show_pager) {
            return;
        }

        $classes = 'amw-hero__controls';
        if ('yes' !== ($settings['show_notch'] ?? '')) {
            $classes .= ' amw-hero__controls--no-notch';
        }

        printf('<div class="%s">', esc_attr($classes));

        if ($show_nav) {
            printf(
                '<button type="button" class="amw-hero__btn amw-hero__btn--prev" aria-label="%s">',
                esc_attr__('اسلاید قبلی', 'almasara-widgets')
            );
            $this->render_nav_icon($settings);
            echo '</button>';
        }

        if ($show_pager) {
            echo '<div class="swiper-pagination amw-hero__pagination"></div>';
        }

        if ($show_nav) {
            printf(
                '<button type="button" class="amw-hero__btn amw-hero__btn--next" aria-label="%s">',
                esc_attr__('اسلاید بعدی', 'almasara-widgets')
            );
            $this->render_nav_icon($settings);
            echo '</button>';
        }

        echo '</div>'; // .amw-hero__controls
    }

    /** آیکون فلش سفارشی یا شورون پیش‌فرض inline (رنگ‌پذیر با currentColor) */
    private function render_nav_icon(array $settings): void {
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

        echo '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>';
    }
}
