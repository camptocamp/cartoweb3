<?php
/**
 * @package Plugins
 * @version $Id$
 */

// Misc constants apprearing in config files  (mapfiles, ini, ...) 
define('HILIGHT_SUFFIX', '_hilight');
define('HILIGHT_CLASS', 'hilight');

/**
 * @package Plugins
 */
class ServerHilight extends ServerPlugin {

    private $log;

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    function getType() {
        return ServerPlugin::TYPE_PRE_DRAWING;
    }

    /**
     * Build a mapserver expression string.
     * 
     */
    private function buildExpression($requ, $select) {

        if ($select) {
            $comp_op = '='; 
            $bool_op = ' OR ';
        } else {
            $comp_op = '!='; 
            $bool_op = ' AND ';
        }

        $idType = $requ->idType;
        if (empty($requ->idType)) {
            $idType = $this->serverContext->getIdAttributeType($requ->layerId);
        }

        if ($idType == 'string') {
            $expr_pattern = '"[%s]"%s"%s"';
        } else {
            $expr_pattern = '[%s]%s%s';
        }

        $id_exprs = array();

        $ids = $requ->selectedIds;
        
        $idAttribute = $requ->idAttribute;
        if (empty($idAttribute))
            $idAttribute = $this->serverContext->getIdAttribute($requ->layerId);
        if (empty($idAttribute))
            throw new CartoserverException("no idAttributeString declared in ini config " .
                "or metadata, for layer $requ->layerId");
        
        foreach ($ids as $id)
            $id_exprs[] = sprintf($expr_pattern, $idAttribute, $comp_op, $id);
        return sprintf('(%s)', implode($bool_op, $id_exprs));  
    }    

    /**
     * Sets a color given in an array to a mapserver color object
     */
    private function setHilightColor($colorObj, $color) {
         $colorObj->setRGB($color[0], $color[1], $color[2]);
    }
    
    /**
     * Change the color and styles of this class to be hilighted.
     *
     * @param $class the class to hilight.
     */
    private function setupHilightClass($layer, $class) {
        
        if ($layer->getMetaData('hilight_color'))
            $hilightColor = explode(',', $layer->getMetaData('hilight_color'));
        else
            $hilightColor = array(0, 255, 0);
            
        $style = $class->getStyle(0);
        if (!empty($style)) {
            $this->setHilightColor($style->color, $hilightColor);
            $this->setHilightColor($style->outlinecolor, $hilightColor);
        }
        $label = $class->label;
        if (!empty($label)) {
            $color = $label->color; 
            $this->setHilightColor($label->color, $hilightColor);
        }
        return $class;
    }
    
    /**
     * Sets the expression of a mapserver class, so that it filters a given set
     * of elements. These elements are specified in the hilightRequest $requ.
     */
    private function setClassExpression($msLayer, $classIndex, $requ, $select=true) {
        
        $class = $msLayer->getClass($classIndex);
        if (empty($class)) 
            throw new CartoserverException("no class at index $classIndex for layer $msLayer");    

        $expression = $this->buildExpression($requ, $select);
        $this->log->debug("setting expression $expression");
        $class->setexpression($expression);
    }
    
    /**
     * Create a new layer which is a copy of $msLayer, and change some of 
     * its attributes, to be hilighted. These attributes are read from metadata.
     */
    private function createHilightLayer($msMapObj, $msLayer) {

        $msMapObj = $this->serverContext->msMapObj;

        $hilightTransparency = 20;
        if ($msLayer->getMetaData('hilight_transparency'))
            $hilightTransparency = $msLayer->getMetaData('hilight_transparency');
        
        $hilightColor = '255, 255, 0';

        if ($msLayer->getMetaData('hilight_color'))
            $hilightColor = $msLayer->getMetaData('hilight_color');

        $msHilightLayer = ms_newLayerObj($msMapObj, $msLayer);

        $msHilightLayer->set('transparency', $hilightTransparency);

        $class = $msHilightLayer->getClass(0);

        $hlColor = explode(',', $hilightColor);
        $style = $class->getStyle(0);
        $style->color->setRGB($hlColor[0], $hlColor[1], $hlColor[2]);

        return $msHilightLayer;
    }

    /**
     * Hilight a whole layer, by setting its classes to be hilighted.
     */ 
    private function hilightWholeLayer($layer, $requ) {
        
        $layer->set('status', MS_ON);
        
        for ($i = 0; $i < $layer->numclasses; $i++)
              $this->setClassExpression($layer, $i, $requ);
    }
    
    private function hilightLayer(HilightRequest $requ) {

        $mapInfo = $this->serverContext->mapInfo;

        $serverLayer = $mapInfo->getLayerById($requ->layerId);
        if (!$serverLayer)
            throw new CartoserverException("can't find serverLayer $requ->layerId");
        
        $msMapObj = $this->serverContext->msMapObj;
        
        $msLayer = @$msMapObj->getLayerByName($serverLayer->msLayer);
        if (empty($msLayer))
            throw new CartoserverException("can't find mslayer $serverLayer->msLayer");
        
        // activate this layer to be visible
        $msLayer->set('status', MS_ON);
        
        // if a layer with HILIGHT_SUFFIX exists, use it as hilight
        
        $msHilightLayer = @$msMapObj->getLayerByName($serverLayer->msLayer . HILIGHT_SUFFIX);
        if (!empty($msHilightLayer)) {
            $this->log->debug("activating special hilight layer");
            $msHilightLayer->set('status', MS_ON);
            $this->hilightWholeLayer($msHilightLayer, $requ);
            
            return;
        }
        
        // check if a class named HILIGHT_CLASS exists at position 0
        
        if ($msLayer->getClass(0)->name == HILIGHT_CLASS) {
            $this->log->debug("activating special hilight class");
            $this->setClassExpression($msLayer, $hilightIndex, $requ);
            return;            
        }

        // if "hilight_createlayer" is set in metadata, create a new layer

        if ($msLayer->getMetaData('hilight_createlayer')) {
            $this->log->debug("creating hilight layer");

            $newLayer = $this->createHilightLayer($msLayer);
            $this->hilightWholeLayer($newLayer, $requ);
            return;
        }

        // Fallback 1: create a new class with QUERYMAP color

        $this->log->debug("fallback: creating new class");

        $hilightClass = ms_newClassObj($msLayer, $msLayer->getClass(0));
        $hilightClass->set('name', 'dynamic_class');
        $hilightClass->set('minscale', $msLayer->minscale);
        $hilightClass->set('maxscale', $msLayer->maxscale);

        // move the new class to the top
        for($i = $msLayer->numclasses - 1; $i >= 1; $i--) {
            $msLayer->moveclassup($i);
        }

        // The new class has to be fetched again. Mapscript bug ?
        $cl = $msLayer->getClass(0);
        $hilightClass = $this->setupHilightClass($msLayer, $cl);

        $this->setClassExpression($msLayer, 0, $requ);
    }
    
    function getResultFromRequest($requ) {
        
        // FIXME: HilightRequest should support multiple layers hilight
        // This is a temporary solution
        if (isset($requ->multipleRequests)) {
            foreach ($requ->multipleRequests as $hilightRequest) {
                $this->hilightLayer($hilightRequest);   
            }               
        } else {
            $this->hilightLayer($requ);
        }
    }
}
?>