Edit plugin

REQUIREMENTS

 * Postgresql database with postGIS enabled

INSTALLATION


USAGE
 1 - if allowed draw feature on map and validate it
 
 2 - clic on feature with black arrow tool to retrieve it from database and edit it
 
 3 - use "edit_layer_id" and "edit_features_ids" in GET parametres to retrieve features from database
 Note that if the feature id exists in database but feature has no geometry, user can add one using edit new feature tool.