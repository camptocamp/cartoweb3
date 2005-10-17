\echo Testing non-postgis functions

\i data_no_postgis.sql

\echo > Content of graph1 table
SELECT * FROM graph1;

\echo > Creating graph tables
SELECT create_graph_tables('graph1', 'varchar');

\echo > Content of graph1 table
SELECT * FROM graph1;

\echo > Content of graph1_edges table
SELECT * FROM graph1_edges;

\echo > Content of graph1_vertices table
SELECT * FROM graph1_vertices;

\echo > Updating graph1_edges costs
UPDATE graph1_edges SET cost = source + target, reverse_cost = 1;

\echo > Computing path: not directed, no reverse cost -> result ok
SELECT * FROM shortest_path('SELECT id, source, target, cost FROM graph1_edges', 2, 1, false, false);

\echo > Computing path: directed -> not path found
SELECT * FROM shortest_path('SELECT id, source, target, cost FROM graph1_edges', 2, 1, true, false);

\echo > Computing path: directed, reverse cost -> result ok
SELECT * FROM shortest_path('SELECT id, source, target, cost, reverse_cost FROM graph1_edges', 2, 1, true, true);

\echo > Computing path: -> target not found
SELECT * FROM shortest_path('SELECT id, source, target, cost FROM graph1_edges', 2, 1000, false, false);

\echo > Computing path: -> source not found
SELECT * FROM shortest_path('SELECT id, source, target, cost FROM graph1_edges', 1000, 1, false, false);

\echo > Computing path: directed, -> result ok
SELECT * FROM shortest_path('SELECT id, source, target, cost FROM graph1_edges', 1, 14, false, false);

\echo > Dropping graph tables
SELECT drop_graph_tables('graph1');

DROP TABLE graph1;
