<?php

class CartocommonException extends Exception {

}

class LayerBase {
    const TYPE_LAYERGROUP = 'layerGroup';
    const TYPE_LAYER = 'layer';
    const TYPE_LAYERCLASS = 'layerClass';
    
    function getVarInfo() {
        return array(
            'children' => 'array',

            //'msLayers' => 'array',
            'test' => 'array,int',
            'maptest' => 'obj,stdclass',
            'selected' => 'boolean',
        );
    }

    function endSerialize() {
        if (!@$this->type) {
            
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
    public $selected;


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

class Location2 {
    function getVarInfo($context) {
        
        if ($context == StructHandler::CONTEXT_INI)
            return array(
                'bbox' => 'bbox',
                );
        else 
            return array(
                'bbox' => 'obj,Bbox',
                );
    }
}

class MapInfo {

    //private $initialMapInfoStruct;
    //private $layerList;
    
    public $layers;

    function getVarInfo() {
        return array(
        
        'layers' => 'map,obj,LayerBase',
        'extent' => 'bbox',
        'location' => 'obj,Location2',
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