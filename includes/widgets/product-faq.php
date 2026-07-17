<?php
namespace Almasara_Widgets\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ویجت «سوالات متداول محصول»
 *
 * منبع ترکیبی: سوالات اختصاصی خود محصول (متاباکس) + سوالات عمومی ریپیتر ویجت.
 * آکاردئون شماره‌دار مطابق دیزاین + اسکیمای FAQPage برای سئو.
 */
class Product_Faq extends Widget_Base {

    use Traits\Intro_Row;

    public function get_name(): string {
        return 'almasara-product-faq';
    }

    public function get_title(): string {
        return __('سوالات متداول الماسارا', 'almasara-widgets');
    }

    public function get_icon(): string {
        return 'eicon-help-o';
    }

    public function get_categories(): array {
        return ['almasara', 'woocommerce-elements'];
    }

    public function get_keywords(): array {
        return ['سوال', 'faq', 'پرسش', 'آکاردئون', 'accordion', 'الماسارا'];
    }

    public function get_style_depends(): array {
        return ['almasara-widgets'];
    }

    public function get_script_depends(): array {
        return ['almasara-faq'];
    }

    protected function register_controls(): void {
        $this->register_intro_content_controls(
            __('سوالات متداول مشتریان', 'almasara-widgets'),
            __('پرسش و پاسخ جامع به سوالات پرتکرار مشتریان', 'almasara-widgets')
        );

        /* ---------------- محتوا: سوالات ---------------- */
        $this->start_controls_section('section_faqs', [
            'label' => __('سوالات', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_product_faqs', [
            'label'       => __('سوالات اختصاصی محصول', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => 'yes',
            'description' => __('از متاباکس «سوالات متداول این محصول» در صفحه ویرایش محصول خوانده می‌شود.', 'almasara-widgets'),
        ]);

        $this->add_control('show_widget_faqs', [
            'label'       => __('سوالات عمومی (ریپیتر پایین)', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => 'yes',
            'description' => __('برای همه محصولات یکسان نمایش داده می‌شوند.', 'almasara-widgets'),
        ]);

        $this->add_control('faq_order', [
            'label'     => __('ترتیب نمایش', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'product_first',
            'options'   => [
                'product_first' => __('اول سوالات محصول، بعد عمومی', 'almasara-widgets'),
                'widget_first'  => __('اول سوالات عمومی، بعد محصول', 'almasara-widgets'),
            ],
            'condition' => [
                'show_product_faqs' => 'yes',
                'show_widget_faqs'  => 'yes',
            ],
        ]);

        $repeater = new Repeater();

        $repeater->add_control('q', [
            'label'       => __('سوال', 'almasara-widgets'),
            'type'        => Controls_Manager::TEXT,
            'label_block' => true,
            'dynamic'     => ['active' => true],
        ]);

        $repeater->add_control('a', [
            'label'   => __('پاسخ', 'almasara-widgets'),
            'type'    => Controls_Manager::TEXTAREA,
            'rows'    => 4,
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('faqs', [
            'label'       => __('سوالات عمومی', 'almasara-widgets'),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $repeater->get_controls(),
            'title_field' => '{{{ q }}}',
            'condition'   => ['show_widget_faqs' => 'yes'],
        ]);

        $this->add_control('question_tag', [
            'label'     => __('تگ HTML سوال', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'h3',
            'options'   => ['h3' => 'H3', 'h4' => 'H4', 'div' => 'div'],
            'separator' => 'before',
        ]);

        $this->add_control('accordion_mode', [
            'label'       => __('فقط یک سوال باز باشد', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => 'yes',
        ]);

        $this->add_control('first_open', [
            'label'   => __('سوال اول باز باشد', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => '',
        ]);

        $this->add_control('enable_schema', [
            'label'       => __('اسکیمای FAQPage (سئو)', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => 'yes',
            'description' => __('JSON-LD استاندارد schema.org برای موتورهای جستجو.', 'almasara-widgets'),
        ]);

        $this->end_controls_section();

        /* ---------------- استایل ---------------- */
        $this->register_intro_style_controls();

        $this->start_controls_section('section_style_item', [
            'label' => __('باکس سوال', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('faq_gap', [
            'label'      => __('فاصله بین سوالات', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-faq' => 'row-gap: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('faq_padding', [
            'label'      => __('پدینگ باکس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .amw-faq__q' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->start_controls_tabs('faq_box_tabs');

        $this->start_controls_tab('faq_box_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => 'faq_bg',
            'types'    => ['classic'],
            'selector' => '{{WRAPPER}} .amw-faq__item',
        ]);
        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'faq_border',
            'selector' => '{{WRAPPER}} .amw-faq__item',
        ]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'faq_shadow',
            'selector' => '{{WRAPPER}} .amw-faq__item',
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('faq_box_open', ['label' => __('باز', 'almasara-widgets')]);
        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => 'faq_bg_open',
            'types'    => ['classic'],
            'selector' => '{{WRAPPER}} .amw-faq__item.is-open',
        ]);
        $this->add_control('faq_border_open', [
            'label'     => __('رنگ حاشیه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-faq__item.is-open' => 'border-color: {{VALUE}};'],
        ]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'faq_shadow_open',
            'selector' => '{{WRAPPER}} .amw-faq__item.is-open',
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control('faq_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'separator'  => 'before',
            'selectors'  => [
                '{{WRAPPER}} .amw-faq__item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->end_controls_section();

        /* شماره سوال */
        $this->start_controls_section('section_style_num', [
            'label' => __('شماره سوال', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'num_typography',
            'selector' => '{{WRAPPER}} .amw-faq__num',
        ]);

        $this->add_responsive_control('num_size', [
            'label'      => __('اندازه باکس', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 24, 'max' => 80]],
            'selectors'  => [
                '{{WRAPPER}} .amw-faq__num' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('num_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => ['px' => ['min' => 0, 'max' => 40], '%' => ['min' => 0, 'max' => 50]],
            'selectors'  => ['{{WRAPPER}} .amw-faq__num' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);

        $this->start_controls_tabs('num_tabs');

        $this->start_controls_tab('num_tab_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->add_control('num_color', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-faq__num' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('num_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-faq__num' => 'background-color: {{VALUE}};'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('num_tab_open', ['label' => __('باز', 'almasara-widgets')]);
        $this->add_control('num_color_open', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-faq__item.is-open .amw-faq__num' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('num_bg_open', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-faq__item.is-open .amw-faq__num' => 'background-color: {{VALUE}};'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();

        /* سوال و پاسخ */
        $this->start_controls_section('section_style_qa', [
            'label' => __('متن سوال و پاسخ', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'q_typography',
            'label'    => __('تایپوگرافی سوال', 'almasara-widgets'),
            'selector' => '{{WRAPPER}} .amw-faq__qtext',
        ]);

        $this->add_control('q_color', [
            'label'     => __('رنگ سوال', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-faq__qtext' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('q_color_open', [
            'label'     => __('رنگ سوال (باز)', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-faq__item.is-open .amw-faq__qtext' => 'color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'      => 'a_typography',
            'label'     => __('تایپوگرافی پاسخ', 'almasara-widgets'),
            'selector'  => '{{WRAPPER}} .amw-faq__a-in',
            'separator' => 'before',
        ]);

        $this->add_control('a_color', [
            'label'     => __('رنگ پاسخ', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-faq__a-in' => 'color: {{VALUE}};'],
        ]);

        $this->add_responsive_control('a_padding', [
            'label'      => __('پدینگ پاسخ', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .amw-faq__a-in' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_control('chev_color', [
            'label'     => __('رنگ فلش', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'separator' => 'before',
            'selectors' => ['{{WRAPPER}} .amw-faq__chev' => 'color: {{VALUE}};'],
        ]);

        $this->add_responsive_control('chev_size', [
            'label'      => __('اندازه فلش', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 10, 'max' => 40]],
            'selectors'  => ['{{WRAPPER}} .amw-faq__chev svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->end_controls_section();
    }

    /** تبدیل عدد به رقم فارسی دو رقمی (۰۱، ۰۲، ...) */
    private function fa_number(int $number): string {
        $padded = str_pad((string) $number, 2, '0', STR_PAD_LEFT);
        return strtr($padded, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }

    /** جمع‌آوری سوالات از دو منبع با ترتیب انتخابی */
    private function collect_faqs(array $settings): array {
        $product_faqs = [];
        $widget_faqs  = [];

        if ('yes' === $settings['show_product_faqs']) {
            $product = $this->resolve_product();
            if ($product) {
                $meta = get_post_meta($product->get_id(), '_almasara_faqs', true);
                if (is_array($meta)) {
                    foreach ($meta as $faq) {
                        if (!empty($faq['q']) && !empty($faq['a'])) {
                            $product_faqs[] = ['q' => $faq['q'], 'a' => $faq['a']];
                        }
                    }
                }
            }
        }

        if ('yes' === $settings['show_widget_faqs']) {
            foreach ((array) $settings['faqs'] as $faq) {
                if (!empty($faq['q']) && !empty($faq['a'])) {
                    $widget_faqs[] = ['q' => $faq['q'], 'a' => $faq['a']];
                }
            }
        }

        return 'widget_first' === ($settings['faq_order'] ?? 'product_first')
            ? array_merge($widget_faqs, $product_faqs)
            : array_merge($product_faqs, $widget_faqs);
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $faqs     = $this->collect_faqs($settings);

        if (empty($faqs)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="amw-paw__notice">' . esc_html__('سوالی برای نمایش نیست. در متاباکس محصول یا ریپیتر ویجت سوال اضافه کنید.', 'almasara-widgets') . '</div>';
            }
            return;
        }

        $q_tag      = Utils::validate_html_tag($settings['question_tag']);
        $first_open = 'yes' === $settings['first_open'];

        ?>
        <div class="amw-paw amw-faq-widget">
            <?php $this->render_intro_row($settings, 'none', null); ?>

            <div class="amw-faq" data-accordion="<?php echo 'yes' === $settings['accordion_mode'] ? '1' : '0'; ?>">
                <?php foreach ($faqs as $i => $faq) :
                    $is_open = $first_open && 0 === $i;
                    ?>
                    <div class="amw-faq__item<?php echo $is_open ? ' is-open' : ''; ?>">
                        <<?php echo $q_tag; // phpcs:ignore ?> class="amw-faq__qwrap">
                            <button type="button" class="amw-faq__q" aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>">
                                <span class="amw-faq__num" aria-hidden="true"><?php echo esc_html($this->fa_number($i + 1)); ?></span>
                                <span class="amw-faq__qtext"><?php echo esc_html($faq['q']); ?></span>
                                <span class="amw-faq__chev" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                </span>
                            </button>
                        </<?php echo $q_tag; // phpcs:ignore ?>>
                        <div class="amw-faq__a"<?php echo $is_open ? '' : ' hidden'; ?>>
                            <div class="amw-faq__a-in"><?php echo wp_kses_post(wpautop($faq['a'])); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php

        if ('yes' === $settings['enable_schema'] && !\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            $this->render_schema($faqs);
        }
    }

    /** اسکیمای FAQPage استاندارد schema.org */
    private function render_schema(array $faqs): void {
        $entities = [];
        foreach ($faqs as $faq) {
            $entities[] = [
                '@type'          => 'Question',
                'name'           => wp_strip_all_tags($faq['q']),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => wp_strip_all_tags($faq['a']),
                ],
            ];
        }

        $schema = [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $entities,
        ];

        printf(
            '<script type="application/ld+json">%s</script>',
            wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}
