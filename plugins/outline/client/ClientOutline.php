<?php
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * Contains the state of an outline.
 */
class OutlineState {
 
    public $shapes;
    public $maskMode;
}

/**
 * @package CorePlugins
 */
class ClientOutline extends ClientPlugin implements ToolProvider {
    private $log;

    private $outlineState;
    private $area;
    
    const TOOL_POINT     = 'outline_point';
    const TOOL_RECTANGLE = 'outline_rectangle';
    const TOOL_POLYGON   = 'outline_poly';

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    function loadSession($sessionObject) {
        $this->outlineState = $sessionObject;
    }

    function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->outlineState = new OutlineState();
        $this->outlineState->shapes = array();
        $this->outlineState->maskMode = false;
        
        return;
    }

    function saveSession() {
        return $this->outlineState;
    }

    function handleMainmapTool(ToolDescription $tool, 
                               Shape $mainmapShape) {
        
        return $mainmapShape;
    }
    
    function handleKeymapTool(ToolDescription $tool, 
                            Shape $keymapShape) {
        /* nothing to do */
    }

    function getTools() {
        return array(new ToolDescription(self::TOOL_POINT, true,
                        new JsToolAttributes(JsToolAttributes::SHAPE_POINT),
                                         70),
                     new ToolDescription(self::TOOL_RECTANGLE, true,
                        new JsToolAttributes(JsToolAttributes::SHAPE_RECTANGLE),
                                         71),
                     new ToolDescription(self::TOOL_POLYGON, true, 
                        new JsToolAttributes(JsToolAttributes::SHAPE_POLYGON),
                                         72),
                    );
    }

    function handleHttpRequest($request) {

        if (!empty($request['outline_clear'])) {
            $this->outlineState->shapes = array();
        }

        if (!empty($request['outline_mask'])) {
            $this->outlineState->maskMode = $request['outline_mask'] == 'yes' ? true : false;
        }

        $shape = $this->cartoclient->getHttpRequestHandler()->handleTools($this);
        if ($shape) {
            if (!is_null($this->getConfig()->multipleShapes)
                    && !$this->getConfig()->multipleShapes) {
                $this->outlineState->shapes = array();
            }
            $this->outlineState->shapes[] = $shape;
        } 
    }

    function buildMapRequest($mapRequest) {
    
        $outlineRequest = new OutlineRequest();
        $outlineRequest->shapes   = $this->outlineState->shapes;
        $outlineRequest->maskMode = $this->outlineState->maskMode;
      
        $mapRequest->outlineRequest = $outlineRequest;
    }

    function handleResult($outlineResult) {
        if (is_null($outlineResult)) {
            return;
        }
        $this->area = $outlineResult->area;
    }

    private function drawOutline() {
        $this->smarty = new Smarty_CorePlugin($this->cartoclient->getConfig(),
                                              $this);
        $maskSelected = $this->outlineState->maskMode ? 'yes' : 'no';
        $this->smarty->assign(array('outline_mask_selected' => $maskSelected,
                                    'outline_area'          => $this->area));
        return $this->smarty->fetch('outline.tpl');
    }

    function renderForm($template) {
        if (!$template instanceof Smarty) {
            throw new CartoclientException('unknown template type');
        }

        $outline_active = $this->getConfig()->outlineActive;
       
        $template->assign(array('outline_active' => true,
                                'outline' => $this->drawOutline()));
    }
}
?>
