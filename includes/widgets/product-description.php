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
