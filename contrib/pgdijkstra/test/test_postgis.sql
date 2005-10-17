\i data_postgis.sql

-- BEGIN;

ALTER TABLE graph2 ADD COLUMN edge_id integer;

SELECT create_graph_tables('graph2', 'varchar');

SELECT * from graph2;
SELECT * from graph2_vertices;
SELECT * from graph2_edges;

SELECT assign_vertex_id('graph2', 0.01);

SELECT * from graph2;
--SELECT * from graph2_edges;

SELECT drop_graph_tables('graph2');
SELECT create_graph_tables('graph2', 'varchar');

SELECT * from graph2_edges;

SELECT update_cost_from_distance('graph2'); 

SELECT * from graph2_edges;

SELECT * FROM shortest_path('SELECT id, source, target, cost FROM graph2_edges', 1, 14, false, false);

SELECT gid, asText(the_geom) FROM shortest_path_as_geometry('graph2', 1, 10);

SELECT add_vertices_geometry('graph2');

SELECT *, asText(the_geom) FROM graph2_vertices;

-- cleanup

SELECT drop_graph_tables('graph2');

SELECT DropGeometryColumn('graph2', 'the_geom');
DROP TABLE graph2;

-- COMMIT;