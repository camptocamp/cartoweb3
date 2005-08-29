import sys


if len(sys.argv) != 2:
    print "Usage: %s %s" % (sys.argv[0], "")
    sys.exit()

m = int(sys.argv[1])

f = open("create_edges_perfs_%s.sql" % m, 'w')
print >>f, "CREATE TABLE edges_perfs_%s (id int, source int, target int, cost float8);" % m
print >>f, "COPY edges_perfs_%s FROM stdin;" % m
for i in range(m * 100000):
    print >> f, "%s\t%s\t%s\t%s" % (i, i, i + 1, 2.0)
print >> f, "\."
f.close()
f = open("query_edges_perfs_%s.sql" % m, 'w')
print >> f, """SELECT * FROM shortest_path('SELECT id, source, target, cost FROM edges_perfs_%s', 1, 2, false, false);""" % m
