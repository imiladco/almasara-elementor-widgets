<?php
namespace Almasara_Widgets\Widgets\Traits;

use Elementor\Controls_Manager;
use Elementor\Utils;
use Elementor\Group_Control_Typography;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * سطر معرفی مشترک بین ویجت‌های الماسارا:
 * عنوان + خط جداکننده + آیکون، به‌همراه کنترل‌های چیدمان و استایل و رندر آن.
 */
trait Intro_Row {

    /* ---------------------------------------------------------------------
     * گزینه‌های مشترک کنترل‌های چیدمان
     * ------------------------------------------------------------------- */

    private function layout_direction_options(): array {
        $start = is_rtl() ? 'right' : 'left';
        $end   = is_rtl() ? 'left' : 'right';

        return [
            'row' => [
                'title' => __('سطری', 'almasara-widgets'),
                'icon'  => 'eicon-arrow-' . $end,
            ],
            'column' => [
                'title' => __('ستونی', 'almasara-widgets'),
                'icon'  => 'eicon-arrow-down',
            ],
            'row-reverse' => [
                'title' => __('سطری معکوس', 'almasara-widgets'),
                'icon'  => 'eicon-arrow-' . $start,
            ],
            'column-reverse' => [
                'title' => __('ستونی معکوس', 'almasara-widgets'),
                'icon'  => 'eicon-arrow-up',
            ],
        ];
    }

    private function layout_justify_options(): array {
        return [
            'flex-start' => [
                'title' => __('ابتدا', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-justify-start-h',
            ],
            'center' => [
                'title' => __('وسط', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-justify-center-h',
            ],
            'flex-end' => [
                'title' => __('انتها', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-justify-end-h',
            ],
            'space-between' => [
                'title' => __('فاصله بین', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-justify-space-between-h',
            ],
            'space-around' => [
                'title' => __('فاصله اطراف', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-justify-space-around-h',
            ],
            'space-evenly' => [
                'title' => __('فاصله مساوی', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-justify-space-evenly-h',
            ],
        ];
    }

    private function layout_align_options(): array {
        return [
            'flex-start' => [
                'title' => __('ابتدا', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-align-start-v',
            ],
            'center' => [
                'title' => __('وسط', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-align-center-v',
            ],
            'flex-end' => [
                'title' => __('انتها', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-align-end-v',
            ],
            'stretch' => [
                'title' => __('کشیده', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-align-stretch-v',
            ],
        ];
    }

    private function layout_wrap_options(): array {
        return [
            'nowrap' => [
                'title' => __('در یک خط بماند', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-nowrap',
            ],
            'wrap' => [
                'title' => __('به چند خط تقسیم شود', 'almasara-widgets'),
                'icon'  => 'eicon-flex eicon-wrap',
            ],
        ];
    }

    /* ---------------------------------------------------------------------
     * محتوا: بخش معرفی
     * ------------------------------------------------------------------- */

    private function register_intro_content_controls(string $default_title): void {
        $this->start_controls_section('section_intro', [
            'label' => __('معرفی', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('title', [
            'label'       => __('عنوان', 'almasara-widgets'),
            'type'        => Controls_Manager::TEXT,
            'default'     => $default_title,
            'dynamic'     => ['active' => true],
            'label_block' => true,
        ]);

        $this->add_control('title_tag', [
            'label'   => __('تگ', 'almasara-widgets'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'h3',
            'options' => [
                'h2'   => 'H2',
                'h3'   => 'H3',
                'h4'   => 'H4',
                'h5'   => 'H5',
                'h6'   => 'H6',
                'div'  => 'div',
                'span' => 'span',
            ],
        ]);

        $this->add_control('show_divider', [
            'label'   => __('خط جداکننده', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('icon_image', [
            'label'       => __('آیکون', 'almasara-widgets'),
            'type'        => Controls_Manager::MEDIA,
            'media_types' => ['image', 'svg'],
            'dynamic'     => ['active' => true],
            'description' => __('تصویر یا SVG انتخاب کنید. SVG به‌صورت inline رندر می‌شود و رنگش از تب استایل قابل تغییر است.', 'almasara-widgets'),
        ]);

        $this->end_controls_section();
    }

    /* ---------------------------------------------------------------------
     * محتوا: کنترل‌های چیدمان سطر معرفی (بدون section — داخل بخش چیدمان ویجت)
     * ------------------------------------------------------------------- */

    private function register_header_layout_controls(): void {
        $this->add_control('heading_header_layout', [
            'label' => __('سطر معرفی', 'almasara-widgets'),
            'type'  => Controls_Manager::HEADING,
        ]);

        $this->add_responsive_control('header_direction', [
            'label'     => __('جهت', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => $this->layout_direction_options(),
            'selectors' => [
                '{{WRAPPER}} .amw-paw__header' => 'flex-direction: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('header_justify', [
            'label'     => __('تراز کردن محتوا', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => $this->layout_justify_options(),
            'selectors' => [
                '{{WRAPPER}} .amw-paw__header' => 'justify-content: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('header_align', [
            'label'     => __('تراز موارد', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => $this->layout_align_options(),
            'selectors' => [
                '{{WRAPPER}} .amw-paw__header' => 'align-items: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('header_gaps', [
            'label'      => __('شکاف‌ها', 'almasara-widgets'),
            'type'       => Controls_Manager::GAPS,
            'size_units' => ['px', '%', 'em', 'rem'],
            'default'    => ['unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .amw-paw__header' => 'gap: {{ROW}}{{UNIT}} {{COLUMN}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('header_wrap', [
            'label'       => __('Wrap', 'almasara-widgets'),
            'type'        => Controls_Manager::CHOOSE,
            'options'     => $this->layout_wrap_options(),
            'description' => __('اقلام داخل سطر می‌توانند در یک خط باقی بمانند (No wrap)، یا به چند خط تقسیم شوند (Wrap).', 'almasara-widgets'),
            'selectors'   => [
                '{{WRAPPER}} .amw-paw__header' => 'flex-wrap: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('header_spacing', [
            'label'      => __('فاصله تا محتوای بعدی', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 100]],
            'selectors'  => [
                '{{WRAPPER}} .amw-paw__header' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ]);
    }

    /* ---------------------------------------------------------------------
     * استایل: تب معرفی
     * ------------------------------------------------------------------- */

    private function register_intro_style_controls(): void {
        $this->start_controls_section('section_style_intro', [
            'label' => __('معرفی', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'title_typography',
            'label'    => __('تایپوگرافی عنوان', 'almasara-widgets'),
            'selector' => '{{WRAPPER}} .amw-paw__title',
        ]);

        $this->add_control('divider_style', [
            'label'     => __('نوع خط جداکننده', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'dashed',
            'options'   => [
                'solid'  => __('یک‌دست', 'almasara-widgets'),
                'dashed' => __('خط‌چین', 'almasara-widgets'),
                'dotted' => __('نقطه‌چین', 'almasara-widgets'),
                'double' => __('دوخطی', 'almasara-widgets'),
            ],
            'selectors' => [
                '{{WRAPPER}} .amw-paw__divider' => 'border-top-style: {{VALUE}};',
            ],
            'condition' => ['show_divider' => 'yes'],
        ]);

        $this->add_responsive_control('divider_weight', [
            'label'      => __('ضخامت', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 1, 'max' => 10]],
            'selectors'  => [
                '{{WRAPPER}} .amw-paw__divider' => 'border-top-width: {{SIZE}}{{UNIT}};',
            ],
            'condition'  => ['show_divider' => 'yes'],
        ]);

        $this->add_responsive_control('icon_size', [
            'label'      => __('سایز آیکون', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 8, 'max' => 200]],
            'default'    => ['size' => 28, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .amw-paw' => '--amw-icon-size: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('icon_padding', [
            'label'      => __('پدینگ آیکون', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .amw-paw__icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        /*
         * رنگ‌های سطر معرفی — دو تب عادی/هاور.
         * رنگ SVG خودکار اعمال می‌شود: «رنگ آیکون» فقط بخش‌های دارای fill را
         * بازرنگ می‌کند و «رنگ خطوط آیکون» فقط بخش‌های دارای stroke را.
         * هرکدام خالی بماند، رنگ اصلی همان بخش حفظ می‌شود.
         */
        $this->add_control('intro_colors_divider', [
            'type' => Controls_Manager::DIVIDER,
        ]);

        $this->start_controls_tabs('intro_color_tabs');

        $this->start_controls_tab('intro_colors_normal', ['label' => __('عادی', 'almasara-widgets')]);

        $this->add_control('title_color', [
            'label'     => __('رنگ عنوان', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__title' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('divider_color', [
            'label'     => __('رنگ خط جداکننده', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__divider' => 'border-top-color: {{VALUE}};',
            ],
            'condition' => ['show_divider' => 'yes'],
        ]);

        $this->add_control('icon_fill_color', [
            'label'       => __('رنگ آیکون', 'almasara-widgets'),
            'type'        => Controls_Manager::COLOR,
            'description' => __('بخش‌های توپُر (fill) را بازرنگ می‌کند؛ خالی بماند تا رنگ اصلی فایل حفظ شود.', 'almasara-widgets'),
            'selectors'   => [
                '{{WRAPPER}} .amw-paw__icon' => 'color: {{VALUE}};',
                '{{WRAPPER}} .amw-paw__icon svg [fill]:not([fill="none"]):not([fill="transparent"])' => 'fill: {{VALUE}};',
                '{{WRAPPER}} .amw-paw__icon svg :is(path,circle,rect,ellipse,polygon,polyline,line):not([fill]):not([stroke])' => 'fill: {{VALUE}};',
            ],
        ]);

        $this->add_control('icon_stroke_color', [
            'label'       => __('رنگ خطوط آیکون', 'almasara-widgets'),
            'type'        => Controls_Manager::COLOR,
            'description' => __('بخش‌های خطی (stroke) را بازرنگ می‌کند؛ خالی بماند تا رنگ اصلی فایل حفظ شود.', 'almasara-widgets'),
            'selectors'   => [
                '{{WRAPPER}} .amw-paw__icon svg [stroke]:not([stroke="none"]):not([stroke="transparent"])' => 'stroke: {{VALUE}};',
            ],
        ]);

        $this->end_controls_tab();

        $this->start_controls_tab('intro_colors_hover', ['label' => __('هاور', 'almasara-widgets')]);

        $this->add_control('title_color_hover', [
            'label'     => __('رنگ عنوان', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__header:hover .amw-paw__title' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('divider_color_hover', [
            'label'     => __('رنگ خط جداکننده', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__header:hover .amw-paw__divider' => 'border-top-color: {{VALUE}};',
            ],
            'condition' => ['show_divider' => 'yes'],
        ]);

        $this->add_control('icon_fill_color_hover', [
            'label'     => __('رنگ آیکون', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__header:hover .amw-paw__icon' => 'color: {{VALUE}};',
                '{{WRAPPER}} .amw-paw__header:hover .amw-paw__icon svg [fill]:not([fill="none"]):not([fill="transparent"])' => 'fill: {{VALUE}};',
                '{{WRAPPER}} .amw-paw__header:hover .amw-paw__icon svg :is(path,circle,rect,ellipse,polygon,polyline,line):not([fill]):not([stroke])' => 'fill: {{VALUE}};',
            ],
        ]);

        $this->add_control('icon_stroke_color_hover', [
            'label'     => __('رنگ خطوط آیکون', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amw-paw__header:hover .amw-paw__icon svg [stroke]:not([stroke="none"]):not([stroke="transparent"])' => 'stroke: {{VALUE}};',
            ],
        ]);

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    /* ---------------------------------------------------------------------
     * رندر سطر معرفی
     * ------------------------------------------------------------------- */

    private function render_intro_row(array $settings, string $scope, ?array $global_link): void {
        $header_tag = 'div';
        $this->add_render_attribute('header', 'class', 'amw-paw__header');
        if ('header' === $scope && $global_link) {
            $header_tag = 'a';
            $this->add_link_attributes('header', $global_link);
        }

        $title_tag = Utils::validate_html_tag($settings['title_tag']);

        ?>
        <<?php echo $header_tag; // phpcs:ignore ?> <?php $this->print_render_attribute_string('header'); ?>>
            <?php if ('' !== $settings['title']) : ?>
                <<?php echo $title_tag; // phpcs:ignore ?> class="amw-paw__title">
                    <?php if ('title' === $scope && $global_link) : ?>
                        <?php $this->add_link_attributes('title-link', $global_link); ?>
                        <a <?php $this->print_render_attribute_string('title-link'); ?>><?php echo esc_html($settings['title']); ?></a>
                    <?php else : ?>
                        <?php echo esc_html($settings['title']); ?>
                    <?php endif; ?>
                </<?php echo $title_tag; // phpcs:ignore ?>>
            <?php endif; ?>

            <?php if ('yes' === $settings['show_divider']) : ?>
                <span class="amw-paw__divider" aria-hidden="true"></span>
            <?php endif; ?>

            <?php $this->render_icon($settings); ?>
        </<?php echo $header_tag; // phpcs:ignore ?>>
        <?php
    }

    /** رندر آیکون؛ SVG همیشه inline و پاک‌سازی‌شده درج می‌شود */
    private function render_icon(array $settings): void {
        if (empty($settings['icon_image']['url'])) {
            return;
        }

        $url    = $settings['icon_image']['url'];
        $is_svg = 'svg' === strtolower(pathinfo(wp_parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

        echo '<span class="amw-paw__icon">';

        $svg = '';
        if ($is_svg && !empty($settings['icon_image']['id'])) {
            $svg = $this->get_inline_svg((int) $settings['icon_image']['id']);
        }

        if ($svg) {
            echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput -- sanitized in get_inline_svg()
        } else {
            printf('<img src="%s" alt="">', esc_url($url));
        }

        echo '</span>';
    }

    /** خواندن و پاک‌سازی فایل SVG برای درج inline */
    private function get_inline_svg(int $attachment_id): string {
        $path = get_attached_file($attachment_id);
        if (!$path || !file_exists($path)) {
            return '';
        }

        $svg = file_get_contents($path);
        if (false === $svg || false === stripos($svg, '<svg')) {
            return '';
        }

        // پاک‌سازی امنیتی: حذف اسکریپت‌ها، رویدادها و لینک‌های خطرناک
        $svg = preg_replace('/<\?xml.*?\?>/is', '', $svg);
        $svg = preg_replace('/<!DOCTYPE.*?>/is', '', $svg);
        $svg = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $svg);
        $svg = preg_replace('/<foreignObject\b[^>]*>.*?<\/foreignObject>/is', '', $svg);
        $svg = preg_replace('/\son\w+\s*=\s*(["\']).*?\1/is', '', $svg);
        $svg = preg_replace('/\s(?:xlink:)?href\s*=\s*(["\'])\s*(?:javascript|data):.*?\1/is', '', $svg);

        return trim($svg);
    }

    /** پیدا کردن محصول: صفحه محصول جاری، وگرنه آخرین محصول برای پیش‌نمایش ادیتور */
    private function resolve_product() {
        if (!function_exists('wc_get_product')) {
            return false;
        }

        $post_id = get_the_ID();
        if ($post_id && 'product' === get_post_type($post_id)) {
            return wc_get_product($post_id);
        }

        $is_preview = \Elementor\Plugin::$instance->editor->is_edit_mode() || is_preview()
            || (\Elementor\Plugin::$instance->preview && \Elementor\Plugin::$instance->preview->is_preview_mode());

        if (!$is_preview) {
            return false;
        }

        $latest = wc_get_products(['limit' => 1, 'orderby' => 'date', 'order' => 'DESC', 'status' => 'publish']);
        return $latest ? $latest[0] : false;
    }
}
