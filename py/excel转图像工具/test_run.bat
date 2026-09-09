@echo off
chcp 65001 >nul
cd /d "D:\phpstudy_pro\WWW\xiayuxuan.com\py\excel转图像工具"
D:\Python312\python.exe excel_to_image.py "半山壹号.xlsx" --dpi 200 --fmt png 2>&1
echo Exit code: %ERRORLEVEL%
pause
