@echo off

echo Creating webmail project structure...

:: Root files
type nul > index.php
type nul > config.php
type nul > mailer.php
type nul > database.sql

:: Auth folder
mkdir auth
cd auth
type nul > login.php
type nul > register.php
type nul > forgot_password.php
type nul > reset_password.php
type nul > logout.php
cd ..

:: Dashboard folder
mkdir dashboard
cd dashboard
type nul > index.php
type nul > compose.php
type nul > sent.php
type nul > view_email.php
type nul > drafts.php
type nul > templates.php
type nul > settings.php
cd ..

:: Components folder
mkdir components
cd components
type nul > header.php
type nul > sidebar.php
type nul > footer.php
cd ..

:: Uploads folder
mkdir uploads
type nul > uploads\.htaccess

:: Assets
mkdir assets
mkdir assets\css
mkdir assets\js
mkdir assets\images

:: CSS files
type nul > assets\css\main.css
type nul > assets\css\auth.css
type nul > assets\css\dashboard.css

:: JS files
type nul > assets\js\main.js
type nul > assets\js\compose.js
type nul > assets\js\sidebar.js

:: Image
type nul > assets\images\logo.svg

echo.
echo Webmail structure created successfully.
pause