-- We force the usage of public to stay coherent with our .map file
-- Fill free to change it for your own needs.
CREATE TABLE public.edit_poly
(
  parc_id serial NOT NULL,
  name varchar,
  culture varchar,
  surf numeric,
  parc_type numeric,
  CONSTRAINT edit_poly_pkey PRIMARY KEY (parc_id)
) 
WITH OIDS;
ALTER TABLE public.edit_poly OWNER TO postgres;
GRANT ALL ON TABLE public.edit_poly TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE public.edit_poly_parc_id_seq TO postgres WITH GRANT OPTION;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE public.edit_poly TO "www-data";
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE public.edit_poly_parc_id_seq TO "www-data";

SELECT addgeometrycolumn('public','edit_poly', 'the_geom', -1, 'MULTIPOLYGON', 2);

CREATE TABLE public.edit_point
(
  id serial NOT NULL,
  name varchar,
  surname varchar,
  place varchar,
  age numeric,
  CONSTRAINT edit_point_pkey PRIMARY KEY (id)
) 
WITH OIDS;
ALTER TABLE public.edit_point OWNER TO postgres;
GRANT ALL ON TABLE public.edit_point TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE public.edit_point_id_seq TO postgres WITH GRANT OPTION;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE public.edit_point TO "www-data";
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE public.edit_point_id_seq TO "www-data";

SELECT addgeometrycolumn('public','edit_point', 'the_geom', -1, 'POINT', 2);

CREATE TABLE public.edit_line
(
  id serial NOT NULL,
  name varchar,
  length integer,
  CONSTRAINT edit_line_pkey PRIMARY KEY (id)
) 
WITH OIDS;
ALTER TABLE public.edit_line OWNER TO postgres;
GRANT ALL ON TABLE public.edit_line TO postgres WITH GRANT OPTION;
GRANT ALL ON TABLE public.edit_line_id_seq TO postgres WITH GRANT OPTION;
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE public.edit_line TO "www-data";
GRANT SELECT, UPDATE, INSERT, DELETE ON TABLE public.edit_line_id_seq TO "www-data";

SELECT addgeometrycolumn('public','edit_line', 'the_geom', -1, 'LINESTRING', 2);

GRANT ALL ON TABLE geometry_columns TO postgres WITH GRANT OPTION;
GRANT SELECT ON TABLE geometry_columns TO "www-data";


CREATE FUNCTION public.calc_surf() RETURNS "trigger"
    AS 'BEGIN
   NEW.surf = round(area(NEW.the_geom)::numeric, 2);
   RETURN NEW;
 END'
    LANGUAGE plpgsql;


CREATE TRIGGER calc_surf_edit_poly
    BEFORE UPDATE ON public.edit_poly
    FOR EACH ROW
    EXECUTE PROCEDURE public.calc_surf();

CREATE TRIGGER calc_surf_edit_poly_insert
    BEFORE INSERT ON public.edit_poly
    FOR EACH ROW
    EXECUTE PROCEDURE public.calc_surf();
