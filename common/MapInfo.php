<?php

class CartocommonException extends Exception {

}

class LayerBase {
    const TYPE_LAYERGROUP = 'layerGroup';
    const TYPE_LAYER = 'layer';
    const TYPE_LAYERCLASS = 'layerClass';
    
    // Should not be there, but we've no polymorphism
    
    public $minScale = 0;
    public $maxScale = 0;
	public $icon = "none";
    
    function getVarInfo() {
        return array(
            'children' => 'array',
            /* 'msLayers' => 'array', */
            'test' => 'array,int',
            'maptest' => 'obj,stdclass',
        );
    }

    function endSerialize() {

        if (empty($this->type)) {
            
            // TEMP HACK
            $this->type = LayerBase::TYPE_LAYER;

            // FIXME: fix code and uncomment this
            // throw new CartocommonException(sprintf("Config error: layer %s has no type property", $this->id));
        }
        switch ($this->type) {
        case LayerBase::TYPE_LAYERGROUP:
            return copy_all_vars($this, new LayerGroup());
        case LayerBase::TYPE_LAYER:
            return copy_all_vars($this, new Layer());
        case LayerBase::TYPE_LAYERCLASS:
            /* should not happend */
            return copy_all_vars($this, new LayerClass());
        default:
            throw new CartocommonException('unknown layer type: ' .
                                           $layerStruct->type);
        }
    }
}

class LayerContainer extends LayerBase {
    public $children = array();
}

class LayerGroup extends LayerContainer {

}
class Layer extends LayerContainer {

    function loadFromStruct($layerStruct) {
        $this->layerStruct = $layerStruct;
    }
}

class LayerClass extends LayerBase {
    public $name;
}

class Location {
    function getVarInfo($context) {
        
        if ($context == StructHandler::CONTEXT_INI)
            return array('bbox' => 'bbox');
        else 
            return array('bbox' => 'obj,Bbox');
    }
}

class InitialLocation {
	public $bbox;
	
	function getVarInfo($context) {
        if ($context == StructHandler::CONTEXT_INI)
            return array('bbox' => 'bbox');
        else 
            return array('bbox' => 'obj,Bbox');
    }
}

class LayerState {
	public $id;
	public $hidden;
	public $selected;
	public $folded;

    function getVarInfo() {
        return array(
        'hidden' => 'boolean',
        'selected' => 'boolean',
        'folded' => 'boolean',
        );
    }
}

class InitialMapState {

	public $id;
	public $location;
	public $layers;

    function getVarInfo() {
        return array(
        'location' => 'obj,InitialLocation',
        'layers' => 'map,obj,LayerState',
        );
    }
}

class MapInfo {
    
    public $layers;
	public $initialMapStates;

    function getVarInfo() {
        return array(
        'layers' => 'map,obj,LayerBase',
        'initialMapStates' => 'map,obj,InitialMapState',
        'extent' => 'bbox',
        'location' => 'obj,Location',
        );
    }

    function getLayerList() {
        return $this->layerList;
    }

    function getLayerById($layerId) {

        foreach ($this->layers as $layer) {
            if ($layer->id == $layerId)
                return $layer;
        }
        return NULL;
    }

    function getInitialMapStateById($mapStateId) {

        foreach ($this->initialMapStates as $mapState) {
            if ($mapState->id == $mapStateId)
                return $mapState;
        }
        return NULL;
    }

    function getLayersByType($type) {

        $layers = array();
        if (!class_exists($type))
            return $layers;

        foreach ($this->layers as $layer) {
            if ($layer instanceof $type)
                $layers[] = $layer;
        }
        return $layers;
    }

    function addChildLayerBase($parentLayer, $childLayer) {
        
        $childLayerId = $childLayer->id;

        if (in_array($childLayerId, array_keys($this->layers)))
            throw new CartocommonException("Trying to replace layer " . $childLayerId);

        if (!in_array($childLayerId, $parentLayer->children))
            $parentLayer->children[] = $childLayerId;

        $this->layers[$childLayerId] = $childLayer;
    }
}

?>