@echo off
chcp 65001 >nul
echo ========================================================
echo   KHOI TAO / TAI TAO DATABASE CHO FOOD FINDER
echo ========================================================
echo.

where php >nul 2>&1
if %errorlevel% equ 0 (
    php "%~dp0database\setup.php"
    pause
    exit /b 0
)

set MYSQL_BIN=C:\xampp\mysql\bin\mysql.exe

if not exist "%MYSQL_BIN%" (
    echo [LOI] Khong tim thay MySQL tai C:\xampp\mysql\bin\mysql.exe va khong tim thay lenh php!
    echo Vui long kiem tra lai duong dan cai dat XAMPP.
    pause
    exit /b 1
)

echo Dang kiem tra ket noi MySQL...
"%MYSQL_BIN%" -u root -e "SELECT 1;" >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo [CHU Y] Chua the ket noi den MySQL!
    echo Ban hay mo XAMPP Control Panel va bam [Start] tai muc MySQL truoc nhe.
    echo.
    pause
    exit /b 1
)

echo [OK] MySQL dang chay.
echo Dang tao database 'food_finder'...
"%MYSQL_BIN%" -u root -e "CREATE DATABASE IF NOT EXISTS food_finder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo Dang import schema.sql...
"%MYSQL_BIN%" --default-character-set=utf8mb4 -u root food_finder < "%~dp0database\schema.sql"

echo Dang import seed_categories.sql...
"%MYSQL_BIN%" --default-character-set=utf8mb4 -u root food_finder < "%~dp0database\seed_categories.sql"

echo Dang import seed_restaurants.sql...
"%MYSQL_BIN%" --default-character-set=utf8mb4 -u root food_finder < "%~dp0database\seed_restaurants.sql"

echo.
echo ========================================================
echo   IMPORT DATABASE THANH CONG!
echo ========================================================
echo.
pause
