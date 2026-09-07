@echo off
chcp 65001 >nul
echo ============================================
echo    Excel 转高清图像工具 - 直接转换模式
echo ============================================
echo.
cd /d D:\phpstudy_pro\WWW\xiayuxuan.com\py\excel转图像工具

echo 正在转换：半山壹号.xlsx
echo DPI: 200, 格式: PNG
echo.
python excel_to_image.py "半山壹号.xlsx" --dpi 200 --fmt png
echo.
pause
