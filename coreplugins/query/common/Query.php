<?php

class QueryRequest {

    public $shape;
    
    // if empty: server should select layers from layersRequest  
    public $layers;
    
    // retrieveType: fetch attributes ? fetch shapes ?
    // resultProperties: layer * maxResult * startIndex
}

class ResultElement {
    
    // public $index
    public $id;
    
    // do we need this ?
    public $tileindex;
    public $classindex;
    
    public $values;
}

class LayerResult {
    
    public $layerId;

    public $numResults;
    // TODO: remove these: use a simplier solution
    public $startIndex;
    public $endIndex;
    
    public $fields;
    
    public $resultElements;
}

class QueryResult {

    public $layerResults;
}

?>