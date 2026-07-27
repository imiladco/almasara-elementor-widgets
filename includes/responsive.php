<?php
namespace Almasara_Widgets;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * خواندن مقدار کنترل‌های ریسپانسیو المنتور.
 *
 * چرا این کلاس لازم است: المنتور مقدارِ تنظیم‌نشدهٔ یک کنترل ریسپانسیو را
 * «رشتهٔ خالی» ذخیره می‌کند، نه null. یعنی الگوی رایج
 *
 *     $settings['gap_mobile'] ?? $settings['gap']
 *
 * هیچ‌وقت به شاخهٔ دوم نمی‌رسد و در عمل (int) '' یعنی صفر برمی‌گرداند. همین
 * اشتباه مستقل در دو ویجت تکرار شده بود و باعث می‌شد فاصله و سرعت روی تبلت
 * و موبایل بی‌سروصدا صفر شوند و مقدار دسکتاپ دور ریخته شود.
 *
 * ترتیب ارث‌بری هم عمداً همان چیزی است که خودِ CSS المنتور می‌سازد:
 * دسکتاپ بدون مدیا-کوئری، تبلت زیر ۱۰۲۴ و موبایل زیر ۷۶۸ — پس مقدار
 * نداشته را باید از دستگاه بزرگ‌تر به ارث برد: موبایل ← تبلت ← دسکتاپ.
 */
final class Responsive {

    /** دستگاه‌ها از بزرگ به کوچک — ترتیب ارث‌بری */
    public const DEVICES = ['desktop', 'tablet', 'mobile'];

    /**
     * مقدار خام را به «تنظیم‌شده یا نه» تبدیل می‌کند.
     * هم مقدار ساده (NUMBER/TEXT) و هم آرایه‌ای (SLIDER/DIMENSIONS) را می‌فهمد.
     *
     * @return mixed|null null یعنی تنظیم نشده
     */
    public static function value($raw) {
        if (is_array($raw)) {
            $size = $raw['size'] ?? null;
            return ('' !== $size && null !== $size) ? $raw : null;
        }

        return ('' !== $raw && null !== $raw) ? $raw : null;
    }

    /** کلید یک کنترل برای دستگاه مشخص ('' برای دسکتاپ) */
    private static function key(string $key, string $device): string {
        return 'desktop' === $device ? $key : $key . '_' . $device;
    }

    /**
     * مقدار هر سه دستگاه، با ارث‌بری درست و در صورت نیاز تبدیل نوع.
     *
     * دربارهٔ ‎$default‎: ارث‌بری از دستگاه بزرگ‌تر به کوچک‌تر است، پس دسکتاپ —
     * که اولین است — چیزی برای ارث بردن ندارد. اگر مقدار دسکتاپ خالی باشد،
     * هر سه دستگاه بی‌مقدار می‌مانند و اینجا باید مقدار پشتیبان بنشیند.
     *
     * و ‎$cast‎ عمداً روی null اجرا نمی‌شود. قبلاً می‌شد و همین یک خط، خرابیِ
     * بدی می‌ساخت: ‎(int) null‎ یعنی صفر، پس «تنظیم نشده» بی‌سروصدا به یک
     * عددِ معتبرِ غلط تبدیل می‌شد. برای slidesPerView این یعنی صفر — یعنی
     * تقسیم بر صفر در سوایپر — و کل اسلایدر بی‌آنکه خطایی بدهد از کار
     * می‌افتاد. «تنظیم نشده» باید null بماند تا صدازننده تصمیم بگیرد.
     *
     * @param callable|null $cast    روی مقدار نهایی هر دستگاه اجرا می‌شود
     * @param mixed         $default وقتی هیچ دستگاهی مقدار ندارد
     * @return array{desktop:mixed,tablet:mixed,mobile:mixed}
     */
    public static function resolve(array $settings, string $key, ?callable $cast = null, $default = null): array {
        $out      = [];
        $previous = null;

        foreach (self::DEVICES as $device) {
            $value    = self::value($settings[self::key($key, $device)] ?? null);
            $previous = $value ?? $previous;
            $resolved = $previous ?? $default;

            $out[$device] = (null !== $resolved && null !== $cast) ? $cast($resolved) : $resolved;
        }

        return $out;
    }

    /**
     * آیا این کنترل برای دستگاه داده‌شده مقدار مؤثری دارد؟ (با ارث‌بری)
     * برای تصمیم‌های دودویی مثل «عرض دستی تعیین شده یا نه» به کار می‌رود.
     */
    public static function has(array $settings, string $key, string $device): bool {
        foreach (self::DEVICES as $candidate) {
            if (null !== self::value($settings[self::key($key, $candidate)] ?? null)) {
                return true;
            }
            if ($candidate === $device) {
                break;
            }
        }

        return false;
    }

    /** تبدیل‌های پرکاربرد */
    public static function to_int(): callable {
        return static fn($v) => (int) $v;
    }

    public static function to_float(): callable {
        return static fn($v) => (float) $v;
    }
}
