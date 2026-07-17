<?php
namespace Almasara_Widgets\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Utils;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ویجت «گالری محصول»
 *
 * تصویر شاخص بزرگ + تامبنیل‌های مربعی گالری (بدون اسلایدر و فلش).
 * تامبنیل آخر در صورت وجود تصاویر بیشتر، اورلی رنگی و سه‌نقطه می‌گیرد.
 * کلیک روی هر تصویر مودال گالری را باز می‌کند؛ تصاویر کامل فقط همان لحظه
 * و به‌صورت ایجکسی (REST) لود می‌شوند.
 */
class Product_Gallery extends Widget_Base {

    use Traits\Intro_Row; // فقط برای resolve_product

    public function get_name(): string {
        return 'almasara-product-gallery';
    }

    public function get_title(): string {
        return __('گالری محصول الماسارا', 'almasara-widgets');
    }

    public function get_icon(): string {
        return 'eicon-product-images';
    }

    public function get_categories(): array {
        return ['almasara', 'woocommerce-elements'];
    }

    public function get_keywords(): array {
        return ['گالری', 'تصویر', 'محصول', 'gallery', 'product', 'lightbox', 'الماسارا'];
    }

    public function get_style_depends(): array {
        return ['almasara-widgets'];
    }

    public function get_script_depends(): array {
        return ['almasara-gallery'];
    }

    /* ---------------------------------------------------------------------
     * کنترل‌ها
     * ------------------------------------------------------------------- */

    protected function register_controls(): void {
        $this->register_images_content_controls();
        $this->register_modal_content_controls();

        $this->register_main_image_style_controls();
        $this->register_thumbs_style_controls();
        $this->register_modal_style_controls();
    }

    /** محتوا — تصاویر */
    private function register_images_content_controls(): void {
        $this->start_controls_section('section_images', [
            'label' => __('تصاویر', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('thumbs_count', [
            'label'       => __('تعداد تامبنیل‌ها (دسکتاپ)', 'almasara-widgets'),
            'type'        => Controls_Manager::NUMBER,
            'min'         => 1,
            'max'         => 10,
            'default'     => 4,
            'description' => __('تعداد تامبنیل‌های قابل نمایش کنار تصویر شاخص در دسکتاپ. تامبنیل‌ها همیشه مربعی‌اند و اسلاید نمی‌شوند. اگر تصاویر بیشتری در گالری باشد، روی تامبنیل آخر اورلی می‌نشیند.', 'almasara-widgets'),
            'selectors'   => [
                '{{WRAPPER}} .amw-pg' => '--amw-pg-thumbs: {{VALUE}};',
            ],
        ]);

        $this->add_control('mobile_layout', [
            'label'        => __('چیدمان موبایل', 'almasara-widgets'),
            'type'         => Controls_Manager::SELECT,
            'default'      => 'alternating',
            'options'      => [
                'alternating' => __('نوار اسکرولی یکی‌درمیون (۱ بزرگ، ۲ کوچک)', 'almasara-widgets'),
                'match'       => __('همان چیدمان دسکتاپ', 'almasara-widgets'),
            ],
            'description'  => __('نوار افقی قابل سوایپ: تصویر شاخص تمام‌ارتفاع و مربع، بعد ستونی از ۲ مربع کوچک، بعد تصویر بزرگ بعدی و همین‌طور یکی‌درمیون. تمام تصاویر گالری نمایش داده می‌شوند.', 'almasara-widgets'),
        ]);

        $this->add_responsive_control('mobile_height', [
            'label'       => __('ارتفاع نوار گالری موبایل', 'almasara-widgets'),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => ['px', 'vw'],
            'range'       => [
                'px' => ['min' => 150, 'max' => 600],
                'vw' => ['min' => 30, 'max' => 100],
            ],
            'default'     => ['size' => 85, 'unit' => 'vw'],
            'description' => __('عرض تصویر بزرگ = همین ارتفاع (مربع ۱:۱)؛ ارتفاع و عرض دو مربع کوچک هم از همین مقدار منهای گپ محاسبه می‌شود.', 'almasara-widgets'),
            'selectors'   => [
                '{{WRAPPER}} .amw-pg' => '--amw-pg-mh: {{SIZE}}{{UNIT}};',
            ],
            'condition'   => ['mobile_layout' => 'alternating'],
        ]);

        $this->add_control('show_mobile_counter', [
            'label'       => __('نشانگر پایین گالری موبایل', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => 'yes',
            'description' => __('پیل شامل نوار پیشرفت اسکرول + تعداد تصاویر، پایین نوار گالری — مثل دیزاین.', 'almasara-widgets'),
            'condition'   => ['mobile_layout' => 'alternating'],
        ]);

        $this->add_control('show_dots', [
            'label'   => __('نمایش سه‌نقطه روی تامبنیل آخر', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('show_more_count', [
            'label'       => __('نمایش تعداد تصاویر باقی‌مانده (+N)', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => '',
            'description' => __('بجی مثل «+18» روی تامبنیل آخر.', 'almasara-widgets'),
        ]);

        $this->end_controls_section();
    }

    /** محتوا — مودال */
    private function register_modal_content_controls(): void {
        $this->start_controls_section('section_modal', [
            'label' => __('مودال گالری', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('modal_enable', [
            'label'       => __('باز شدن مودال با کلیک', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => 'yes',
            'description' => __('تصاویر کامل فقط بعد از باز شدن مودال و به‌صورت ایجکسی لود می‌شوند.', 'almasara-widgets'),
        ]);

        $this->add_control('show_tab', [
            'label'     => __('نمایش تب بالای مودال', 'almasara-widgets'),
            'type'      => Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'condition' => ['modal_enable' => 'yes'],
        ]);

        $this->add_control('tab_label', [
            'label'     => __('متن تب', 'almasara-widgets'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('رسمی', 'almasara-widgets'),
            'condition' => [
                'modal_enable' => 'yes',
                'show_tab'     => 'yes',
            ],
        ]);

        $this->add_control('modal_animation', [
            'label'       => __('انیمیشن ورود/خروج', 'almasara-widgets'),
            'type'        => Controls_Manager::SELECT,
            'default'     => 'fade-scale',
            'options'     => [
                'fade'       => __('محو ساده (fade)', 'almasara-widgets'),
                'fade-scale' => __('محو + بزرگ‌نمایی نرم (پیشنهادی)', 'almasara-widgets'),
                'slide-up'   => __('اسلاید از پایین', 'almasara-widgets'),
                'zoom'       => __('بزرگ‌نمایی از کوچک', 'almasara-widgets'),
            ],
            'condition'   => ['modal_enable' => 'yes'],
        ]);

        $this->add_control('modal_duration', [
            'label'      => __('مدت انیمیشن (میلی‌ثانیه)', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'range'      => ['px' => ['min' => 100, 'max' => 800]],
            'default'    => ['size' => 260],
            'selectors'  => [
                '{{WRAPPER}} .amw-pg-modal' => '--amw-pg-modal-dur: {{SIZE}}ms;',
            ],
            'condition'  => ['modal_enable' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    /** استایل — تصویر شاخص */
    private function register_main_image_style_controls(): void {
        $this->start_controls_section('section_style_main', [
            'label' => __('تصویر شاخص', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('main_ratio', [
            'label'     => __('نسبت ابعاد', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => '1 / 1',
            'options'   => [
                'auto'   => __('خودکار (ابعاد اصلی)', 'almasara-widgets'),
                '1 / 1'  => __('مربع (۱:۱)', 'almasara-widgets'),
                '4 / 3'  => '4:3',
                '3 / 4'  => '3:4',
                '16 / 9' => '16:9',
            ],
            'selectors' => [
                '{{WRAPPER}} .amw-pg__main' => 'aspect-ratio: {{VALUE}};',
            ],
        ]);

        $this->add_control('main_fit', [
            'label'     => __('نحوه جای‌گیری تصویر', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'cover',
            'options'   => [
                'cover'   => __('کاور (کات از وسط)', 'almasara-widgets'),
                'contain' => __('کامل داخل کادر', 'almasara-widgets'),
                'fill'    => __('کشیده', 'almasara-widgets'),
            ],
            'selectors' => [
                '{{WRAPPER}} .amw-pg__main img' => 'object-fit: {{VALUE}};',
            ],
            'condition' => ['main_ratio!' => 'auto'],
        ]);

        $this->add_control('main_position', [
            'label'     => __('نقطه برش', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'center center',
            'options'   => [
                'center center' => __('وسط', 'almasara-widgets'),
                'top center'    => __('بالا', 'almasara-widgets'),
                'bottom center' => __('پایین', 'almasara-widgets'),
                'center right'  => __('راست', 'almasara-widgets'),
                'center left'   => __('چپ', 'almasara-widgets'),
            ],
            'selectors' => [
                '{{WRAPPER}} .amw-pg__main img' => 'object-position: {{VALUE}};',
            ],
            'condition' => [
                'main_ratio!' => 'auto',
                'main_fit'    => 'cover',
            ],
        ]);

        $this->add_responsive_control('main_spacing', [
            'label'      => __('فاصله تا تامبنیل‌ها (سطر)', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 100]],
            'selectors'  => [
                '{{WRAPPER}} .amw-pg' => '--amw-pg-rgap: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'main_border',
            'label'    => __('حاشیه', 'almasara-widgets'),
            'selector' => '{{WRAPPER}} .amw-pg__main',
        ]);

        $this->add_responsive_control('main_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .amw-pg__main' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;',
            ],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'main_shadow',
            'label'    => __('سایه', 'almasara-widgets'),
            'selector' => '{{WRAPPER}} .amw-pg__main',
        ]);

        $this->add_control('main_hover_opacity', [
            'label'     => __('شفافیت در هاور', 'almasara-widgets'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 0.1, 'max' => 1, 'step' => 0.05]],
            'selectors' => [
                '{{WRAPPER}} .amw-pg__main:hover img' => 'opacity: {{SIZE}};',
            ],
        ]);

        $this->add_control('main_hover_scale', [
            'label'     => __('بزرگ‌نمایی در هاور', 'almasara-widgets'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 1, 'max' => 1.5, 'step' => 0.01]],
            'selectors' => [
                '{{WRAPPER}} .amw-pg__main:hover img' => 'transform: scale({{SIZE}});',
            ],
        ]);

        $this->end_controls_section();
    }

    /** استایل — تامبنیل‌ها */
    private function register_thumbs_style_controls(): void {
        $this->start_controls_section('section_style_thumbs', [
            'label' => __('تامبنیل‌ها', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('thumbs_gap', [
            'label'      => __('فاصله بین تامبنیل‌ها (ستون)', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .amw-pg' => '--amw-pg-cgap: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('thumb_fit', [
            'label'     => __('نحوه جای‌گیری تصویر', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'cover',
            'options'   => [
                'cover'   => __('کاور (کات از وسط)', 'almasara-widgets'),
                'contain' => __('کامل داخل کارت', 'almasara-widgets'),
                'fill'    => __('کشیده', 'almasara-widgets'),
            ],
            'selectors' => [
                '{{WRAPPER}} .amw-pg__thumb img' => 'object-fit: {{VALUE}};',
            ],
        ]);

        $this->add_control('thumb_bg', [
            'label'       => __('رنگ پس‌زمینه کارت', 'almasara-widgets'),
            'type'        => Controls_Manager::COLOR,
            'description' => __('برای حالت «کامل داخل کارت» یا وقتی پدینگ می‌دهید دیده می‌شود.', 'almasara-widgets'),
            'selectors'   => [
                '{{WRAPPER}} .amw-pg__thumb' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('thumb_padding', [
            'label'       => __('پدینگ داخلی کارت', 'almasara-widgets'),
            'type'        => Controls_Manager::DIMENSIONS,
            'size_units'  => ['px', 'em', '%'],
            'description' => __('فاصله تصویر از لبه‌های کارت — برای ظاهر کارتی مثل دیزین.', 'almasara-widgets'),
            'selectors'   => [
                '{{WRAPPER}} .amw-pg__thumb' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_control('thumb_position', [
            'label'     => __('نقطه برش کاور', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'center center',
            'options'   => [
                'center center' => __('وسط', 'almasara-widgets'),
                'top center'    => __('بالا', 'almasara-widgets'),
                'bottom center' => __('پایین', 'almasara-widgets'),
                'center right'  => __('راست', 'almasara-widgets'),
                'center left'   => __('چپ', 'almasara-widgets'),
            ],
            'selectors' => [
                '{{WRAPPER}} .amw-pg__thumb img' => 'object-position: {{VALUE}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'thumb_border',
            'label'    => __('حاشیه', 'almasara-widgets'),
            'selector' => '{{WRAPPER}} .amw-pg__thumb',
        ]);

        $this->add_responsive_control('thumb_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .amw-pg__thumb' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;',
            ],
        ]);

        $this->add_control('thumb_hover_opacity', [
            'label'     => __('شفافیت در هاور', 'almasara-widgets'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 0.1, 'max' => 1, 'step' => 0.05]],
            'selectors' => [
                '{{WRAPPER}} .amw-pg__thumb:hover img' => 'opacity: {{SIZE}};',
            ],
        ]);

        $this->add_control('thumb_hover_border_color', [
            'label'     => __('رنگ حاشیه در هاور', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pg__thumb:hover' => 'border-color: {{VALUE}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'thumb_shadow',
            'label'    => __('سایه کارت', 'almasara-widgets'),
            'selector' => '{{WRAPPER}} .amw-pg__thumb',
        ]);

        $this->add_control('heading_overlay', [
            'label'     => __('اورلی تصاویر بیشتر', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('overlay_color', [
            'label'     => __('رنگ اورلی', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => 'rgba(16, 24, 40, 0.6)',
            'selectors' => [
                '{{WRAPPER}} .amw-pg__more-overlay' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_control('dots_color', [
            'label'     => __('رنگ سه‌نقطه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [
                '{{WRAPPER}} .amw-pg__dots span' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('dots_size', [
            'label'      => __('اندازه سه‌نقطه', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 2, 'max' => 20]],
            'default'    => ['size' => 6, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .amw-pg__dots span' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ],
            'condition'  => ['show_dots' => 'yes'],
        ]);

        $this->add_control('dots_direction', [
            'label'     => __('جهت سه‌نقطه', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'row',
            'options'   => [
                'row'    => __('افقی', 'almasara-widgets'),
                'column' => __('عمودی', 'almasara-widgets'),
            ],
            'selectors' => [
                '{{WRAPPER}} .amw-pg__dots' => 'flex-direction: {{VALUE}};',
            ],
            'condition' => ['show_dots' => 'yes'],
        ]);

        $this->add_responsive_control('dots_gap', [
            'label'      => __('فاصله نقطه‌ها', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 1, 'max' => 24]],
            'selectors'  => [
                '{{WRAPPER}} .amw-pg__dots' => 'gap: {{SIZE}}{{UNIT}};',
            ],
            'condition'  => ['show_dots' => 'yes'],
        ]);

        $this->add_responsive_control('more_blur', [
            'label'       => __('بلور تصویر آخر', 'almasara-widgets'),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => ['px'],
            'range'       => ['px' => ['min' => 0, 'max' => 20]],
            'description' => __('محو کردن تصویر تامبنیل «تصاویر بیشتر» — مثل دیزاین دوم.', 'almasara-widgets'),
            'selectors'   => [
                '{{WRAPPER}} .amw-pg__thumb--more img' => 'filter: blur({{SIZE}}{{UNIT}});',
            ],
        ]);

        // ---------------- بج +N ----------------
        $this->add_control('heading_more_count', [
            'label'     => __('بج تعداد (+N)', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['show_more_count' => 'yes'],
        ]);

        $this->add_control('more_count_position', [
            'label'     => __('موقعیت', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'top-start',
            'options'   => [
                'top-start'    => __('بالا - ابتدا', 'almasara-widgets'),
                'top-end'      => __('بالا - انتها', 'almasara-widgets'),
                'bottom-start' => __('پایین - ابتدا', 'almasara-widgets'),
                'bottom-end'   => __('پایین - انتها', 'almasara-widgets'),
                'center'       => __('وسط', 'almasara-widgets'),
            ],
            'condition' => ['show_more_count' => 'yes'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'      => 'more_count_typography',
            'selector'  => '{{WRAPPER}} .amw-pg__more-count',
            'condition' => ['show_more_count' => 'yes'],
        ]);

        $this->add_control('more_count_color', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pg__more-count' => 'color: {{VALUE}};',
            ],
            'condition' => ['show_more_count' => 'yes'],
        ]);

        $this->add_control('more_count_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pg__more-count' => 'background-color: {{VALUE}};',
            ],
            'condition' => ['show_more_count' => 'yes'],
        ]);

        $this->add_responsive_control('more_count_padding', [
            'label'      => __('پدینگ', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .amw-pg__more-count' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
            'condition'  => ['show_more_count' => 'yes'],
        ]);

        $this->add_responsive_control('more_count_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .amw-pg__more-count' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
            'condition'  => ['show_more_count' => 'yes'],
        ]);

        // ---------------- نشانگر موبایل ----------------
        $this->add_control('heading_counter', [
            'label'     => __('نشانگر گالری موبایل', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['show_mobile_counter' => 'yes', 'mobile_layout' => 'alternating'],
        ]);

        $this->add_control('counter_bg', [
            'label'     => __('رنگ پس‌زمینه پیل', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pg__counter' => 'background-color: {{VALUE}};',
            ],
            'condition' => ['show_mobile_counter' => 'yes', 'mobile_layout' => 'alternating'],
        ]);

        $this->add_control('counter_color', [
            'label'     => __('رنگ متن و آیکون', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pg__counter' => 'color: {{VALUE}};',
            ],
            'condition' => ['show_mobile_counter' => 'yes', 'mobile_layout' => 'alternating'],
        ]);

        $this->add_control('counter_track_color', [
            'label'     => __('رنگ زمینه نوار پیشرفت', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pg__counter-bar' => 'background-color: {{VALUE}};',
            ],
            'condition' => ['show_mobile_counter' => 'yes', 'mobile_layout' => 'alternating'],
        ]);

        $this->add_control('counter_fill_color', [
            'label'     => __('رنگ نوار پیشرفت', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pg__counter-fill' => 'background-color: {{VALUE}};',
            ],
            'condition' => ['show_mobile_counter' => 'yes', 'mobile_layout' => 'alternating'],
        ]);

        $this->end_controls_section();
    }

    /** استایل — مودال */
    private function register_modal_style_controls(): void {
        $this->start_controls_section('section_style_modal', [
            'label'     => __('مودال', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['modal_enable' => 'yes'],
        ]);

        $this->add_control('modal_backdrop', [
            'label'     => __('رنگ پس‌زمینه مودال', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => 'rgba(255, 255, 255, 0.98)',
            'selectors' => [
                '{{WRAPPER}} .amw-pg-modal' => 'background-color: {{VALUE}};',
            ],
        ]);

        // ---------------- تب بالا ----------------
        $this->add_control('heading_modal_tab', [
            'label'     => __('تب بالا', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['show_tab' => 'yes'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'      => 'modal_tab_typography',
            'selector'  => '{{WRAPPER}} .amw-pg-modal__tab',
            'condition' => ['show_tab' => 'yes'],
        ]);

        $this->add_control('modal_tab_color', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pg-modal__tab' => 'color: {{VALUE}};',
            ],
            'condition' => ['show_tab' => 'yes'],
        ]);

        $this->add_control('modal_tab_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pg-modal__tab' => 'background-color: {{VALUE}};',
            ],
            'condition' => ['show_tab' => 'yes'],
        ]);

        // ---------------- دکمه بستن ----------------
        $this->add_control('heading_modal_close', [
            'label'     => __('دکمه بستن', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_responsive_control('close_size', [
            'label'      => __('اندازه', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 16, 'max' => 80]],
            'selectors'  => [
                '{{WRAPPER}} .amw-pg-modal__close' => 'font-size: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('close_color', [
            'label'     => __('رنگ', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pg-modal__close' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('close_color_hover', [
            'label'     => __('رنگ در هاور', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pg-modal__close:hover' => 'color: {{VALUE}};',
            ],
        ]);

        // ---------------- فلش‌های مودال ----------------
        $this->add_control('heading_modal_nav', [
            'label'     => __('فلش‌های قبلی/بعدی', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_responsive_control('nav_size', [
            'label'      => __('اندازه دکمه', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 24, 'max' => 96]],
            'selectors'  => [
                '{{WRAPPER}} .amw-pg-modal__nav' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('nav_color', [
            'label'     => __('رنگ فلش', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pg-modal__nav' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('nav_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pg-modal__nav' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_control('nav_color_hover', [
            'label'     => __('رنگ فلش در هاور', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pg-modal__nav:hover' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('nav_bg_hover', [
            'label'     => __('رنگ پس‌زمینه در هاور', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-pg-modal__nav:hover' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('nav_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => [
                'px' => ['min' => 0, 'max' => 50],
                '%'  => ['min' => 0, 'max' => 50],
            ],
            'selectors'  => [
                '{{WRAPPER}} .amw-pg-modal__nav' => 'border-radius: {{SIZE}}{{UNIT}};',
            ],
        ]);

        // ---------------- تصویر مودال ----------------
        $this->add_control('heading_modal_image', [
            'label'     => __('تصویر بزرگ', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_responsive_control('modal_img_height', [
            'label'      => __('حداکثر ارتفاع (نسبت به صفحه)', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['vh'],
            'range'      => ['vh' => ['min' => 30, 'max' => 95]],
            'default'    => ['size' => 72, 'unit' => 'vh'],
            'selectors'  => [
                '{{WRAPPER}} .amw-pg-modal__img' => 'max-height: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('modal_img_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .amw-pg-modal__img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        // ---------------- نوار تامبنیل مودال ----------------
        $this->add_control('heading_modal_strip', [
            'label'     => __('نوار تامبنیل پایین', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_responsive_control('strip_size', [
            'label'      => __('اندازه تامبنیل', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 32, 'max' => 160]],
            'default'    => ['size' => 64, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .amw-pg-modal__strip img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('strip_gap', [
            'label'      => __('فاصله', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => [
                '{{WRAPPER}} .amw-pg-modal__strip' => 'gap: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('strip_radius', [
            'label'      => __('رادیوس تامبنیل', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => [
                'px' => ['min' => 0, 'max' => 40],
                '%'  => ['min' => 0, 'max' => 50],
            ],
            'selectors'  => [
                '{{WRAPPER}} .amw-pg-modal__strip img' => 'border-radius: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('strip_active_color', [
            'label'     => __('رنگ کادر تامبنیل فعال', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#1a2b4a',
            'selectors' => [
                '{{WRAPPER}} .amw-pg-modal__strip .is-active img' => 'border-color: {{VALUE}};',
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

        $is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();

        if (!$product) {
            if ($is_editor) {
                echo '<div class="amw-paw__notice">' . esc_html__('محصولی برای پیش‌نمایش پیدا نشد. این ویجت را در قالب صفحه محصول استفاده کنید.', 'almasara-widgets') . '</div>';
            }
            return;
        }

        $main_id     = (int) $product->get_image_id();
        $gallery_ids = array_map('intval', $product->get_gallery_image_ids());

        if (!$main_id && empty($gallery_ids)) {
            if ($is_editor) {
                echo '<div class="amw-paw__notice">' . esc_html__('این محصول تصویر شاخص یا گالری ندارد.', 'almasara-widgets') . '</div>';
            }
            return;
        }

        // اگر تصویر شاخص نبود، اولین تصویر گالری جایش را بگیرد
        if (!$main_id) {
            $main_id = array_shift($gallery_ids);
        }

        $count     = max(1, (int) ($settings['thumbs_count'] ?? 4));
        $remaining = max(0, count($gallery_ids) - $count);
        $total     = 1 + count($gallery_ids);

        $modal_enabled  = 'yes' === $settings['modal_enable'];
        $mobile_layout  = (string) ($settings['mobile_layout'] ?? 'alternating');
        $anim           = (string) ($settings['modal_animation'] ?? 'fade-scale');
        $anim_whitelist = ['fade', 'fade-scale', 'slide-up', 'zoom'];
        if (!in_array($anim, $anim_whitelist, true)) {
            $anim = 'fade-scale';
        }
        $trigger_tag = $modal_enabled ? 'button' : 'div';

        $this->add_render_attribute('wrapper', 'class', [
            'amw-pg',
            'amw-pg--mobile-' . ($mobile_layout === 'match' ? 'match' : 'alt'),
        ]);
        if ($modal_enabled) {
            /*
             * پارامتر v از زمان آخرین ویرایش محصول ساخته می‌شود؛ با هر آپدیت
             * محصول URL عوض و کش مرورگر/CDN خودکار باطل می‌شود (cache-busting).
             */
            $modified = $product->get_date_modified();
            $endpoint = add_query_arg(
                'v',
                $modified ? $modified->getTimestamp() : 0,
                rest_url('almasara/v1/product-gallery/' . $product->get_id())
            );

            $this->add_render_attribute('wrapper', [
                'data-endpoint' => esc_url_raw($endpoint),
                'data-total'    => (string) $total,
            ]);
        }

        ?>
        <div <?php $this->print_render_attribute_string('wrapper'); ?>>

            <div class="amw-pg__strip">

            <<?php echo $trigger_tag; // phpcs:ignore ?> class="amw-pg__main" <?php echo $modal_enabled ? 'type="button" data-index="0" aria-label="' . esc_attr__('بزرگ‌نمایی تصویر', 'almasara-widgets') . '"' : ''; ?>>
                <?php echo wp_get_attachment_image($main_id, 'large', false, ['loading' => 'eager']); ?>
            </<?php echo $trigger_tag; // phpcs:ignore ?>>

            <?php
            /*
             * تمام تصاویر گالری به صورت flat فرزند مستقیم .amw-pg__strip رندر می‌شوند:
             * - دسکتاپ: گرید N ستونه؛ فقط N تای اول نمایش، بقیه با --extra مخفی
             * - موبایل (یکی‌درمیون): نوار اسکرول افقی با flex ستونی wrap شده؛
             *   آیتم‌های جایگاه 3n+1 تمام‌ارتفاع (مربع بزرگ) و بقیه ۲تایی مربع کوچک
             */
            foreach ($gallery_ids as $i => $attachment_id) :
                $data_index   = $i + 1;
                $is_visible   = $i < $count;
                $is_last_more = $is_visible && $i === $count - 1 && $remaining > 0;

                $classes = ['amw-pg__thumb'];
                if (!$is_visible) {
                    $classes[] = 'amw-pg__thumb--extra';
                }
                if ($is_last_more) {
                    $classes[] = 'amw-pg__thumb--more';
                }
                ?>
                <<?php echo $trigger_tag; // phpcs:ignore ?> class="<?php echo esc_attr(implode(' ', $classes)); ?>" <?php echo $modal_enabled ? 'type="button" data-index="' . esc_attr($data_index) . '" aria-label="' . esc_attr__('بزرگ‌نمایی تصویر', 'almasara-widgets') . '"' : ''; ?>>
                    <?php echo wp_get_attachment_image($attachment_id, 'medium', false, ['loading' => 'lazy']); ?>
                    <?php if ($is_last_more) : ?>
                        <span class="amw-pg__more-overlay" aria-hidden="true">
                            <?php if ('yes' === $settings['show_more_count']) :
                                $position_whitelist = ['top-start', 'top-end', 'bottom-start', 'bottom-end', 'center'];
                                $badge_position = in_array($settings['more_count_position'] ?? '', $position_whitelist, true) ? $settings['more_count_position'] : 'top-start';
                                ?>
                                <span class="amw-pg__more-count amw-pg__more-count--<?php echo esc_attr($badge_position); ?>">+<?php echo esc_html($remaining); ?></span>
                            <?php endif; ?>
                            <?php if ('yes' === $settings['show_dots']) : ?>
                                <span class="amw-pg__dots"><span></span><span></span><span></span></span>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </<?php echo $trigger_tag; // phpcs:ignore ?>>
            <?php endforeach; ?>

            </div><!-- /.amw-pg__strip -->

            <?php if ('alternating' === $mobile_layout && 'yes' === ($settings['show_mobile_counter'] ?? 'yes')) : ?>
                <div class="amw-pg__counter" aria-hidden="true">
                    <span class="amw-pg__counter-bar"><span class="amw-pg__counter-fill"></span></span>
                    <span class="amw-pg__counter-num"><?php echo esc_html(number_format_i18n($total)); ?></span>
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.5-3.5a2 2 0 0 0-2.8 0L7 19"/></svg>
                </div>
            <?php endif; ?>

            <?php if ($modal_enabled) : ?>
                <div class="amw-pg-modal amw-pg-modal--anim-<?php echo esc_attr($anim); ?>" role="dialog" aria-modal="true" aria-hidden="true" aria-label="<?php echo esc_attr__('گالری تصاویر محصول', 'almasara-widgets'); ?>">
                    <button type="button" class="amw-pg-modal__close" aria-label="<?php echo esc_attr__('بستن', 'almasara-widgets'); ?>">
                        <svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>

                    <?php if ('yes' === $settings['show_tab'] && '' !== $settings['tab_label']) : ?>
                        <div class="amw-pg-modal__tab"><?php echo esc_html($settings['tab_label']); ?></div>
                    <?php endif; ?>

                    <div class="amw-pg-modal__stage">
                        <button type="button" class="amw-pg-modal__nav amw-pg-modal__nav--prev" aria-label="<?php echo esc_attr__('تصویر قبلی', 'almasara-widgets'); ?>">
                            <svg viewBox="0 0 24 24" width="1.2em" height="1.2em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
                        </button>

                        <div class="amw-pg-modal__imgwrap">
                            <span class="amw-pg-modal__spinner" hidden></span>
                            <img class="amw-pg-modal__img" alt="">
                        </div>

                        <button type="button" class="amw-pg-modal__nav amw-pg-modal__nav--next" aria-label="<?php echo esc_attr__('تصویر بعدی', 'almasara-widgets'); ?>">
                            <svg viewBox="0 0 24 24" width="1.2em" height="1.2em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 6-6 6 6 6"/></svg>
                        </button>
                    </div>

                    <div class="amw-pg-modal__strip" role="tablist"></div>
                </div>
            <?php endif; ?>

        </div>
        <?php
    }
}
