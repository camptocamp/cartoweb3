Mechanism of the extension of coreplugin demoLocation
-----------------------------------------------------

This extension allows you to select geographic objets of a layer by specifying a name.

According to the name selected, the id corresponding is fetched from the search name database and submit to the recenterid coreplugin. This coreplugin select the geographic object in the shapefile according to this id and the layer selected and recenter on it.

The names of the id and the names on which the search is done must be specified in the metadata of the mapfile for each layer queryable.
Theses metadata are respectively "id_attribute_string" for the id and "recenter_name_string" for the name.
You also must use the metadata "exported_values" to transmit these metadata to the client.

For example, you can type :
"exported_values" "recenter_name_string,id_attribute_string"
"recenter_name_string" "NAM"
"id_attribute_string" "OGC_FID|string"

The name of the database must be specified in the location.ini

NB : In the location.ini, you also need to specify the layers on which you want to do a search by name (in the idRecenterLayers options) and activate the coreplugin idRecenter (idRecenterActive option).