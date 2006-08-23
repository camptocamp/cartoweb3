%1\php\php -n cw3setup.php --install --base-url http://localhost/cartoweb3/htdocs/ --profile production
cd %1\scripts
%1\php\php -n refresh_php_ext.php
del %1\www\cartoweb3\post_setup_main.bat
