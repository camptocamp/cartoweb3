<?php
/**
 * Vector objects hilighting
 * @package Plugins
 * @version $Id$
 */

// Misc constants apprearing in config files (mapfiles, ini, ...) 
define('HILIGHT_SUFFIX', '_hilight');
define('HILIGHT_CLASS', 'hilight');

define('MASK_SUFFIX', '_mask');
define('MASK_DEFAULT_OUTSIDE', 'default_outside_mask');

/**
 * Hilighting server plugin
 * 
 * This plugin is a service server plugin, it doesn't implement any interfaces
 * and doesn't have a client side. Vector hilighting is used by 
 * {@link ServerSelection} and may be used by {@link ServerQuery}.
 * @package Plugins
 */
class ServerHilight extends ServerPlugin {

    /**
     * @var Logger
     */
    private $log;

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * UTF-8 decodes an Id
     * @param string
     * @return string
     */
    private function decodeId($id) {
        return utf8_decode($id);   
    }

    /**
     * Builds a mapserver expression string.
     * @param QuerySelection
     * @return string expression string
     */
    private function buildExpression($querySelection) {

        if (!$querySelection->maskMode) {
            $comp_op = '='; 
            $bool_op = ' OR ';
        } else {
            $comp_op = '!='; 
            $bool_op = ' AND ';
        }

        $idType = $querySelection->idType;
        if (empty($querySelection->idType)) {
            $idType = $this->serverContext->getIdAttributeType($querySelection->layerId);
        }

        if ($idType == 'string') {
            $expr_pattern = '"[%s]"%s"%s"';
        } else {
            $expr_pattern = '[%s]%s%s';
        }

        $id_exprs = array();

        $ids = $querySelection->selectedIds;
        
        $idAttribute = $querySelection->idAttribute;
        if (empty($idAttribute))
            $idAttribute = $this->serverContext->getIdAttribute($querySelection->layerId);
        if (empty($idAttribute))
            throw new CartoserverException("no id_attribute_string metadata declared" .
                " for layer $querySelection->layerId");
        
        foreach ($ids as $id) {
            $id = $this->decodeId($id);
            $id_exprs[] = sprintf($expr_pattern, $idAttribute, $comp_op, $id);
        }
        
        $result = sprintf('(%s)', implode($bool_op, $id_exprs));
        if (count($ids) == 0) {
            if (!$querySelection->maskMode) {
                // normal mode, nothing selected, so nothing must be hilighted
                $result = '(1=0)';
            } else {
                // mask mode, nothing selected, so everything must be masked
                $result = '(1=1)';
            }
        }
        return $result;  
    }    

    /**
     * Sets a color given in an array to a mapserver color object
     * @param MsColor MapServer color
     * @param array initial color (array(int red, int green, int blue))     
     */
    private function setHilightColor($colorObj, $color) {
         $colorObj->setRGB($color[0], $color[1], $color[2]);
    }
    
    /**
     * Change the color and styles of this class to be hilighted.
     * @param MsLayer layer
     * @param MsClass class to hilight
     * @return MsClass resulting class
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
     * of elements. These elements are specified in the {@link QuerySelection}.
     * @param MsLayer MapServer layer
     * @param int index of layer's class
     * @param QuerySelection
     */
    private function setClassExpression($msLayer, $classIndex, $querySelection) {
        
        $class = $msLayer->getClass($classIndex);
        if (empty($class)) 
            throw new CartoserverException("no class at index $classIndex for layer $msLayer");    

        $expression = $this->buildExpression($querySelection);
        $this->log->debug("setting expression $expression");
        $class->setexpression($expression);
    }
    
    private function createLayer($msLayer, $defaultTrans, $defaultColor,
                                 $metaTrans, $metaColor) {

        $msMapObj = $this->serverContext->getMapObj();

        $transparency = $defaultTrans;
        if ($msLayer->getMetaData($metaTrans))
            $transparency = $msLayer->getMetaData($metaTrans);
        
        $color = $defaultColor;

        if ($msLayer->getMetaData($metaColor))
            $color = $msLayer->getMetaData($metaColor);

        $msNewLayer = ms_newLayerObj($msMapObj, $msLayer);

        $msNewLayer->set('transparency', $transparency);

        $class = $msNewLayer->getClass(0);

        $newColor = explode(',', $color);
        $style = $class->getStyle(0);
        $style->color->setRGB($newColor[0], $newColor[1], $newColor[2]);
        $style->outlinecolor->setRGB($newColor[0], $newColor[1], $newColor[2]);

        return $msNewLayer;
    }
    
    /**
     * Create a new layer which is a copy of $msLayer, and change some of 
     * its attributes, to be hilighted. These attributes are read from metadata.
     */
    private function createHilightLayer($msLayer) {

        return $this->createLayer($msLayer, 20, '255, 255, 0', 
                                  'hilight_transparency', 'hilight_color');
    }

    /**
     * Hilight a whole layer, by setting its classes to be hilighted.
     */ 
    private function hilightWholeLayer($layer, $querySelection) {
        
        $layer->set('status', MS_ON);
        
        for ($i = 0; $i < $layer->numclasses; $i++)
              $this->setClassExpression($layer, $i, $querySelection);
    }
    
    /**
     * Create a new layer which is a copy of $msLayer, and change some of 
     * its attributes, to be masked. These attributes are read from metadata.
     */
    private function createMaskLayer($msLayer) {

        return $this->createLayer($msLayer, 100, '255, 255, 255', 
                                  'mask_transparency', 'mask_color');
    }

    /**
     * Mask a whole layer, by setting its classes to be masked.
     */ 
    private function maskWholeLayer($layer, $querySelection) {
        
        $layer->set('status', MS_ON);
        
        for ($i = 0; $i < $layer->numclasses; $i++) {
            $class = $layer->getClass($i);
            $expression = $this->buildExpression($querySelection, false);
            $class->setexpression($expression);
        }
    }
        
    /**
     * Main function, does hilight given a {@link QuerySelection}
     *
     * @param QuerySelection
     * @see ServerQuery::handlePreDrawing()
     */
    function hilightLayer($querySelection) {
        
        $mapInfo = $this->serverContext->getMapInfo();
        
        $serverLayer = $mapInfo->getLayerById($querySelection->layerId);
        if (!$serverLayer)
            throw new CartoserverException("can't find serverLayer $querySelection->layerId");
        
        $msMapObj = $this->serverContext->getMapObj();
        
        $msLayer = @$msMapObj->getLayerByName($serverLayer->msLayer);
        if (empty($msLayer))
            throw new CartoserverException("can't find mslayer $serverLayer->msLayer");
        
        // activate this layer to be visible
        $msLayer->set('status', MS_ON);
        
        // TODO(sp): create two functions: hilightLayerMask hilightLayerNormal
        if ($querySelection->maskMode) {
            
            // Activate outside mask layer
            $outsideMask = false;
            if ($msLayer->getMetaData('outside_mask')) {
                $msMaskLayer = @$msMapObj->getLayerByName($msLayer->getMetaData('outside_mask'));
                if (!empty($msMaskLayer)) {
                    $msMaskLayer->set('status', MS_ON);
                    $outsideMask = true;
                }
            }                            
            if (!$outsideMask) {
                $msMaskLayer = @$msMapObj->getLayerByName(MASK_DEFAULT_OUTSIDE);
                if (!empty($msMaskLayer))
                    $msMaskLayer->set('status', MS_ON);
            }                            
            
            // if a layer with MASK_SUFFIX exists, use it as mask
            $msMaskLayer = @$msMapObj->getLayerByName($serverLayer->msLayer . MASK_SUFFIX);
            if (!empty($msMaskLayer)) {
                $this->log->debug("activating special mask layer");
                $msMaskLayer->set('status', MS_ON);
                $this->maskWholeLayer($msMaskLayer, $querySelection);
                return;
            }
            
            // fall-back                                  
            $newLayer = $this->createMaskLayer($msLayer);
            $this->maskWholeLayer($newLayer, $querySelection);
            return;               
                
        } else {
            // if a layer with HILIGHT_SUFFIX exists, use it as hilight
        
            $msHilightLayer = @$msMapObj->getLayerByName($serverLayer->msLayer . HILIGHT_SUFFIX);
            if (!empty($msHilightLayer)) {
                $this->log->debug("activating special hilight layer");
                $msHilightLayer->set('status', MS_ON);
                $this->hilightWholeLayer($msHilightLayer, $querySelection);
                
                return;
            }
            
            // check if a class named HILIGHT_CLASS exists at position 0
        
            if ($msLayer->getClass(0)->name == HILIGHT_CLASS) {
                $this->log->debug("activating special hilight class");
                $this->setClassExpression($msLayer, 0, $querySelection);
                return;            
            }

            // if "hilight_createlayer" is set in metadata, create a new layer

            if ($msLayer->getMetaData('hilight_createlayer')) {
                $this->log->debug("creating hilight layer");

                $newLayer = $this->createHilightLayer($msLayer);
                $this->hilightWholeLayer($newLayer, $querySelection);
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

            $this->setClassExpression($msLayer, 0, $querySelection);
        }
    }
}

?>
