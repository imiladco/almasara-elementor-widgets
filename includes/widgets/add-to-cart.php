<?php
namespace Almasara_Widgets\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ویجت «افزودن به سبد خرید»
 *
 * مارک‌آپ استاندارد ووکامرس تولید می‌کند (کلاس‌ها و data-attributeها)، پس:
 * - افزودن واقعی را اسکریپت بومی wc-add-to-cart انجام می‌دهد (همه هوک‌ها فایر می‌شوند)
 * - لایه خوش‌بینانه افزونه almasara-fast-cart خودکار رویش سوار می‌شود (بج آنی)
 * بدون هیچ سیم‌کشی بین دو افزونه.
 */
class Add_To_Cart extends Widget_Base {

    use Traits\Intro_Row; // فقط برای resolve_product و get_inline_svg

    public function get_name(): string {
        return 'almasara-add-to-cart';
    }

    public function get_title(): string {
        return __('افزودن به سبد الماسارا', 'almasara-widgets');
    }

    public function get_icon(): string {
        return 'eicon-cart-medium';
    }

    public function get_categories(): array {
        return ['almasara', 'woocommerce-elements'];
    }

    public function get_keywords(): array {
        return ['سبد', 'خرید', 'cart', 'add to cart', 'دکمه', 'الماسارا'];
    }

    public function get_style_depends(): array {
        return ['almasara-widgets'];
    }

    public function get_script_depends(): array {
        // wc-add-to-cart = افزودن ایجکسی بومی ووکامرس روی هر صفحه
        return ['wc-add-to-cart', 'almasara-atc'];
    }

    /* ---------------------------------------------------------------------
     * کنترل‌ها
     * ------------------------------------------------------------------- */

    protected function register_controls(): void {
        $this->register_content_controls();
        $this->register_quantity_content_controls();

        $this->register_layout_style_controls();
        $this->register_button_style_controls();
        $this->register_quantity_style_controls();
    }

    private function register_content_controls(): void {
        $this->start_controls_section('section_content', [
            'label' => __('دکمه', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('product_id', [
            'label'       => __('شناسه محصول', 'almasara-widgets'),
            'type'        => Controls_Manager::NUMBER,
            'description' => __('خالی = محصول جاری (در صفحه/قالب محصول). برای دکمه‌ی یک محصول خاص، شناسه‌اش را وارد کنید.', 'almasara-widgets'),
            'dynamic'     => ['active' => true],
        ]);

        $this->add_control('button_text', [
            'label'       => __('متن دکمه', 'almasara-widgets'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('افزودن به سبد خرید', 'almasara-widgets'),
            'placeholder' => __('افزودن به سبد خرید', 'almasara-widgets'),
            'dynamic'     => ['active' => true],
            'label_block' => true,
        ]);

        $this->add_control('added_text', [
            'label'       => __('متن پس از افزودن', 'almasara-widgets'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('به سبد اضافه شد', 'almasara-widgets'),
            'description' => __('هنگام تکمیل موفق افزودن، کوتاه نمایش داده می‌شود.', 'almasara-widgets'),
        ]);

        $this->add_control('show_icon', [
            'label'   => __('نمایش آیکون', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('icon_image', [
            'label'       => __('آیکون سفارشی', 'almasara-widgets'),
            'type'        => Controls_Manager::MEDIA,
            'media_types' => ['image', 'svg'],
            'description' => __('خالی = آیکون سبد پیش‌فرض. SVG به‌صورت inline و رنگ‌پذیر رندر می‌شود.', 'almasara-widgets'),
            'condition'   => ['show_icon' => 'yes'],
        ]);

        $this->add_control('icon_position', [
            'label'     => __('جای آیکون', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'start',
            'options'   => [
                'start' => __('ابتدای متن', 'almasara-widgets'),
                'end'   => __('انتهای متن', 'almasara-widgets'),
            ],
            'condition' => ['show_icon' => 'yes'],
        ]);

        $this->add_control('show_price', [
            'label'   => __('نمایش قیمت روی دکمه', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => '',
        ]);

        $this->add_control('hide_wc_view_cart', [
            'label'       => __('مخفی کردن لینک «مشاهده سبد» ووکامرس', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => 'yes',
            'description' => __('ووکامرس بعد از افزودن یک لینک «مشاهده سبد» کنار دکمه می‌گذارد؛ چون دسترسی سبد خودتان را دارید معمولاً لازم نیست.', 'almasara-widgets'),
        ]);

        $this->end_controls_section();
    }

    private function register_quantity_content_controls(): void {
        $this->start_controls_section('section_quantity', [
            'label' => __('تعداد', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_quantity', [
            'label'   => __('انتخابگر تعداد', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('quantity_style', [
            'label'     => __('نوع', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'stepper',
            'options'   => [
                'stepper' => __('دکمه‌ای (− عدد +)', 'almasara-widgets'),
                'input'   => __('فیلد عددی ساده', 'almasara-widgets'),
            ],
            'condition' => ['show_quantity' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: چیدمان ---------------- */

    private function register_layout_style_controls(): void {
        $this->start_controls_section('section_style_layout', [
            'label' => __('چیدمان', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('direction', [
            'label'     => __('جهت تعداد و دکمه', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'row',
            'options'   => [
                'row'    => __('کنار هم', 'almasara-widgets'),
                'column' => __('روی هم', 'almasara-widgets'),
            ],
            'selectors' => ['{{WRAPPER}} .amw-atc' => 'flex-direction: {{VALUE}};'],
        ]);

        $this->add_responsive_control('gap', [
            'label'      => __('فاصله', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-atc' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('button_grow', [
            'label'        => __('دکمه فضای باقی‌مانده را پر کند', 'almasara-widgets'),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => '1',
            'selectors'    => ['{{WRAPPER}} .amw-atc__btn' => 'flex-grow: {{VALUE}};'],
        ]);

        $this->add_responsive_control('align', [
            'label'     => __('تراز کل ویجت', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'flex-start'   => ['title' => __('راست', 'almasara-widgets'), 'icon' => 'eicon-align-start-h'],
                'center'       => ['title' => __('وسط', 'almasara-widgets'), 'icon' => 'eicon-align-center-h'],
                'flex-end'     => ['title' => __('چپ', 'almasara-widgets'), 'icon' => 'eicon-align-end-h'],
                'stretch'      => ['title' => __('کشیده', 'almasara-widgets'), 'icon' => 'eicon-align-stretch-h'],
            ],
            'selectors' => ['{{WRAPPER}} .amw-atc' => 'justify-content: {{VALUE}};'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: دکمه ---------------- */

    private function register_button_style_controls(): void {
        $this->start_controls_section('section_style_button', [
            'label' => __('دکمه', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'btn_typography',
            'selector' => '{{WRAPPER}} .amw-atc__btn',
        ]);

        $this->add_responsive_control('btn_padding', [
            'label'      => __('پدینگ', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .amw-atc__btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('btn_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .amw-atc__btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('icon_size', [
            'label'      => __('اندازه آیکون', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 8, 'max' => 60]],
            'selectors'  => [
                '{{WRAPPER}} .amw-atc__icon' => '--amw-atc-icon: {{SIZE}}{{UNIT}};',
            ],
            'condition'  => ['show_icon' => 'yes'],
        ]);

        $this->add_responsive_control('icon_gap', [
            'label'      => __('فاصله آیکون از متن', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'selectors'  => ['{{WRAPPER}} .amw-atc__btn' => '--amw-atc-icongap: {{SIZE}}{{UNIT}};'],
            'condition'  => ['show_icon' => 'yes'],
        ]);

        $this->start_controls_tabs('btn_tabs');

        // عادی
        $this->start_controls_tab('btn_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => 'btn_bg',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .amw-atc__btn',
        ]);
        $this->add_control('btn_color', [
            'label'     => __('رنگ متن و آیکون', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-atc__btn' => 'color: {{VALUE}};'],
        ]);
        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'btn_border',
            'selector' => '{{WRAPPER}} .amw-atc__btn',
        ]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'btn_shadow',
            'selector' => '{{WRAPPER}} .amw-atc__btn',
        ]);
        $this->end_controls_tab();

        // هاور
        $this->start_controls_tab('btn_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => 'btn_bg_hover',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .amw-atc__btn:hover',
        ]);
        $this->add_control('btn_color_hover', [
            'label'     => __('رنگ متن و آیکون', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-atc__btn:hover' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('btn_border_color_hover', [
            'label'     => __('رنگ حاشیه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-atc__btn:hover' => 'border-color: {{VALUE}};'],
        ]);
        $this->add_control('btn_transform_hover', [
            'label'     => __('جابه‌جایی عمودی', 'almasara-widgets'),
            'type'      => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'     => ['px' => ['min' => -10, 'max' => 10]],
            'selectors' => ['{{WRAPPER}} .amw-atc__btn:hover' => 'transform: translateY({{SIZE}}px);'],
        ]);
        $this->end_controls_tab();

        // در حال افزودن + افزوده‌شد
        $this->start_controls_tab('btn_added', ['label' => __('افزوده‌شد', 'almasara-widgets')]);
        $this->add_control('btn_bg_added', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#1eaa59',
            'selectors' => ['{{WRAPPER}} .amw-atc__btn.amw-added' => 'background-color: {{VALUE}}; border-color: {{VALUE}};'],
        ]);
        $this->add_control('btn_color_added', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => ['{{WRAPPER}} .amw-atc__btn.amw-added' => 'color: {{VALUE}};'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control('btn_transition', [
            'label'     => __('مدت انیمیشن (میلی‌ثانیه)', 'almasara-widgets'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 0, 'max' => 1000]],
            'default'   => ['size' => 250],
            'separator' => 'before',
            'selectors' => ['{{WRAPPER}} .amw-atc__btn' => 'transition: all {{SIZE}}ms ease;'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: تعداد ---------------- */

    private function register_quantity_style_controls(): void {
        $this->start_controls_section('section_style_quantity', [
            'label'     => __('تعداد', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_quantity' => 'yes'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qty_typography',
            'selector' => '{{WRAPPER}} .amw-atc__qty, {{WRAPPER}} .amw-atc__qty-input',
        ]);

        $this->add_responsive_control('qty_height', [
            'label'      => __('ارتفاع', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 28, 'max' => 80]],
            'selectors'  => ['{{WRAPPER}} .amw-atc__qty' => 'height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('qty_btn_width', [
            'label'      => __('عرض دکمه‌های +/−', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 24, 'max' => 70]],
            'default'    => ['size' => 40, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-atc__step' => 'width: {{SIZE}}{{UNIT}};'],
            'condition'  => ['quantity_style' => 'stepper'],
        ]);

        $this->add_control('qty_color', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-atc__qty' => 'color: {{VALUE}};',
                '{{WRAPPER}} .amw-atc__qty-input' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('qty_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-atc__qty' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('qty_step_color', [
            'label'     => __('رنگ دکمه‌های +/−', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-atc__step' => 'color: {{VALUE}};'],
            'condition' => ['quantity_style' => 'stepper'],
        ]);

        $this->add_control('qty_step_color_hover', [
            'label'     => __('رنگ +/− در هاور', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-atc__step:hover' => 'color: {{VALUE}};'],
            'condition' => ['quantity_style' => 'stepper'],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'      => 'qty_border',
            'selector'  => '{{WRAPPER}} .amw-atc__qty',
            'separator' => 'before',
        ]);

        $this->add_responsive_control('qty_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .amw-atc__qty' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->end_controls_section();
    }

    /* ---------------------------------------------------------------------
     * رندر
     * ------------------------------------------------------------------- */

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $product  = $this->resolve_atc_product($settings);

        $is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();

        if (!$product) {
            if ($is_editor) {
                echo '<div class="amw-paw__notice">' . esc_html__('محصولی پیدا نشد. این ویجت را در صفحه/قالب محصول استفاده کنید یا شناسه محصول را وارد کنید.', 'almasara-widgets') . '</div>';
            }
            return;
        }

        $wrapper_classes = ['amw-atc'];
        if ('yes' === $settings['hide_wc_view_cart']) {
            $wrapper_classes[] = 'amw-atc--hide-viewcart';
        }

        // محصولی که مستقیم قابل افزودن نیست (متغیر بدون انتخاب، خارج از انبار، ...)
        $simple_addable = $product->is_purchasable() && $product->is_in_stock() && !$product->is_type('variable') && !$product->is_type('grouped') && !$product->is_type('external');

        echo '<div class="' . esc_attr(implode(' ', $wrapper_classes)) . '">';

        if ($simple_addable) {
            $this->render_quantity($settings, $product);
            $this->render_button($settings, $product);
        } else {
            // fallback: لینک به صفحه محصول («انتخاب گزینه‌ها» / «مشاهده»)
            printf(
                '<a class="amw-atc__btn amw-atc__btn--link" href="%s">%s%s</a>',
                esc_url($product->add_to_cart_url()),
                $this->get_icon_html($settings),
                '<span class="amw-atc__text">' . esc_html($product->add_to_cart_text()) . '</span>'
            );
        }

        echo '</div>';
    }

    /** رندر انتخابگر تعداد */
    private function render_quantity(array $settings, $product): void {
        if ('yes' !== $settings['show_quantity']) {
            return;
        }

        $min  = 1;
        $max  = $product->managing_stock() && !$product->backorders_allowed()
            ? (int) $product->get_stock_quantity()
            : 0; // 0 = بدون سقف
        $step = 1;

        $stepper = 'stepper' === $settings['quantity_style'];

        echo '<div class="amw-atc__qty' . ($stepper ? ' amw-atc__qty--stepper' : '') . '">';

        if ($stepper) {
            echo '<button type="button" class="amw-atc__step amw-atc__step--minus" tabindex="-1" aria-label="' . esc_attr__('کاهش', 'almasara-widgets') . '">−</button>';
        }

        printf(
            '<input type="number" class="amw-atc__qty-input" value="%d" min="%d"%s step="%d" inputmode="numeric" aria-label="%s" />',
            $min,
            $min,
            $max > 0 ? ' max="' . esc_attr($max) . '"' : '',
            $step,
            esc_attr__('تعداد', 'almasara-widgets')
        );

        if ($stepper) {
            echo '<button type="button" class="amw-atc__step amw-atc__step--plus" tabindex="-1" aria-label="' . esc_attr__('افزایش', 'almasara-widgets') . '">+</button>';
        }

        echo '</div>';
    }

    /** رندر دکمه با مارک‌آپ استاندارد ووکامرس */
    private function render_button(array $settings, $product): void {
        $text = '' !== trim((string) $settings['button_text'])
            ? $settings['button_text']
            : $product->single_add_to_cart_text();

        $this->add_render_attribute('btn', [
            'class'              => ['amw-atc__btn', 'ajax_add_to_cart', 'add_to_cart_button', 'product_type_simple'],
            'type'               => 'button',
            'data-product_id'    => (string) $product->get_id(),
            'data-product_sku'   => (string) $product->get_sku(),
            'data-quantity'      => '1',
            'data-added-text'    => (string) $settings['added_text'],
            'aria-label'         => $product->add_to_cart_description(),
            'rel'                => 'nofollow',
        ]);

        $icon  = $this->get_icon_html($settings);
        $start = 'start' === $settings['icon_position'];

        $price = '';
        if ('yes' === $settings['show_price']) {
            $price = '<span class="amw-atc__price">' . wp_kses_post($product->get_price_html()) . '</span>';
        }

        echo '<button ' . $this->get_render_attribute_string('btn') . '>';
        if ($start) {
            echo $icon; // phpcs:ignore
        }
        echo '<span class="amw-atc__text">' . esc_html($text) . '</span>';
        if (!$start) {
            echo $icon; // phpcs:ignore
        }
        echo $price; // phpcs:ignore
        echo '</button>';
    }

    /** آیکون: سفارشی (تصویر/SVG inline) یا سبد پیش‌فرض */
    private function get_icon_html(array $settings): string {
        if ('yes' !== $settings['show_icon']) {
            return '';
        }

        $inner = '';
        if (!empty($settings['icon_image']['url'])) {
            $url    = $settings['icon_image']['url'];
            $is_svg = 'svg' === strtolower(pathinfo(wp_parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
            if ($is_svg && !empty($settings['icon_image']['id'])) {
                $inner = $this->get_inline_svg((int) $settings['icon_image']['id']);
            }
            if ('' === $inner) {
                $inner = '<img src="' . esc_url($url) . '" alt="">';
            }
        } else {
            // آیکون سبد پیش‌فرض (SVG)
            $inner = '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>';
        }

        return '<span class="amw-atc__icon">' . $inner . '</span>';
    }

    /** محصول: شناسه دستی، وگرنه محصول جاری (از trait) */
    private function resolve_atc_product(array $settings) {
        if (!empty($settings['product_id'])) {
            return function_exists('wc_get_product') ? wc_get_product((int) $settings['product_id']) : false;
        }
        return $this->resolve_product();
    }
}
