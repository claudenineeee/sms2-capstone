#!/bin/bash
set -e

# Default to port 80 if PORT environment variable is not set
APP_PORT="${PORT:-80}"

# Configure Apache to listen on APP_PORT, 80, and 8080 so health checks pass on any port
cat <<EOF > /etc/apache2/ports.conf
Listen ${APP_PORT}
EOF

if [ "${APP_PORT}" != "80" ]; then
    echo "Listen 80" >> /etc/apache2/ports.conf
fi

if [ "${APP_PORT}" != "8080" ]; then
    echo "Listen 8080" >> /etc/apache2/ports.conf
fi

# Configure VirtualHost to respond to any listening port
cat <<EOF > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:* >
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

exec apache2-foreground "$@"
