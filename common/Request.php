<?php

////////////////////////////////////
// Request objects

class LayerSelectionRequest {

    public $layersId;
}

class LayerSelection {

    public $layersId;
}

class MapRequest /* extends MapState */ {

    public $locationRequest;
    public $outlineRequest;
    public $layerSelectionRequest;

    public $images;
}

// FIXME: does not extend to prevent axis problems
class MapResult /* extends MapState */ {

    //public $locationResult; DEPR
    public $location;
    public $images;

    //public $layerSelection;

}

////////////////////////////////////
// Result


?>