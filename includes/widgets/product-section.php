<?php
namespace Almasara_Widgets\Widgets;

use Elementor\Widget_Base;
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
 * ویجت «بخش محصولات» — عنوان + فیلتر پیلی دسته‌بندی (AJAX زنده) + دکمه
 * مشاهده همه + اسلایدر کارت‌های محصول (کارت از قالب Listing جت‌انجین).
 *
 * کوئری و رندر کارت‌ها با Product_Section_Ajax مشترک است تا رندر اولیه
 * (این کلاس) و فیلتر AJAX همیشه دقیقاً یک خروجی تولید کنند.
 */
class Product_Section extends Widget_Base {

    use Traits\Intro_Row; // برای گزینه‌های چیدمان مشترک

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

    /* ---------------- استایل: هدر (عنوان + پیل‌ها + دکمه) ---------------- */

    /**
     * ثبت مجموعه کامل کنترل‌های فلکس‌باکس (جهت / تراز کردن محتوا / تراز
     * موارد / شکاف‌ها / wrap) برای یک سلکتور — همه ریسپانسیو.
     */
    /* ---------------- استایل: چیدمان (هدر / ردیف فیلتر / پیل‌ها) ---------------- */

    private function register_layout_style_controls(): void {
        $this->start_controls_section('section_style_layout', [
            'label' => __('چیدمان', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->start_controls_tabs('layout_tabs');

        $this->start_controls_tab('layout_tab_header', ['label' => __('هدر', 'almasara-widgets')]);
        $this->add_full_flex_controls('header', '{{WRAPPER}} .amw-ps__header', [
            'direction' => 'row',
            'justify'   => 'space-between',
            'align'     => 'center',
            'wrap'      => 'wrap',
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('layout_tab_filter', ['label' => __('ردیف فیلتر', 'almasara-widgets')]);
        $this->add_control('filter_row_note', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('پیل‌های دسته‌بندی و دکمه «مشاهده همه» با هم در یک ردیف‌اند؛ با «جهت = افقی معکوس» می‌توانید جای دکمه و پیل‌ها را عوض کنید.', 'almasara-widgets'),
            'content_classes' => 'elementor-descriptor',
        ]);
        $this->add_responsive_control('filter_row_width', [
            'label'                => __('عرض ردیف فیلتر', 'almasara-widgets'),
            'type'                 => Controls_Manager::CHOOSE,
            'default'              => 'auto',
            'options'              => [
                'auto' => ['title' => __('خودکار (اندازه محتوا)', 'almasara-widgets'), 'icon' => 'eicon-h-align-left'],
                'grow' => ['title' => __('پرکردن فضای باقی‌مانده', 'almasara-widgets'), 'icon' => 'eicon-grow'],
                'full' => ['title' => __('تمام عرض (خط جدا)', 'almasara-widgets'), 'icon' => 'eicon-h-align-stretch'],
            ],
            'selectors_dictionary' => [
                'auto' => 'flex: 0 1 auto; width: auto;',
                'grow' => 'flex: 1 1 auto; width: auto;',
                'full' => 'flex: 1 0 100%; width: 100%;',
            ],
            'selectors'            => ['{{WRAPPER}} .amw-ps__filter-row' => '{{VALUE}}'],
            'description'          => __('«پرکردن» ردیف را کنار عنوان کش می‌آورد؛ «تمام عرض» آن را به خط بعدی زیر عنوان می‌برد.', 'almasara-widgets'),
        ]);
        $this->add_responsive_control('button_joins_title', [
            'label'       => __('دکمه در ردیف عنوان', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'description' => __('دکمه «مشاهده همه» را از کنار پیل‌ها به ردیف عنوان می‌برد و پیل‌ها را به خط بعد می‌فرستد. معمولاً فقط روی موبایل لازم است — همین کنترل را در حالت موبایل روشن کنید تا دسکتاپ دست‌نخورده بماند.', 'almasara-widgets'),
            'selectors'   => [
                // ردیف فیلتر از چیدمان خارج می‌شود تا پیل‌ها و دکمه مستقیماً
                // آیتم‌های فلکسِ خودِ هدر شوند و بشود با order جابه‌جایشان کرد
                '{{WRAPPER}} .amw-ps__filter-row' => 'display: contents;',
                '{{WRAPPER}} .amw-ps__title'      => 'order: 1;',
                '{{WRAPPER}} .amw-ps__viewall'    => 'order: 2;',
                '{{WRAPPER}} .amw-ps__pills'      => 'order: 3; flex: 1 0 100%;',
            ],
        ]);

        $this->add_full_flex_controls('filter_row', '{{WRAPPER}} .amw-ps__filter-row', [
            'direction' => 'row',
            'justify'   => 'flex-start',
            'align'     => 'center',
            'wrap'      => 'wrap',
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('layout_tab_pills', ['label' => __('پیل‌ها', 'almasara-widgets')]);
        $this->add_control('pills_grow', [
            'label'        => __('پیل‌ها فضای باقی‌مانده را بگیرند', 'almasara-widgets'),
            'type'         => Controls_Manager::SWITCHER,
            'description'  => __('روشن = پیل‌ها کش می‌آیند و دکمه «مشاهده همه» به انتهای ردیف رانده می‌شود.', 'almasara-widgets'),
            'return_value' => '1 1 auto',
            'selectors'    => ['{{WRAPPER}} .amw-ps__pills' => 'flex: {{VALUE}};'],
        ]);
        $this->add_full_flex_controls('pills', '{{WRAPPER}} .amw-ps__pills', [
            'direction' => 'row',
            'justify'   => 'flex-start',
            'align'     => 'center',
            'wrap'      => 'wrap',
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control('header_margin_bottom', [
            'label'      => __('فاصله هدر تا اسلایدر', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 80]],
            'default'    => ['size' => 24, 'unit' => 'px'],
            'separator'  => 'before',
            'selectors'  => ['{{WRAPPER}} .amw-ps__header' => 'margin-bottom: {{SIZE}}{{UNIT}};'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: عنوان ---------------- */

    private function register_header_style_controls(): void {
        $this->start_controls_section('section_style_title', [
            'label' => __('عنوان', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'title_typo',
            'selector' => '{{WRAPPER}} .amw-ps__title',
        ]);

        $this->add_control('title_color', [
            'label'     => __('رنگ', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-ps__title' => 'color: {{VALUE}};'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: دکمه مشاهده همه ---------------- */

    private function register_button_style_controls(): void {
        $this->start_controls_section('section_style_button', [
            'label'     => __('دکمه مشاهده همه', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_view_all' => 'yes'],
        ]);

        $this->add_responsive_control('viewall_hide', [
            'label'       => __('مخفی کردن دکمه', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'description' => __('برای پنهان‌کردن فقط در موبایل، همین کنترل را در حالت موبایل روشن کنید تا دسکتاپ دست‌نخورده بماند.', 'almasara-widgets'),
            'selectors'   => ['{{WRAPPER}} .amw-ps__viewall' => 'display: none;'],
        ]);

        $this->add_responsive_control('btn_width', [
            'label'                => __('عرض دکمه', 'almasara-widgets'),
            'type'                 => Controls_Manager::CHOOSE,
            'default'              => 'auto',
            'options'              => [
                'auto' => ['title' => __('خودکار (اندازه محتوا)', 'almasara-widgets'), 'icon' => 'eicon-h-align-left'],
                'grow' => ['title' => __('پرکردن فضای باقی‌مانده', 'almasara-widgets'), 'icon' => 'eicon-grow'],
                'full' => ['title' => __('تمام عرض ردیف', 'almasara-widgets'), 'icon' => 'eicon-h-align-stretch'],
            ],
            'selectors_dictionary' => [
                'auto' => 'flex: 0 0 auto; width: auto;',
                'grow' => 'flex: 1 1 auto; width: auto;',
                'full' => 'flex: 1 0 100%; width: 100%; justify-content: center;',
            ],
            'selectors'            => ['{{WRAPPER}} .amw-ps__viewall' => '{{VALUE}}'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'btn_typo',
            'selector' => '{{WRAPPER}} .amw-ps__viewall',
        ]);

        $this->add_responsive_control('btn_padding', [
            'label'      => __('پدینگ', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .amw-ps__viewall' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->add_control('btn_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ['{{WRAPPER}} .amw-ps__viewall' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('heading_btn_normal', [
            'label'     => __('حالت عادی', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('btn_color', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-ps__viewall' => 'color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => 'btn_bg',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .amw-ps__viewall',
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'btn_border',
            'selector' => '{{WRAPPER}} .amw-ps__viewall',
        ]);

        $this->add_control('heading_btn_hover', [
            'label'     => __('حالت هاور', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('btn_color_hover', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-ps__viewall:hover' => 'color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => 'btn_bg_hover',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .amw-ps__viewall:hover',
        ]);

        $this->add_control('btn_border_color_hover', [
            'label'     => __('رنگ حاشیه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amw-ps__viewall:hover' => 'border-color: {{VALUE}};'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: پیل‌های دسته‌بندی ---------------- */

    /**
     * مجموعه کامل کنترل‌های یک حالت پیل: تایپوگرافی، رنگ، پس‌زمینه،
     * حاشیه، پدینگ، رادیوس — روی یک سلکتور مشخص (حالت عادی/هاور/فعال).
     */
    private function register_pill_state_controls(string $prefix, string $selector, array $defaults = []): void {
        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => $prefix . '_typo',
            'label'    => __('تایپوگرافی', 'almasara-widgets'),
            'selector' => $selector,
        ]);

        $this->add_control($prefix . '_color', [
            'label'     => __('رنگ متن', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => $defaults['color'] ?? '',
            'selectors' => [$selector => 'color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => $prefix . '_bg',
            'label'    => __('پس‌زمینه', 'almasara-widgets'),
            'types'    => ['classic', 'gradient'],
            'selector' => $selector,
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => $prefix . '_border',
            'label'    => __('حاشیه', 'almasara-widgets'),
            'selector' => $selector,
        ]);

        $this->add_responsive_control($prefix . '_padding', [
            'label'      => __('پدینگ', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->add_responsive_control($prefix . '_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);
    }

    private function register_pills_style_controls(): void {
        $this->start_controls_section('section_style_pills', [
            'label' => __('پیل‌های دسته‌بندی', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('pill_states_note', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('برای جلوگیری از پرش چیدمان هنگام هاور یا انتخاب، بهتر است پدینگ و ضخامت حاشیه را در هر سه حالت یکسان نگه دارید و فقط رنگ‌ها را تغییر دهید.', 'almasara-widgets'),
            'content_classes' => 'elementor-descriptor',
        ]);

        $this->start_controls_tabs('pill_state_tabs');

        $this->start_controls_tab('pill_tab_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->register_pill_state_controls('pill', '{{WRAPPER}} .amw-ps__pill');
        $this->end_controls_tab();

        $this->start_controls_tab('pill_tab_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->register_pill_state_controls('pill_hover', '{{WRAPPER}} .amw-ps__pill:hover');
        $this->end_controls_tab();

        $this->start_controls_tab('pill_tab_active', ['label' => __('فعال', 'almasara-widgets')]);
        $this->register_pill_state_controls('pill_active', '{{WRAPPER}} .amw-ps__pill.is-active', ['color' => '#ffffff']);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    /* ---------------- استایل: کارت (بسته‌بندی دور کارت جت‌انجین) ---------------- */

    private function register_card_style_controls(): void {
        $this->start_controls_section('section_style_card', [
            'label' => __('بسته‌بندی کارت', 'almasara-widgets'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('card_note', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('طراحی خودِ کارت از قالب Listing جت‌انجین می‌آید؛ این کنترل‌ها فقط دور آن را احاطه می‌کنند (مثلاً برای سایه/رادیوس یکسان روی همه کارت‌ها بدون دست‌زدن به قالب).', 'almasara-widgets'),
            'content_classes' => 'elementor-descriptor',
        ]);

        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'      => 'card_bg',
            'types'     => ['classic', 'gradient'],
            'selector'  => '{{WRAPPER}} .amw-ps__card',
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'card_border',
            'selector' => '{{WRAPPER}} .amw-ps__card',
        ]);

        $this->add_control('card_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .amw-ps__card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'card_shadow',
            'selector' => '{{WRAPPER}} .amw-ps__card',
        ]);

        $this->add_control('card_clip_corners', [
            'label'       => __('قطع محتوای اضافی روی گوشه‌های گرد', 'almasara-widgets'),
            'type'        => Controls_Manager::SWITCHER,
            'description' => __('فقط اگر محتوای داخل کارت (مثلاً تصویر) گوشهٔ گرد ندارد و از رادیوس بالا بیرون می‌زند روشن کنید. توجه: با روشن‌بودن این گزینه، هر سایه‌ای — حتی سایه هاوری که خودِ قالب Listing روی کارت گذاشته — روی لبه‌های کارت قطع می‌شود؛ به همین دلیل پیش‌فرض خاموش است.', 'almasara-widgets'),
            'selectors'   => ['{{WRAPPER}} .amw-ps__card' => 'overflow: hidden;'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: کارت داخلی ---------------- */

    /**
     * مجموعهٔ استایل «جعبه‌ای» یک حالت: پس‌زمینه، حاشیه، رادیوس، سایه، پدینگ.
     * در همهٔ تب‌های عادی/هاورِ کارت تکرار می‌شود.
     */
    private function card_box_controls(string $prefix, string $selector, bool $with_padding = true): void {
        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => $prefix . '_bg',
            'label'    => __('پس‌زمینه', 'almasara-widgets'),
            'types'    => ['classic', 'gradient'],
            'selector' => $selector,
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => $prefix . '_border',
            'label'    => __('حاشیه', 'almasara-widgets'),
            'selector' => $selector,
        ]);

        $this->add_responsive_control($prefix . '_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => $prefix . '_shadow',
            'selector' => $selector,
        ]);

        if ($with_padding) {
            $this->add_responsive_control($prefix . '_padding', [
                'label'      => __('پدینگ', 'almasara-widgets'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
            ]);
        }
    }

    /** مجموعهٔ استایل «متنی» یک حالت: تایپوگرافی، رنگ، سایهٔ متن، پدینگ، تراز */
    private function card_text_controls(string $prefix, string $selector): void {
        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => $prefix . '_typo',
            'label'    => __('تایپوگرافی', 'almasara-widgets'),
            'selector' => $selector,
        ]);

        $this->add_control($prefix . '_color', [
            'label'     => __('رنگ', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$selector => 'color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Text_Shadow::get_type(), [
            'name'     => $prefix . '_tshadow',
            'selector' => $selector,
        ]);

        $this->add_responsive_control($prefix . '_padding', [
            'label'      => __('پدینگ', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->add_responsive_control($prefix . '_align', [
            'label'     => __('ترازبندی', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'start'  => ['title' => __('ابتدا', 'almasara-widgets'), 'icon' => 'eicon-text-align-right'],
                'center' => ['title' => __('وسط', 'almasara-widgets'), 'icon' => 'eicon-text-align-center'],
                'end'    => ['title' => __('انتها', 'almasara-widgets'), 'icon' => 'eicon-text-align-left'],
            ],
            'selectors' => [$selector => 'text-align: {{VALUE}};'],
        ]);
    }

    /**
     * «هاور» در همهٔ بخش‌های داخلی یعنی هاور روی خودِ کارت، نه روی آن بخش —
     * پس سلکتور هاور همیشه از ریشهٔ .amw-card شروع می‌شود.
     */
    private function card_hover_selector(string $child = ''): string {
        return '{{WRAPPER}} .amw-card:hover' . ('' === $child ? '' : ' ' . $child);
    }

    private function register_card_item_style_controls(): void {
        $this->start_controls_section('section_style_card_item', [
            'label'     => __('کارت: آیتم', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['card_source' => 'builtin'],
        ]);

        $this->add_control('card_item_hover_note', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('در همهٔ بخش‌های کارت، «هاور» یعنی موس روی هر جای کارت باشد — نه فقط روی خودِ آن بخش.', 'almasara-widgets'),
            'content_classes' => 'elementor-descriptor',
        ]);

        $this->add_responsive_control('card_item_transition', [
            'label'      => __('مدت انیمیشن حالت هاور (ثانیه)', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['s'],
            'range'      => ['s' => ['min' => 0, 'max' => 2, 'step' => 0.05]],
            'default'    => ['size' => 0.25, 'unit' => 's'],
            'selectors'  => ['{{WRAPPER}} .amw-card' => '--amw-card-transition: {{SIZE}}s;'],
        ]);

        $this->add_responsive_control('card_item_height', [
            'label'       => __('ارتفاع کارت', 'almasara-widgets'),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => ['px', '%', 'vh'],
            'range'       => ['px' => ['min' => 100, 'max' => 900]],
            'description' => __('خالی = ارتفاع از محتوا. چون کارت‌ها کشیده می‌شوند، معمولاً لازم نیست.', 'almasara-widgets'),
            'selectors'   => ['{{WRAPPER}} .amw-card' => 'height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->start_controls_tabs('card_item_tabs');

        $this->start_controls_tab('card_item_tab_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->card_box_controls('card_item', '{{WRAPPER}} .amw-card');
        $this->end_controls_tab();

        $this->start_controls_tab('card_item_tab_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->card_box_controls('card_item_hover', $this->card_hover_selector());
        $this->add_responsive_control('card_item_hover_move', [
            'label'      => __('جابه‌جایی عمودی', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => -40, 'max' => 40]],
            'selectors'  => [$this->card_hover_selector() => 'transform: translateY({{SIZE}}{{UNIT}});'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control('heading_card_body', [
            'label'     => __('بدنه (زیر تصویر)', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_full_flex_controls('card_body', '{{WRAPPER}} .amw-card__body', [
            'direction' => 'column',
            'align'     => 'stretch',
        ]);

        $this->add_responsive_control('card_body_padding', [
            'label'      => __('پدینگ بدنه', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .amw-card__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->end_controls_section();
    }

    private function register_card_image_style_controls(): void {
        $this->start_controls_section('section_style_card_image', [
            'label'     => __('کارت: تصویر', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['card_source' => 'builtin', 'card_show_image' => 'yes'],
        ]);

        $this->add_responsive_control('card_img_width', [
            'label'      => __('عرض', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'default'    => ['size' => 100, 'unit' => '%'],
            'range'      => ['px' => ['min' => 20, 'max' => 600]],
            'selectors'  => ['{{WRAPPER}} .amw-card__img' => 'width: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('card_img_max_width', [
            'label'      => __('حداکثر عرض', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => ['px' => ['min' => 20, 'max' => 800]],
            'selectors'  => ['{{WRAPPER}} .amw-card__img' => 'max-width: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('card_img_height', [
            'label'      => __('ارتفاع', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%', 'vh'],
            'range'      => ['px' => ['min' => 40, 'max' => 700]],
            'selectors'  => ['{{WRAPPER}} .amw-card__img' => 'height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('card_img_ratio', [
            'label'       => __('نسبت ابعاد', 'almasara-widgets'),
            'type'        => Controls_Manager::SELECT,
            'default'     => '',
            'options'     => [
                ''      => __('پیش‌فرض (از خودِ تصویر)', 'almasara-widgets'),
                '1/1'   => __('۱:۱ مربع', 'almasara-widgets'),
                '4/3'   => '۴:۳',
                '3/4'   => '۳:۴',
                '16/9'  => '۱۶:۹',
                '3/2'   => '۳:۲',
                '2/3'   => '۲:۳',
            ],
            'description' => __('با تعیین نسبت، همهٔ تصویرها هم‌اندازه می‌شوند و کارت‌ها هم‌قد می‌مانند.', 'almasara-widgets'),
            'selectors'   => ['{{WRAPPER}} .amw-card__img' => 'aspect-ratio: {{VALUE}};'],
        ]);

        $this->add_control('card_img_fit', [
            'label'     => __('نحوهٔ جاگیری تصویر (object-fit)', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'cover',
            'options'   => [
                'cover'      => __('پوشاندن کامل (برش لبه‌ها)', 'almasara-widgets'),
                'contain'    => __('جاشدن کامل (بدون برش)', 'almasara-widgets'),
                'fill'       => __('کشیده‌شدن', 'almasara-widgets'),
                'none'       => __('اندازهٔ اصلی', 'almasara-widgets'),
                'scale-down' => __('کوچک‌شدن در صورت نیاز', 'almasara-widgets'),
            ],
            'selectors' => ['{{WRAPPER}} .amw-card__img' => 'object-fit: {{VALUE}};'],
        ]);

        $this->add_control('card_img_position', [
            'label'     => __('نقطهٔ تمرکز تصویر', 'almasara-widgets'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'center center',
            'options'   => [
                'center center' => __('وسط', 'almasara-widgets'),
                'top center'    => __('بالا', 'almasara-widgets'),
                'bottom center' => __('پایین', 'almasara-widgets'),
                'center left'   => __('چپ', 'almasara-widgets'),
                'center right'  => __('راست', 'almasara-widgets'),
            ],
            'selectors' => ['{{WRAPPER}} .amw-card__img' => 'object-position: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'      => 'card_img_border',
            'label'     => __('حاشیه', 'almasara-widgets'),
            'selector'  => '{{WRAPPER}} .amw-card__img',
            'separator' => 'before',
        ]);

        $this->add_responsive_control('card_img_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .amw-card__img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('card_media_padding', [
            'label'      => __('پدینگ کادر تصویر', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .amw-card__media' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('card_media_align', [
            'label'     => __('ترازبندی افقی تصویر', 'almasara-widgets'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'flex-start' => ['title' => __('ابتدا', 'almasara-widgets'), 'icon' => 'eicon-h-align-right'],
                'center'     => ['title' => __('وسط', 'almasara-widgets'), 'icon' => 'eicon-h-align-center'],
                'flex-end'   => ['title' => __('انتها', 'almasara-widgets'), 'icon' => 'eicon-h-align-left'],
            ],
            'selectors' => ['{{WRAPPER}} .amw-card__media' => 'justify-content: {{VALUE}};'],
        ]);

        $this->start_controls_tabs('card_img_tabs');

        $this->start_controls_tab('card_img_tab_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->card_image_state_controls('card_img', '{{WRAPPER}} .amw-card__img', 1, 1);
        $this->end_controls_tab();

        $this->start_controls_tab('card_img_tab_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->card_image_state_controls('card_img_hover', $this->card_hover_selector('.amw-card__img'), 1, 1.05);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    /** شفافیت و مقیاس تصویر در یک حالت */
    private function card_image_state_controls(string $prefix, string $selector, float $opacity, float $scale): void {
        $this->add_responsive_control($prefix . '_opacity', [
            'label'      => __('شفافیت', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 1, 'step' => 0.01]],
            'default'    => ['size' => $opacity],
            'selectors'  => [$selector => 'opacity: {{SIZE}};'],
        ]);

        $this->add_responsive_control($prefix . '_scale', [
            'label'      => __('مقیاس', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0.5, 'max' => 2, 'step' => 0.01]],
            'default'    => ['size' => $scale],
            'selectors'  => [$selector => 'transform: scale({{SIZE}});'],
        ]);

        $this->add_group_control(Group_Control_Css_Filter::get_type(), [
            'name'     => $prefix . '_filter',
            'selector' => $selector,
        ]);
    }

    private function register_card_title_style_controls(): void {
        $this->start_controls_section('section_style_card_title', [
            'label'     => __('کارت: عنوان', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['card_source' => 'builtin', 'card_show_title' => 'yes'],
        ]);

        $this->add_responsive_control('card_title_margin', [
            'label'      => __('مارجین', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .amw-card__title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->start_controls_tabs('card_title_tabs');

        $this->start_controls_tab('card_title_tab_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->card_text_controls('card_title', '{{WRAPPER}} .amw-card__title');
        $this->end_controls_tab();

        $this->start_controls_tab('card_title_tab_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->card_text_controls('card_title_hover', $this->card_hover_selector('.amw-card__title'));
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    private function register_card_price_style_controls(): void {
        $this->start_controls_section('section_style_card_price', [
            'label'     => __('کارت: بخش قیمت', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['card_source' => 'builtin'],
        ]);

        /* --- ۱) خودِ دیواید بخش قیمت --- */

        $this->add_control('heading_card_pricerow', [
            'label' => __('کادر بخش قیمت', 'almasara-widgets'),
            'type'  => Controls_Manager::HEADING,
        ]);

        $this->add_full_flex_controls('card_pricerow', '{{WRAPPER}} .amw-card__price', [
            'direction' => 'row',
            'justify'   => 'space-between',
            'align'     => 'center',
            'wrap'      => 'wrap',
        ]);

        $this->add_responsive_control('card_pricerow_margin', [
            'label'      => __('مارجین', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .amw-card__price' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->start_controls_tabs('card_pricerow_tabs');

        $this->start_controls_tab('card_pricerow_tab_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->card_box_controls('card_pricerow', '{{WRAPPER}} .amw-card__price');
        $this->end_controls_tab();

        $this->start_controls_tab('card_pricerow_tab_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->card_box_controls('card_pricerow_hover', $this->card_hover_selector('.amw-card__price'));
        $this->end_controls_tab();

        $this->end_controls_tabs();

        /* --- ۲) شعار --- */

        $this->add_control('heading_card_slogan', [
            'label'     => __('شعار', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['card_show_slogan' => 'yes'],
        ]);

        $this->add_responsive_control('card_slogan_radius', [
            'label'      => __('رادیوس', 'almasara-widgets'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .amw-card__slogan' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
            'condition'  => ['card_show_slogan' => 'yes'],
        ]);

        $this->start_controls_tabs('card_slogan_tabs', ['condition' => ['card_show_slogan' => 'yes']]);

        $this->start_controls_tab('card_slogan_tab_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->card_text_controls('card_slogan', '{{WRAPPER}} .amw-card__slogan');
        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => 'card_slogan_bg',
            'label'    => __('پس‌زمینه', 'almasara-widgets'),
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .amw-card__slogan',
        ]);
        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'card_slogan_border',
            'label'    => __('حاشیه', 'almasara-widgets'),
            'selector' => '{{WRAPPER}} .amw-card__slogan',
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('card_slogan_tab_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->card_text_controls('card_slogan_hover', $this->card_hover_selector('.amw-card__slogan'));
        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => 'card_slogan_bg_hover',
            'label'    => __('پس‌زمینه', 'almasara-widgets'),
            'types'    => ['classic', 'gradient'],
            'selector' => $this->card_hover_selector('.amw-card__slogan'),
        ]);
        $this->add_control('card_slogan_border_color_hover', [
            'label'     => __('رنگ حاشیه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$this->card_hover_selector('.amw-card__slogan') => 'border-color: {{VALUE}};'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        /* --- ۳) قیمت: بستهٔ عدد+واحد، و هرکدام جداگانه --- */

        $this->add_control('heading_card_amount', [
            'label'     => __('قیمت', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['card_show_price' => 'yes'],
        ]);

        $this->add_full_flex_controls('card_amount', '{{WRAPPER}} .amw-card__amount', [
            'direction' => 'row',
            'align'     => 'baseline',
        ]);

        $this->start_controls_tabs('card_amount_tabs', ['condition' => ['card_show_price' => 'yes']]);

        $this->start_controls_tab('card_amount_tab_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->add_control('heading_card_num_normal', [
            'label' => __('عدد', 'almasara-widgets'),
            'type'  => Controls_Manager::HEADING,
        ]);
        $this->card_text_controls('card_num', '{{WRAPPER}} .amw-card__num');
        $this->add_control('heading_card_unit_normal', [
            'label'     => __('واحد پول', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);
        $this->card_text_controls('card_unit', '{{WRAPPER}} .amw-card__unit');
        $this->end_controls_tab();

        $this->start_controls_tab('card_amount_tab_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->add_control('heading_card_num_hover', [
            'label' => __('عدد', 'almasara-widgets'),
            'type'  => Controls_Manager::HEADING,
        ]);
        $this->card_text_controls('card_num_hover', $this->card_hover_selector('.amw-card__num'));
        $this->add_control('heading_card_unit_hover', [
            'label'     => __('واحد پول', 'almasara-widgets'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);
        $this->card_text_controls('card_unit_hover', $this->card_hover_selector('.amw-card__unit'));
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    /* ---------------- استایل: دکمه‌های قبلی/بعدی ---------------- */

    /**
     * یک حالت آیکون ناوبری (عادی/هاور/غیرفعال): رنگ آیکون، پس‌زمینه، سایه،
     * شفافیت.
     *
     * دربارهٔ رنگ‌کردن SVG — دو نکته که نسخهٔ قبل را ناکار می‌کرد:
     *   • «svg [fill]» سلکتور نواده است و به خودِ تگ ریشهٔ <svg fill="..">
     *     نمی‌خورد، درحالی‌که بسیاری از فایل‌های آیکون رنگ را همان‌جا
     *     می‌گذارند. پس ریشه جداگانه هدف گرفته می‌شود.
     *   • فایل‌های خروجی ایلوستریتور/فیگما معمولاً style="fill:.." اینلاین
     *     دارند و استایل اینلاین از قاعدهٔ معمولی قوی‌تر است؛ تنها راه غلبه
     *     !important است. چون سلکتورها فقط به svgِ همین دکمه محدودند، دامنهٔ
     *     اثرش بسته است.
     * حالت‌های هاور/غیرفعال هم به‌خاطر ویژگیِ بالاترشان روی حالت عادی
     * می‌نشینند، حتی با وجود !important در هر دو.
     */
    private function register_nav_state_controls(string $prefix, string $selector, array $defaults = []): void {
        $paintable = static function (string $attr) use ($selector): array {
            $skip = ':not([' . $attr . '="none"]):not([' . $attr . '="transparent"])';

            return [
                // ریشهٔ svg
                $selector . ' svg' . $skip,
                // فرزندان دارای همان ویژگی
                $selector . ' svg [' . $attr . ']' . $skip,
            ];
        };

        $fill_targets   = $paintable('fill');
        $stroke_targets = $paintable('stroke');

        $this->add_control($prefix . '_color', [
            'label'       => __('رنگ آیکون', 'almasara-widgets'),
            'type'        => Controls_Manager::COLOR,
            'default'     => $defaults['color'] ?? '',
            'description' => __('هم روی آیکون پیش‌فرض و هم روی SVG سفارشی اثر می‌گذارد؛ بخش‌هایی که عمداً بی‌رنگ‌اند (none/transparent) دست‌نخورده می‌مانند.', 'almasara-widgets'),
            'selectors'   => [
                $selector                     => 'color: {{VALUE}};',
                implode(', ', $fill_targets)  => 'fill: {{VALUE}} !important;',
                implode(', ', $stroke_targets) => 'stroke: {{VALUE}} !important;',
            ],
        ]);

        $this->add_control($prefix . '_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => $defaults['bg'] ?? '',
            'selectors' => [$selector => 'background-color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => $prefix . '_shadow',
            'selector' => $selector,
        ]);

        $this->add_responsive_control($prefix . '_opacity', [
            'label'      => __('شفافیت', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 1, 'step' => 0.01]],
            'default'    => ['size' => $defaults['opacity'] ?? 1],
            'selectors'  => [$selector => 'opacity: {{SIZE}};'],
        ]);
    }

    private function register_nav_style_controls(): void {
        $this->start_controls_section('section_style_nav', [
            'label'     => __('دکمه‌های قبلی/بعدی', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_navigation' => 'yes'],
        ]);

        $this->add_responsive_control('nav_size', [
            'label'      => __('اندازه آیکون', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 10, 'max' => 40]],
            'default'    => ['size' => 18, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-ps__btn' => '--amw-ps-nav-size: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('nav_box_size', [
            'label'      => __('اندازه دکمه', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 24, 'max' => 80]],
            'default'    => ['size' => 40, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-ps__btn' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('nav_offset_x', [
            'label'      => __('فاصله از کناره‌های بخش', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => -40, 'max' => 40]],
            'default'    => ['size' => 0, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .amw-ps__btn--prev' => 'left: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .amw-ps__btn--next' => 'right: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('heading_nav_rotate', [
            'label'       => __('زاویه آیکون', 'almasara-widgets'),
            'type'        => Controls_Manager::HEADING,
            'separator'   => 'before',
        ]);

        $this->add_control('nav_rotate_note', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('چون فقط یک فایل آیکون آپلود می‌شود، اگر جهت پیش‌فرض آن با دکمه بعدی هم‌خوان نیست، اینجا زاویه چرخش هرکدام را جدا تنظیم کنید.', 'almasara-widgets'),
            'content_classes' => 'elementor-descriptor',
        ]);

        $this->add_responsive_control('nav_rotate_prev', [
            'label'      => __('زاویه آیکون قبلی', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['deg'],
            'range'      => ['deg' => ['min' => -180, 'max' => 180]],
            'default'    => ['size' => 0, 'unit' => 'deg'],
            'selectors'  => ['{{WRAPPER}} .amw-ps__btn--prev svg, {{WRAPPER}} .amw-ps__btn--prev img' => 'transform: rotate({{SIZE}}{{UNIT}});'],
        ]);

        $this->add_responsive_control('nav_rotate_next', [
            'label'      => __('زاویه آیکون بعدی', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['deg'],
            'range'      => ['deg' => ['min' => -180, 'max' => 180]],
            'default'    => ['size' => 0, 'unit' => 'deg'],
            'selectors'  => ['{{WRAPPER}} .amw-ps__btn--next svg, {{WRAPPER}} .amw-ps__btn--next img' => 'transform: rotate({{SIZE}}{{UNIT}});'],
        ]);

        $this->start_controls_tabs('nav_state_tabs');

        $this->start_controls_tab('nav_state_tab_normal', ['label' => __('عادی', 'almasara-widgets')]);
        $this->register_nav_state_controls('nav', '{{WRAPPER}} .amw-ps__btn', ['color' => '#16265c', 'bg' => '#ffffff']);
        $this->end_controls_tab();

        $this->start_controls_tab('nav_state_tab_hover', ['label' => __('هاور', 'almasara-widgets')]);
        $this->register_nav_state_controls('nav_hover', '{{WRAPPER}} .amw-ps__btn:hover');
        $this->end_controls_tab();

        $this->start_controls_tab('nav_state_tab_disabled', ['label' => __('غیرفعال', 'almasara-widgets')]);
        $this->add_control('nav_disabled_note', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('این حالت وقتی دیده می‌شود که اسلایدر «بازگشت به کارت اول» خاموش باشد و کاربر به ابتدا/انتهای لیست برسد.', 'almasara-widgets'),
            'content_classes' => 'elementor-descriptor',
        ]);
        $this->register_nav_state_controls('nav_disabled', '{{WRAPPER}} .amw-ps__btn.swiper-button-disabled', ['opacity' => 0.35]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    /* ---------------- استایل: پیجینیشن ---------------- */

    private function register_pagination_style_controls(): void {
        $this->start_controls_section('section_style_pagination', [
            'label'     => __('پیجینیشن (نقطه‌ها)', 'almasara-widgets'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_pagination' => 'yes'],
        ]);

        $this->add_control('dot_size', [
            'label'      => __('اندازه نقطه', 'almasara-widgets'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 2, 'max' => 20]],
            'default'    => ['size' => 6, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amw-ps__pagination .swiper-pagination-bullet' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('dot_color', [
            'label'     => __('رنگ نقطه غیرفعال', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#cad2de',
            'selectors' => ['{{WRAPPER}} .amw-ps__pagination .swiper-pagination-bullet' => 'background: {{VALUE}};'],
        ]);

        $this->add_control('dot_color_active', [
            'label'     => __('رنگ نقطه فعال', 'almasara-widgets'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#16265c',
            'selectors' => ['{{WRAPPER}} .amw-ps__pagination .swiper-pagination-bullet-active' => 'background: {{VALUE}};'],
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

    /* =====================================================================
     * رندر
     * =================================================================== */

    protected function render(): void {
        $settings = $this->get_settings_for_display();

        $listing_id = absint($settings['listing_id'] ?? 0);
        $source     = 'builtin' === ($settings['card_source'] ?? '') ? 'builtin' : 'jetengine';

        // محتوای ویجت داخل آی‌فریمِ پیش‌نمایش رندر می‌شود، جایی که is_edit_mode
        // لزوماً true نیست — پس هر دو حالت بررسی می‌شوند، وگرنه ادیتور رفتار
        // فرانت‌اند (از جمله کش) را می‌گرفت.
        $preview   = \Elementor\Plugin::$instance->preview;
        $is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode()
            || ($preview && $preview->is_preview_mode());

        // فقط مسیر جت‌انجین به قالب Listing نیاز دارد
        if ('jetengine' === $source && !$listing_id) {
            if ($is_editor) {
                echo '<div class="amw-ps__notice">' . esc_html__('یک قالب Listing جت‌انجین برای کارت محصول انتخاب کنید، یا در همان بخش «کارت محصول از» را روی «کارت داخلی این افزونه» بگذارید.', 'almasara-widgets') . '</div>';
            }
            return;
        }

        $card = [
            'show_image'  => 'yes' === ($settings['card_show_image'] ?? 'yes'),
            'show_title'  => 'yes' === ($settings['card_show_title'] ?? 'yes'),
            'show_slogan' => 'yes' === ($settings['card_show_slogan'] ?? 'yes'),
            'show_price'  => 'yes' === ($settings['card_show_price'] ?? 'yes'),
            'link_card'   => 'yes' === ($settings['card_link'] ?? 'yes'),
            'link_title'  => 'yes' === ($settings['card_link_title'] ?? ''),
            'new_tab'     => 'yes' === ($settings['card_new_tab'] ?? ''),
            'title_tag'   => (string) ($settings['card_title_tag'] ?? 'h3'),
            'title_lines' => (int) ($settings['card_title_lines'] ?? 2),
            'slogan'      => (string) ($settings['card_slogan'] ?? ''),
            'unit'        => (string) ($settings['card_unit'] ?? ''),
            'free_text'   => (string) ($settings['card_free_text'] ?? ''),
            'image_size'  => (string) ($settings['card_image_size'] ?? 'woocommerce_thumbnail'),
        ];

        $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');

        // المنتور دسکتاپ‌محور (پایه=دسکتاپ) → breakpoints موبایل‌محور Swiper
        // (پایه=کوچک‌ترین)، عیناً مثل ویجت اسلایدر هیرو.
        //
        // نکتهٔ مهم: المنتور مقدارِ تنظیم‌نشدهٔ یک کنترل ریسپانسیو را «رشتهٔ
        // خالی» ذخیره می‌کند، نه null. پس ?? هیچ‌وقت عمل نمی‌کرد و مثلاً
        // (int) '' برابر صفر می‌شد — یعنی فاصلهٔ کارت‌ها و سرعت گذار روی
        // تبلت و موبایل به‌زور صفر می‌شدند و مقدار دسکتاپ دور ریخته می‌شد.
        // ترتیب ارث‌بری هم مثل خودِ CSS المنتور است: موبایل ← تبلت ← دسکتاپ.
        $set = static function ($value) {
            if (is_array($value)) {
                return (isset($value['size']) && '' !== $value['size'] && null !== $value['size']) ? $value : null;
            }
            return ('' !== $value && null !== $value) ? $value : null;
        };

        $responsive = static function (array $settings, string $key, callable $cast) use ($set) {
            $desktop = $set($settings[$key] ?? null);
            $tablet  = $set($settings[$key . '_tablet'] ?? null) ?? $desktop;
            $mobile  = $set($settings[$key . '_mobile'] ?? null) ?? $tablet;

            return [
                'desktop' => $cast($desktop),
                'tablet'  => $cast($tablet),
                'mobile'  => $cast($mobile),
            ];
        };
        $to_int   = static fn($v) => (int) $v;
        $to_float = static fn($v) => (float) $v;
        $is_set   = static fn($v) => null !== $v;

        $speed = $responsive($settings, 'speed', $to_int);
        $spv   = $responsive($settings, 'slides_per_view', $to_float);
        $space = $responsive($settings, 'space_between', $to_int);

        // عرض دستی کارت: هرجا مقدار دارد، خودِ CSS عرض اسلاید را تعیین می‌کند
        // و Swiper باید در همان بریک‌پوینت روی 'auto' برود تا عرض را بازنویسی
        // نکند؛ وگرنه «تعداد کارت هم‌زمان» ملاک می‌ماند.
        $width = $responsive($settings, 'slide_width', $is_set);
        foreach (['desktop', 'tablet', 'mobile'] as $device) {
            if ($width[$device]) {
                $spv[$device] = 'auto';
            }
        }

        $cfg = [
            'restUrl'              => esc_url_raw(rest_url('almasara/v1/product-section')),
            'listingId'            => $listing_id,
            'count'                => max(1, (int) $settings['products_count']),
            'orderby'              => $settings['orderby'],
            'order'                => $settings['order'],
            // در ادیتور هرگز کش نمی‌شود: وگرنه بعد از تغییر تنظیمات، خروجیِ
            // ذخیره‌شدهٔ حالت قبلی نمایش داده می‌شد و پیش‌نمایش با چیزی که
            // واقعاً منتشر می‌شود نمی‌خواند.
            'cache'                => $is_editor ? 0 : max(0, (int) ($settings['cache_minutes'] ?? 0)),
            'speed'                => $speed['mobile'],
            'slidesPerView'        => $spv['mobile'],
            'spaceBetween'         => $space['mobile'],
            'breakpoints'          => [
                768  => ['speed' => $speed['tablet'], 'slidesPerView' => $spv['tablet'], 'spaceBetween' => $space['tablet']],
                1025 => ['speed' => $speed['desktop'], 'slidesPerView' => $spv['desktop'], 'spaceBetween' => $space['desktop']],
            ],
            'rewind'               => 'yes' === $settings['rewind'],
            'rtl'                  => 'yes' === $settings['rtl'],
            'autoplay'             => 'yes' === $settings['autoplay'],
            'delay'                => max(1000, (int) $settings['autoplay_delay']),
            'disableOnInteraction' => 'yes' === ($settings['pause_on_interaction'] ?? ''),
            'navigation'           => 'yes' === $settings['show_navigation'],
            'pagination'           => 'yes' === $settings['show_pagination'],
            'paginationClickable'  => 'yes' === ($settings['pagination_clickable'] ?? ''),
            'hasPrice'             => 'yes' === ($settings['filter_has_price'] ?? ''),
            'inStock'              => 'yes' === ($settings['filter_in_stock'] ?? ''),
            'hasImage'             => 'yes' === ($settings['filter_has_image'] ?? ''),
            'minPrice'             => max(0, (float) ($settings['filter_min_price'] ?? 0)),
            'source'               => $source,
            'card'                 => wp_json_encode($card),
        ];

        // حداقل قیمت بدون فیلتر «دارای قیمت» بی‌معناست
        if (!$cfg['hasPrice']) {
            $cfg['minPrice'] = 0.0;
        }

        printf('<div class="amw-ps" data-cfg="%s">', esc_attr(wp_json_encode($cfg)));

        $filters = [
            'has_price' => $cfg['hasPrice'],
            'in_stock'  => $cfg['inStock'],
            'has_image' => $cfg['hasImage'],
            'min_price' => $cfg['minPrice'],
        ];

        $this->render_header($settings, $shop_url, $filters);

        echo '<div class="amw-ps__slider-wrap">';
        echo '<div class="amw-ps__slider swiper">';
        echo '<div class="swiper-wrapper">';

        $result = \Almasara_Widgets\Product_Section_Ajax::query_and_render([
            'listing_id' => $listing_id,
            'category'   => 0,
            'count'      => $cfg['count'],
            'orderby'    => $cfg['orderby'],
            'order'      => $cfg['order'],
            'cache'      => $cfg['cache'],
            'source'     => $source,
            'card'       => $card,
        ] + $filters);
        echo $result['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- رندرشده از قالب Listing، محتوایش مسئولیت خودِ جت‌انجین است

        echo '</div>'; // .swiper-wrapper
        echo '</div>'; // .swiper

        if ('yes' === $settings['show_navigation']) {
            echo '<button type="button" class="amw-ps__btn amw-ps__btn--prev" aria-label="' . esc_attr__('قبلی', 'almasara-widgets') . '">';
            $this->render_nav_icon($settings, true);
            echo '</button>';
            echo '<button type="button" class="amw-ps__btn amw-ps__btn--next" aria-label="' . esc_attr__('بعدی', 'almasara-widgets') . '">';
            $this->render_nav_icon($settings, false);
            echo '</button>';
        }

        echo '</div>'; // .amw-ps__slider-wrap

        // پیجینیشن عمداً بیرون از «slider-wrap» است: هم از باکس برش سوایپر
        // خارج می‌ماند، هم ارتفاعش وارد محاسبهٔ «top:50%» دکمه‌های ناوبری
        // نمی‌شود تا دکمه‌ها همیشه دقیقاً وسط کارت‌ها بایستند.
        if ('yes' === $settings['show_pagination']) {
            echo '<div class="swiper-pagination amw-ps__pagination"></div>';
        }

        echo '</div>'; // .amw-ps
    }

    /** هدر: عنوان + پیل‌های دسته‌بندی + دکمه مشاهده همه */
    private function render_header(array $settings, string $shop_url, array $filters): void {
        echo '<div class="amw-ps__header">';

        if ('' !== trim((string) $settings['title'])) {
            echo '<h2 class="amw-ps__title">' . esc_html($settings['title']) . '</h2>';
        }

        // پیل‌های دسته‌بندی + دکمه مشاهده همه با هم در یک ردیف مدیریت‌شده
        // (تب «ردیف فیلتر» در استایل → چیدمان)
        echo '<div class="amw-ps__filter-row">';

        echo '<div class="amw-ps__pills" role="tablist">';
        printf(
            '<button type="button" class="amw-ps__pill is-active" data-term="0" data-link="%s" role="tab" aria-selected="true">%s</button>',
            esc_url($shop_url),
            esc_html($settings['all_label'])
        );

        // دسته‌هایی که با فیلترهای فعال هیچ محصولی ندارند یکجا کنار گذاشته
        // می‌شوند (کلیک روی پیل خالی تجربه بدی است). یکجا و کش‌شده، تا مثل
        // قبل به‌ازای هر پیل یک کوئری جدا زده نشود.
        $rows      = (array) ($settings['categories'] ?? []);
        $wanted    = array_map(static fn($row) => absint($row['category'] ?? 0), $rows);
        $non_empty = \Almasara_Widgets\Product_Section_Ajax::filter_non_empty_categories($wanted, $filters);

        foreach ($rows as $row) {
            $term_id = absint($row['category'] ?? 0);
            if (!$term_id || !in_array($term_id, $non_empty, true)) {
                continue;
            }
            $term = get_term($term_id, 'product_cat');
            if (!$term || is_wp_error($term)) {
                continue;
            }
            $label = '' !== trim((string) ($row['label'] ?? '')) ? $row['label'] : $term->name;
            $link  = get_term_link($term, 'product_cat');
            printf(
                '<button type="button" class="amw-ps__pill" data-term="%d" data-link="%s" role="tab" aria-selected="false">%s</button>',
                $term_id,
                esc_url(is_wp_error($link) ? $shop_url : $link),
                esc_html($label)
            );
        }
        echo '</div>'; // .amw-ps__pills

        if ('yes' === $settings['show_view_all']) {
            printf(
                '<a class="amw-ps__viewall" href="%s">%s</a>',
                esc_url($shop_url),
                esc_html($settings['view_all_text'])
            );
        }

        echo '</div>'; // .amw-ps__filter-row

        echo '</div>'; // .amw-ps__header
    }

    /** آیکون فلش سفارشی یا شورون پیش‌فرض inline (رنگ‌پذیر با currentColor) */
    private function render_nav_icon(array $settings, bool $is_prev): void {
        if (!empty($settings['nav_icon']['url'])) {
            $url    = $settings['nav_icon']['url'];
            $is_svg = 'svg' === strtolower(pathinfo(wp_parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
            if ($is_svg && !empty($settings['nav_icon']['id'])) {
                $svg = $this->get_inline_svg((int) $settings['nav_icon']['id']);
                if ($svg) {
                    echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput -- sanitized in get_inline_svg()
                    return;
                }
            }
            printf('<img src="%s" alt="">', esc_url($url));
            return;
        }

        $path = $is_prev ? 'm15 6-6 6 6 6' : 'm9 6 6 6-6 6';
        printf(
            '<svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true"><path d="%s" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            $path
        );
    }
}
