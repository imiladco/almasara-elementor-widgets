<?php
/**
 * اجراکنندهٔ تست‌های یکپارچه.
 *
 *     WP_ROOT=/tmp/wpint/wp php tests/integration/run.php
 *
 * بدون WP_ROOT، با پیام راهنما و کد خروج ۰ رد می‌شود تا نبودِ محیط با
 * شکست تست اشتباه گرفته نشود.
 */

require_once __DIR__ . '/bootstrap.php';

/** قالب‌بندی عدد قیمت، دقیقاً همان‌طور که کارت انجام می‌دهد */
function wc_price_test_format($value): string {
    return number_format(
        (float) $value,
        wc_get_price_decimals(),
        wc_get_price_decimal_separator(),
        wc_get_price_thousand_separator()
    );
}

/*
 * ویجت قیمت از Elementor\Widget_Base ارث می‌برد، ولی منطق قیمتش هیچ
 * وابستگی‌ای به المنتور ندارد. با استابِ کمینه، همان منطق را روی دادهٔ
 * واقعی ووکامرس می‌سنجیم بدون آنکه نصب المنتور لازم شود.
 */
require_once dirname(__DIR__) . '/lib/elementor-stub.php';
require_once dirname(__DIR__, 2) . '/includes/widgets/traits/intro-row.php';
require_once dirname(__DIR__, 2) . '/includes/widgets/product-price.php';

foreach (glob(__DIR__ . '/*-test.php') as $file) {
    require_once $file;
}

Fixture::cleanup();

exit(Tests::summary());
