<?php
/**
 * Outline plugin
 * @package Plugins
 * @version $Id$
 */

/**
 * Contains the state of an outline.
 * @package Plugins
 */
class OutlineState {

    /** 
     * Current drawn shapes
     * @var array
     */
    public $shapes;
    
    /**
     * If true, will draw a mask instead of a standard shape
     * @var boolean
     */
    public $maskMode;
}

/**
 * Client Outline class
 * @package Plugins
 */
class ClientOutline extends ClientPlugin
                    implements Sessionable, GuiProvider, ServerCaller,
                    ToolProvider, Exportable {
                    
    /**                    
     * @var Logger
     */
    private $log;

    /**
     * @var OutlineState
     */
    private $outlineState;
    
    /**
     * Total shapes area
     * @var double
     */
    private $area;
    
    const TOOL_POINT     = 'outline_point';
    const TOOL_RECTANGLE = 'outline_rectangle';
    const TOOL_POLYGON   = 'outline_poly';

    /**
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    /**
     * @see Sessionable::loadSession()
     */
    public function loadSession($sessionObject) {
        $this->outlineState = $sessionObject;
    }

    /**
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo, 
                                  InitialMapState $initialMapState) {
        $this->outlineState = new OutlineState();
        $this->outlineState->shapes = array();
        $this->outlineState->maskMode = false;
        
        return;
    }

    /**
     * @see Sessionable::saveSession()
     */
    public function saveSession() {
        return $this->outlineState;
    }

    /**
     * @see ToolProvider::handleMainmapTool()
     */
    public function handleMainmapTool(ToolDescription $tool, 
                               Shape $mainmapShape) {
        
        return $mainmapShape;
    }
    
    /**
     * @see ToolProvider::handleKeymapTool()
     */
    public function handleKeymapTool(ToolDescription $tool, 
                            Shape $keymapShape) {
        /* nothing to do */
    }

    /**
     * Returns outline tools : Point, Rectangle and Polygon
     * @return array array of ToolDescription
     */
    public function getTools() {
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

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {

        if (!empty($request['outline_clear'])) {
            $this->outlineState->shapes = array();
        }

        if (!empty($request['outline_mask'])) {
            $this->outlineState->maskMode = ($request['outline_mask'] == 'yes');
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

    /**
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {
    }
    
    /**
     * @see ServerCaller::buildMapRequest()
     */
    public function buildMapRequest($mapRequest) {
    
        $outlineRequest = new OutlineRequest();
        $outlineRequest->shapes   = $this->outlineState->shapes;
        $outlineRequest->maskMode = $this->outlineState->maskMode;
      
        $mapRequest->outlineRequest = $outlineRequest;
    }

    /**
     * @see ServerCaller::initializeResult()
     */ 
    public function initializeResult($outlineResult) {
        if (is_null($outlineResult)) {
            return;
        }
        $this->area = $outlineResult->area;
    }

    /**
     * @see ServerCaller::handleResult()
     */ 
    public function handleResult($outlineResult) {}
    
    /**
     * Draws Outline form and returns Smarty generated HTML
     * @return string
     */
    private function drawOutline() {
        $this->smarty = new Smarty_CorePlugin($this->getCartoclient(), $this);
        $maskSelected = $this->outlineState->maskMode ? 'yes' : 'no';
        $this->smarty->assign(array('outline_mask_selected' => $maskSelected,
                                    'outline_area'          => $this->area));
        return $this->smarty->fetch('outline.tpl');
    }

    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {

        $outline_active = $this->getConfig()->outlineActive;
       
        $template->assign(array('outline_active' => true,
                                'outline' => $this->drawOutline()));
    }

    /**
     * @see Exportable::adjustExportMapRequest
     */
    public function adjustExportMapRequest(ExportConfiguration $configuration,
                                    MapRequest $mapRequest) {

        $printOutline = $configuration->getPrintOutline();
        if (!is_null($printOutline)) {
            $outlineRequest = new OutlineRequest();
            array_push($this->outlineState->shapes, $printOutline);
            $outlineRequest->shapes   = $this->outlineState->shapes;
            $mapRequest->outlineRequest = $outlineRequest;
        }
    }
}

?>
