<?php
/**
 * @package Plugins
 * @version $Id$
 */

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
    private function buildExpression($ids, $idField, $select) {

        if ($select) {
            $comp_op = '='; 
            $bool_op = ' OR ';
        } else {
            $comp_op = '!='; 
            $bool_op = ' AND ';
        }

        // FIXME: in config
        $id_is_string = true;
        
        if ($id_is_string) {
            $expr_pattern = '"[%s]"%s"%s"';
        } else {
            $expr_pattern = '[%s]%s%s';
        }

        $id_exprs = array();
        foreach ($ids as $id)
            $id_exprs[] = sprintf($expr_pattern, $idField, $comp_op, $id);
        return sprintf('(%s)', implode($bool_op, $id_exprs));  
    }    
    
    // TODO: make this function for all plugins, and read config from configuration file
    private function getConfig() {
        
        return array('outline_color' => array(255, 0, 0));
        
    }
    
    private function setHilightColor($colorObj) {
         $config = $this->getConfig();
         $clr = $config['outline_color'];
         $colorObj->setRGB($clr[0], $clr[1], $clr[2]);
    }
    
    private function makeClassHilighted($class) {
        $style = $class->getStyle(0);
        if (!empty($style)) {
            $color = $style->outlinecolor; 
            $this->setHilightColor($color);
        }
        $label = $class->label;
        if (!empty($label)) {
            $color = $label->color; 
            $this->setHilightColor($color);
        }
        return $class;
    }
    
    private function hilightClass($msLayer, $classIndex, $selectedIds, $select) {
        
        // activate the layer to be shown
        $msLayer->set('status', MS_ON);
        
        $class = $msLayer->getClass($classIndex);
        if (empty($class)) 
            throw new CartoserverException("no class at index $classIndex for layer $msLayer");    

        $classItem = $msLayer->classitem; 
        if (empty($classItem))
            throw new CartoserverException("no classitem for layer $msLayer->name");
        
        $class->setexpression($this->buildExpression($selectedIds, 
            $classItem, $select));
    }
    
    
    function getResultFromRequest($requ) {
        //return;
        
        define('HILIGHT_SUFFIX', '_hl');
        define('CREATE_HILIGHT_LAYER', true);

        define('HILIGHT_TRANSPARENCY', 20);
        define('HILIGHT_COLOR', '255, 255, 0');

        
        $mapInfo = $this->serverContext->mapInfo;
        $serverLayer = $mapInfo->getLayerById($requ->layerId);
        if (!$serverLayer)
            throw new CartoserverException("can't find serverLayer $requ->layerId");
        
        $msMapObj = $this->serverContext->msMapObj;
        
        $msLayer = @$msMapObj->getLayerByName($serverLayer->msLayer);
        if (empty($msLayer))
            throw new CartoserverException("can't find mslayer $serverLayer->msLayer");
        $msHilightLayer = @$msMapObj->getLayerByName($serverLayer->msLayer . HILIGHT_SUFFIX);
        
        if (CREATE_HILIGHT_LAYER) {
            $msHilightLayer = ms_newLayerObj($msMapObj, $msLayer);

            $msHilightLayer->set('transparency', HILIGHT_TRANSPARENCY);

            $class = $msHilightLayer->getClass(0);
            //$class->setExpression('("[lknr]" EQ "245")');
            $hlColor = explode(',', HILIGHT_COLOR);
            $style = $class->getStyle(0);
            $style->color->setRGB($hlColor[0], $hlColor[1], $hlColor[2]);
            
        }
        
        if (!empty($msHilightLayer)) {
            $this->hilightClass($msLayer, 0, $requ->selectedIds, false);
            $this->hilightClass($msHilightLayer, 0, $requ->selectedIds, true);

        } else {
            if ($msLayer->numclasses == 1) { 
                // creates a new class dynamically
                
                $hilightClass = ms_newClassObj($msLayer, $msLayer->getClass(0));
                // FIXME: should we set inverse expression ?
                $hilightClass->setExpression(NULL);
                $hilightClass = $this->makeClassHilighted($hilightClass);
            }
            
            $this->hilightClass($msLayer, 0, $requ->selectedIds, false);
            //$this->hilightClass($msLayer, 1, $requ->selectedIds, true);
        }
    }
}
?>