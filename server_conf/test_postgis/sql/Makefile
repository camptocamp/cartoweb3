SHP2PGSQL=~/tmp/shp2pgsql

all:
	-$(SHP2PGSQL) ../shapes/postgis_lines.shp lines > lines.sql
	-$(SHP2PGSQL) ../shapes/postgis_points.shp points > points.sql
	-$(SHP2PGSQL) ../shapes/postgis_polygons.shp polygons > polygons.sql

clean: 
	rm *sql *~

