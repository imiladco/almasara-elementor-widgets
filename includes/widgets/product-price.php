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
 * ویجت «قیمت محصول» — فقط نمایش قیمت (بدون افزودن به سبد)، بر پایه «نقش»:
 *   • قیمت فعلی  : همیشه نمایش داده می‌شود (قیمتی که مشتری می‌پردازد).
 *   • قیمت پیشین : فقط هنگام فروش ویژه، خط‌خورده.
 *   • بج تخفیف   : درصد تخفیف، فقط هنگام فروش ویژه.
 * محصول متغیر: یا کمترین قیمت («شروع از») یا بازهٔ کامل (کمترین–بیشترین).
 *
 * ساختار مارکاپ هم‌خانواده با ویجت اسلایدر/سبد سریع: دیواید اصلی › باکس
 * قیمت (دیواید ۱، پیش‌فرض ستونی) › گروه قیمت پیشین (دیواید ۲: بج+قیمت
 * پیشین) + قیمت فعلی. تب استایل هم دقیقاً همان الگوی تب‌بندی «چیدمان».
 */
class Product_Price extends Widget_Base {

    /** سقف تعداد گزینه‌ای که برای یافتن ارزان‌ترین، تک‌تک خوانده می‌شود */
    private const MAX_VARIATIONS_SCANNED = 50;

    use Traits\Intro_Row; // برای گزینه‌های چیدمان مشترک

    public function get_name(): string {
        return 'almasara-product-price';
    }

    public function get_title(): string {
        return __('قیمت محصول الماسارا', 'almasara-widgets');
    }

    public function get_icon(): string {
        return 'eicon-product-price';
    }

    public function get_categories(): array {
        return ['almasara'];
    }

    public function get_keywords(): array {
        return ['قیمت', 'محصول', 'تخفیف', 'price', 'product', 'woocommerce', 'sale', 'الماسارا'];
    }

    public function get_style_depends(): array {
        return ['almasara-widgets'];
    }

    /* =====================================================================
     * کنترل‌ها
     * =================================================================== */

    protected function register_controls(): void {
        $this->register_product_content_controls();
        $this->register_price_content_controls();

        $this->register_layout_style_controls();
        $this->register_price_visual_style_controls();
    }

    /* ---------------- محتوا: محصول ---------------- */

    private function register_product_content_controls(): void {
        $this->start_controls_section('section_product', [
            'label' => __('محصول', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('product_id', [
            'label'       => __('شناسه محصول', 'almasara-widgets'),
            'type'        => Controls_Manager::NUMBER,
            'min'         => 0,
            'description' => __('خالی = محصول جاری (در صفحه/قالب محصول).', 'almasara-widgets'),
        ]);

        $this->add_control('heading_variable', [
            'label'     => __('محصول متغیر', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('variable_mode', [
            'label'   => __('نحوه نمایش قیمت', 'almasara-widgets'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'min',
            'options' => [
                'min'   => __('کمترین قیمت («شروع از ...»)', 'almasara-widgets'),
                'range' => __('بازه کامل (کمترین – بیشترین)', 'almasara-widgets'),
            ],
        ]);

        $this->add_control('variable_prefix', [
            'label'     => __('پیشوند («شروع از»)', 'almasara-widgets'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('شروع از', 'almasara-widgets'),
            'condition' => ['variable_mode' => 'min'],
        ]);

        $this->add_control('range_separator', [
            'label'     => __('جداکننده بازه', 'almasara-widgets'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('تا', 'almasara-widgets'),
            'condition' => ['variable_mode' => 'range'],
        ]);

        $this->add_control('range_note', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('در حالت بازه، چون یک درصد تخفیف واحد معنا ندارد، بج تخفیف و قیمت پیشین نمایش داده نمی‌شوند.', 'almasara-widgets'),
            'content_classes' => 'elementor-descriptor',
            'condition'       => ['variable_mode' => 'range'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- محتوا: قیمت ---------------- */

    private function register_price_content_controls(): void {
        $this->start_controls_section('section_price_content', [
            'label' => __('قیمت', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_old', [
            'label'       => __('نمایش قیمت پیش از تخفیف', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => 'yes',
            'description' => __('قیمت پیشین فقط هنگامی نمایش داده می‌شود که محصول فروش ویژه داشته باشد.', 'almasara-widgets'),
        ]);

        $this->add_control('show_discount_badge', [
            'label'   => __('بج درصد تخفیف', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('heading_currency', [
            'label'     => __('واحد پول', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('currency_text', [
            'label'       => __('متن واحد پول', 'almasara-widgets'),
            'type'        => Controls_Manager::TEXT,
            'placeholder' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : __('تومان', 'almasara-widgets'),
            'description' => __('خالی = نماد پیش‌فرض ووکامرس.', 'almasara-widgets'),
        ]);

        $this->add_control('currency_on_now', [
            'label'   => __('واحد پول برای قیمت فعلی', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('currency_on_old', [
            'label'     => __('واحد پول برای قیمت پیشین', 'almasara-widgets'),
            'type'      => Controls_Manager::SWITCHER,
            'condition' => ['show_old' => 'yes'],
        ]);

        $this->add_control('free_text', [
            'label'       => __('متن «رایگان»', 'almasara-widgets'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('رایگان', 'almasara-widgets'),
            'description' => __('وقتی قیمت صفر باشد، این متن به‌جای عدد و واحد پول نمایش داده می‌شود. برای نمایش خودِ صفر، خالی بگذارید.', 'almasara-widgets'),
            'separator'   => 'before',
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: چیدمان (باکس قیمت / فعلی / پیشین) ---------------- */

    private function register_layout_style_controls(): void {
        $this->start_controls_section('section_style_layout', [
            'label' => __('چیدمان', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->start_controls_tabs('layout_tabs');

        $this->start_controls_tab('layout_tab_stack', ['label' => __('باکس قیمت', 'almasara-widgets')]);
        $this->add_full_flex_controls('stack', '{{WRAPPER}} .amw-pp__stack', [
            'direction' => 'column',
            'align'     => 'flex-start',
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('layout_tab_now', ['label' => __('قیمت فعلی', 'almasara-widgets')]);
        $this->add_full_flex_controls('now', '{{WRAPPER}} .amw-pp__now', [
            'direction' => 'row',
            'align'     => 'baseline',
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('layout_tab_old', ['label' => __('قیمت پیشین', 'almasara-widgets')]);
        $this->add_control('old_group_note', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('بج تخفیف و قیمت پیشین با هم در یک ردیف‌اند؛ با «جهت = افقی معکوس» جای‌شان عوض می‌شود.', 'almasara-widgets'),
            'content_classes' => 'elementor-descriptor',
        ]);
        $this->add_full_flex_controls('oldgrp', '{{WRAPPER}} .amw-pp__old-group', [
            'direction' => 'row',
            'align'     => 'center',
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    /* ---------------- استایل: قیمت (فعلی / پیشین / تخفیف) ---------------- */

    private function register_price_visual_style_controls(): void {
        $this->start_controls_section('section_price_visual', [
            'label' => __('قیمت', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->start_controls_tabs('price_visual_tabs');

        $this->start_controls_tab('visual_tab_now', ['label' => __('فعلی', 'almasara-widgets')]);
        $this->register_price_block_visual_controls('now', '{{WRAPPER}} .amw-pp__now', [
            'unit_condition' => ['currency_on_now' => 'yes'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('visual_tab_old', ['label' => __('پیشین', 'almasara-widgets')]);
        $this->register_price_block_visual_controls('old', '{{WRAPPER}} .amw-pp__old', [
            'unit_condition' => ['currency_on_old' => 'yes'],
            'extra_before_style' => function () {
                $this->add_control('old_strike', [
                    'label'       => __('خط‌خورده', 'almasara-widgets'),
                    'type'        => Controls_Manager::SWITCHER,
                    'default'     => 'yes',
                    'description' => __('روی کل قیمت پیشین (عدد و واحد) خط کشیده می‌شود.', 'almasara-widgets'),
                    'separator'   => 'before',
                    'selectors'   => ['{{WRAPPER}} .amw-pp__old' => 'text-decoration: line-through;'],
                ]);
            },
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('visual_tab_discount', ['label' => __('تخفیف', 'almasara-widgets')]);
        $this->register_discount_visual_controls();
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    /**
     * بلوک تکرارشونده تب فعلی/پیشین: تایپوگرافی، رنگ، پس‌زمینه، حاشیه،
     * سایه، پدینگ، رادیوس، و سوییچ «استایل منحصربه‌فرد واحد پول».
     */
    private function register_price_block_visual_controls(string $prefix, string $selector, array $args = []): void {
        if (!empty($args['extra_before_style']) && is_callable($args['extra_before_style'])) {
            ($args['extra_before_style'])();
        }

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'      => $prefix . '_typo',
            'label'     => __('تایپوگرافی', 'almasara-widgets'),
            'selector'  => $selector,
            'separator' => 'before',
        ]);

        $this->add_control($prefix . '_color', [
            'label'     => __('رنگ', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$selector => 'color: {{VALUE}};'],
        ]);

        $this->add_control('heading_' . $prefix . '_box', [
            'label'     => __('کادر', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
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

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => $prefix . '_shadow',
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

        $unit_condition = $args['unit_condition'] ?? [];

        $this->add_control($prefix . '_unit_custom', [
            'label'       => __('استایل منحصربه‌فرد واحد پول', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'description' => __('با فعال‌کردن، می‌توانید برای واحد پول این قیمت استایل جداگانه‌ای جدا از عدد تعیین کنید.', 'almasara-widgets'),
            'separator'   => 'before',
            'condition'   => $unit_condition,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'      => $prefix . '_unit_typo',
            'label'     => __('تایپوگرافی واحد پول', 'almasara-widgets'),
            'selector'  => $selector . ' .amw-pp__unit',
            'condition' => [$prefix . '_unit_custom' => 'yes'],
        ]);

        $this->add_control($prefix . '_unit_color', [
            'label'     => __('رنگ واحد پول', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$selector . ' .amw-pp__unit' => 'color: {{VALUE}};'],
            'condition' => [$prefix . '_unit_custom' => 'yes'],
        ]);
    }

    /** تب «تخفیف»: بج (.amw-pp__discount) + آیکون + چیدمان محتوای بج */
    private function register_discount_visual_controls(): void {
        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'discount_typo',
            'label'    => __('تایپوگرافی', 'almasara-widgets'),
            'selector' => '{{WRAPPER}} .amw-pp__discount',
        ]);

        $this->add_control('discount_color', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => ['{{WRAPPER}} .amw-pp__discount' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('heading_discount_box', [
            'label'     => __('کادر', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('discount_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#e6123d',
            'selectors' => ['{{WRAPPER}} .amw-pp__discount' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'discount_border',
            'label'    => __('حاشیه', 'almasara-widgets'),
            'selector' => '{{WRAPPER}} .amw-pp__discount',
        ]);

        $this->add_responsive_control('discount_padding', [
            'label'      => __('پدینگ', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'default'    => ['top' => 3, 'right' => 8, 'bottom' => 3, 'left' => 8, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-pp__discount' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('discount_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'default'    => ['size' => 8, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-pp__discount' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('heading_discount_icon', [
            'label'     => __('آیکون', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_responsive_control('discount_icon_size', [
            'label'      => __('سایز آیکون', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 6, 'max' => 30], 'em' => ['min' => 0.5, 'max' => 2, 'step' => 0.1]],
            'default'    => ['size' => 0.9, 'unit' => 'em'],
            'selectors'  => ['{{WRAPPER}} .amw-pp__discount-icon' => '--amw-pp-disc-icon: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('discount_icon_color', [
            'label'     => __('رنگ آیکون', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-pp__discount-icon' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('heading_discount_flex', [
            'label'     => __('چیدمان محتوای بج (عدد + آیکون)', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_full_flex_controls('discount', '{{WRAPPER}} .amw-pp__discount', [
            'direction' => 'row',
            'align'     => 'center',
        ]);
    }

    /* =====================================================================
     * منطق محصول
     * =================================================================== */

    private function resolve_product(array $settings) {
        if (!empty($settings['product_id'])) {
            $product = wc_get_product(absint($settings['product_id']));
            if ($product instanceof \WC_Product) {
                return $product;
            }
        }

        global $product;
        if ($product instanceof \WC_Product) {
            return $product;
        }

        $current = wc_get_product(get_the_ID());
        return $current instanceof \WC_Product ? $current : false;
    }

    /**
     * استخراج داده قیمت بر اساس نقش، با پشتیبانی از محصول متغیر
     * (کمترین قیمت یا بازه کامل).
     *
     * @return array{current:string,old:string,max:string,on_sale:bool,is_range:bool,has_price:bool}
     */
    private function get_price_data($product, string $variable_mode): array {
        $data = ['current' => '', 'old' => '', 'max' => '', 'on_sale' => false, 'is_range' => false, 'has_price' => false];

        if (!$product instanceof \WC_Product) {
            return $data;
        }

        if ($product->is_type('variable')) {
            if ('range' === $variable_mode) {
                $min = $product->get_variation_price('min', true);
                $max = $product->get_variation_price('max', true);

                if ('' !== $min && '' !== $max && (float) $max > (float) $min) {
                    $data['current']   = (string) $min;
                    $data['max']       = (string) $max;
                    $data['is_range']  = true;
                    $data['has_price'] = true;
                    return $data;
                }
            }

            // قیمت فعلی و پیشین باید از یک گزینهٔ واحد بیایند. کمترین قیمت
            // فعال و کمترین قیمت عادی می‌توانند متعلق به دو گزینهٔ متفاوت
            // باشند، و مقایسهٔ آن دو درصد تخفیفی می‌سازد که هیچ گزینه‌ای
            // واقعاً ندارد.
            $cheapest = $this->cheapest_variation($product);

            $data['current'] = $cheapest['price'];
            if ('' !== $cheapest['regular'] && (float) $cheapest['regular'] > (float) $cheapest['price']) {
                $data['old']     = $cheapest['regular'];
                $data['on_sale'] = true;
            }

            $data['has_price'] = ('' !== $data['current']);
            return $data;
        }

        $regular = $product->get_regular_price();
        $sale    = $product->get_sale_price();

        if ($product->is_on_sale() && '' !== $sale && null !== $sale) {
            $data['current'] = $this->display_price($product, $sale);
            $data['old']     = $this->display_price($product, $regular);
            $data['on_sale'] = '' !== $data['old'];
        } else {
            $data['current'] = $this->display_price($product, ('' === $regular || null === $regular) ? $product->get_price() : $regular);
        }

        $data['has_price'] = ('' !== $data['current']);
        return $data;
    }

    /**
     * قیمت آماده نمایش.
     *
     * مقادیر خامِ get_price/get_regular_price تنظیمات مالیاتی فروشگاه را
     * نمی‌دانند، در حالی که مسیر محصول متغیر (get_variation_price با آرگومان
     * دوم true) قیمت نمایشی می‌دهد. بدون این تبدیل، فروشگاهی که قیمت را
     * بدون مالیات ذخیره و با مالیات نمایش می‌دهد، برای محصول ساده عددی
     * متفاوت از خودِ ووکامرس نشان می‌داد.
     */
    private function display_price(\WC_Product $product, $raw): string {
        if ('' === $raw || null === $raw) {
            return '';
        }

        if (!function_exists('wc_get_price_to_display')) {
            return (string) $raw;
        }

        return (string) wc_get_price_to_display($product, ['price' => $raw]);
    }

    /**
     * ارزان‌ترین گزینهٔ یک محصول متغیر، همراه قیمت عادیِ همان گزینه.
     *
     * @return array{price:string,regular:string}
     */
    private function cheapest_variation(\WC_Product $product): array {
        $best     = ['price' => '', 'regular' => ''];
        $children = $product->get_children();

        /*
         * محصولی با صدها گزینه نباید صدها آبجکت بسازد. از یک حدی به بعد
         * ارزشِ درصدِ دقیق‌تر، هزینهٔ لود همهٔ گزینه‌ها را توجیه نمی‌کند و به
         * مقدار تجمیعی خودِ ووکامرس اکتفا می‌شود.
         */
        if (count($children) > self::MAX_VARIATIONS_SCANNED) {
            return $this->aggregate_variation_price($product);
        }

        foreach ($children as $child_id) {
            $variation = wc_get_product($child_id);

            if (!$variation instanceof \WC_Product || !$variation->exists()) {
                continue;
            }

            // گزینهٔ پنهان یا غیرقابل‌خرید نباید مبنای قیمت نمایشی شود؛ وگرنه
            // کارت عددی نشان می‌دهد که مشتری اصلاً نمی‌تواند بخرد
            if (method_exists($variation, 'variation_is_visible') && !$variation->variation_is_visible()) {
                continue;
            }

            if (!$variation->is_purchasable()) {
                continue;
            }

            $price = $this->display_price($variation, $variation->get_price());
            if ('' === $price) {
                continue;
            }

            if ('' === $best['price'] || (float) $price < (float) $best['price']) {
                $best = [
                    'price'   => $price,
                    'regular' => $this->display_price($variation, $variation->get_regular_price()),
                ];
            }
        }

        // اگر هیچ گزینهٔ قابل نمایشی نبود، به مقدار تجمیعی برمی‌گردیم
        return '' === $best['price'] ? $this->aggregate_variation_price($product) : $best;
    }

    /** کمترین قیمت تجمیعیِ خودِ ووکامرس، بدون قیمت پیشین */
    private function aggregate_variation_price(\WC_Product $product): array {
        $min = $product->get_variation_price('min', true);

        return [
            'price'   => ('' === $min || null === $min) ? '' : (string) $min,
            'regular' => '',
        ];
    }

    private function format_amount(string $value): string {
        if ('' === $value) {
            return '';
        }
        $decimals = function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 0;
        $dec_sep  = function_exists('wc_get_price_decimal_separator') ? wc_get_price_decimal_separator() : '.';
        $thou_sep = function_exists('wc_get_price_thousand_separator') ? wc_get_price_thousand_separator() : ',';
        return number_format((float) $value, $decimals, $dec_sep, $thou_sep);
    }

    private function currency_text(array $settings): string {
        $text = trim((string) ($settings['currency_text'] ?? ''));
        if ('' !== $text) {
            return $text;
        }
        $symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '';
        return html_entity_decode((string) $symbol, ENT_QUOTES, 'UTF-8');
    }

    /* =====================================================================
     * رندر
     * =================================================================== */

    protected function render(): void {
        if (!function_exists('wc_get_product')) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="amw-pp__notice">' . esc_html__('این ویجت به ووکامرس فعال نیاز دارد.', 'almasara-widgets') . '</div>';
            }
            return;
        }

        $settings = $this->get_settings_for_display();
        $product  = $this->resolve_product($settings);
        $price    = $this->get_price_data($product, $settings['variable_mode']);

        if (!$price['has_price'] && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
            // نمونه در ادیتور تا پنل استایل روی محتوای واقعی دیده شود
            $price = ['current' => '199000', 'old' => '250000', 'max' => '', 'on_sale' => true, 'is_range' => false, 'has_price' => true];
        }

        if (!$price['has_price']) {
            return;
        }

        $currency  = $this->currency_text($settings);
        $free_text = trim((string) ($settings['free_text'] ?? ''));
        $unit      = '<span class="amw-pp__unit">' . esc_html($currency) . '</span>';

        $badge_on = !$price['is_range'] && $price['on_sale'] && 'yes' === ($settings['show_discount_badge'] ?? 'yes');
        $old_on   = !$price['is_range'] && $price['on_sale'] && 'yes' === ($settings['show_old'] ?? 'yes') && '' !== $price['old'];

        echo '<div class="amw-pp"><div class="amw-pp__stack">';

        if ($badge_on || $old_on) {
            echo '<div class="amw-pp__old-group">';
            if ($badge_on) {
                $regular = (float) $price['old'];
                $active  = (float) $price['current'];
                $pct     = $regular > 0 ? (int) round(($regular - $active) / $regular * 100) : 0;
                echo '<span class="amw-pp__discount"><span class="amw-pp__discount-num">' . esc_html($this->fa((string) $pct) . '%') . '</span><span class="amw-pp__discount-icon">' . $this->discount_icon_svg() . '</span></span>'; // phpcs:ignore WordPress.Security.EscapeOutput
            }
            if ($old_on) {
                echo '<del class="amw-pp__old"><span class="amw-pp__num">' . esc_html($this->format_amount($price['old'])) . '</span>';
                if ('yes' === ($settings['currency_on_old'] ?? '')) {
                    echo $unit; // phpcs:ignore WordPress.Security.EscapeOutput
                }
                echo '</del>';
            }
            echo '</div>';
        }

        echo '<span class="amw-pp__now">';
        if (0.0 === (float) $price['current'] && !$price['is_range'] && '' !== $free_text) {
            echo '<span class="amw-pp__num amw-pp__num--free">' . esc_html($free_text) . '</span>';
        } elseif ($price['is_range']) {
            if ('' !== trim((string) ($settings['range_separator'] ?? ''))) {
                printf(
                    '<span class="amw-pp__num">%1$s</span> <span class="amw-pp__range-sep">%2$s</span> <span class="amw-pp__num">%3$s</span>',
                    esc_html($this->format_amount($price['current'])),
                    esc_html($settings['range_separator']),
                    esc_html($this->format_amount($price['max']))
                );
            } else {
                printf(
                    '<span class="amw-pp__num">%1$s – %2$s</span>',
                    esc_html($this->format_amount($price['current'])),
                    esc_html($this->format_amount($price['max']))
                );
            }
            if ('yes' === ($settings['currency_on_now'] ?? 'yes')) {
                echo $unit; // phpcs:ignore WordPress.Security.EscapeOutput
            }
        } else {
            if ($product && $product->is_type('variable') && 'min' === $settings['variable_mode'] && '' !== trim((string) ($settings['variable_prefix'] ?? ''))) {
                echo '<span class="amw-pp__prefix">' . esc_html($settings['variable_prefix']) . '</span>';
            }
            echo '<span class="amw-pp__num">' . esc_html($this->format_amount($price['current'])) . '</span>';
            if ('yes' === ($settings['currency_on_now'] ?? 'yes')) {
                echo $unit; // phpcs:ignore WordPress.Security.EscapeOutput
            }
        }
        echo '</span>';

        echo '</div></div>';
    }

    private function discount_icon_svg(): string {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M9 15 15 9M9.5 9.5h.01M14.5 14.5h.01"/></svg>';
    }

    private function fa(string $str): string {
        return strtr($str, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }
}
