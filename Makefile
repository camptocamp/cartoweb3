# Makefile for CartoWeb maintenance and installation
#
# Please note that tasks there can be done with the Php installation script
# Thus, this file is not needed for installing and running CartoWeb.

all:
	:

prepare_prod:
	rm htdocs/info.php
	rm htdocs/runtests.php
	#rm r.php

delete_server:
	rm -r server
	rm -r server_conf
	rm -r coreplugins/*/server
	rm -r plugins/*/server
	rm htdocs/server.php
	rm htdocs/cartoserver.wsdl.php

delete_client:
	rm -r client
	rm -r client_conf
	rm -r templates
	rm -r templates_c
	rm -r coreplugins/*/client
	rm -r coreplugins/*/htdocs
	rm -r coreplugins/*/templates
	rm -r plugins/*/client
	rm -r plugins/*/htdocs
	rm -r plugins/*/templates
	rm htdocs/client.php

