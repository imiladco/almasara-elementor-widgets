<?php
namespace Almasara_Widgets\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Almasara_Widgets\Product_Section\Query;
use Almasara_Widgets\Product_Section\Rest;
use Almasara_Widgets\Product_Section\Settings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ویجت «بخش محصولات» — عنوان + دکمهٔ مشاهده همه + فیلتر پیلی دسته‌بندی
 * (AJAX زنده) + اسلایدر کارت محصول.
 *
 * کارت از دو منبع می‌آید: قالب Listing جت‌انجین، یا کارت داخلی خودِ افزونه.
 *
 * تقسیم مسئولیت‌ها:
 *   • Settings  تنظیمات خام المنتور را به پیکربندی معتبر تبدیل می‌کند
 *   • Query     کوئری محصولات، فیلترها، کش و رندر کارت‌ها
 *   • Rest      endpoint فیلتر دسته‌بندی و بی‌اعتبارسازی کش
 *   • تِریت‌ها   ثبت کنترل‌های تب محتوا و تب استایل
 * این کلاس فقط ویجت را به هم وصل و مارکاپ را چاپ می‌کند.
 */
class Product_Section extends Widget_Base {

    use Traits\Intro_Row;                    // گزینه‌های چیدمان مشترک
    use Product_Section\Content_Controls;
    use Product_Section\Style_Controls;

    public function get_name(): string {
        return 'almasara-product-section';
    }

    public function get_title(): string {
        return __('بخش محصولات الماسارا', 'almasara-widgets');
    }

    public function get_icon(): string {
        return 'eicon-product-related';
    }

    public function get_categories(): array {
        return ['almasara'];
    }

    public function get_keywords(): array {
        return ['محصولات', 'دسته‌بندی', 'اسلایدر', 'products', 'category', 'jetengine', 'الماسارا'];
    }

    public function get_style_depends(): array {
        return ['almasara-swiper', 'almasara-widgets'];
    }

    public function get_script_depends(): array {
        return ['almasara-swiper', 'almasara-product-section'];
    }

    /* =====================================================================
     * کنترل‌ها
     * =================================================================== */

    protected function register_controls(): void {
        $this->register_header_content_controls();
        $this->register_categories_content_controls();
        $this->register_source_content_controls();
        $this->register_card_content_controls();
        $this->register_slider_content_controls();

        $this->register_layout_style_controls();
        $this->register_header_style_controls();
        $this->register_button_style_controls();
        $this->register_pills_style_controls();
        $this->register_card_style_controls();
        $this->register_card_item_style_controls();
        $this->register_card_image_style_controls();
        $this->register_card_title_style_controls();
        $this->register_card_price_style_controls();
        $this->register_nav_style_controls();
        $this->register_pagination_style_controls();
    }

    /* =====================================================================
     * رندر
     * =================================================================== */

    protected function render(): void {
        $settings   = $this->get_settings_for_display();
        $is_editing = Settings::is_editing();
        $query_args = Settings::query($settings, $is_editing);

        // فقط مسیر جت‌انجین به قالب Listing نیاز دارد
        if (Settings::SOURCE_JETENGINE === $query_args['source'] && !$query_args['listing_id']) {
            if ($is_editing) {
                $this->render_notice(__('یک قالب Listing جت‌انجین برای کارت محصول انتخاب کنید، یا در همان بخش «کارت محصول از» را روی «کارت داخلی این افزونه» بگذارید.', 'almasara-widgets'));
            }
            return;
        }

        $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');

        printf('<div class="amw-ps" data-cfg="%s">', esc_attr(wp_json_encode($this->build_js_config($settings, $query_args))));

        $this->render_header($settings, $shop_url, $query_args);
        $this->render_slider($settings, $query_args);

        // پیجینیشن عمداً بیرون از slider-wrap است: هم از باکس برش سوایپر خارج
        // می‌ماند، هم ارتفاعش وارد محاسبهٔ «top:50%» دکمه‌های ناوبری نمی‌شود
        // تا دکمه‌ها همیشه دقیقاً وسط کارت‌ها بایستند.
        if ('yes' === ($settings['show_pagination'] ?? '')) {
            echo '<div class="swiper-pagination amw-ps__pagination"></div>';
        }

        echo '</div>'; // .amw-ps
    }

    /**
     * پیکربندی‌ای که به JS داده می‌شود.
     *
     * عمداً هیچ پارامتر گرانی اینجا نیست: برای فیلتر ایجکسی فقط «کدام ویجت»
     * و «کدام دسته» فرستاده می‌شود و سرور خودش تنظیمات ذخیره‌شده را می‌خواند.
     * count فقط برای تعداد اسکلت‌های حالت بارگذاری است و به سرور نمی‌رود.
     */
    private function build_js_config(array $settings, array $query_args): array {
        return Settings::slider($settings) + [
            'restUrl'   => esc_url_raw(Rest::url()),
            'postId'    => $this->owner_post_id(),
            'elementId' => $this->get_id(),
            'count'     => $query_args['count'],
        ];
    }

    /** شناسهٔ سندی که این ویجت در آن ذخیره شده (صفحه، قالب یا Listing) */
    private function owner_post_id(): int {
        if (class_exists('\Elementor\Plugin')) {
            $document = \Elementor\Plugin::$instance->documents->get_current();
            if ($document) {
                return (int) $document->get_main_id();
            }
        }

        return (int) get_the_ID();
    }

    /** هدر: عنوان + پیل‌های دسته‌بندی + دکمهٔ مشاهده همه */
    private function render_header(array $settings, string $shop_url, array $query_args): void {
        echo '<div class="amw-ps__header">';

        if ('' !== trim((string) ($settings['title'] ?? ''))) {
            echo '<h2 class="amw-ps__title">' . esc_html($settings['title']) . '</h2>';
        }

        // پیل‌ها و دکمه با هم در یک ردیف مدیریت‌شده
        // (تب «ردیف فیلتر» در استایل ← چیدمان)
        echo '<div class="amw-ps__filter-row">';
        echo '<div class="amw-ps__pills" role="tablist">';

        printf(
            '<button type="button" class="amw-ps__pill is-active" data-term="0" data-link="%s" role="tab" aria-selected="true">%s</button>',
            esc_url($shop_url),
            esc_html($settings['all_label'] ?? '')
        );

        foreach ($this->visible_categories($settings, $query_args) as $term_id => $label) {
            $link = get_term_link($term_id, 'product_cat');
            printf(
                '<button type="button" class="amw-ps__pill" data-term="%d" data-link="%s" role="tab" aria-selected="false">%s</button>',
                $term_id,
                esc_url(is_wp_error($link) ? $shop_url : $link),
                esc_html($label)
            );
        }

        echo '</div>'; // .amw-ps__pills

        if ('yes' === ($settings['show_view_all'] ?? '')) {
            printf(
                '<a class="amw-ps__viewall" href="%s">%s</a>',
                esc_url($shop_url),
                esc_html($settings['view_all_text'] ?? '')
            );
        }

        echo '</div>'; // .amw-ps__filter-row
        echo '</div>'; // .amw-ps__header
    }

    /**
     * دسته‌بندی‌های قابل نمایش، به‌صورت [term_id => برچسب].
     * دسته‌ای که با فیلترهای فعال هیچ محصولی ندارد کنار گذاشته می‌شود —
     * کلیک روی پیل خالی تجربهٔ بدی است.
     */
    private function visible_categories(array $settings, array $query_args): array {
        $rows = (array) ($settings['categories'] ?? []);
        if (!$rows) {
            return [];
        }

        $wanted    = array_map(static fn($row) => absint($row['category'] ?? 0), $rows);
        $non_empty = Query::non_empty_categories($wanted, $query_args);

        $out = [];
        foreach ($rows as $row) {
            $term_id = absint($row['category'] ?? 0);
            if (!$term_id || !in_array($term_id, $non_empty, true) || isset($out[$term_id])) {
                continue;
            }

            $term = get_term($term_id, 'product_cat');
            if (!$term || is_wp_error($term)) {
                continue;
            }

            $label         = trim((string) ($row['label'] ?? ''));
            $out[$term_id] = '' !== $label ? $label : $term->name;
        }

        return $out;
    }

    /** اسلایدر + دکمه‌های قبلی/بعدی */
    private function render_slider(array $settings, array $query_args): void {
        echo '<div class="amw-ps__slider-wrap">';
        echo '<div class="amw-ps__slider swiper">';
        echo '<div class="swiper-wrapper">';

        $result = Query::render(['category' => 0] + $query_args);
        // رندرشده از قالب Listing یا کارت داخلی؛ هر دو خودشان خروجی را
        // اسکیپ می‌کنند
        echo $result['html']; // phpcs:ignore WordPress.Security.EscapeOutput

        echo '</div>'; // .swiper-wrapper
        echo '</div>'; // .swiper

        if ('yes' === ($settings['show_navigation'] ?? '')) {
            $this->render_nav_button($settings, true);
            $this->render_nav_button($settings, false);
        }

        echo '</div>'; // .amw-ps__slider-wrap
    }

    private function render_nav_button(array $settings, bool $is_prev): void {
        printf(
            '<button type="button" class="amw-ps__btn amw-ps__btn--%s" aria-label="%s">',
            $is_prev ? 'prev' : 'next',
            esc_attr($is_prev ? __('قبلی', 'almasara-widgets') : __('بعدی', 'almasara-widgets'))
        );
        $this->render_nav_icon($settings, $is_prev);
        echo '</button>';
    }

    /** آیکون فلش سفارشی، وگرنه شورون پیش‌فرض inline و رنگ‌پذیر */
    private function render_nav_icon(array $settings, bool $is_prev): void {
        $url = $settings['nav_icon']['url'] ?? '';

        if ('' !== $url) {
            $is_svg = 'svg' === strtolower(pathinfo(wp_parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

            if ($is_svg && !empty($settings['nav_icon']['id'])) {
                $svg = $this->get_inline_svg((int) $settings['nav_icon']['id']);
                if ($svg) {
                    echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput -- در get_inline_svg پاک‌سازی شده
                    return;
                }
            }

            printf('<img src="%s" alt="">', esc_url($url));
            return;
        }

        // fill/stroke روی خودِ path گذاشته می‌شود تا کنترل‌های رنگ که ویژگی
        // fill و stroke را هدف می‌گیرند بتوانند بازنویسی‌اش کنند
        printf(
            '<svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true"><path d="%s" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            $is_prev ? 'm15 6-6 6 6 6' : 'm9 6 6 6-6 6'
        );
    }

    private function render_notice(string $message): void {
        echo '<div class="amw-ps__notice">' . esc_html($message) . '</div>';
    }
}
