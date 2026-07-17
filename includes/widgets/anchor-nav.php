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

        $this->add_control('sticky', [
            'label'   => __('چسبان (sticky)', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
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
            'condition'   => ['sticky' => 'yes'],
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
            'selectors' => ['{{WRAPPER}} .amw-nav__item::after' => 'background-color: {{VALUE}};'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control('indicator_height', [
            'label'      => __('ضخامت خط تب فعال', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 1, 'max' => 8]],
            'separator'  => 'before',
            'selectors'  => ['{{WRAPPER}} .amw-nav__item::after' => 'height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('indicator_gap', [
            'label'      => __('فاصله خط از متن', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 20]],
            'selectors'  => ['{{WRAPPER}} .amw-nav__item::after' => 'bottom: -{{SIZE}}{{UNIT}};'],
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
            'class'       => 'amw-nav' . ('yes' === $settings['sticky'] ? ' amw-nav--sticky' : ''),
            'data-offset' => (string) $offset,
        ]);

        ?>
        <div <?php $this->print_render_attribute_string('nav'); ?>>
            <?php if ('' !== $settings['side_title']) : ?>
                <span class="amw-nav__title"><?php echo esc_html($settings['side_title']); ?></span>
            <?php endif; ?>

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
}
