@ECHO OFF

ECHO setup CartoWeb
REM setup CartoWeb
%1\Apache\cgi-bin\php -n cw3setup.php --install --base-url http://localhost/cartoweb3/htdocs/ --profile production
REM restart apache
cd %1
call apache-restart.bat
REM delete post setup script
del %1\apps\cartoweb3\post_setup_main.bat
