<?php
namespace Almasara_Widgets\Product_Section;

use Almasara_Widgets\Responsive;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * تبدیل تنظیمات خام المنتور به پیکربندی معتبر و تایپ‌شده.
 *
 * دلیل وجودش: این ویجت دو مسیر رندر دارد — رندر اولیهٔ PHP و endpoint فیلتر
 * AJAX. قبلاً هرکدام تنظیمات را جدا و با دست می‌خواندند و همین باعث می‌شد
 * به‌مرور از هم فاصله بگیرند. حالا هر دو از همین‌جا تغذیه می‌شوند، پس خروجی
 * دو مسیر ساختاراً نمی‌تواند فرق کند.
 */
final class Settings {

    public const SOURCE_JETENGINE = 'jetengine';
    public const SOURCE_BUILTIN   = 'builtin';

    private const ORDERBY = ['date', 'title', 'price', 'popularity', 'rand', 'menu_order'];

    /** منبع کارت؛ هر مقدار ناشناخته به جت‌انجین برمی‌گردد (سازگاری با تنظیمات قدیمی) */
    public static function source(array $s): string {
        return self::SOURCE_BUILTIN === ($s['card_source'] ?? '')
            ? self::SOURCE_BUILTIN
            : self::SOURCE_JETENGINE;
    }

    /**
     * شناسهٔ دسته‌بندی‌هایی که در پیل‌های این ویجت تعریف شده‌اند.
     *
     * @return int[]
     */
    public static function categories(array $s): array {
        $ids = array_map(
            static fn($row) => absint($row['category'] ?? 0),
            (array) ($s['categories'] ?? [])
        );

        return array_values(array_unique(array_filter($ids)));
    }

    /** ۰ یعنی «همه»؛ بقیه فقط اگر واقعاً در تنظیمات همین ویجت باشند */
    public static function allows_category(array $s, int $category): bool {
        return 0 === $category || in_array($category, self::categories($s), true);
    }

    /** فیلترهای محتوایی */
    public static function filters(array $s): array {
        $has_price = 'yes' === ($s['filter_has_price'] ?? '');

        return [
            'has_price' => $has_price,
            'in_stock'  => 'yes' === ($s['filter_in_stock'] ?? ''),
            'has_image' => 'yes' === ($s['filter_has_image'] ?? ''),
            // حداقل قیمت بدون فیلتر «دارای قیمت» معنا ندارد
            'min_price' => $has_price ? max(0.0, (float) ($s['filter_min_price'] ?? 0)) : 0.0,
        ];
    }

    /**
     * آرگومان‌های کوئری و رندر کارت.
     *
     * @param bool $is_editor در ادیتور هرگز کش نمی‌شود، وگرنه بعد از هر تغییر
     *                       تنظیمات، خروجیِ ذخیره‌شدهٔ حالت قبلی دیده می‌شد.
     */
    public static function query(array $s, bool $is_editor = false): array {
        $orderby = (string) ($s['orderby'] ?? 'date');

        return [
            'source'     => self::source($s),
            'listing_id' => absint($s['listing_id'] ?? 0),
            'count'      => max(1, min(48, absint($s['products_count'] ?? 12))),
            'orderby'    => in_array($orderby, self::ORDERBY, true) ? $orderby : 'date',
            'order'      => 'ASC' === strtoupper((string) ($s['order'] ?? 'DESC')) ? 'ASC' : 'DESC',
            // پیش‌فرض عمداً با پیش‌فرض خودِ کنترل یکی است، تا اگر کلید موجود
            // نبود کش بی‌سروصدا خاموش نشود
            'cache'      => $is_editor ? 0 : max(0, min(1440, absint($s['cache_minutes'] ?? 30))),
            'card'       => self::card($s),
        ] + self::filters($s);
    }

    /** تنظیمات کارت داخلی (Product_Card خودش دوباره اعتبارسنجی می‌کند) */
    public static function card(array $s): array {
        return [
            'show_image'  => 'yes' === ($s['card_show_image'] ?? 'yes'),
            'show_title'  => 'yes' === ($s['card_show_title'] ?? 'yes'),
            'show_slogan' => 'yes' === ($s['card_show_slogan'] ?? 'yes'),
            'show_price'  => 'yes' === ($s['card_show_price'] ?? 'yes'),
            'link_card'   => 'yes' === ($s['card_link'] ?? 'yes'),
            'link_title'  => 'yes' === ($s['card_link_title'] ?? ''),
            'new_tab'     => 'yes' === ($s['card_new_tab'] ?? ''),
            'title_tag'   => (string) ($s['card_title_tag'] ?? 'h3'),
            'title_lines' => (int) ($s['card_title_lines'] ?? 2),
            'slogan'      => (string) ($s['card_slogan'] ?? ''),
            'unit'        => (string) ($s['card_unit'] ?? ''),
            'free_text'   => (string) ($s['card_free_text'] ?? ''),
            'image_size'  => (string) ($s['card_image_size'] ?? 'woocommerce_thumbnail'),
        ];
    }

    /**
     * پیکربندی Swiper.
     *
     * المنتور دسکتاپ‌محور است (پایه = دسکتاپ، override برای کوچک‌تر) ولی
     * Swiper موبایل‌محور است (پایه = کوچک‌ترین، breakpoints برای بزرگ‌تر)،
     * پس ترتیب عمداً برعکس می‌شود.
     */
    public static function slider(array $s): array {
        // پشتیبان‌ها عمداً همان default کنترل‌های متناظر در content-controls
        // هستند. لازم‌اند چون مقدار دسکتاپ می‌تواند خالی باشد — مثلاً وقتی
        // کاربر عدد را پاک کرده یا ویجت با نسخه‌ای ذخیره شده که این کنترل را
        // نداشته — و آن‌وقت هیچ دستگاهی چیزی برای ارث بردن ندارد.
        $speed = Responsive::resolve($s, 'speed', Responsive::to_int(), 600);
        $spv   = Responsive::resolve($s, 'slides_per_view', Responsive::to_float(), 4);
        $space = Responsive::resolve($s, 'space_between', Responsive::to_int(), 20);

        // صفر برای slidesPerView عددِ معتبر نیست، تقسیم بر صفر است: سوایپر
        // بی‌آنکه خطایی بدهد هندسه‌اش NaN می‌شود، اسلایدر حرکت نمی‌کند و
        // چیدمان از لحظهٔ init به هم می‌ریزد.
        foreach (Responsive::DEVICES as $device) {
            if (!is_string($spv[$device]) && $spv[$device] <= 0) {
                $spv[$device] = 4.0;
            }
        }

        // عرض دستی کارت: هرجا مقدار دارد، CSS عرض اسلاید را تعیین می‌کند و
        // Swiper باید در همان بریک‌پوینت روی 'auto' برود تا بازنویسی‌اش نکند
        foreach (Responsive::DEVICES as $device) {
            if (Responsive::has($s, 'slide_width', $device)) {
                $spv[$device] = 'auto';
            }
        }

        $per_device = static fn(string $d): array => [
            'speed'         => $speed[$d],
            'slidesPerView' => $spv[$d],
            'spaceBetween'  => $space[$d],
        ];

        return [
            'breakpoints'          => [
                768  => $per_device('tablet'),
                1025 => $per_device('desktop'),
            ],
            'rewind'               => 'yes' === ($s['rewind'] ?? ''),
            'rtl'                  => 'yes' === ($s['rtl'] ?? ''),
            'autoplay'             => 'yes' === ($s['autoplay'] ?? ''),
            'delay'                => max(1000, (int) ($s['autoplay_delay'] ?? 3500)),
            'disableOnInteraction' => 'yes' === ($s['pause_on_interaction'] ?? ''),
            'navigation'           => 'yes' === ($s['show_navigation'] ?? ''),
            'pagination'           => 'yes' === ($s['show_pagination'] ?? ''),
            'paginationClickable'  => 'yes' === ($s['pagination_clickable'] ?? ''),
        ] + $per_device('mobile');
    }

    /** آیا الان داخل ادیتور یا پیش‌نمایش المنتور هستیم؟ */
    public static function is_editing(): bool {
        if (!class_exists('\Elementor\Plugin')) {
            return false;
        }

        $elementor = \Elementor\Plugin::$instance;
        $preview   = $elementor->preview ?? null;

        return $elementor->editor->is_edit_mode()
            || ($preview && $preview->is_preview_mode());
    }
}
