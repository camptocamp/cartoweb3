BEGIN;
CREATE TABLE "points" (gid serial, "fid" int8);
SELECT AddGeometryColumn('','points','the_geom','-1','MULTIPOINT',2);
INSERT INTO "points" (gid,"fid", "the_geom") VALUES ('0','0',GeometryFromText('MULTIPOINT (0.357267762551516 51.5768135215505)',-1) );
INSERT INTO "points" (gid,"fid", "the_geom") VALUES ('1','1',GeometryFromText('MULTIPOINT (0.0257340253041684 51.4762664045164)',-1) );
INSERT INTO "points" (gid,"fid", "the_geom") VALUES ('2','2',GeometryFromText('MULTIPOINT (-0.129162884721232 51.0931003639273)',-1) );
INSERT INTO "points" (gid,"fid", "the_geom") VALUES ('3','3',GeometryFromText('MULTIPOINT (0.226828259372232 50.9273334953036)',-1) );
INSERT INTO "points" (gid,"fid", "the_geom") VALUES ('4','4',GeometryFromText('MULTIPOINT (0.457814879585547 51.4001766943285)',-1) );
INSERT INTO "points" (gid,"fid", "the_geom") VALUES ('5','5',GeometryFromText('MULTIPOINT (0.169760976731295 51.1664725844656)',-1) );
INSERT INTO "points" (gid,"fid", "the_geom") VALUES ('6','6',GeometryFromText('MULTIPOINT (0.0909537768938105 51.4246341011746)',-1) );
INSERT INTO "points" (gid,"fid", "the_geom") VALUES ('7','7',GeometryFromText('MULTIPOINT (0.395312617645474 51.2887596186962)',-1) );
INSERT INTO "points" (gid,"fid", "the_geom") VALUES ('8','8',GeometryFromText('MULTIPOINT (0.297482990261011 51.4164816322259)',-1) );
INSERT INTO "points" (gid,"fid", "the_geom") VALUES ('9','9',GeometryFromText('MULTIPOINT (0.0366039839024421 51.0251631226881)',-1) );

ALTER TABLE ONLY "points" ADD CONSTRAINT "points_pkey" PRIMARY KEY (gid);
SELECT setval ('"points_gid_seq"', 9, true);
END;
