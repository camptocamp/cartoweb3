all:
	:

ROUTING_DB ?= routing

createdb:
	-dropdb $(ROUTING_DB)
	createdb $(ROUTING_DB)
	createlang plpgsql $(ROUTING_DB)
	psql $(ROUTING_DB) -f /usr/share/postgresql/8.0/contrib/lwpostgis.sql
	psql $(ROUTING_DB) -f /usr/share/postgresql/8.0/contrib/spatial_ref_sys.sql

	psql $(ROUTING_DB) -f /usr/share/postgresql/8.0/contrib/dijkstra.sql
	psql $(ROUTING_DB) -f /usr/share/postgresql/8.0/contrib/dijkstra_postgis.sql

.PHONY: createdb all
