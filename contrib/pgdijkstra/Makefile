
BOOST_PATH=/usr/include/boost/graph

MODULE_big = dijkstra

OBJS = dijkstra.o boost_wrapper.o

DATA_built = dijkstra.sql
DOCS = README.dijkstra

SHLIB_LINK += -lstdc++

PGXS := $(shell pg_config --pgxs)
include $(PGXS)

boost_wrapper.o: boost_wrapper.cpp
	$(CXX) $(CPPFLAGS) $(CFLAGS) -I$(BOOST_PATH) -c boost_wrapper.cpp

