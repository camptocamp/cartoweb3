%1\php\php cw3setup.php --install --base-url http://127.0.0.1/cartoweb3/htdocs/ --profile production
copy %1\php\php-cgi.exe %1\Apache\cgi-bin\
del %1\www\cartoweb3\cw3post_setup.bat