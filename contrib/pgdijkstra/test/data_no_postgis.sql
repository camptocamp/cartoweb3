
CREATE TABLE graph1 (
    gid integer NOT NULL,
    source_id character varying,
    target_id character varying,
    edge_id integer
);

COPY graph1 (gid, source_id, target_id, edge_id) FROM stdin;
0	R	V	\N
1	Q	P	\N
2	W	P	\N
3	E	R	\N
4	H	E	\N
5	H	S	\N
6	D	H	\N
7	K	J	\N
8	J	I	\N
9	H	J	\N
10	D	F	\N
11	F	K	\N
12	K	L	\N
13	L	M	\N
14	M	V	\N
15	X	W	\N
16	N	A	\N
17	A	B	\N
18	B	G	\N
19	G	P	\N
20	P	E	\N
21	S	T	\N
22	T	U	\N
23	G	D	\N
24	O	C	\N
25	N	O	\N
26	N	Y	\N
27	Y	Z	\N
28	Z	X	\N
29	C	Q	\N
30	X	Q	\N
31	I	U	\N
32	U	V	\N
33	O	Z	\N
34	I	M	\N
\.

