<?php
namespace Almasara_Widgets;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * کارت محصول داخلی — جایگزین قالب Listing جت‌انجین.
 *
 * ساختار مارکاپ (هر بخش کلاس مستقل دارد تا از پنل کامل استایل بگیرد):
 *   .amw-card                 دیواید اصلی (خودش لینک محصول است)
 *     .amw-card__media > img  تصویر شاخص
 *     .amw-card__body         دیواید ۱
 *       .amw-card__title      عنوان
 *       .amw-card__price      دیواید ۲
 *         .amw-card__slogan   شعار
 *         .amw-card__amount   بستهٔ قیمت
 *           .amw-card__num    فقط عدد
 *           .amw-card__unit   فقط واحد پول
 *
 * چون هم ویجت (رندر اولیه) و هم endpoint ایجکس از همین کلاس استفاده می‌کنند،
 * خروجی دو مسیر هیچ‌وقت از هم جدا نمی‌افتد.
 */
final class Product_Card {

    /**
     * تنظیمات کارت با مقادیر پیش‌فرض و پاک‌سازی‌شده.
     *
     * از REST هم به‌صورت JSON می‌آید، پس همه‌چیز اینجا whitelist می‌شود و
     * هیچ کلید ناشناخته‌ای عبور نمی‌کند.
     */
    public static function sanitize_args($raw): array {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw     = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($raw)) {
            $raw = [];
        }

        $allowed_tags = ['h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p'];
        $tag          = isset($raw['title_tag']) ? strtolower((string) $raw['title_tag']) : 'h3';

        return [
            'show_image'  => !isset($raw['show_image']) || (bool) $raw['show_image'],
            'show_title'  => !isset($raw['show_title']) || (bool) $raw['show_title'],
            'show_slogan' => !empty($raw['show_slogan']),
            'show_price'  => !isset($raw['show_price']) || (bool) $raw['show_price'],
            'link_card'   => !isset($raw['link_card']) || (bool) $raw['link_card'],
            'link_title'  => !empty($raw['link_title']),
            'new_tab'     => !empty($raw['new_tab']),
            'title_tag'   => in_array($tag, $allowed_tags, true) ? $tag : 'h3',
            'title_lines' => max(0, min(10, (int) ($raw['title_lines'] ?? 2))),
            'slogan'      => sanitize_text_field((string) ($raw['slogan'] ?? '')),
            'unit'        => sanitize_text_field((string) ($raw['unit'] ?? '')),
            'free_text'   => sanitize_text_field((string) ($raw['free_text'] ?? '')),
            'image_size'  => sanitize_key((string) ($raw['image_size'] ?? 'woocommerce_thumbnail')),
            'eager'       => !empty($raw['eager']),
        ];
    }

    /**
     * قیمتی که مشتری می‌پردازد.
     *   • محصول متغیر → کمترین قیمت بین گزینه‌ها
     *   • محصول تخفیف‌خورده → فقط قیمت نهایی (قیمت پیشین نمایش داده نمی‌شود)
     *
     * @return array{num:string,is_free:bool,has:bool}
     */
    public static function get_price(\WC_Product $product): array {
        $empty = ['num' => '', 'is_free' => false, 'has' => false];

        $value = $product->is_type('variable')
            ? $product->get_variation_price('min', true)
            : $product->get_price();

        if ('' === $value || null === $value) {
            return $empty;
        }

        return [
            'num'     => self::format_amount((float) $value),
            'is_free' => 0.0 === (float) $value,
            'has'     => true,
        ];
    }

    private static function format_amount(float $value): string {
        return number_format(
            $value,
            function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 0,
            function_exists('wc_get_price_decimal_separator') ? wc_get_price_decimal_separator() : '.',
            function_exists('wc_get_price_thousand_separator') ? wc_get_price_thousand_separator() : ','
        );
    }

    private static function currency(array $args): string {
        if ('' !== $args['unit']) {
            return $args['unit'];
        }
        $symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '';
        return html_entity_decode((string) $symbol, ENT_QUOTES, 'UTF-8');
    }

    /** رندر یک کارت کامل برای یک محصول */
    public static function render(\WP_Post $post, array $args): string {
        $args    = self::sanitize_args($args);
        $product = function_exists('wc_get_product') ? wc_get_product($post) : null;

        if (!$product instanceof \WC_Product) {
            return '';
        }

        $permalink = get_permalink($post);
        $title     = get_the_title($post);

        // کل کارت لینک است؛ در این حالت عنوان دیگر لینک تودرتو نمی‌گیرد
        // (تگ <a> داخل <a> مارکاپ نامعتبر است و مرورگر آن را می‌شکند).
        $root_tag   = $args['link_card'] ? 'a' : 'div';
        $root_attrs = 'class="amw-card"';
        if ($args['link_card']) {
            $root_attrs .= sprintf(' href="%s"', esc_url($permalink));
            if ($args['new_tab']) {
                $root_attrs .= ' target="_blank" rel="noopener"';
            }
        }

        $html = sprintf('<%1$s %2$s>', $root_tag, $root_attrs);

        if ($args['show_image']) {
            $html .= '<div class="amw-card__media">' . self::render_image($product, $args, $title) . '</div>';
        }

        $html .= '<div class="amw-card__body">';

        if ($args['show_title'] && '' !== $title) {
            $html .= self::render_title($title, $permalink, $args);
        }

        if ($args['show_slogan'] || $args['show_price']) {
            $html .= '<div class="amw-card__price">';

            if ($args['show_slogan'] && '' !== $args['slogan']) {
                $html .= '<span class="amw-card__slogan">' . esc_html($args['slogan']) . '</span>';
            }

            if ($args['show_price']) {
                $html .= self::render_amount($product, $args);
            }

            $html .= '</div>';
        }

        $html .= '</div>'; // .amw-card__body
        $html .= sprintf('</%s>', $root_tag);

        return $html;
    }

    private static function render_title(string $title, string $permalink, array $args): string {
        $inner = esc_html($title);

        // فقط وقتی خودِ کارت لینک نیست، عنوان می‌تواند لینک شود
        if ($args['link_title'] && !$args['link_card']) {
            $inner = sprintf(
                '<a href="%s"%s>%s</a>',
                esc_url($permalink),
                $args['new_tab'] ? ' target="_blank" rel="noopener"' : '',
                $inner
            );
        }

        return sprintf('<%1$s class="amw-card__title">%2$s</%1$s>', $args['title_tag'], $inner);
    }

    private static function render_amount(\WC_Product $product, array $args): string {
        $price = self::get_price($product);

        if (!$price['has']) {
            return '';
        }

        if ($price['is_free'] && '' !== $args['free_text']) {
            return '<span class="amw-card__amount"><span class="amw-card__num amw-card__num--free">'
                . esc_html($args['free_text']) . '</span></span>';
        }

        $unit = self::currency($args);

        return '<span class="amw-card__amount">'
            . '<span class="amw-card__num">' . esc_html($price['num']) . '</span>'
            . ('' !== $unit ? '<span class="amw-card__unit">' . esc_html($unit) . '</span>' : '')
            . '</span>';
    }

    /**
     * تصویر شاخص. عمداً از wp_get_attachment_image استفاده می‌شود تا srcset،
     * sizes، width/height و lazy-loading را خودِ وردپرس درست بسازد — همین
     * width/height است که جلوی پرش چیدمان (CLS) هنگام لود تصویر را می‌گیرد.
     */
    private static function render_image(\WC_Product $product, array $args, string $alt): string {
        $image_id = (int) $product->get_image_id();

        $attr = [
            'class'    => 'amw-card__img',
            'alt'      => $alt,
            'decoding' => 'async',
            // کارت‌های ابتدای اسلایدر در نمای اول دیده می‌شوند، پس lazy
            // کردنشان فقط لود را عقب می‌اندازد
            'loading'  => $args['eager'] ? 'eager' : 'lazy',
        ];

        if ($image_id) {
            $image = wp_get_attachment_image($image_id, $args['image_size'], false, $attr);
            if ($image) {
                return $image;
            }
        }

        if (function_exists('wc_placeholder_img')) {
            return wc_placeholder_img($args['image_size'], $attr);
        }

        return '';
    }
}
