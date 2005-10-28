This help gives you some informations to integrate the routing fonctionalities in a custom application.

1. REQUIREMENT and INSTALLATION
-------------------------------

REQUIREMENT
*Postgresql >= 8.0
*The boost graph library (See http://www.boost.org/libs/graph/doc/index.html)
*C++ Compiler

INSTALLATION
*Edit Makefile, and set the BOST_PATH with the location of your boost library
(if you are on debian, just type "apt-get install libboost-graph-dev")
*Type make install
*Execute the sql file dijkstra.sql to install the functions in your databASe
*If you have PostGIS installed, you can launch "dijkstra_postgis.sql"

USAGE
The core module is a function which compute a shortest path from a set of edges and vertices.
The function expects data in a specific format in input.
Some functions are provided for importing data from geometric tables, and for generating results a geometies.
For more informations on these functions, you can have a look to the routing module README file (http://www.cartoweb.org/pgdijkstra/README).


2. Routing integration step by step
-----------------------------------
This chapter explain the main step to integrate the routing fonctionnalities in your custom application.
We describe the steps followed to install the routing demo. To resume, we used an Europe roads shapefile, imported it in PostGIS, generated the graph tables and configured files to suggest a search of the shortest path between two european towns.

Note : the steps 2.2 to 2.4 can be done by launching the demo_routing.sql file.

2.1. database installation
--------------------------
    - createdb demo_routing
    - createlang plpgsql demo_routing
    - psql -d demo_routing -f lwpostgis.sql
    - psql -d demo_routing -f spatial_ref_sys.sql
    - psql -d demo_routing -f dijkstra.sql
    - psql -d demo_routing -f dijkstra_postgis.sql


2.2. Import of the Europe road geodata in postGIS
-------------------------------------------------
    
    - shp2pgsql roadl.shp roads_europe_tmp > /tmp/roadl.sql
    - psql -d demo_routing -f /tmp/roadl.sql

    
2.3. Graph importation
----------------------

The first step is to delete uneeded cols of the table roads_europe_tmp. To do so, you can type :
    - CREATE TABLE roads_europe (gid int UNIQUE, source_id int, target_id int);
    - SELECT AddGeometryColumn('roads_europe', 'the_geom', -1, 'MULTILINESTRING', 2 );
    - INSERT INTO roads_europe (gid, the_geom) (SELECT gid, the_geom FROM roads_europe);
The resulting table is so roads_europe.

You can then fill the cols source_id and target_id with the "assign_vertex_id" function.
    - SELECT assign_vertex_id('roads_europe', 1);

Here's the content of the roads_europe table
SELECT gid, source_id, target_id, edge_id, AStext(the_geom) FROM roads_europe limit 3;
 
   gid  | source_id | target_id | edge_id |                                                     AStext
-------+-----------+-----------+---------+----------------------------------------------------------------------------------------------------------------
 13115 |     11051 |     11099 |      14 | MULTILINESTRING((1062096.06 4861316.234,1061616.495 4860772.073))
 12869 |     10918 |     10916 |     267 | MULTILINESTRING((250681.597 4779596.532,248423.861 4779852.646,248311.216 4779866.142,246918.803 4780025.504))
 12868 |     10918 |     10913 |     268 | MULTILINESTRING((250681.597 4779596.532,255197.548 4780850.435))
(3 lignes)


        
But if the data quality is poor, you need to delete the duplicates edges (they have the same pair source-target of vertices).
    - For example, to verify you have duplicates edges, you can type :
    SELECT * FROM (SELECT source_id, target_id, count(*) AS c FROM roadl group by source_id, target_id order by c) AS foo where foo.c = 2;
If there is duplicates edges, to delete one of two rows, you can type : 
    - CREATE TABLE roads_europe_tmp AS SELECT * FROM roads_europe WHERE gid  in (SELECT gid FROM (SELECT DISTINCT on (source_id, target_id) source_id, gid FROM roads_europe) AS doublon);
    - DELETE FROM roads_europe;
    - INSERT INTO roads_europe (SELECT * FROM roads_europe_tmp);
    - ALTER TABLE roads_europe ADD COLUMN edge_id int;

The following step is to create and fill the edges and vertices tables of the resulting graph. To do so, you can use "create_graph_tables" function.
    - SELECT create_graph_tables('roads_europe', 'int4');
    
SELECT * FROM roads_europe_edges LIMIT 3;
 id | source | target | cost | reverse_cost 
----+--------+--------+------+--------------
  1 |      1 |      2 |      |             
  2 |      3 |      3 |      |             
  4 |      2 |      2 |      |             
(3 rows)

We can see that it contains NULL values for the cost column. 

The function update_cost_from_distance can update the cost column with
the distance of the lines contained in the geometry table, attached to
each edge:

SELECT update_cost_from_distance('roads_europe');

The costs are now:
SELECT * FROM roads_europe_edges LIMIT 3;

 id | source | target |       cost       | reverse_cost
----+--------+--------+------------------+--------------
  1 |      1 |      2 | 6857.46585793103 |
  2 |      3 |      4 | 37349.9592156392 |
  3 |      5 |      6 | 14040.5673116933 |
(3 lignes)

Now, all is set up correctly for using the shortest path function on these data.
But to include the routing fonctionnalities in a custom project, we must respect certains rules dictated by the routing plugin.


2.4. Routing plugin specific configuration
------------------------------------------

The two things to do are to :
-create the routing results table. In this example the table is routing_results.
    CREATE TABLE routing_results (
    results_id integer,
    "timestamp" bigint,
    gid integer
    );
    SELECT AddGeometryColumn('','routing_results','the_geom','-1','MULTILINESTRING',2);

- create the 'routing_results_seq' sequence.
    CREATE SEQUENCE routing_results_seq
      INCREMENT 1
      MINVALUE 1
      MAXVALUE 9223372036854775807
      START 1
      CACHE 1;
    
    
    
2.5. Mapfile configuration
--------------------------
In the mapfile, you must specify, in the routing layer the connection to the database, a symbology for the route and a first route using an unique identifier.
The data paramater will be overwritten by the routing plugin to draw the route choosen by the end-user.

Example :
  LAYER
    NAME "graph"
    TYPE LINE
    TRANSPARENCY 80
    CONNECTIONTYPE postgis
        CONNECTION "user=@DB_USER@ password=@DB_PASSWD@ host=@DB_HOST@ dbname=@DB_NAME@"
        DATA "the_geom from (SELECT the_geom from routing_results) as foo using unique gid using srid=-1"
    TEMPLATE "t"
    CLASS
      NAME "0"
      STYLE
        SYMBOL "circle"
        SIZE 10
        COLOR 90 27 191
      END
    END
  END

  
2.6 General configuration
-------------------------
For the demo, we propose to select your route by starting from a town until an other town.
This is possible because for each objet of an europeans towns layer, we have identified the nearest object of the roads_europe_vertices table. That's why in the demoRouting configuration there's a client-side configuration.
Normaly, in the plugin routing, client-side only allows you to type an id of object, from which to start and an other to finish the route and no configuration is needed.

So, if you want to extend the routing plugin in a custom application, you need to specify the following options.
If you use demoRouting extension, you must specify Client-side, the :
- postgresRoutingVerticesTable : vertices table
- stepName : vertices table col containing informations you want to propose a choice on.
- dsn : the connexion string to the database

Anyway, server-side, you must specify :
    - the routing TABLE (postgresRoutingTable option), 
    - the routing layer in the mapfile (postgresRoutingResultsLayer option),
    - the results routing table (postgresRoutingResultsTable), and
    - the connexion string to the database (dsn option).
