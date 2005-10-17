all:
	:

createdb:
	-dropdb routing
	createdb routing
	createlang plpgsql routing
	psql routing -f /usr/share/postgresql/8.0/contrib/lwpostgis.sql
	psql routing -f /usr/share/postgresql/8.0/contrib/spatial_ref_sys.sql

	psql routing -f /usr/share/postgresql/8.0/contrib/dijkstra.sql
	psql routing -f /usr/share/postgresql/8.0/contrib/dijkstra_postgis.sql

.PHONY: createdb all
