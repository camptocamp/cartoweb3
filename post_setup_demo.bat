%1\php\php -n cw3setup.php --install-demo-data
%1\php\php -n cw3setup.php --install --project demoPlugins --config-from-file projects/demoPlugins/demo.properties --base-url http://localhost/cartoweb3/htdocs/ --profile production
del %1\www\cartoweb3\post_setup_demo.bat
