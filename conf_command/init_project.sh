#!/bin/sh
php artisan migrate:refresh
php artisan db:seed --class=PermissionTableSeeder
php artisan db:seed --class=CreateAdminUserSeeder
php artisan passport:install --force
php artisan config:cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan serve --host=0.0.0.0 --port=8000
