<?php
namespace Almasara_Widgets\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ویجت «امتیاز و دیدگاه کاربران»
 *
 * لیست دیدگاه‌های ووکامرس با کارت کاربر/پاسخ مدیر مطابق دیزاین +
 * دکمه «ثبت دیدگاه» در سطر عنوان که مودال فرم کامل را باز می‌کند
 * (امتیاز اموجی، نکات مثبت/منفی، پیشنهاد خرید، ثبت ناشناس).
 */
class Product_Reviews extends Widget_Base {

    use Traits\Intro_Row;

    public function get_name(): string {
        return 'almasara-product-reviews';
    }

    public function get_title(): string {
        return __('دیدگاه‌های محصول الماسارا', 'almasara-widgets');
    }

    public function get_icon(): string {
        return 'eicon-review';
    }

    public function get_categories(): array {
        return ['almasara', 'woocommerce-elements'];
    }

    public function get_keywords(): array {
        return ['دیدگاه', 'نظر', 'امتیاز', 'review', 'comment', 'rating', 'الماسارا'];
    }

    public function get_style_depends(): array {
        return ['almasara-widgets'];
    }

    public function get_script_depends(): array {
        return ['almasara-reviews'];
    }

    protected function register_controls(): void {
        $this->register_intro_content_controls(
            __('امتیاز و دیدگاه کاربران', 'almasara-widgets'),
            __('دیدگاه خریداران این محصول', 'almasara-widgets')
        );

        /* ---------------- محتوا: دیدگاه‌ها ---------------- */
        $this->start_controls_section('section_reviews', [
            'label' => __('دیدگاه‌ها', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('reviews_limit', [
            'label'   => __('تعداد دیدگاه‌های نمایشی', 'almasara-widgets'),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 1,
            'max'     => 100,
            'default' => 10,
        ]);

        $this->add_control('generic_name', [
            'label'       => __('نام عمومی به‌جای نام واقعی', 'almasara-widgets'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('کاربر سایت', 'almasara-widgets'),
            'description' => __('اگر پر باشد به‌جای نام واقعی کاربران نمایش داده می‌شود (مثل دیزاین). خالی = نام واقعی.', 'almasara-widgets'),
        ]);

        $this->add_control('admin_name', [
            'label'   => __('نام نمایشی پاسخ مدیر', 'almasara-widgets'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('مدیر سایت', 'almasara-widgets'),
        ]);

        $this->add_control('show_pros_cons', [
            'label'   => __('نمایش نکات مثبت/منفی', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('empty_text', [
            'label'   => __('متن حالت خالی', 'almasara-widgets'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('هنوز دیدگاهی برای این محصول ثبت نشده است؛ اولین نفر باشید!', 'almasara-widgets'),
        ]);

        $this->end_controls_section();

        /* ---------------- محتوا: دکمه و مودال ثبت ---------------- */
        $this->start_controls_section('section_submit', [
            'label' => __('ثبت دیدگاه', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_button', [
            'label'   => __('دکمه ثبت دیدگاه در سطر عنوان', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('button_text', [
            'label'     => __('متن دکمه', 'almasara-widgets'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('ثبت دیدگاه', 'almasara-widgets'),
            'condition' => ['show_button' => 'yes'],
        ]);

        $this->add_control('allow_anonymous', [
            'label'     => __('گزینه «کاربر ناشناس»', 'almasara-widgets'),
            'type'      => Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'condition' => ['show_button' => 'yes'],
        ]);

        $this->end_controls_section();

        /* ---------------- استایل ---------------- */
        $this->register_intro_style_controls();
        $this->register_button_style_controls();
        $this->register_cards_style_controls();
        $this->register_modal_style_controls();
    }

    private function register_button_style_controls(): void {
        $this->start_controls_section('section_style_button', [
            'label'     => __('دکمه ثبت دیدگاه', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_button' => 'yes'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'btn_typography',
            'selector' => '{{WRAPPER}} .amw-rv__open',
        ]);

        $this->add_responsive_control('btn_padding', [
            'label'      => __('پدینگ', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .amw-rv__open' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('btn_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 50]],
            'selectors'  => ['{{WRAPPER}} .amw-rv__open' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);

        $this->start_controls_tabs('btn_tabs');

        $this->start_controls_tab('btn_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->add_control('btn_color', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-rv__open' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('btn_bg', [
            'label'     => __('پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-rv__open' => 'background-color: {{VALUE}};'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('btn_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->add_control('btn_color_hover', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-rv__open:hover' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('btn_bg_hover', [
            'label'     => __('پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-rv__open:hover' => 'background-color: {{VALUE}};'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    private function register_cards_style_controls(): void {
        $this->start_controls_section('section_style_cards', [
            'label' => __('کارت دیدگاه', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('card_gap', [
            'label'      => __('فاصله بین کارت‌ها', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 80]],
            'selectors'  => ['{{WRAPPER}} .amw-rv__list' => 'row-gap: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'card_border',
            'selector' => '{{WRAPPER}} .amw-rv__card',
        ]);

        $this->add_responsive_control('card_radius', [
            'label'      => __('رادیوس کارت', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors'  => [
                '{{WRAPPER}} .amw-rv__card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'card_shadow',
            'selector' => '{{WRAPPER}} .amw-rv__card',
        ]);

        $this->add_control('heading_user_card', [
            'label'     => __('هدر کارت کاربر', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('user_head_bg', [
            'label'     => __('پس‌زمینه هدر', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-rv__card--user .amw-rv__head' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('user_head_color', [
            'label'     => __('رنگ متن هدر', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-rv__card--user .amw-rv__head' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('heading_admin_card', [
            'label'     => __('هدر پاسخ مدیر', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('admin_head_bg', [
            'label'     => __('پس‌زمینه هدر', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-rv__card--admin .amw-rv__head' => 'background-color: {{VALUE}};',
                '{{WRAPPER}} .amw-rv__reply-badge' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_control('admin_head_color', [
            'label'     => __('رنگ متن هدر', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-rv__card--admin .amw-rv__head' => 'color: {{VALUE}};',
                '{{WRAPPER}} .amw-rv__reply-badge' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('heading_card_text', [
            'label'     => __('متن دیدگاه', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'review_typography',
            'selector' => '{{WRAPPER}} .amw-rv__text',
        ]);

        $this->add_control('review_color', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-rv__text' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('star_color', [
            'label'     => __('رنگ ستاره پر', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#f6b40e',
            'selectors' => ['{{WRAPPER}} .amw-rv__star.is-on' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('star_empty_color', [
            'label'     => __('رنگ ستاره خالی', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#d4d7de',
            'selectors' => ['{{WRAPPER}} .amw-rv__star' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('pros_color', [
            'label'     => __('رنگ نکات مثبت', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#1eaa59',
            'selectors' => ['{{WRAPPER}} .amw-rv__pros li::before' => 'color: {{VALUE}};'],
            'condition' => ['show_pros_cons' => 'yes'],
        ]);

        $this->add_control('cons_color', [
            'label'     => __('رنگ نکات منفی', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#e5484d',
            'selectors' => ['{{WRAPPER}} .amw-rv__cons li::before' => 'color: {{VALUE}};'],
            'condition' => ['show_pros_cons' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    private function register_modal_style_controls(): void {
        $this->start_controls_section('section_style_modal', [
            'label'     => __('مودال ثبت دیدگاه', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_button' => 'yes'],
        ]);

        $this->add_control('rmodal_backdrop', [
            'label'     => __('رنگ پس‌زمینه پشت مودال', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-rv-modal' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('rmodal_bg', [
            'label'     => __('پس‌زمینه کادر فرم', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-rv-modal__sheet' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_responsive_control('rmodal_width', [
            'label'      => __('حداکثر عرض فرم', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 300, 'max' => 800]],
            'selectors'  => ['{{WRAPPER}} .amw-rv-modal__sheet' => 'max-width: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('rmodal_radius', [
            'label'      => __('رادیوس کادر', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ['{{WRAPPER}} .amw-rv-modal__sheet' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('emoji_size', [
            'label'      => __('اندازه اموجی امتیاز', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 24, 'max' => 72]],
            'selectors'  => ['{{WRAPPER}} .amw-rv-form__emoji span' => 'font-size: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('submit_bg', [
            'label'     => __('پس‌زمینه دکمه ثبت', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-rv-form__submit' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('submit_color', [
            'label'     => __('رنگ متن دکمه ثبت', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-rv-form__submit' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('input_border_color', [
            'label'     => __('رنگ کادر فیلدها', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-rv-form input[type="text"], {{WRAPPER}} .amw-rv-form input[type="email"], {{WRAPPER}} .amw-rv-form textarea' => 'border-color: {{VALUE}};',
            ],
        ]);

        $this->end_controls_section();
    }

    /* ------------------------------------------------------------------ */

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $product  = $this->resolve_product();

        if (!$product) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="amw-paw__notice">' . esc_html__('محصولی برای پیش‌نمایش پیدا نشد.', 'almasara-widgets') . '</div>';
            }
            return;
        }

        $reviews = get_comments([
            'post_id' => $product->get_id(),
            'status'  => 'approve',
            'parent'  => 0,
            'type'    => 'review',
            'number'  => max(1, (int) $settings['reviews_limit']),
        ]);

        $this->add_render_attribute('wrapper', [
            'class'         => 'amw-paw amw-rv',
            'data-endpoint' => esc_url_raw(rest_url('almasara/v1/reviews')),
            'data-nonce'    => wp_create_nonce('wp_rest'),
        ]);

        ?>
        <div <?php $this->print_render_attribute_string('wrapper'); ?>>

            <div class="amw-rv__headwrap">
                <?php $this->render_intro_row($settings, 'none', null); ?>
                <?php if ('yes' === $settings['show_button']) : ?>
                    <button type="button" class="amw-rv__open"><?php echo esc_html($settings['button_text']); ?></button>
                <?php endif; ?>
            </div>

            <?php if (empty($reviews)) : ?>
                <p class="amw-rv__empty"><?php echo esc_html($settings['empty_text']); ?></p>
            <?php else : ?>
                <div class="amw-rv__list">
                    <?php foreach ($reviews as $review) :
                        $this->render_card($review, $settings);
                        foreach (get_comments(['parent' => $review->comment_ID, 'status' => 'approve']) as $reply) {
                            $this->render_card($reply, $settings, true);
                        }
                    endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ('yes' === $settings['show_button']) : ?>
                <?php $this->render_modal($product, $settings); ?>
            <?php endif; ?>

        </div>
        <?php
    }

    /** کارت یک دیدگاه یا پاسخ */
    private function render_card(\WP_Comment $comment, array $settings, bool $is_reply = false): void {
        $is_admin = $comment->user_id && user_can((int) $comment->user_id, 'edit_products');

        if ($is_admin) {
            $name = $settings['admin_name'];
        } elseif (get_comment_meta($comment->comment_ID, '_amw_anonymous', true)) {
            $name = __('کاربر ناشناس', 'almasara-widgets');
        } else {
            $name = '' !== trim((string) $settings['generic_name']) ? $settings['generic_name'] : $comment->comment_author;
        }

        $rating = (int) get_comment_meta($comment->comment_ID, 'rating', true);
        $pros   = (array) get_comment_meta($comment->comment_ID, '_amw_pros', true);
        $cons   = (array) get_comment_meta($comment->comment_ID, '_amw_cons', true);

        ?>
        <article class="amw-rv__card amw-rv__card--<?php echo $is_admin ? 'admin' : 'user'; ?><?php echo $is_reply ? ' amw-rv__card--reply' : ''; ?>">
            <header class="amw-rv__head">
                <span class="amw-rv__author"><?php echo esc_html($name); ?></span>
                <time class="amw-rv__date" datetime="<?php echo esc_attr(get_comment_date('c', $comment)); ?>"><?php echo esc_html(get_comment_date('', $comment)); ?></time>
            </header>

            <?php if ($is_admin && $is_reply) : ?>
                <span class="amw-rv__reply-badge" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 17H5a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h11"/><path d="m12 3 4 4-4 4"/></svg>
                </span>
            <?php endif; ?>

            <div class="amw-rv__body">
                <div class="amw-rv__text"><?php echo wp_kses_post(wpautop($comment->comment_content)); ?></div>

                <?php if ('yes' === $settings['show_pros_cons'] && array_filter($pros)) : ?>
                    <ul class="amw-rv__pros">
                        <?php foreach ($pros as $pro) : ?>
                            <li><?php echo esc_html($pro); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ('yes' === $settings['show_pros_cons'] && array_filter($cons)) : ?>
                    <ul class="amw-rv__cons">
                        <?php foreach ($cons as $con) : ?>
                            <li><?php echo esc_html($con); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="amw-rv__foot">
                    <?php if (!is_user_logged_in()) : ?>
                        <a class="amw-rv__login" href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" rel="nofollow"><?php esc_html_e('برای پاسخ دادن وارد شوید', 'almasara-widgets'); ?></a>
                    <?php endif; ?>

                    <?php if (!$is_reply && $rating > 0) : ?>
                        <span class="amw-rv__stars" role="img" aria-label="<?php echo esc_attr(sprintf(__('امتیاز %d از 5', 'almasara-widgets'), $rating)); ?>">
                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                <span class="amw-rv__star<?php echo $i <= $rating ? ' is-on' : ''; ?>" aria-hidden="true">★</span>
                            <?php endfor; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </article>
        <?php
    }

    /** مودال فرم ثبت دیدگاه */
    private function render_modal($product, array $settings): void {
        $needs_login = get_option('comment_registration') && !is_user_logged_in();
        ?>
        <div class="amw-rv-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-label="<?php echo esc_attr__('ثبت دیدگاه', 'almasara-widgets'); ?>" data-product="<?php echo esc_attr($product->get_id()); ?>">
            <div class="amw-rv-modal__sheet">
                <div class="amw-rv-modal__bar">
                    <span class="amw-rv-modal__title"><?php esc_html_e('ثبت دیدگاه', 'almasara-widgets'); ?></span>
                    <button type="button" class="amw-rv-modal__close" aria-label="<?php echo esc_attr__('بستن', 'almasara-widgets'); ?>">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="amw-rv-modal__product">
                    <?php echo wp_get_attachment_image($product->get_image_id(), 'thumbnail'); ?>
                    <span><?php echo esc_html($product->get_name()); ?></span>
                </div>

                <?php if ($needs_login) : ?>
                    <div class="amw-rv-form__login">
                        <p><?php esc_html_e('برای ثبت دیدگاه ابتدا وارد حساب کاربری خود شوید.', 'almasara-widgets'); ?></p>
                        <a class="amw-rv-form__submit" href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" rel="nofollow"><?php esc_html_e('ورود به حساب', 'almasara-widgets'); ?></a>
                    </div>
                <?php else : ?>
                    <form class="amw-rv-form" novalidate>
                        <p class="amw-rv-form__label amw-rv-form__label--center"><?php esc_html_e('به این کالا امتیاز دهید :)', 'almasara-widgets'); ?> <b>*</b></p>
                        <div class="amw-rv-form__emoji" role="radiogroup" aria-label="<?php echo esc_attr__('امتیاز', 'almasara-widgets'); ?>">
                            <?php
                            $moods = [
                                5 => ['😍', __('عالی', 'almasara-widgets')],
                                4 => ['😄', __('خوب', 'almasara-widgets')],
                                3 => ['🙂', __('معمولی', 'almasara-widgets')],
                                2 => ['😕', __('ضعیف', 'almasara-widgets')],
                                1 => ['😢', __('بد', 'almasara-widgets')],
                            ];
                            foreach ($moods as $value => $mood) : ?>
                                <label>
                                    <input type="radio" name="amw_rating" value="<?php echo esc_attr($value); ?>" <?php checked(5, $value); ?> />
                                    <span><?php echo esc_html($mood[0]); ?></span>
                                    <small><?php echo esc_html($mood[1]); ?></small>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <?php if (!is_user_logged_in()) : ?>
                            <div class="amw-rv-form__identity">
                                <input type="text" name="amw_author" placeholder="<?php echo esc_attr__('نام شما *', 'almasara-widgets'); ?>" />
                                <input type="email" name="amw_email" placeholder="<?php echo esc_attr__('ایمیل شما *', 'almasara-widgets'); ?>" />
                            </div>
                        <?php endif; ?>

                        <div class="amw-rv-form__points" data-kind="pros">
                            <p class="amw-rv-form__label"><?php esc_html_e('نکات مثبت', 'almasara-widgets'); ?></p>
                            <div class="amw-rv-form__pointrow">
                                <input type="text" placeholder="<?php echo esc_attr__('نکته مثبت را بنویسید و + را بزنید', 'almasara-widgets'); ?>" />
                                <button type="button" class="amw-rv-form__add" aria-label="<?php echo esc_attr__('افزودن', 'almasara-widgets'); ?>">+</button>
                            </div>
                            <ul class="amw-rv-form__pointlist"></ul>
                        </div>

                        <div class="amw-rv-form__points" data-kind="cons">
                            <p class="amw-rv-form__label"><?php esc_html_e('نکات منفی', 'almasara-widgets'); ?></p>
                            <div class="amw-rv-form__pointrow">
                                <input type="text" placeholder="<?php echo esc_attr__('نکته منفی را بنویسید و + را بزنید', 'almasara-widgets'); ?>" />
                                <button type="button" class="amw-rv-form__add" aria-label="<?php echo esc_attr__('افزودن', 'almasara-widgets'); ?>">+</button>
                            </div>
                            <ul class="amw-rv-form__pointlist"></ul>
                        </div>

                        <p class="amw-rv-form__label"><?php esc_html_e('نظر شما', 'almasara-widgets'); ?> <b>*</b></p>
                        <textarea name="amw_comment" rows="4" required></textarea>

                        <p class="amw-rv-form__label"><?php esc_html_e('آیا خرید این محصول را پیشنهاد میکنید؟', 'almasara-widgets'); ?></p>
                        <div class="amw-rv-form__recommend">
                            <label><input type="radio" name="amw_recommend" value="yes" /> <?php esc_html_e('توصیه میکنم', 'almasara-widgets'); ?></label>
                            <label><input type="radio" name="amw_recommend" value="no" /> <?php esc_html_e('خیر، پیشنهاد نمی‌کنم', 'almasara-widgets'); ?></label>
                            <label><input type="radio" name="amw_recommend" value="neutral" /> <?php esc_html_e('نظری ندارم', 'almasara-widgets'); ?></label>
                        </div>

                        <div class="amw-rv-form__foot">
                            <button type="submit" class="amw-rv-form__submit"><?php esc_html_e('ثبت نظر', 'almasara-widgets'); ?></button>
                            <?php if ('yes' === $settings['allow_anonymous']) : ?>
                                <label class="amw-rv-form__anon"><input type="checkbox" name="amw_anonymous" value="1" /> <?php esc_html_e('کاربر ناشناس', 'almasara-widgets'); ?></label>
                            <?php endif; ?>
                        </div>

                        <div class="amw-rv-form__message" role="status" aria-live="polite"></div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
