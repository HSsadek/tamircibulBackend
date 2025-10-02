@echo off
echo Setting up TamirciBul Laravel Backend...
echo.

echo Step 1: Copying environment file...
copy .env.example .env

echo Step 2: Installing Composer dependencies...
composer install

echo Step 3: Generating application key...
php artisan key:generate

echo Step 4: Please configure your database settings in .env file
echo Then run the following commands:
echo.
echo php artisan migrate
echo php artisan db:seed
echo php artisan serve
echo.
echo Default admin credentials:
echo Email: admin@tamircibul.com
echo Password: admin123
echo.
pause
