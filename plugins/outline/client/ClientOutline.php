<?php
/**
 * Outline plugin
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
 * Contains the state of an outline.
 * @package Plugins
 */
class OutlineState {

    /** 
     * Current drawn styled shapes
     * @var array
     */
    public $shapes;
    
    /**
     * If true, will draw a mask instead of a standard shape
     * @var boolean
     */
    public $maskMode;
    
    /**
     * If true, will ask for a label text
     * @var boolean
     */
    public $labelMode;
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
    protected $outlineState;
    
    /**
     * Total shapes area
     * @var double
     */
    protected $area;
    
    const TOOL_POINT     = 'outline_point';
    const TOOL_LINE      = 'outline_line';
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
        $this->outlineState->labelMode = false;
        
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
        return array(new ToolDescription(self::TOOL_POINT, true, 70),
                     new ToolDescription(self::TOOL_LINE, true, 71),
                     new ToolDescription(self::TOOL_RECTANGLE, true, 72),
                     new ToolDescription(self::TOOL_POLYGON, true, 73),
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
            $styledShape = new StyledShape();
            
            // TODO: Add style management on client
            
            // Uncomment following code for testing
            
            // $shapeStyle = new ShapeStyle();
            // $shapeStyle->color->setFromRGB(255,255,255);
            // $shapeStyle->outlineColor->setFromRGB(0,0,0);
            // $shapeStyle->backgroundColor->setFromRGB(255,0,0);
            // $shapeStyle->size = 3;
            // $shapeStyle->transparency = 100;
            // $labelStyle = new LabelStyle();
            // $labelStyle->size = 25;
            // $labelStyle->color->setFromRGB(0,255,0);
            
            // $styledShape->shapeStyle = $shapeStyle;
            // $styledShape->labelStyle = $labelStyle;
            
            $styledShape->shape = $shape;
            if ($this->getConfig()->labelMode
                    && !empty($request['outline_label_text'])) {
                $styledShape->label = $request['outline_label_text'];
            }
            if (!is_null($this->getConfig()->multipleShapes)
                    && !$this->getConfig()->multipleShapes) {
                $this->outlineState->shapes = array();
            }
            $this->outlineState->shapes[] = $styledShape;
        }
    }

    /**
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {
    }
    
    /**
     * @see ServerCaller::buildRequest()
     */
    public function buildRequest() {
    
        if (!empty($this->outlineState->shapes)) {
            $outlineRequest = new OutlineRequest();
            $outlineRequest->shapes   = $this->outlineState->shapes;        
            $outlineRequest->maskMode = $this->outlineState->maskMode;

            return $outlineRequest;
        }
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
    protected function drawOutline() {
        $this->smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $maskSelected = $this->outlineState->maskMode ? 'yes' : 'no';
        $this->smarty->assign(array('outline_mask_selected' => $maskSelected,
                                    'outline_area'          => $this->area));
        return $this->smarty->fetch('outline.tpl');
    }
    
    /**
     * Draws Outlinelabel form and returns Smarty generated HTML
     * @return string
     */    
    protected function drawOutlinelabel() {
        $this->smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        return $this->smarty->fetch('outlinelabel.tpl');
    }

    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {

        $template->assign(array('outline' => $this->drawOutline(),
                                'outlinelabel' => $this->drawOutlinelabel()));
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
