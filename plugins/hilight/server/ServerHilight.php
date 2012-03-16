<?php
/**
 * Vector objects hilighting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * @copyright 2005 Camptocamp SA
 * @package Plugins
 * @version $Id$
 */

/** 
 * Misc constants apprearing in config files (mapfiles, ini, ...) 
 */
define('HILIGHT_SUFFIX', '_hilight');
define('HILIGHT_CLASS', 'hilight');

define('MASK_SUFFIX', '_mask');
define('MASK_DEFAULT_OUTSIDE', 'default_outside_mask');

/**
 * Hilighting server plugin
 * 
 * This plugin is a service server plugin, it doesn't implement any interfaces
 * and doesn't have a client side. Vector hilighting is used by 
 * {@link ServerQuery}.
 * @package Plugins
 */
class ServerHilight extends ServerPlugin {

    /**
     * @var Logger
     */
    private $log;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->log = LoggerManager::getLogger(__CLASS__);
    }

    /**
     * Builds a mapserver expression string
     * @param QuerySelection
     * @return string expression string
     */
    protected function buildExpression($querySelection) {

        if (!$querySelection->maskMode) {
            $comp_op = '='; 
            $bool_op = ' OR ';
        } else {
            $comp_op = '!='; 
            $bool_op = ' AND ';
        }

        $idType = $querySelection->idType;
        if (empty($querySelection->idType)) {
            $idType = $this->serverContext->
                                   getIdAttributeType($querySelection->layerId);
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
            $idAttribute = $this->serverContext->
                                       getIdAttribute($querySelection->layerId);
        if (empty($idAttribute))
            throw new CartoserverException('no id_attribute_string metadata ' .
                                           'declared for layer ' .
                                           $querySelection->layerId);
        
        foreach ($ids as $id) {
            $id = Encoder::decode($id, 'config');
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
    protected function setHilightColor($colorObj, $color) {
         $colorObj->setRGB($color[0], $color[1], $color[2]);
    }
    
    /**
     * Change the color and styles of this class to be hilighted
     * @param MsLayer layer
     * @param MsClass class to hilight
     * @return MsClass resulting class
     */
    protected function setupHilightClass($layer, $class) {
        
        if ($layer->getMetaData('hilight_color'))
            $hilightColor = explode(',', $layer->getMetaData('hilight_color'));
        else
            $hilightColor = array(0, 255, 0);
            
        if ($class->numstyles >= 1)
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
     * of elements
     * 
     * These elements are specified in the {@link QuerySelection}.
     * @param MsLayer MapServer layer
     * @param int index of layer's class
     * @param QuerySelection
     */
    protected function setClassExpression($msLayer, $classIndex,
                                          $querySelection) {
        
        $class = $msLayer->getClass($classIndex);
        if (empty($class)) 
            throw new CartoserverException("no class at index $classIndex for "
                                           . "layer $msLayer");    

        $expression = $this->buildExpression($querySelection);

        $useLogExp = $msLayer->getMetaData('hilight_use_logical_expressions');
        if ($useLogExp == 'true') {
            $origExp = $class->getExpression();
            if (strlen($origExp) > 0)
                $expression = sprintf("(%s AND %s)", $origExp, $expression);
        }

        $this->log->debug("setting expression $expression");
        $class->setexpression($expression);
    }
    
    /**
     * Copies a layer and sets some attributes
     * @param MsLayer layer to be copied
     * @param string transparency if not specified in source layer
     * @param string color if not specified in source layer
     * @param string meta data name for transparency
     * @param string meta data name for color
     * @return MsLayer new layer
     */
    protected function createLayer($msLayer, $defaultTrans, $defaultColor,
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

        $newColor = explode(' ', $color);
        $style = $class->getStyle(0);
        $style->color->setRGB($newColor[0], $newColor[1], $newColor[2]);
        $style->outlinecolor->setRGB($newColor[0], $newColor[1], $newColor[2]);

        return $msNewLayer;
    }
    
    /**
     * Create a new layer which is a copy of $msLayer, and change some of 
     * its attributes, to be hilighted
     * 
     * These attributes are read from metadata.
     * @param MsLayer
     * @return MsLayer
     */
    protected function createHilightLayer($msLayer) {
    
        return $this->createLayer($msLayer, 20, '255, 255, 0', 
                                  'hilight_transparency', 'hilight_color');
    }

    /**
     * Hilight a whole layer, by setting its classes to be hilighted
     * @param MsLayer
     * @param QuerySelection
     */ 
    protected function hilightWholeLayer($layer, $querySelection) {
        
        $layer->set('status', MS_ON);
        
        for ($i = 0; $i < $layer->numclasses; $i++)
              $this->setClassExpression($layer, $i, $querySelection);
    }
    
    /**
     * Create a new layer which is a copy of $msLayer, and change some of 
     * its attributes, to be masked
     * 
     * These attributes are read from metadata.
     * @param MsLayer
     * @return MsLayer
     */
    protected function createMaskLayer($msLayer) {

        return $this->createLayer($msLayer, 100, '255, 255, 255', 
                                  'mask_transparency', 'mask_color');
    }

    /**
     * Mask a whole layer, by setting its classes to be masked
     * @param MsLayer
     * @param QuerySelection
     */ 
    protected function maskWholeLayer($layer, $querySelection) {
        
        $layer->set('status', MS_ON);
        
        for ($i = 0; $i < $layer->numclasses; $i++) {
            $class = $layer->getClass($i);
            $expression = $this->buildExpression($querySelection, false);
            $class->setexpression($expression);
        }
    }
        
    /**
     * Main function, does hilight given a {@link QuerySelection}
     * @param QuerySelection
     * @see ServerQuery::handlePreDrawing()
     */
    public function hilightLayer(QuerySelection $querySelection) {
 
        $layersInit = $this->serverContext->getMapInfo()->layersInit;
        
        $serverLayer = $layersInit->getLayerById($querySelection->layerId);
        if (!$serverLayer) {
            throw new CartoserverException("can't find serverLayer " .
                                           $querySelection->layerId);
        }
        
        $msMapObj = $this->serverContext->getMapObj();
        
        $msLayer = @$msMapObj->getLayerByName($serverLayer->msLayer);
        if (empty($msLayer)) {
            throw new CartoserverException("can't find mslayer " .
                                           $serverLayer->msLayer);
        }

        if ($msLayer->type == MS_LAYER_RASTER) {
            // No hilights on raster
            return;
        }

        // activate this layer to be visible
        $msLayer->set('status', MS_ON);
        
        // TODO(sp): create two functions: hilightLayerMask hilightLayerNormal
        if ($querySelection->maskMode) {
            
            // Activate outside mask layer
            $outsideMask = false;
            if ($msLayer->getMetaData('outside_mask')) {
                $msMaskLayer = @$msMapObj->getLayerByName($msLayer->
                                                   getMetaData('outside_mask'));
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
            $msMaskLayer = @$msMapObj->getLayerByName($serverLayer->msLayer . 
                                                                   MASK_SUFFIX);
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
        
            $msHilightLayer = @$msMapObj->getLayerByName($serverLayer->msLayer .
                                                                HILIGHT_SUFFIX);
            if (!empty($msHilightLayer)) {
                $this->log->debug("activating special hilight layer");
                $msHilightLayer->set('status', MS_ON);
                $this->hilightWholeLayer($msHilightLayer, $querySelection);
                
                return;
            }
            
            // check if a class named HILIGHT_CLASS exists at position 0
        
            if ($msLayer->numclasses >= 1 && 
                       $msLayer->getClass(0)->name == HILIGHT_CLASS) {
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

            
            if ($msLayer->numclasses >= 1)
                $hilightClass = ms_newClassObj($msLayer, $msLayer->getClass(0));
            else
                $hilightClass = ms_newClassObj($msLayer);
                
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
