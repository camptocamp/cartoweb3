clean:
	find -name "*~" -type f -exec  rm {} \;
	rm -f  www-data/images/*
	rm -f  www-data/saved_posts/*
	rm -f templates_c/*
dirs:
	-mkdir -p www-data/images
	-mkdir -p www-data/saved_posts
	-mkdir -p templates_c

perms:
	sudo chown www-data www-data/images
	sudo chown www-data www-data/saved_posts
	sudo chown www-data templates_c

