<?php
/**
 * اجراکنندهٔ تست‌ها.
 *
 *   php tests/run.php            همه
 *   php tests/run.php svg        فقط فایل‌هایی که نامشان svg دارد
 */

require_once __DIR__ . '/bootstrap.php';

$filter = $argv[1] ?? '';
$files  = glob(__DIR__ . '/*-test.php');

if ($filter) {
    $files = array_values(array_filter(
        $files,
        static fn($file) => false !== stripos(basename($file), $filter)
    ));
}

if (!$files) {
    echo "هیچ فایل تستی پیدا نشد.\n";
    exit(1);
}

foreach ($files as $file) {
    require_once $file;
}

exit(Tests::summary());
