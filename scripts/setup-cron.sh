#!/bin/bash
# One-time cron setup per server. Run from project root as root.
# Usage: ./scripts/setup-cron.sh SITE_USER [PHP_PATH]
# Example: ./scripts/setup-cron.sh taskbook-horizon /usr/bin/php8.4
set -e
if [ -z "$1" ]; then
  echo "Usage: $0 SITE_USER [PHP_PATH]"
  echo "Example: $0 taskbook-horizon /usr/bin/php8.4"
  exit 1
fi
USER="$1"
PHP="${2:-php}"
DIR="$(pwd)"
LINE1="* * * * * cd $DIR && $PHP artisan schedule:run >> /dev/null 2>&1"
LINE2="* * * * * sleep 30 && cd $DIR && $PHP artisan schedule:run >> /dev/null 2>&1"
EXISTING=$(crontab -u "$USER" -l 2>/dev/null || true)
# Avoid duplicate lines: keep only crontab lines that don't match our schedule:run
FILTERED=$(echo "$EXISTING" | grep -v "artisan schedule:run" || true)
( echo "$FILTERED"; echo "$LINE1"; echo "$LINE2" ) | crontab -u "$USER" -
echo "Cron set for user $USER (path=$DIR)."
crontab -u "$USER" -l
