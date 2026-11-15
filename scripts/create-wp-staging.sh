#!/usr/bin/env bash
# Requires WP-CLI, rsync, mysql client and a pre-created EMPTY staging database.
set -euo pipefail
if [[ $# -ne 5 ]]; then echo 'Usage: create-wp-staging.sh PROD_PATH STAGING_PATH STAGING_URL STAGING_DB STAGING_DB_USER' >&2; exit 2; fi
PROD=$(realpath "$1"); TARGET="$2"; URL="$3"; DB="$4"; DBUSER="$5"
[[ "$URL" == https://* ]] || { echo 'HTTPS staging URL required' >&2; exit 2; }
[[ "$TARGET" != / && "$TARGET" != "$PROD" ]] || exit 2
mkdir -p "$TARGET"; TARGET=$(realpath "$TARGET")
[[ "$TARGET" != "$PROD"/* && "$PROD" != "$TARGET"/* ]] || { echo 'Paths overlap' >&2; exit 2; }
[[ -z "$(ls -A "$TARGET")" ]] || { echo 'Staging directory must be empty' >&2; exit 2; }
[[ -f "$PROD/wp-config.php" ]] || { echo 'Invalid production WordPress path' >&2; exit 2; }
command -v wp >/dev/null; command -v rsync >/dev/null
# Secrets are obtained from environment, never command-line arguments.
: "${STAGING_DB_PASSWORD:?Set STAGING_DB_PASSWORD in the environment}"
wp --path="$PROD" db export - --quiet > "$TARGET/.staging-import.sql"
rsync -a --exclude='.git' --exclude='wp-config.php' --exclude='wp-content/cache/' "$PROD/" "$TARGET/"
cp "$PROD/wp-config.php" "$TARGET/wp-config.php"
chmod 600 "$TARGET/wp-config.php"
wp --path="$TARGET" config set DB_NAME "$DB" --type=constant
wp --path="$TARGET" config set DB_USER "$DBUSER" --type=constant
wp --path="$TARGET" config set DB_PASSWORD "$STAGING_DB_PASSWORD" --type=constant
# Assert target DB empty; avoid accidental production overwrite.
export MYSQL_PWD="$STAGING_DB_PASSWORD"
TABLES=$(mysql -N -u "$DBUSER" "$DB" -e 'SHOW TABLES' | wc -l)
[[ "$TABLES" -eq 0 ]] || { echo 'Target database is NOT empty. Aborting.' >&2; exit 2; }
mysql -u "$DBUSER" "$DB" < "$TARGET/.staging-import.sql"
rm -f "$TARGET/.staging-import.sql"
OLD_URL=$(wp --path="$PROD" option get home)
wp --path="$TARGET" search-replace "$OLD_URL" "$URL" --all-tables --precise --skip-columns=guid --quiet
wp --path="$TARGET" option update blog_public 0 --quiet
wp --path="$TARGET" config set DISABLE_WP_CRON true --raw --type=constant
wp --path="$TARGET" config set WP_ENVIRONMENT_TYPE staging --type=constant
# Application-level suppression is not a security boundary. Enforce HTTP auth and outbound firewall rules.
mkdir -p "$TARGET/wp-content/mu-plugins"
cat > "$TARGET/wp-content/mu-plugins/wpnm-staging-guard.php" <<'EOF'
<?php
/* Staging guard: blocks WordPress wp_mail; does not block direct SMTP/API calls. */
add_filter('pre_wp_mail', static function () { return true; }, 999);
add_filter('wp_robots', static function ($robots) { $robots['noindex'] = true; $robots['nofollow'] = true; return $robots; });
EOF
cat > "$TARGET/wp-content/mu-plugins-placeholder.txt" <<'EOF'
Configure HTTP basic auth, block outgoing email/payment webhooks, disable cron at host level,
and restrict staging database access before visiting this site.
EOF
printf 'Staging clone created at %s. DO NOT open until access and outbound integrations are blocked.\n' "$TARGET"
