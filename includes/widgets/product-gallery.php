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

        $this->add_responsive_control('thumbs_count', [
            'label'       => __('تعداد تامبنیل‌ها', 'almasara-widgets'),
            'type'        => Controls_Manager::NUMBER,
            'min'         => 1,
            'max'         => 10,
            'default'     => 4,
            'description' => __('تامبنیل‌ها همیشه مربعی‌اند و اسلاید نمی‌شوند. اگر گالری تصاویر بیشتری داشته باشد، روی تامبنیل آخر اورلی و سه‌نقطه نمایش داده می‌شود.', 'almasara-widgets'),
            'selectors'   => [
                '{{WRAPPER}} .amw-pg__thumbs' => '--amw-pg-cols: {{VALUE}};',
            ],
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
            'label'      => __('فاصله تا تامبنیل‌ها', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 100]],
            'selectors'  => [
                '{{WRAPPER}} .amw-pg__main' => 'margin-bottom: {{SIZE}}{{UNIT}};',
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
            'label'      => __('فاصله بین تامبنیل‌ها', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .amw-pg__thumbs' => 'gap: {{SIZE}}{{UNIT}};',
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
        $visible   = array_slice($gallery_ids, 0, $count);
        $remaining = count($gallery_ids) - count($visible);
        $total     = 1 + count($gallery_ids);

        $modal_enabled = 'yes' === $settings['modal_enable'];
        $trigger_tag   = $modal_enabled ? 'button' : 'div';

        $this->add_render_attribute('wrapper', 'class', 'amw-pg');
        if ($modal_enabled) {
            $this->add_render_attribute('wrapper', [
                'data-endpoint' => esc_url_raw(rest_url('almasara/v1/product-gallery/' . $product->get_id())),
                'data-total'    => (string) $total,
            ]);
        }

        ?>
        <div <?php $this->print_render_attribute_string('wrapper'); ?>>

            <<?php echo $trigger_tag; // phpcs:ignore ?> class="amw-pg__main" <?php echo $modal_enabled ? 'type="button" data-index="0" aria-label="' . esc_attr__('بزرگ‌نمایی تصویر', 'almasara-widgets') . '"' : ''; ?>>
                <?php echo wp_get_attachment_image($main_id, 'large', false, ['loading' => 'eager']); ?>
            </<?php echo $trigger_tag; // phpcs:ignore ?>>

            <?php if (!empty($visible)) : ?>
                <div class="amw-pg__thumbs">
                    <?php foreach ($visible as $i => $attachment_id) :
                        $is_last_more = $remaining > 0 && $i === count($visible) - 1;
                        ?>
                        <<?php echo $trigger_tag; // phpcs:ignore ?> class="amw-pg__thumb<?php echo $is_last_more ? ' amw-pg__thumb--more' : ''; ?>" <?php echo $modal_enabled ? 'type="button" data-index="' . esc_attr($i + 1) . '" aria-label="' . esc_attr__('بزرگ‌نمایی تصویر', 'almasara-widgets') . '"' : ''; ?>>
                            <?php echo wp_get_attachment_image($attachment_id, 'medium', false, ['loading' => 'lazy']); ?>
                            <?php if ($is_last_more) : ?>
                                <span class="amw-pg__more-overlay" aria-hidden="true">
                                    <span class="amw-pg__dots"><span></span><span></span><span></span></span>
                                </span>
                            <?php endif; ?>
                        </<?php echo $trigger_tag; // phpcs:ignore ?>>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($modal_enabled) : ?>
                <div class="amw-pg-modal" hidden role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__('گالری تصاویر محصول', 'almasara-widgets'); ?>">
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
