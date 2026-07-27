<?php
namespace Almasara_Widgets\Product_Section;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * endpoint فیلتر دسته‌بندی ویجت «بخش محصولات» و بی‌اعتبارسازی کش.
 *
 * اصل طراحی: کلاینت فقط می‌گوید «کدام ویجت» و «کدام دسته». هر چیز دیگری —
 * قالب کارت، تعداد، مرتب‌سازی، فیلترها، مدت کش — از تنظیمات ذخیره‌شدهٔ همان
 * ویجت روی سرور خوانده می‌شود.
 *
 * دلیلش امنیتی است: نسخهٔ قبلی همهٔ این‌ها را از رشتهٔ کوئری می‌گرفت، پس
 * هرکسی می‌توانست با count=48 و cache=0 و orderby=rand درخواست بفرستد و در
 * هر بار ۴۸ کارت با قالب کامل المنتور را بی‌واسطهٔ کش رندر کند. این از باز
 * کردن یک صفحهٔ معمولی به‌مراتب هدف ارزان‌تری برای فشار آوردن به CPU و
 * دیتابیس بود. حالا آن پارامترها اصلاً پذیرفته نمی‌شوند.
 */
final class Rest {

    private const NAMESPACE = 'almasara/v1';
    private const ROUTE     = '/product-section';

    private const WIDGET = 'almasara-product-section';

    /** سقف درخواست در هر بازه، برای هر IP */
    private const RATE_LIMIT  = 40;
    private const RATE_WINDOW = MINUTE_IN_SECONDS;

    public static function init(): void {
        add_action('rest_api_init', [self::class, 'register_route']);

        $bump = [Query::class, 'bump_cache_version'];

        // با هر تغییر محصول یا موجودی، خروجی کش‌شده کهنه می‌شود
        add_action('save_post_product', $bump);
        add_action('woocommerce_update_product', $bump);
        add_action('woocommerce_product_set_stock', $bump);
        add_action('woocommerce_variation_set_stock', $bump);
        add_action('woocommerce_product_set_stock_status', $bump);

        // و با ذخیرهٔ صفحه در المنتور. بدون این، بعد از تغییر تنظیمات ویجت
        // سایت تا انقضای ترنزینت خروجی قدیمی را نشان می‌داد در حالی که
        // ادیتور تازه را — همان اختلاف پیش‌نمایش و سایت.
        add_action('elementor/document/after_save', $bump);
        add_action('elementor/core/files/clear_cache', $bump);
    }

    public static function register_route(): void {
        register_rest_route(self::NAMESPACE, self::ROUTE, [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => [self::class, 'handle'],
            'args'                => [
                'post_id'    => ['required' => true, 'sanitize_callback' => 'absint'],
                'element_id' => ['required' => true, 'sanitize_callback' => 'sanitize_key'],
                'category'   => ['default' => 0, 'sanitize_callback' => 'absint'],
            ],
        ]);
    }

    public static function url(): string {
        return rest_url(self::NAMESPACE . self::ROUTE);
    }

    public static function handle(\WP_REST_Request $request) {
        if (!function_exists('wc_get_product')) {
            return new \WP_Error('woocommerce_missing', __('ووکامرس فعال نیست.', 'almasara-widgets'), ['status' => 500]);
        }

        if (self::rate_limited()) {
            return new \WP_Error('too_many_requests', __('درخواست‌های بیش از حد. کمی بعد دوباره تلاش کنید.', 'almasara-widgets'), ['status' => 429]);
        }

        $settings = self::widget_settings(
            absint($request->get_param('post_id')),
            (string) $request->get_param('element_id')
        );

        if (null === $settings) {
            return new \WP_Error('widget_not_found', __('این ویجت پیدا نشد.', 'almasara-widgets'), ['status' => 404]);
        }

        $category = absint($request->get_param('category'));

        // دسته باید یکی از همان‌هایی باشد که در پیل‌های این ویجت تعریف شده
        // (یا ۰ برای «همه»). وگرنه می‌شد با پیمایش شناسه‌ها، برای هر ترمِ
        // سایت یک کوئری و یک کلید کش تازه ساخت.
        if (!Settings::allows_category($settings, $category)) {
            return new \WP_Error('invalid_category', __('این دسته‌بندی در تنظیمات ویجت نیست.', 'almasara-widgets'), ['status' => 400]);
        }

        $result = Query::render(['category' => $category] + Settings::query($settings));

        $response = rest_ensure_response($result);

        // عمداً هیچ‌جا عمومی کش نمی‌شود: این HTML می‌تواند از یک قالب Listing
        // بیاید که نام کاربر، قیمت نقش‌محور، واحد پول انتخابی، وضعیت ورود یا
        // nonce داخلش باشد. با «public» یک CDN می‌توانست پاسخ یک کاربر را به
        // بقیه بدهد.
        $response->header('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }

    /**
     * تنظیمات ذخیره‌شدهٔ ویجت، از سندی که واقعاً به آن تعلق دارد.
     *
     * از create_element_instance استفاده می‌شود نه دادهٔ خام، چون فقط این
     * مسیر مقادیر پیش‌فرض کنترل‌ها و تگ‌های داینامیک را هم درست ادغام می‌کند؛
     * با دادهٔ خام، هر تنظیمی که کاربر دست نزده بود گم می‌شد.
     *
     * @return array|null null یعنی سند یا ویجت معتبر نبود
     */
    private static function widget_settings(int $post_id, string $element_id): ?array {
        if ($post_id <= 0 || '' === $element_id || !class_exists('\Elementor\Plugin')) {
            return null;
        }

        $post = get_post($post_id);
        if (!$post instanceof \WP_Post || '' !== $post->post_password) {
            return null;
        }

        // فقط سندی که واقعاً برای عموم قابل مشاهده است
        $status = get_post_status_object($post->post_status);
        if (!$status || (empty($status->public) && empty($status->publicly_queryable))) {
            return null;
        }

        $document = \Elementor\Plugin::$instance->documents->get($post_id);
        if (!$document) {
            return null;
        }

        $data = self::find_element($document->get_elements_data(), $element_id);
        if (null === $data) {
            return null;
        }

        $instance = \Elementor\Plugin::$instance->elements_manager->create_element_instance($data);

        return $instance ? $instance->get_settings_for_display() : null;
    }

    /** جست‌وجوی بازگشتی درخت المان‌ها برای یک ویجتِ از همین نوع */
    private static function find_element(array $elements, string $element_id): ?array {
        foreach ($elements as $element) {
            if (($element['id'] ?? '') === $element_id) {
                return self::WIDGET === ($element['widgetType'] ?? '') ? $element : null;
            }

            if (!empty($element['elements']) && is_array($element['elements'])) {
                $found = self::find_element($element['elements'], $element_id);
                if (null !== $found) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * محدودیت نرخ سرانگشتی بر پایهٔ IP.
     *
     * چون بعد از این تغییرات پارامترهای گران دیگر از کلاینت نمی‌آیند و پاسخ
     * هم کش ترنزینت دارد، این فقط لایهٔ دوم است؛ هدفش جلوگیری از هزینهٔ
     * درخواست‌های پشت‌سرهم است، نه مسدودسازی جدی.
     */
    private static function rate_limited(): bool {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        if ('' === $ip) {
            return false;
        }

        $key   = 'amw_ps_rl_' . md5($ip);
        $count = (int) get_transient($key);

        if ($count >= self::RATE_LIMIT) {
            return true;
        }

        set_transient($key, $count + 1, self::RATE_WINDOW);

        return false;
    }
}
