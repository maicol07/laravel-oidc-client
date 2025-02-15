#!/bin/sh
if [ "$SUPERVISOR_PHP_USER" != "root" ] && [ "$SUPERVISOR_PHP_USER" != "sail" ]; then
    echo "You should set SUPERVISOR_PHP_USER to either 'sail' or 'root'."
    exit 1
fi

webserver=${WEBSERVER:-cli}
if [ "$webserver" = "cli" ]; then
  export SUPERVISOR_PHP_COMMAND="/usr/bin/php -d variables_order=EGPCS /var/www/sample/artisan serve --host=${SERVER_NAME:-0.0.0.0} --port=80"
  elif [ "$webserver" = "octane" ]; then
  export SUPERVISOR_PHP_COMMAND="/usr/bin/php -d variables_order=EGPCS /var/www/sample/artisan octane:start --host=${SERVER_NAME:-0.0.0.0} --port=443 --admin-port=2019 --https"
  elif [ "$webserver" = "octane-watch" ]; then
  export SUPERVISOR_PHP_COMMAND="/usr/bin/php -d variables_order=EGPCS /var/www/sample/artisan octane:start --watch --host=${SERVER_NAME:-0.0.0.0} --port=443 --admin-port=2019 --https"
fi
if [ $# -gt 0 ]; then
    exec "$@"
else
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
fi
