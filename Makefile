LIBS_URL="http://www.camptocamp.com/~sypasche/cartoweb3/cartoweb3_includes.tgz"

all:
	:

fetch_libs:
	-rm -r include
	wget -O- $(LIBS_URL) | tar xzf -

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

soft_clean:
	-find www-data -type f|xargs -r rm
	-rm -f templates_c/*

clean: soft_clean
	find -name "*~" -type f -exec  rm {} \;
	rm -f templates_c/*
	find -type l -exec rm {} \;

dirs:
	-mkdir -p www-data/mapinfo_cache
	-mkdir -p www-data/mapresult_cache
	-mkdir -p www-data/soapxml_cache
	-mkdir -p www-data/images
	-mkdir -p www-data/saved_posts
	-mkdir -p www-data/wsdl_cache
	-mkdir -p www-data/icons
	-mkdir -p templates_c

links:
	ln -snf ../www-data/images htdocs/images

perms:
	chmod +x scripts/*sh scripts/*py
	chmod 777 log
	chmod -R 777 www-data
	chmod 777 templates_c

perms_sudo:
	chmod +x scripts/*sh scripts/*py
	sudo chown www-data log
	sudo chown -R www-data www-data
	sudo chown www-data templates_c

create_config:
	for i in `find -name "*.dist"`; do \
	 cp -i $$i $${i%%.dist} ;  \
	done

setup_files:
	./scripts/cw3_admin.py setup_files

init: fetch_libs dirs perms links create_config setup_files
	:

init_sudo: fetch_libs dirs perms_sudo links create_config setup_files
	:
