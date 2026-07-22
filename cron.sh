#!/bin/sh
cd "$(dirname "$0")"
/opt/alt/php83/usr/bin/php artisan schedule:run >> /dev/null 2>&1
/opt/alt/php83/usr/bin/php artisan queue:work --stop-when-empty --max-time=60 >> /dev/null 2>&1
