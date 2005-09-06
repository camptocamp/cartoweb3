# Makefile for CartoWeb maintenance and installation
#
# Please note that tasks there can be done with the Php installation script
# Thus, this file is not needed for installing and running CartoWeb.

LIBS_URL="http://www.cartoweb.org/downloads/cartoweb-includes-3.0.0.tar.gz"
DEMO_URL="http://www.cartoweb.org/downloads/cartoweb-demodata-3.0.0.tar.gz"

all:
	:

fetch_libs:
	-rm -r include
	wget -O- $(LIBS_URL) | tar xzf -

fetch_demo:
	-rm -r projects/demo/server_conf/demo/data
	(cd projects/demo/server_conf/demo/; wget -O- $(DEMO_URL)|tar zxf -)

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

