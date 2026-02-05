@echo off
echo Preparing for Deployment...

echo 1. Clearing Caches...
call php artisan optimize:clear
call php artisan config:clear
call php artisan route:clear
call php artisan view:clear

echo 2. Installing Production Dependencies...
call composer install --optimize-autoloader --no-dev

echo 3. Creating ZIP file instructions...
echo ---------------------------------------------------
echo PLEASE MANUALLY ZIP THE 'localgo-backend' FOLDER NOW.
echo (Or just the contents: app, bootstrap, config, database, public, resources, routes, storage, vendor, .env, .htaccess, server.php)
echo ---------------------------------------------------
echo DONE.
pause
