<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * @package CorePlugins
 */
class ServerLayers extends ClientResponderAdapter
                   implements CoreProvider {
    private $log;

    private $requestedLayerNames;
    private $mapInfo;
    
    private $imageType;

    /**
     * @var float
     */
    private $resRatio;
    
    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
        
        // If image type is null, will use mapfile image type
        $this->imageType = null;
    }

    private function getMapInfo() {
        if(!$this->mapInfo) $this->mapInfo =& $this->serverContext->getMapInfo();
        return $this->mapInfo;
    }
   
    function getRequestedLayerNames() {
        if(!$this->requestedLayerNames) return array();
        return $this->requestedLayerNames;
    }

    function getImageType() {
        return $this->imageType;
    }

    /**
     * @return float ratio client-required resolution / Mapserver resolution.
     */
    function getResRatio() {
        return $this->resRatio;
    }
  
    /**
     * @see ClientResponder::initializeRequest()
     */
    function initializeRequest($requ) {
        if (isset($requ->resolution) && $requ->resolution) {
        
            $msMapObj = $this->serverContext->getMapObj();
        
            if ($requ->resolution != $msMapObj->resolution)
                $this->resRatio = $requ->resolution / $msMapObj->resolution;
            else
                $this->resRatio = 1;
        }
    }    

    /**
     * Generic function to multiply integer properties of a
     * php mapscript object of a given ratio.
     * @param mixed php mapscript object to update
     * @param array array of numerical properties to update
     * @param float multiplicative coefficient
     */
    private function updateProperties($obj, $properties, $ratio) {
        foreach($properties as $p) {
            $value = $obj->$p;

            // special case for bitmapped labels size
            if ($p == 'size' && get_class($obj) == 'ms_label_obj' && 
                $value <= 4) { 
                $obj->set($p, 4); 
                continue;
            }           

            $obj->set($p, $value * $ratio);
        }
    }

    /**
     * Updates mapfile objects (layers, classes, styles) properties
     * according to the ratio between required resolution and mapserver one.
     * @param ms_layer_obj Mapserver layer object
     * @param float resolutions ratio
     */
    private function updateRatioParameters($layer, $resRatio) {
 
        $invResRatio = 1 / $resRatio;
        
        $label_props = array('size', 'mindistance', 'minfeaturesize', 
                             'minsize', 'maxsize', 'offsetx', 'offsety');

        if ($layer->toleranceunits == MS_PIXELS)
            $this->updateProperties($layer, array('tolerance'), $resRatio);

        $this->updateProperties($layer, array('minscale', 'maxscale'),
                                $invResRatio);
        
        for ($j = 0; $j < $layer->numclasses; $j++) {
            $class = $layer->getclass($j);

            for ($k = 0; $k < $class->numstyles; $k++) {
                $style = $class->getStyle($k);
                // style sizes not totally resized by the ratio factor,
                // to improve readability:
                $styleRatio = $resRatio * 1.0;
                $this->updateProperties($style, 
                                        array('size','offsetx', 'offsety',
                                              'minsize', 'maxsize'),
                                        $styleRatio);
            }
            
            $this->updateProperties($class, array('minscale', 'maxscale'),
                                    $invResRatio);
            
            $label = $class->label;
            $this->updateProperties($label, $label_props, $resRatio);
        }
    }

    function handleCorePlugin($requ) {

        $msMapObj = $this->serverContext->getMapObj();

        $layerIds = $requ->layerIds;
        $this->requestedLayerNames = $layerIds;
        
        if (!is_array($layerIds)) {
            throw new CartoclientException('Invalid layer request: ' .
                                           'layerIds not array');
            return;
        }

        $this->log->debug('layers to draw: ');
        $this->log->debug($layerIds);
      
        // disable all layers
        for ($i = 0; $i < $msMapObj->numlayers; $i++) {
            $msLayer = $msMapObj->getLayer($i);
            $msLayer->set('status', MS_OFF);
        }
        
        foreach ($this->getRequestedLayerNames() as $requLayerId) {
            $this->log->debug("testing id $requLayerId");
            
            $msLayer = $this->getMapInfo()->getMsLayerById($msMapObj, $requLayerId);
            $msLayer->set('status', MS_ON);

            if ($this->resRatio && $this->resRatio != 1)
                $this->updateRatioParameters($msLayer, $this->resRatio);
            
            $forceImageType = $msLayer->getMetaData('force_imagetype');
            if (!empty($forceImageType)) {
                $this->imageType = $forceImageType;
            }
        }
    }
}
?>
