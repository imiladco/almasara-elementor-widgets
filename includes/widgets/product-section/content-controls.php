<?php
namespace Almasara_Widgets\Widgets\Product_Section;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Css_Filter;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * کنترل‌های تب «محتوا» ویجت بخش محصولات و کمکی‌های پرکردن گزینه‌ها.
 */
trait Content_Controls {

    /* ---------------- محتوا: عنوان و دکمه ---------------- */

    private function register_header_content_controls(): void {
        $this->start_controls_section('section_header', [
            'label' => __('عنوان و دکمه', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('title', [
            'label'       => __('عنوان', 'almasara-widgets'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('محصولات', 'almasara-widgets'),
            'dynamic'     => ['active' => true],
            'label_block' => true,
        ]);

        $this->add_control('show_view_all', [
            'label'   => __('دکمه «مشاهده همه»', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('view_all_text', [
            'label'     => __('متن دکمه', 'almasara-widgets'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('مشاهده همه', 'almasara-widgets'),
            'condition' => ['show_view_all' => 'yes'],
        ]);

        $this->add_control('view_all_note', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('لینک این دکمه خودکار است: اگر پیل «همه» فعال باشد به فروشگاه، وگرنه به آرشیو همان دسته‌بندی می‌رود.', 'almasara-widgets'),
            'content_classes' => 'elementor-descriptor',
            'condition'       => ['show_view_all' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- محتوا: دسته‌بندی‌ها ---------------- */

    private function register_categories_content_controls(): void {
        $this->start_controls_section('section_categories', [
            'label' => __('فیلتر دسته‌بندی‌ها', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('all_label', [
            'label'   => __('متن گزینه «همه»', 'almasara-widgets'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('همه', 'almasara-widgets'),
        ]);

        $repeater = new Repeater();

        $repeater->add_control('category', [
            'label'   => __('دسته‌بندی', 'almasara-widgets'),
            'type'    => Controls_Manager::SELECT2,
            'options' => $this->get_product_category_options(),
        ]);

        $repeater->add_control('label', [
            'label'       => __('برچسب سفارشی', 'almasara-widgets'),
            'type'        => Controls_Manager::TEXT,
            'placeholder' => __('خالی = نام خودِ دسته‌بندی', 'almasara-widgets'),
        ]);

        $this->add_control('categories', [
            'label'       => __('دسته‌بندی‌ها', 'almasara-widgets'),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $repeater->get_controls(),
            'title_field' => '{{{ label || "دسته‌بندی" }}}',
        ]);

        $this->end_controls_section();
    }

    /* ---------------- محتوا: منبع محصولات ---------------- */

    private function register_source_content_controls(): void {
        $this->start_controls_section('section_source', [
            'label' => __('کارت محصول و کوئری', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('card_source', [
            'label'   => __('کارت محصول از', 'almasara-widgets'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'jetengine',
            'options' => [
                'jetengine' => __('قالب Listing جت‌انجین', 'almasara-widgets'),
                'builtin'   => __('کارت داخلی این افزونه', 'almasara-widgets'),
            ],
        ]);

        $this->add_control('listing_id', [
            'label'       => __('قالب Listing جت‌انجین', 'almasara-widgets'),
            'type'        => Controls_Manager::SELECT2,
            'options'     => $this->get_jetengine_listing_options(),
            'description' => __('قالب کارت محصولی که قبلاً در جت‌انجین ساخته‌اید.', 'almasara-widgets'),
            'condition'   => ['card_source' => 'jetengine'],
        ]);

        $this->add_control('products_count', [
            'label'   => __('تعداد محصول در اسلایدر', 'almasara-widgets'),
            'type'    => Controls_Manager::NUMBER,
            'default' => 12,
            'min'     => 1,
            'max'     => 48,
        ]);

        $this->add_control('orderby', [
            'label'   => __('مرتب‌سازی بر اساس', 'almasara-widgets'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'date',
            'options' => [
                'date'       => __('جدیدترین', 'almasara-widgets'),
                'price'      => __('قیمت', 'almasara-widgets'),
                'popularity' => __('پرفروش‌ترین', 'almasara-widgets'),
                'title'      => __('نام محصول', 'almasara-widgets'),
                'menu_order' => __('ترتیب دستی محصول', 'almasara-widgets'),
                'rand'       => __('تصادفی', 'almasara-widgets'),
            ],
        ]);

        $this->add_control('order', [
            'label'     => __('جهت مرتب‌سازی', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'DESC',
            'options'   => [
                'DESC' => __('نزولی', 'almasara-widgets'),
                'ASC'  => __('صعودی', 'almasara-widgets'),
            ],
            'condition' => ['orderby!' => 'rand'],
        ]);

        $this->add_control('cache_minutes', [
            'label'       => __('کش موقت خروجی (دقیقه)', 'almasara-widgets'),
            'type'        => Controls_Manager::NUMBER,
            'default'     => 30,
            'min'         => 0,
            'max'         => 1440,
            'separator'   => 'before',
            'description' => __('رندر کارت‌ها سنگین‌ترین بخش این بخش است؛ کش این هزینه را فقط یک‌بار در هر بازه پرداخت می‌کند. با هر ویرایش محصول یا تغییر موجودی، کش خودکار باطل می‌شود. ۰ = خاموش. مرتب‌سازی تصادفی هرگز کش نمی‌شود.', 'almasara-widgets'),
        ]);

        $this->add_control('heading_filters', [
            'label'     => __('فیلترها', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('filters_note', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('هر فیلتر مستقل روشن/خاموش می‌شود و در بارگذاری اولیه و سوییچ AJAX دسته‌بندی‌ها یکسان اعمال می‌شود. پیل دسته‌بندی‌ای که بعد از این فیلترها هیچ محصولی نداشته باشد، نمایش داده نمی‌شود.', 'almasara-widgets'),
            'content_classes' => 'elementor-descriptor',
        ]);

        $this->add_control('filter_has_price', [
            'label'       => __('فقط محصولات دارای قیمت', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'description' => __('محصولاتی که هیچ قیمتی برایشان ثبت نشده حذف می‌شوند.', 'almasara-widgets'),
        ]);

        $this->add_control('filter_min_price', [
            'label'       => __('حداقل قیمت', 'almasara-widgets'),
            'type'        => Controls_Manager::NUMBER,
            'min'         => 0,
            'step'        => 1000,
            'placeholder' => __('بدون حداقل', 'almasara-widgets'),
            'description' => __('محصولاتی که ارزان‌تر از این مبلغ‌اند حذف می‌شوند. مبنا کمترین قیمت محصول است (برای محصول متغیر، ارزان‌ترین گزینه). خالی یا ۰ = بدون حداقل.', 'almasara-widgets'),
            'condition'   => ['filter_has_price' => 'yes'],
        ]);

        $this->add_control('filter_in_stock', [
            'label'       => __('فقط محصولات موجود', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'description' => __('محصولات «ناموجود» حذف می‌شوند. محصولاتی که موجودی‌شان مدیریت نمی‌شود (بدون تعداد مشخص) حذف نخواهند شد.', 'almasara-widgets'),
        ]);

        $this->add_control('filter_has_image', [
            'label'       => __('فقط محصولات دارای عکس شاخص', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'description' => __('محصولات بدون تصویر شاخص حذف می‌شوند.', 'almasara-widgets'),
        ]);

        $this->end_controls_section();
    }

    /* ---------------- محتوا: کارت داخلی ---------------- */

    private function register_card_content_controls(): void {
        $this->start_controls_section('section_card', [
            'label'     => __('کارت محصول', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['card_source' => 'builtin'],
        ]);

        $this->add_control('card_link', [
            'label'       => __('کل کارت لینک محصول باشد', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => 'yes',
            'description' => __('با کلیک روی هر جای کارت، صفحهٔ محصول باز می‌شود.', 'almasara-widgets'),
        ]);

        $this->add_control('card_link_title', [
            'label'       => __('فقط عنوان لینک باشد', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'description' => __('وقتی کل کارت لینک است این گزینه اثری ندارد، چون لینک داخل لینک مارکاپ نامعتبری می‌سازد.', 'almasara-widgets'),
            'condition'   => ['card_link!' => 'yes'],
        ]);

        $this->add_control('card_new_tab', [
            'label' => __('باز شدن در تب جدید', 'almasara-widgets'),
            'type'  => Controls_Manager::SWITCHER,
        ]);

        $this->add_control('heading_card_image', [
            'label'     => __('تصویر', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('card_show_image', [
            'label'   => __('نمایش تصویر شاخص', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('card_image_size', [
            'label'     => __('اندازهٔ تصویر', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'woocommerce_thumbnail',
            'options'   => $this->get_image_size_options(),
            'condition' => ['card_show_image' => 'yes'],
        ]);

        $this->add_control('heading_card_title', [
            'label'     => __('عنوان', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('card_show_title', [
            'label'   => __('نمایش عنوان', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('card_title_tag', [
            'label'     => __('تگ عنوان', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'h3',
            'options'   => [
                'h2'   => 'H2',
                'h3'   => 'H3',
                'h4'   => 'H4',
                'h5'   => 'H5',
                'h6'   => 'H6',
                'div'  => 'div',
                'span' => 'span',
                'p'    => 'p',
            ],
            'condition' => ['card_show_title' => 'yes'],
        ]);

        $this->add_responsive_control('card_title_lines', [
            'label'       => __('حداکثر تعداد خط عنوان', 'almasara-widgets'),
            'type'        => Controls_Manager::NUMBER,
            'default'     => 2,
            'min'         => 0,
            'max'         => 10,
            'description' => __('عنوان بلندتر با «...» کوتاه می‌شود. ۰ = بدون محدودیت. چون ارتفاع عنوان ثابت می‌ماند، کارت‌ها هم‌قد می‌مانند.', 'almasara-widgets'),
            'condition'   => ['card_show_title' => 'yes'],
            'selectors'   => ['{{WRAPPER}} .amw-card' => '--amw-card-title-lines: {{VALUE}};'],
        ]);

        $this->add_control('heading_card_price', [
            'label'     => __('قیمت و شعار', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('card_show_slogan', [
            'label'   => __('نمایش شعار', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('card_slogan', [
            'label'       => __('متن شعار', 'almasara-widgets'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('کف قیمت ترب', 'almasara-widgets'),
            'label_block' => true,
            'condition'   => ['card_show_slogan' => 'yes'],
        ]);

        $this->add_control('card_show_price', [
            'label'   => __('نمایش قیمت', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('card_price_note', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('قیمت نمایش‌داده‌شده همان مبلغی است که مشتری می‌پردازد: برای محصول متغیر کمترین قیمت، و برای محصول تخفیف‌خورده فقط قیمت نهایی (قیمت پیشین نمایش داده نمی‌شود).', 'almasara-widgets'),
            'content_classes' => 'elementor-descriptor',
            'condition'       => ['card_show_price' => 'yes'],
        ]);

        $this->add_control('card_unit', [
            'label'       => __('واحد پول', 'almasara-widgets'),
            'type'        => Controls_Manager::TEXT,
            'placeholder' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : __('تومان', 'almasara-widgets'),
            'description' => __('خالی = نماد پیش‌فرض ووکامرس.', 'almasara-widgets'),
            'condition'   => ['card_show_price' => 'yes'],
        ]);

        $this->add_control('card_free_text', [
            'label'       => __('متن «رایگان»', 'almasara-widgets'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('رایگان', 'almasara-widgets'),
            'description' => __('وقتی قیمت صفر است به‌جای عدد نمایش داده می‌شود. برای نمایش خودِ صفر، خالی بگذارید.', 'almasara-widgets'),
            'condition'   => ['card_show_price' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- محتوا: اسلایدر ---------------- */

    private function register_slider_content_controls(): void {
        $this->start_controls_section('section_slider', [
            'label' => __('اسلایدر', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_responsive_control('slides_per_view', [
            'label'          => __('تعداد کارت هم‌زمان', 'almasara-widgets'),
            'type'           => Controls_Manager::NUMBER,
            'default'        => 4,
            'tablet_default' => 2.2,
            'mobile_default' => 1.2,
            'min'            => 1,
            'step'           => 0.1,
        ]);

        $this->add_responsive_control('slide_width', [
            'label'       => __('عرض دستی کارت', 'almasara-widgets'),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => ['px', '%', 'vw'],
            'range'       => [
                'px' => ['min' => 80, 'max' => 600],
                '%'  => ['min' => 10, 'max' => 100],
                'vw' => ['min' => 10, 'max' => 100],
            ],
            'description' => __('خالی = عرض از «تعداد کارت هم‌زمان» حساب می‌شود. اگر مقدار بگذارید، در همان دستگاه ملاکْ همین عرض است و تعداد کارت هم‌زمان نادیده گرفته می‌شود؛ برای موبایل که معمولاً عرض دقیق می‌خواهید مفید است. درصد نسبت به عرض خودِ اسلایدر حساب می‌شود.', 'almasara-widgets'),
            'selectors'   => ['{{WRAPPER}} .amw-ps__slider .swiper-slide' => 'width: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('space_between', [
            'label'       => __('فاصله بین کارت‌ها (px)', 'almasara-widgets'),
            'type'        => Controls_Manager::NUMBER,
            'default'     => 20,
            'min'         => 0,
            'description' => __('برای هر دستگاه جداگانه قابل تنظیم است؛ اگر برای تبلت/موبایل خالی بماند، مقدار دستگاه بزرگ‌تر به ارث می‌رسد.', 'almasara-widgets'),
        ]);

        $this->add_responsive_control('speed', [
            'label'          => __('سرعت گذار (میلی‌ثانیه)', 'almasara-widgets'),
            'type'           => Controls_Manager::NUMBER,
            'default'        => 600,
            'tablet_default' => 500,
            'mobile_default' => 400,
            'min'            => 100,
            'step'           => 50,
        ]);

        $this->add_control('rewind', [
            'label'   => __('بازگشت به کارت اول', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('rtl', [
            'label'   => __('جهت راست‌به‌چپ', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => is_rtl() ? 'yes' : '',
        ]);

        $this->add_control('heading_autoplay', [
            'label'     => __('پخش خودکار', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('autoplay', [
            'label'   => __('پخش خودکار', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
        ]);

        $this->add_control('autoplay_delay', [
            'label'     => __('تأخیر (میلی‌ثانیه)', 'almasara-widgets'),
            'type'      => Controls_Manager::NUMBER,
            'default'   => 3500,
            'min'       => 1000,
            'step'      => 100,
            'condition' => ['autoplay' => 'yes'],
        ]);

        $this->add_control('pause_on_interaction', [
            'label'     => __('توقف بعد از تعامل کاربر', 'almasara-widgets'),
            'type'      => Controls_Manager::SWITCHER,
            'condition' => ['autoplay' => 'yes'],
        ]);

        $this->add_control('heading_nav', [
            'label'     => __('ناوبری', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('show_navigation', [
            'label'   => __('دکمه‌های قبلی/بعدی', 'almasara-widgets'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('nav_icon', [
            'label'       => __('آیکون فلش', 'almasara-widgets'),
            'type'        => Controls_Manager::MEDIA,
            'media_types' => ['image', 'svg'],
            'description' => __('خالی = فلش پیش‌فرض inline و رنگ‌پذیر.', 'almasara-widgets'),
            'condition'   => ['show_navigation' => 'yes'],
        ]);

        $this->add_control('show_pagination', [
            'label'     => __('نقطه‌های پیجینیشن', 'almasara-widgets'),
            'type'      => Controls_Manager::SWITCHER,
            'separator' => 'before',
        ]);

        $this->add_control('pagination_clickable', [
            'label'     => __('کلیک‌پذیر بودن نقطه‌ها', 'almasara-widgets'),
            'type'      => Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'condition' => ['show_pagination' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    /* =====================================================================
     * کمکی‌های کنترل
     * =================================================================== */

    private function get_product_category_options(): array {
        if (!function_exists('get_terms')) {
            return [];
        }
        $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
        if (is_wp_error($terms)) {
            return [];
        }
        $options = [];
        foreach ($terms as $term) {
            $options[$term->term_id] = $term->name;
        }
        return $options;
    }

    /** اندازه‌های تصویر ثبت‌شده در سایت (شامل اندازه‌های خودِ ووکامرس) */
    private function get_image_size_options(): array {
        $options = [];
        foreach (get_intermediate_image_sizes() as $size) {
            $options[$size] = $size;
        }
        $options['full'] = __('اندازهٔ اصلی', 'almasara-widgets');

        return $options;
    }

    private function get_jetengine_listing_options(): array {
        $posts = get_posts([
            'post_type'      => 'jet-engine',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
        $options = [];
        foreach ($posts as $post) {
            $options[$post->ID] = $post->post_title;
        }
        return $options;
    }
}
