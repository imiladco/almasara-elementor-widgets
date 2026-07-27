#!/usr/bin/env bash
#
# ساخت یک نصب وردپرس + ووکامرس برای تست‌های یکپارچه.
#
#   bash tests/integration/setup.sh [مسیر]
#   WP_ROOT=<مسیر>/wp php tests/integration/run.php
#
# عمداً روی SQLite کار می‌کند تا به سرور دیتابیس نیاز نباشد و هم روی
# لپ‌تاپ و هم در CI بدون سرویس جانبی اجرا شود. افزونهٔ رسمی
# sqlite-database-integration همان drop-in وردپرس را فراهم می‌کند.

set -euo pipefail

TARGET="${1:-/tmp/wpint}"
WP="$TARGET/wp"

echo "→ نصب در $TARGET"
rm -rf "$TARGET"
mkdir -p "$TARGET"
cd "$TARGET"

echo "→ دریافت وردپرس"
curl -sSL https://wordpress.org/latest.tar.gz -o wp.tar.gz
tar xzf wp.tar.gz && mv wordpress wp

echo "→ دریافت درایور SQLite"
curl -sSL https://downloads.wordpress.org/plugin/sqlite-database-integration.zip -o sqlite.zip
unzip -qo sqlite.zip -d "$WP/wp-content/plugins/"

cp "$WP/wp-content/plugins/sqlite-database-integration/db.copy" "$WP/wp-content/db.php"
sed -i \
    -e "s|{SQLITE_IMPLEMENTATION_FOLDER_PATH}|$WP/wp-content/plugins/sqlite-database-integration|" \
    -e "s|{SQLITE_PLUGIN}|sqlite-database-integration/load.php|" \
    "$WP/wp-content/db.php"
mkdir -p "$WP/wp-content/database"

echo "→ دریافت ووکامرس"
curl -sSL https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip -o wc.zip
unzip -qo wc.zip -d "$WP/wp-content/plugins/"

echo "→ نوشتن wp-config"
cat > "$WP/wp-config.php" <<PHP
<?php
define('DB_NAME','wordpress'); define('DB_USER','');
define('DB_PASSWORD',''); define('DB_HOST','localhost');
define('DB_CHARSET','utf8'); define('DB_COLLATE','');
define('AUTH_KEY','x');define('SECURE_AUTH_KEY','x');define('LOGGED_IN_KEY','x');define('NONCE_KEY','x');
define('AUTH_SALT','x');define('SECURE_AUTH_SALT','x');define('LOGGED_IN_SALT','x');define('NONCE_SALT','x');
\$table_prefix='wp_';
define('WP_DEBUG',false);
define('WP_HOME','http://localhost'); define('WP_SITEURL','http://localhost');
if(!defined('ABSPATH')) define('ABSPATH', '$WP/');
require_once ABSPATH.'wp-settings.php';
PHP

echo "→ نصب وردپرس و فعال‌سازی ووکامرس"
php -r "
define('WP_INSTALLING', true);
require '$WP/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
if (!is_blog_installed()) {
    wp_install('AMW Test', 'admin', 'admin@example.com', true, '', 'pass');
}
\$r = activate_plugin('woocommerce/woocommerce.php');
if (is_wp_error(\$r)) { fwrite(STDERR, \$r->get_error_message()); exit(1); }
" > /dev/null

echo
echo "آماده است. اجرا:"
echo "  WP_ROOT=$WP php tests/integration/run.php"
