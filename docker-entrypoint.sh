#!/bin/bash
set -e

# Ensure critical directories exist
mkdir -p /var/www/html/tmp/compile
mkdir -p /var/www/html/tmp/aCompile
mkdir -p /var/www/html/tmp/errorLog
mkdir -p /var/www/html/tmp/cache_1954048409
mkdir -p /var/www/html/tmp/upload
mkdir -p /var/www/html/files

# Fix permissions for tmp and files directories
# Using 777 to ensure both web server and CLI can write without issues in container environment
chown -R www-data:www-data /var/www/html/tmp /var/www/html/files
chmod -R 777 /var/www/html/tmp /var/www/html/files

# Clean up empty index.html if it exists in tmp (sometimes created by volume mount)
rm -f /var/www/html/tmp/index.html

echo "Flynax init: Directories created and permissions set."

# Execute the main command (apache2-foreground)
exec "$@"
