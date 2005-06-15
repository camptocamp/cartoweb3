\echo Testing non-postgis functions

\i data_no_postgis.sql

SELECT * FROM graph1;

SELECT create_graph_tables('graph1', 'varchar');

SELECT * FROM graph1;

SELECT * FROM graph1_edges;

SELECT * FROM graph1_vertices;

UPDATE graph1_edges SET cost = source + target, reverse_cost = 1;

-- not directed, no reverse cost
SELECT * FROM shortest_path('SELECT id, source, target, cost FROM graph1_edges', 2, 1, false, false);

-- directed
SELECT * FROM shortest_path('SELECT id, source, target, cost FROM graph1_edges', 2, 1, true, false);

-- directed, reverse cost
SELECT * FROM shortest_path('SELECT id, source, target, cost, reverse_cost FROM graph1_edges', 2, 1, true, true);

-- 
SELECT * FROM shortest_path('SELECT id, source, target, cost FROM graph1_edges', 2, 1000, false, false);

-- 
SELECT * FROM shortest_path('SELECT id, source, target, cost FROM graph1_edges', 1000, 1, false, false);

-- 
SELECT * FROM shortest_path('SELECT id, source, target, cost FROM graph1_edges', 1, 14, false, false);


SELECT drop_graph_tables('graph1');

DROP TABLE graph1;
