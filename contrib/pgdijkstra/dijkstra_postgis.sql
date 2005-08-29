--
-- pgdijkstra postgis related functions
--
--
-- Copyright (c) 2005 Sylvain Pasche
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.


-- TODO: use spatial index when possible
-- TODO: make variable names more consistent

-- Geometry schema description:
-- gid
-- source_id
-- target_id
-- edge_id

-- BEGIN;

-----------------------------------------------------------------------
-- For each vertex in the vertices table, set a point geometry which is
--  the corresponding line start or line end point
-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION add_vertices_geometry(geom_table varchar) RETURNS VOID AS
$$
DECLARE
        vertices_table varchar := quote_ident(geom_table) || '_vertices';
BEGIN
        
        BEGIN
                EXECUTE 'SELECT addGeometryColumn(''' || quote_ident(vertices_table) || ''', ''the_geom'', -1, ''POINT'', 2)';
        EXCEPTION 
                WHEN DUPLICATE_COLUMN THEN
        END;

        EXECUTE 'UPDATE ' || quote_ident(vertices_table) || ' SET the_geom = NULL';
        EXECUTE 'UPDATE ' || quote_ident(vertices_table) || ' SET the_geom = startPoint(geometryn(m.the_geom, 1)) FROM ' 
                                || quote_ident(geom_table) || ' m where geom_id = m.source_id';

        EXECUTE 'UPDATE ' || quote_ident(vertices_table) || ' set the_geom = endPoint(geometryn(m.the_geom, 1)) FROM ' 
                        || quote_ident(geom_table) || ' m where geom_id = m.target_id AND ' 
                        || quote_ident(vertices_table) || '.the_geom IS NULL';

        RETURN;
END;
$$
LANGUAGE 'plpgsql' VOLATILE STRICT; 

-----------------------------------------------------------------------
-- This function should not be used directly. Use assign_vertex_id instead
-- 
-- Inserts a point into a temporary vertices table, and return an id
--  of a new point or an existing point. Tolerance is the minimal distance
--  between existing points and the new point to create a new point.
-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION point_to_id(point geometry, tolerance double precision) RETURNS INT AS
$$
DECLARE
        row record;
        point_id int;
BEGIN
        LOOP
                -- TODO: use && and index       
                SELECT INTO row id, the_geom FROM vertices_tmp WHERE distance(the_geom, point) < tolerance;
                point_id := row.id;

                IF NOT FOUND THEN
                        INSERT INTO vertices_tmp (the_geom) VALUES (point);
                ELSE
                        EXIT;
                END IF;
        END LOOP;
        RETURN point_id;
END;
$$
LANGUAGE 'plpgsql' VOLATILE STRICT; 


-----------------------------------------------------------------------
-- Fill the source_id and target_id column for all lines. All line ends
--  with a distance less than tolerance, are assigned the same id
-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION assign_vertex_id(geom_table varchar, tolerance double precision)
        RETURNS VOID AS
$$
DECLARE
        points record;
        source_id int;
        target_id int;
BEGIN

        BEGIN
                DROP TABLE vertices_tmp;
        EXCEPTION 
                WHEN UNDEFINED_TABLE THEN
        END;

        CREATE TABLE vertices_tmp ( id serial );

        EXECUTE $q$ SELECT addGeometryColumn('vertices_tmp', 'the_geom', -1, 'POINT', 2) $q$;

        CREATE INDEX vertices_tmp_idx ON vertices_tmp USING GIST (the_geom);

        FOR points IN EXECUTE 'SELECT gid AS id, startPoint(geometryN(the_geom , 1)) AS source, endPoint(geometryN(the_geom, 1)) as target FROM ' 
                                || quote_ident(geom_table) loop

                source_id := point_to_id(points.source, tolerance);
                target_id := point_to_id(points.target, tolerance);

                EXECUTE 'update ' || quote_ident(geom_table) || ' SET source_id = '
                   || source_id || ', target_id = ' || target_id || ' WHERE gid =  ' || points.id;
        END LOOP;
        RETURN;
END;
$$
LANGUAGE 'plpgsql' VOLATILE STRICT; 

-----------------------------------------------------------------------
-- Update the cost column from the edges table, from the length of
--  all lines which belong to an edge.
-----------------------------------------------------------------------
-- FIXME: directed or not ?
CREATE OR REPLACE FUNCTION update_cost_from_distance(geom_table varchar) RETURNS VOID AS
$$
DECLARE 
BEGIN
        BEGIN
                EXECUTE 'CREATE INDEX ' || quote_ident(geom_table) || '_edge_idx ON ' || quote_ident(geom_table) || ' (edge_id)';
        EXCEPTION 
                WHEN DUPLICATE_TABLE THEN
                RAISE NOTICE 'Not creating index, already there';
        END;

        EXECUTE 'UPDATE ' || quote_ident(geom_table) || '_edges SET cost = (SELECT sum( length( g.the_geom ) ) FROM ' || quote_ident(geom_table)
                          || ' g WHERE g.edge_id = id GROUP BY id)';

        RETURN;
END;
$$
LANGUAGE 'plpgsql' VOLATILE STRICT; 


CREATE TYPE geoms AS
(
  gid int4,
  the_geom geometry
);

-----------------------------------------------------------------------
-- Compute the shortest path using edges and vertices table, and return
--  the result as a set of (gid integer, the_geom gemoetry) records.
-- This function uses the internal vertices identifiers.
-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION shortest_path_as_geometry_internal_id(geom_table varchar, 
                                                     source int4, target int4) 
                                                     RETURNS SETOF GEOMS AS
$$
DECLARE 
        r record;
        path_result record;
        v_id integer;
        e_id integer;
        geom geoms;
BEGIN
        
        FOR path_result IN EXECUTE 'SELECT vertex_id, edge_id FROM shortest_path(''SELECT id, source, target, cost FROM ' || 
                quote_ident(geom_table) || '_edges '', ' || quote_literal(source) || ' , ' || quote_literal(target) || ' , false, false) ' LOOP

                v_id = path_result.vertex_id;
                e_id = path_result.edge_id;

                FOR r IN EXECUTE 'SELECT gid, the_geom FROM ' || quote_ident(geom_table) || '  WHERE edge_id = ' || quote_literal(e_id) LOOP
                        geom.gid := r.gid;
                        geom.the_geom := r.the_geom;
                        RETURN NEXT geom;
                END LOOP;
        END LOOP;
        RETURN;
END;
$$
LANGUAGE 'plpgsql' VOLATILE STRICT; 

-----------------------------------------------------------------------
-- Compute the shortest path using edges and vertices table, and return
--  the result as a set of (gid integer, the_geom gemoetry) records.
-----------------------------------------------------------------------
CREATE OR REPLACE FUNCTION shortest_path_as_geometry(geom_table varchar, geom_source anyelement, 
                                                     geom_target anyelement) RETURNS SETOF GEOMS AS
$$
DECLARE 
        r record;
        source int4;
        target int4;
        path_result record;
        v_id integer;
        e_id integer;
        geom geoms;
BEGIN
        FOR r IN EXECUTE 'SELECT id FROM ' || quote_ident(geom_table) || '_vertices WHERE geom_id = ' || quote_literal(geom_source) LOOP
                source = r.id;
        END LOOP;
        IF source IS NULL THEN
                RAISE EXCEPTION 'Can''t find source edge';
        END IF;

        FOR r IN EXECUTE 'SELECT id FROM ' || quote_ident(geom_table) || '_vertices WHERE geom_id = ' || quote_literal(geom_target) LOOP
                target = r.id;
        END LOOP;
        IF target IS NULL THEN
                RAISE EXCEPTION 'Can''t find target edge';
        END IF;
        
        FOR geom IN SELECT * FROM shortest_path_as_geometry_internal_id(geom_table, source, target) LOOP
                RETURN NEXT geom;
                END LOOP;
        RETURN;
END;
$$
LANGUAGE 'plpgsql' VOLATILE STRICT; 

-- COMMIT;