LIBS_URL="http://www.camptocamp.com/~sypasche/cartoweb3/cartoweb3_includes.tgz"

all:
	:

fetch_libs:
	-rm -r include
	wget -O- $(LIBS_URL) | tar xzf -

clean:
	find -name "*~" -type f -exec  rm {} \;
	rm -f  www-data/images/*
	rm -f  www-data/saved_posts/*
	rm -f templates_c/*
dirs:
	-mkdir -p www-data/images
	-mkdir -p www-data/saved_posts
	-mkdir -p templates_c

links:
	ln -snf ../www-data/images htdocs/images

perms:
	chmod +x scripts/*sh scripts/*py
	chmod 777 log
	chmod 777 www-data/images
	chmod 777 www-data/saved_posts
	chmod 777 www-data templates_c

perms_sudo:
	sudo chown www-data log
	sudo chown www-data www-data/images
	sudo chown www-data www-data/saved_posts
	sudo chown www-data templates_c

create_config:
	for i in `find -name "*.dist"`; do \
	 cp $$i $${i%%.dist} ;  \
	done

htlinks:
	(cd scripts; ./htlinks.sh)

init: fetch_libs dirs perms links create_config htlinks
	:

init_sudo: fetch_libs dirs perms_sudo links create_config htlinks
	:
