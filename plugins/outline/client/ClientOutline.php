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
}

/**
 * @package CorePlugins
 */
class ClientOutline extends ClientPlugin implements ToolProvider {
    private $log;

    private $outlineState;
    
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
        $weightPoint = $this->getConfig()->weightPoint;
        $weightRect  = $this->getConfig()->weightRectangle;
        $weightPoly  = $this->getConfig()->weightPoly;
        if (!$weightPoint) $weightPoint = 70;
        if (!$weightRect) $weightRect = 71;
        if (!$weightPoly) $weightPoly = 72;
        
        return array(new ToolDescription(self::TOOL_POINT, self::TOOL_POINT,
                                         'Point', ToolDescription::MAINMAP,
                                         $weightPoint, 'outline', 'zoom_out'),
                     new ToolDescription(self::TOOL_RECTANGLE, self::TOOL_RECTANGLE,
                                         'Rect', ToolDescription::MAINMAP,
                                         $weightRect, 'outline', 'zoom_in'),
                     new ToolDescription(self::TOOL_POLYGON, self::TOOL_POLYGON,
                                         'Poly', ToolDescription::MAINMAP,
                                         $weightPoly, 'outline', 'polygon'));
    }

    function handleHttpRequest($request) {

        if (!empty($request['outline_clear'])) {
            $this->outlineState->shapes = array();
        }

        $shape = $this->cartoclient->getHttpRequestHandler()->handleTools($this);
        if ($shape) {
            $this->outlineState->shapes[] = $shape;
        } 
    }

    function buildMapRequest($mapRequest) {
    
        $outlineRequest = new OutlineRequest();
        $outlineRequest->shapes = $this->outlineState->shapes;
      
        $mapRequest->outlineRequest = $outlineRequest;
    }

    function handleResult($outlineResult) {
        /* No results */
    }

    function renderForm($template) {
        if (!$template instanceof Smarty) {
            throw new CartoclientException('unknown template type');
        }

        /* TODO: display clear if at least one shape */
    }
}
?>
