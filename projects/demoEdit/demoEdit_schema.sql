
CREATE TABLE edit_poly
(
  parc_id serial NOT NULL,
  name varchar,
  culture varchar,
  surf numeric,
  parc_type numeric,
  CONSTRAINT edit_poly_pkey PRIMARY KEY (parc_id)
) 
WITH OIDS;
ALTER TABLE edit_poly OWNER TO postgres;
GRANT ALL ON TABLE edit_poly TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE edit_poly_parc_id_seq TO postgres WITH GRANT OPTION;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE edit_poly TO "www-data";
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE edit_poly_parc_id_seq TO "www-data";

SELECT addgeometrycolumn('edit_poly', 'the_geom', -1, 'MULTIPOLYGON', 2);

CREATE TABLE edit_point
(
  id serial NOT NULL,
  name varchar,
  surname varchar,
  place varchar,
  age numeric,
  CONSTRAINT edit_point_pkey PRIMARY KEY (id)
) 
WITH OIDS;
ALTER TABLE edit_point OWNER TO postgres;
GRANT ALL ON TABLE edit_point TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE edit_point_id_seq TO postgres WITH GRANT OPTION;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE edit_point TO "www-data";
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE edit_point_id_seq TO "www-data";

SELECT addgeometrycolumn('edit_point', 'the_geom', -1, 'POINT', 2);

CREATE TABLE edit_line
(
  id serial NOT NULL,
  name varchar,
  length integer,
  CONSTRAINT edit_line_pkey PRIMARY KEY (id)
) 
WITH OIDS;
ALTER TABLE edit_line OWNER TO postgres;
GRANT ALL ON TABLE edit_line TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE edit_line_id_seq TO postgres WITH GRANT OPTION;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE edit_line TO "www-data";
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE edit_line_id_seq TO "www-data";

SELECT addgeometrycolumn('edit_line', 'the_geom', -1, 'LINESTRING', 2);

GRANT ALL ON TABLE geometry_columns TO postgres WITH GRANT OPTION;
GRANT SELECT ON TABLE geometry_columns TO "www-data";


CREATE FUNCTION calc_surf() RETURNS "trigger"
    AS 'BEGIN
   NEW.surf = round(area(NEW.the_geom)::numeric, 2);
   RETURN NEW;
 END'
    LANGUAGE plpgsql;


CREATE TRIGGER calc_surf_edit_poly
    BEFORE UPDATE ON edit_poly
    FOR EACH ROW
    EXECUTE PROCEDURE calc_surf();

CREATE TRIGGER calc_surf_edit_poly_insert
    BEFORE INSERT ON edit_poly
    FOR EACH ROW
    EXECUTE PROCEDURE calc_surf();
