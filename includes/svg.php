<?php
namespace Almasara_Widgets;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * پاک‌سازی SVG برای درج inline.
 *
 * چرا با DOM و نه Regex: نسخهٔ قبلی این افزونه با چند preg_replace کار
 * می‌کرد و ذاتاً قابل دور زدن بود — الگوی رویدادها فقط attribute کوتیشن‌دار
 * را می‌گرفت، پس چیزی مثل «onload=alert(1)» بدون کوتیشن سالم عبور می‌کرد،
 * و عنصرهایی مثل animate/set با رویدادهای خودشان یا use با href از نوع
 * data: هیچ‌وقت دیده نمی‌شدند. با فعال بودن آپلود SVG، همین یعنی امکان
 * Stored XSS برای هر کسی که دسترسی رسانه دارد.
 *
 * رویکرد این کلاس برعکس است: هیچ‌چیز مجاز نیست مگر آنکه در فهرست سفید
 * باشد. هر عنصر یا ویژگیِ ناشناخته حذف می‌شود، پس بردار حملهٔ جدید هم
 * به‌طور پیش‌فرض بسته است.
 */
final class Svg {

    /** عنصرهای مجاز — عمداً فقط شکل و ساختار، بدون هیچ عنصر رفتاری */
    private const ELEMENTS = [
        'svg', 'g', 'defs', 'symbol', 'use', 'title', 'desc',
        'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
        'text', 'tspan',
        'clippath', 'mask', 'pattern',
        'lineargradient', 'radialgradient', 'stop',
        'filter', 'fegaussianblur', 'feoffset', 'feblend', 'feflood',
        'fecomposite', 'fecolormatrix', 'femerge', 'femergenode',
    ];

    /** ویژگی‌های مجاز؛ هر چیز خارج از این فهرست — از جمله همهٔ on* — حذف می‌شود */
    private const ATTRIBUTES = [
        'id', 'class', 'style',
        'width', 'height', 'viewbox', 'preserveaspectratio', 'transform',
        'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry',
        'd', 'points', 'pathlength',
        'fill', 'fill-opacity', 'fill-rule',
        'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin',
        'stroke-dasharray', 'stroke-dashoffset', 'stroke-opacity', 'stroke-miterlimit',
        'opacity', 'color', 'display', 'visibility',
        'gradientunits', 'gradienttransform', 'offset', 'stop-color', 'stop-opacity',
        'clip-path', 'clip-rule', 'mask', 'filter',
        'patternunits', 'patterncontentunits',
        'font-family', 'font-size', 'font-weight', 'font-style',
        'text-anchor', 'dominant-baseline', 'letter-spacing',
        'in', 'in2', 'result', 'stddeviation', 'dx', 'dy', 'mode', 'values', 'type',
        'flood-color', 'flood-opacity', 'operator',
        'xmlns', 'xmlns:xlink',
        'aria-hidden', 'aria-label', 'role', 'focusable',
    ];

    /**
     * خواندن یک فایل پیوست و برگرداندن SVG پاک‌شده.
     * رشتهٔ خالی یعنی فایل نبود، SVG نبود، یا قابل پاک‌سازی نبود.
     */
    public static function from_attachment(int $attachment_id): string {
        if ($attachment_id <= 0) {
            return '';
        }

        // نوع MIME از خودِ پیوست خوانده می‌شود، نه از پسوند فایل
        $mime = get_post_mime_type($attachment_id);
        if ('image/svg+xml' !== $mime) {
            return '';
        }

        $path = get_attached_file($attachment_id);
        if (!$path || !is_readable($path)) {
            return '';
        }

        // جلوی خواندن فایل‌های بی‌قاعده بزرگ را می‌گیرد
        if (filesize($path) > 512 * KB_IN_BYTES) {
            return '';
        }

        $contents = file_get_contents($path);

        return false === $contents ? '' : self::sanitize($contents);
    }

    /** پاک‌سازی یک رشتهٔ SVG */
    public static function sanitize(string $svg): string {
        if (false === stripos($svg, '<svg')) {
            return '';
        }

        if (!class_exists('DOMDocument')) {
            return '';
        }

        $dom                      = new \DOMDocument();
        $dom->preserveWhiteSpace  = false;
        $dom->strictErrorChecking = false;

        // LIBXML_NONET شبکه را می‌بندد و NOENT از باز شدن موجودیت‌ها جلوگیری
        // می‌کند — با هم جلوی XXE و billion-laughs را می‌گیرند
        $previous = libxml_use_internal_errors(true);
        $loaded   = $dom->loadXML($svg, LIBXML_NONET | LIBXML_NOENT | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded || !$dom->documentElement) {
            return '';
        }

        if ('svg' !== strtolower($dom->documentElement->nodeName)) {
            return '';
        }

        // DOCTYPE می‌تواند حامل تعریف موجودیت باشد؛ کاملاً حذف می‌شود
        foreach (iterator_to_array($dom->childNodes) as $node) {
            if (XML_DOCUMENT_TYPE_NODE === $node->nodeType) {
                $dom->removeChild($node);
            }
        }

        self::clean($dom->documentElement);

        $out = $dom->saveXML($dom->documentElement);

        return is_string($out) ? trim($out) : '';
    }

    /** پیمایش بازگشتی و حذف هر چیزی که در فهرست سفید نیست */
    private static function clean(\DOMElement $element): void {
        // فرزندان از آخر به اول پیمایش می‌شوند تا حذف، اندیس‌ها را جابه‌جا نکند
        foreach (iterator_to_array($element->childNodes) as $child) {
            if ($child instanceof \DOMElement) {
                if (!in_array(strtolower($child->nodeName), self::ELEMENTS, true)) {
                    $element->removeChild($child);
                    continue;
                }
                self::clean($child);
                continue;
            }

            // کامنت و بخش CDATA و دستورهای پردازشی نگه داشته نمی‌شوند
            if (in_array($child->nodeType, [XML_COMMENT_NODE, XML_PI_NODE, XML_CDATA_SECTION_NODE], true)) {
                $element->removeChild($child);
            }
        }

        foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
            $name = strtolower($attribute->nodeName);

            if (!in_array($name, self::ATTRIBUTES, true)) {
                $element->removeAttribute($attribute->nodeName);
                continue;
            }

            if (!self::value_is_safe($name, (string) $attribute->nodeValue)) {
                $element->removeAttribute($attribute->nodeName);
            }
        }

        // use فقط به قطعهٔ داخلی همان سند اجازه دارد اشاره کند
        if ('use' === strtolower($element->nodeName)) {
            foreach (['href', 'xlink:href'] as $attr) {
                $value = $element->getAttribute($attr);
                if ('' !== $value && 0 !== strpos($value, '#')) {
                    $element->removeAttribute($attr);
                }
            }
        }
    }

    /**
     * مقدارهایی که حتی روی ویژگی مجاز هم خطرناک‌اند: هر URL اجراشدنی و
     * ترفندهای CSS مثل expression یا @import داخل style.
     */
    private static function value_is_safe(string $name, string $value): bool {
        // فاصله‌ها و کاراکترهای کنترلی برای پنهان‌کردن «javascript:» به کار می‌روند
        $normalized = strtolower(preg_replace('/[\s\x00-\x1F\x7F]+/', '', $value) ?? '');

        if (preg_match('/(javascript|vbscript|livescript|mocha|data):/', $normalized)) {
            return false;
        }

        if ('style' === $name && preg_match('/(expression|@import|url\()/', $normalized)) {
            return false;
        }

        return true;
    }
}
