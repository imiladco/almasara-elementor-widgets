<?php
/**
 * راه‌انداز تست‌های مستقل.
 *
 * این تست‌ها عمداً به نصب وردپرس نیاز ندارند: هدفشان منطق خالصِ افزونه است
 * (پاک‌سازی SVG، حل مقادیر ریسپانسیو، اعتبارسنجی تنظیمات، سبد کش). همین
 * باعث می‌شود در هر محیطی و در CI بدون سرویس جانبی اجرا شوند.
 *
 * اجرا:  php tests/run.php
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', true);
}

// ثابت‌های وردپرس که کد افزونه به آن‌ها تکیه دارد
foreach ([
    'KB_IN_BYTES'       => 1024,
    'MINUTE_IN_SECONDS' => 60,
    'HOUR_IN_SECONDS'   => 3600,
] as $name => $value) {
    if (!defined($name)) {
        define($name, $value);
    }
}

/* --------------------------------------------------------------------------
 * حداقلِ توابع وردپرس که برای این واحدها لازم است
 * ----------------------------------------------------------------------- */

if (!function_exists('absint')) {
    function absint($value) { return abs((int) $value); }
}
if (!function_exists('wp_json_encode')) {
    function wp_json_encode($value) { return json_encode($value, JSON_UNESCAPED_UNICODE); }
}
if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in() { return $GLOBALS['__test_logged_in'] ?? false; }
}
if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user() {
        return (object) ['roles' => $GLOBALS['__test_roles'] ?? []];
    }
}
if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value) { return $value; }
}
if (!function_exists('get_woocommerce_currency')) {
    function get_woocommerce_currency() { return $GLOBALS['__test_currency'] ?? 'IRT'; }
}

/* --------------------------------------------------------------------------
 * چارچوب کوچک assert
 * ----------------------------------------------------------------------- */

final class Tests {

    private static int $passed = 0;
    private static array $failures = [];
    private static string $group = '';

    public static function group(string $name): void {
        self::$group = $name;
        echo "\n\033[1m{$name}\033[0m\n";
    }

    public static function ok(string $label, bool $condition, string $detail = ''): void {
        if ($condition) {
            self::$passed++;
            echo "  \033[32m✓\033[0m {$label}\n";
            return;
        }

        self::$failures[] = self::$group . ' › ' . $label . ($detail ? " — {$detail}" : '');
        echo "  \033[31m✗ {$label}\033[0m" . ($detail ? " — {$detail}" : '') . "\n";
    }

    public static function same(string $label, $actual, $expected): void {
        self::ok(
            $label,
            $actual === $expected,
            $actual === $expected ? '' : sprintf('got %s, expected %s', var_export($actual, true), var_export($expected, true))
        );
    }

    /** خروجی نباید هیچ‌کدام از این رشته‌ها را داشته باشد */
    public static function blocks(string $label, string $output, array $forbidden): void {
        $leaked = array_values(array_filter(
            $forbidden,
            static fn($needle) => false !== stripos($output, $needle)
        ));

        self::ok($label, [] === $leaked, $leaked ? 'leaked: ' . implode(', ', $leaked) : '');
    }

    public static function summary(): int {
        $failed = count(self::$failures);

        echo "\n" . str_repeat('─', 60) . "\n";

        if ($failed) {
            echo "\033[31m{$failed} failed\033[0m, " . self::$passed . " passed\n\n";
            foreach (self::$failures as $failure) {
                echo "  • {$failure}\n";
            }
            echo "\n";
            return 1;
        }

        echo "\033[32mall " . self::$passed . " assertions passed\033[0m\n\n";
        return 0;
    }
}
