<?php
namespace Almasara_Widgets;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * امکانات سمت محصول: متاباکس سوالات متداول + endpoint ثبت دیدگاه
 */
final class Product_Extras {

    const FAQ_META = '_almasara_faqs';

    public static function init(): void {
        add_action('add_meta_boxes', [self::class, 'register_faq_metabox']);
        add_action('save_post_product', [self::class, 'save_faq_metabox']);
        add_action('rest_api_init', [self::class, 'register_review_endpoint']);
    }

    /* ------------------------------------------------------------------
     * متاباکس سوالات متداول محصول
     * ---------------------------------------------------------------- */

    public static function register_faq_metabox(): void {
        add_meta_box(
            'almasara_faqs',
            __('سوالات متداول این محصول (الماسارا)', 'almasara-widgets'),
            [self::class, 'render_faq_metabox'],
            'product',
            'normal',
            'default'
        );
    }

    public static function render_faq_metabox(\WP_Post $post): void {
        $faqs = get_post_meta($post->ID, self::FAQ_META, true);
        if (!is_array($faqs)) {
            $faqs = [];
        }
        wp_nonce_field('almasara_faqs_save', 'almasara_faqs_nonce');
        ?>
        <div id="almasara-faq-rows">
            <?php foreach ($faqs as $i => $faq) : ?>
                <div class="amw-faq-row" style="border:1px solid #dcdcde;border-radius:6px;padding:12px;margin-bottom:10px;">
                    <p style="margin-top:0;">
                        <input type="text" name="almasara_faq_q[]" value="<?php echo esc_attr($faq['q'] ?? ''); ?>" placeholder="<?php esc_attr_e('سوال', 'almasara-widgets'); ?>" style="width:100%;" />
                    </p>
                    <p style="margin-bottom:8px;">
                        <textarea name="almasara_faq_a[]" rows="3" placeholder="<?php esc_attr_e('پاسخ', 'almasara-widgets'); ?>" style="width:100%;"><?php echo esc_textarea($faq['a'] ?? ''); ?></textarea>
                    </p>
                    <button type="button" class="button-link-delete amw-faq-remove"><?php esc_html_e('حذف این سوال', 'almasara-widgets'); ?></button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button" id="almasara-faq-add"><?php esc_html_e('+ افزودن سوال', 'almasara-widgets'); ?></button>

        <script>
        (function () {
            var wrap = document.getElementById('almasara-faq-rows');
            document.getElementById('almasara-faq-add').addEventListener('click', function () {
                var row = document.createElement('div');
                row.className = 'amw-faq-row';
                row.style.cssText = 'border:1px solid #dcdcde;border-radius:6px;padding:12px;margin-bottom:10px;';
                row.innerHTML = '<p style="margin-top:0;"><input type="text" name="almasara_faq_q[]" placeholder="<?php echo esc_js(__('سوال', 'almasara-widgets')); ?>" style="width:100%;" /></p>' +
                    '<p style="margin-bottom:8px;"><textarea name="almasara_faq_a[]" rows="3" placeholder="<?php echo esc_js(__('پاسخ', 'almasara-widgets')); ?>" style="width:100%;"></textarea></p>' +
                    '<button type="button" class="button-link-delete amw-faq-remove"><?php echo esc_js(__('حذف این سوال', 'almasara-widgets')); ?></button>';
                wrap.appendChild(row);
            });
            wrap.addEventListener('click', function (e) {
                if (e.target.classList.contains('amw-faq-remove')) {
                    e.target.closest('.amw-faq-row').remove();
                }
            });
        })();
        </script>
        <?php
    }

    public static function save_faq_metabox(int $post_id): void {
        if (
            !isset($_POST['almasara_faqs_nonce'])
            || !wp_verify_nonce(sanitize_key($_POST['almasara_faqs_nonce']), 'almasara_faqs_save')
            || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            || !current_user_can('edit_post', $post_id)
        ) {
            return;
        }

        $questions = isset($_POST['almasara_faq_q']) ? (array) wp_unslash($_POST['almasara_faq_q']) : [];
        $answers   = isset($_POST['almasara_faq_a']) ? (array) wp_unslash($_POST['almasara_faq_a']) : [];

        $faqs = [];
        foreach ($questions as $i => $question) {
            $question = sanitize_text_field($question);
            $answer   = wp_kses_post($answers[$i] ?? '');
            if ('' === trim($question) || '' === trim(wp_strip_all_tags($answer))) {
                continue;
            }
            $faqs[] = ['q' => $question, 'a' => $answer];
        }

        if ($faqs) {
            update_post_meta($post_id, self::FAQ_META, $faqs);
        } else {
            delete_post_meta($post_id, self::FAQ_META);
        }
    }

    /* ------------------------------------------------------------------
     * ثبت دیدگاه (مودال ویجت دیدگاه‌ها)
     * ---------------------------------------------------------------- */

    public static function register_review_endpoint(): void {
        register_rest_route('almasara/v1', '/reviews', [
            'methods'             => 'POST',
            'permission_callback' => '__return_true',
            'callback'            => [self::class, 'submit_review'],
        ]);
    }

    private static function client_ip(): string {
        return isset($_SERVER['REMOTE_ADDR'])
            ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
            : '';
    }

    /**
     * سقف ارسال دیدگاه برای هر IP.
     *
     * سیل‌بندی خودِ وردپرس داخل wp_new_comment هست، ولی فقط بعد از اعتبارسنجی
     * کامل عمل می‌کند. این بررسی پیش از هر کوئری‌ای انجام می‌شود، پس هزینهٔ
     * درخواست‌های پشت‌سرهم را از همان ابتدا می‌بندد.
     */
    private static function review_rate_limited(): bool {
        $ip = self::client_ip();
        if ('' === $ip) {
            return false;
        }

        $key   = 'amw_rv_rl_' . md5($ip);
        $count = (int) get_transient($key);

        if ($count >= 5) {
            return true;
        }

        set_transient($key, $count + 1, 5 * MINUTE_IN_SECONDS);

        return false;
    }

    public static function submit_review(\WP_REST_Request $request) {
        if (!function_exists('wc_get_product')) {
            return new \WP_Error('woocommerce_missing', __('ووکامرس فعال نیست.', 'almasara-widgets'), ['status' => 500]);
        }

        // تلهٔ ربات: فیلدی که در فرم پنهان است و کاربر واقعی هرگز پرش نمی‌کند
        if ('' !== trim((string) $request->get_param('website'))) {
            return new \WP_Error('spam_detected', __('ارسال ناموفق بود.', 'almasara-widgets'), ['status' => 400]);
        }

        if (self::review_rate_limited()) {
            return new \WP_Error('too_many_requests', __('کمی صبر کنید و دوباره تلاش کنید.', 'almasara-widgets'), ['status' => 429]);
        }

        $product = wc_get_product(absint($request->get_param('product_id')));
        if (!$product || 'publish' !== $product->get_status() || !comments_open($product->get_id())) {
            return new \WP_Error('invalid_product', __('امکان ثبت دیدگاه برای این محصول وجود ندارد.', 'almasara-widgets'), ['status' => 400]);
        }

        if (get_option('comment_registration') && !is_user_logged_in()) {
            return new \WP_Error('login_required', __('برای ثبت دیدگاه ابتدا وارد حساب کاربری شوید.', 'almasara-widgets'), ['status' => 401]);
        }

        // همان قاعده‌ای که خودِ ووکامرس در فرم استانداردش اعمال می‌کند: اگر
        // «فقط خریداران» فعال باشد، فقط مالک تاییدشدهٔ محصول اجازه دارد
        if ('yes' === get_option('woocommerce_review_verification_required')
            && function_exists('wc_customer_bought_product')
        ) {
            $user  = wp_get_current_user();
            $email = $user->exists() ? $user->user_email : sanitize_email((string) $request->get_param('email'));

            if (!wc_customer_bought_product($email, (int) $user->ID, $product->get_id())) {
                return new \WP_Error(
                    'purchase_required',
                    __('فقط خریداران این محصول می‌توانند دیدگاه ثبت کنند.', 'almasara-widgets'),
                    ['status' => 403]
                );
            }
        }

        $rating = min(5, max(0, absint($request->get_param('rating'))));

        // امتیاز اجباری، مطابق تنظیم خودِ ووکامرس
        $ratings_enabled = !function_exists('wc_review_ratings_enabled') || wc_review_ratings_enabled();
        if ($ratings_enabled && 'yes' === get_option('woocommerce_review_rating_required') && $rating < 1) {
            return new \WP_Error('rating_required', __('امتیاز دادن الزامی است.', 'almasara-widgets'), ['status' => 400]);
        }

        $content = sanitize_textarea_field((string) $request->get_param('comment'));
        if ('' === trim($content)) {
            return new \WP_Error('empty_comment', __('متن دیدگاه را بنویسید.', 'almasara-widgets'), ['status' => 400]);
        }

        $sanitize_list = static function ($items): array {
            $out = [];
            foreach ((array) $items as $item) {
                $item = sanitize_text_field((string) $item);
                if ('' !== trim($item)) {
                    $out[] = $item;
                }
            }
            return array_slice($out, 0, 10);
        };

        $pros      = $sanitize_list($request->get_param('pros'));
        $cons      = $sanitize_list($request->get_param('cons'));
        $recommend = in_array($request->get_param('recommend'), ['yes', 'no', 'neutral'], true) ? $request->get_param('recommend') : '';
        $anonymous = rest_sanitize_boolean($request->get_param('anonymous'));

        $user = wp_get_current_user();
        if ($user->exists()) {
            $author = $user->display_name;
            $email  = $user->user_email;
        } else {
            $author = sanitize_text_field((string) $request->get_param('author'));
            $email  = sanitize_email((string) $request->get_param('email'));
            if ('' === $author || !is_email($email)) {
                return new \WP_Error('missing_identity', __('نام و ایمیل معتبر وارد کنید.', 'almasara-widgets'), ['status' => 400]);
            }
        }

        // IP و User Agent صریحاً پاس داده می‌شوند: کنترل سیل‌بندی خودِ وردپرس،
        // آکیسمت و افزونه‌های ضداسپم به همین دو تکیه می‌کنند و بدون آن‌ها
        // عملاً کور می‌مانند.
        $comment_id = wp_new_comment([
            'comment_post_ID'      => $product->get_id(),
            'comment_content'      => $content,
            'comment_type'         => 'review',
            'comment_parent'       => 0,
            'comment_author'       => $author,
            'comment_author_email' => $email,
            'comment_author_url'   => '',
            'comment_author_IP'    => self::client_ip(),
            'comment_agent'        => isset($_SERVER['HTTP_USER_AGENT'])
                ? substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])), 0, 254)
                : '',
            'user_id'              => $user->exists() ? $user->ID : 0,
        ], true);

        if (is_wp_error($comment_id)) {
            return $comment_id;
        }

        if ($rating > 0) {
            add_comment_meta($comment_id, 'rating', $rating);
        }
        if ($pros) {
            add_comment_meta($comment_id, '_amw_pros', $pros);
        }
        if ($cons) {
            add_comment_meta($comment_id, '_amw_cons', $cons);
        }
        if ($recommend) {
            add_comment_meta($comment_id, '_amw_recommend', $recommend);
        }
        if ($anonymous) {
            add_comment_meta($comment_id, '_amw_anonymous', 1);
        }

        $comment = get_comment($comment_id);

        return rest_ensure_response([
            'success'  => true,
            'approved' => $comment && '1' === (string) $comment->comment_approved,
            'message'  => $comment && '1' === (string) $comment->comment_approved
                ? __('دیدگاه شما با موفقیت ثبت شد.', 'almasara-widgets')
                : __('دیدگاه شما ثبت شد و پس از تایید نمایش داده می‌شود.', 'almasara-widgets'),
        ]);
    }
}

Product_Extras::init();
