#ifndef _DIJKSTRA_H
#define _DIJKSTRA_H

#include "postgres.h"

typedef struct edge 
{
    int id;
    int source;
    int target;
    float8 cost;
    float8 reverse_cost;
} edge_t;

typedef struct path_element 
{
    int vertex_id;
    int edge_id;
    float8 cost;
} path_element_t;

#ifdef __cplusplus
extern "C"
#endif
int boost_dijkstra(edge_t *edges, unsigned int count, int start_vertex, int end_vertex,
		   bool directed, bool has_reverse_cost,
                   path_element_t **path, int *path_count, char **err_msg);

#endif
