#!/usr/bin/env bash
#
# افزودن المنتور و یک صفحهٔ آزمایشی به نصبِ ساخته‌شده با
# tests/integration/setup.sh، برای تست‌های مرورگری.
#
#   bash tests/integration/setup.sh /tmp/wpint
#   bash tests/e2e/setup.sh /tmp/wpint
#   WP_ROOT=/tmp/wpint/wp node tests/e2e/run.mjs

set -euo pipefail

TARGET="${1:-/tmp/wpint}"
WP="$TARGET/wp"
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PORT="${PORT:-8899}"

if [ ! -f "$WP/wp-load.php" ]; then
    echo "اول tests/integration/setup.sh را اجرا کنید." >&2
    exit 1
fi

echo "→ دریافت المنتور"
cd "$TARGET"
curl -sSL https://downloads.wordpress.org/plugin/elementor.latest-stable.zip -o el.zip
unzip -qo el.zip -d "$WP/wp-content/plugins/"

echo "→ قرار دادن افزونه"
# محتوا کپی می‌شود نه خودِ پوشه، تا اگر مقصد از قبل باشد کپیِ تودرتو نسازد
DEST="$WP/wp-content/plugins/almasara-elementor-widgets"
rm -rf "$DEST"
mkdir -p "$DEST"
cp -r "$PLUGIN_DIR/." "$DEST/"
rm -rf "$DEST/.git" "$DEST/node_modules"

echo "→ تنظیم آدرس سایت روی 127.0.0.1:$PORT"
sed -i "s|define('WP_HOME','[^']*'); define('WP_SITEURL','[^']*');|define('WP_HOME','http://127.0.0.1:$PORT'); define('WP_SITEURL','http://127.0.0.1:$PORT');|" "$WP/wp-config.php"

echo "→ فعال‌سازی و ساخت صفحهٔ آزمایشی"
php -r "
require '$WP/wp-load.php';
require_once ABSPATH.'wp-admin/includes/plugin.php';

foreach (['elementor/elementor.php','almasara-elementor-widgets/almasara-elementor-widgets.php'] as \$p) {
    \$r = activate_plugin(\$p);
    if (is_wp_error(\$r)) { fwrite(STDERR, \$p.': '.\$r->get_error_message().PHP_EOL); exit(1); }
}

update_option('permalink_structure','/%postname%/');
flush_rewrite_rules(true);

// محصولات تازه
foreach (get_posts(['post_type'=>['product','product_variation'],'numberposts'=>-1,'post_status'=>'any','fields'=>'ids']) as \$id) {
    wp_delete_post(\$id, true);
}
for (\$i = 1; \$i <= 8; \$i++) {
    \$p = new WC_Product_Simple();
    \$p->set_name('محصول شماره '.\$i);
    \$p->set_regular_price((string)(\$i * 100000));
    \$p->set_status('publish');
    \$p->save();
}

// دو ویجت در یک صفحه، تا تداخل چند نمونه هم سنجیده شود
\$widget = function (\$id) {
    return ['id'=>\$id,'elType'=>'widget','widgetType'=>'almasara-product-section','settings'=>[
        'title'=>'محصولات','card_source'=>'builtin','products_count'=>8,
        'slides_per_view'=>4,'slides_per_view_tablet'=>2.2,'slides_per_view_mobile'=>1.2,
        'space_between'=>20,'show_navigation'=>'yes','cache_minutes'=>0,
        'all_label'=>'همه','rtl'=>'yes',
    ]];
};
\$data = [['id'=>'sec1','elType'=>'container','settings'=>[],'elements'=>[\$widget('amwa'), \$widget('amwb')]]];

\$page = get_page_by_path('amw-e2e');
\$pid  = \$page ? \$page->ID : wp_insert_post(['post_title'=>'AMW E2E','post_name'=>'amw-e2e','post_type'=>'page','post_status'=>'publish']);

update_post_meta(\$pid,'_elementor_edit_mode','builder');
update_post_meta(\$pid,'_elementor_version', ELEMENTOR_VERSION);
update_post_meta(\$pid,'_elementor_data', wp_slash(wp_json_encode(\$data)));
delete_post_meta(\$pid,'_elementor_css');

// بدون این، المنتور خروجی قبلی را سرو می‌کند
\Elementor\Plugin::\$instance->files_manager->clear_cache();
wp_cache_flush();

echo 'صفحه: ', get_permalink(\$pid), PHP_EOL;
" 2>/dev/null

echo
echo "آماده است. اجرا:"
echo "  WP_ROOT=$WP node tests/e2e/run.mjs"
