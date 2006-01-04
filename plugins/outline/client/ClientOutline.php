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

define('OUTLINE_SESSION_VERSION', 2);

/**
 * Contains the state of an outline.
 * @package Plugins
 */
class OutlineState {

    /** 
     * Current drawn styled shapes
     * @var array array of StyledShape
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

    /**
     * Current point style
     * @var StyleOverlay
     */
    public $pointStyle;

    /**
     * Current line style
     * @var StyleOverlay
     */
    public $lineStyle;

    /**
     * Current rectangle and polygon style
     * @var StyleOverlay
     */
    public $polygonStyle;
}

/**
 * Upgrades from V1 to V2
 */
class OutlineV1ToV2 extends ViewUpgrader {
    
    protected function callFilters() {

        $this->add('pointStyle', new StyleOverlay());
        $this->add('lineStyle', new StyleOverlay());
        $this->add('polygonStyle', new StyleOverlay());
    }
}

/**
 * Client Outline class
 * @package Plugins
 */
class ClientOutline extends ClientPlugin 
                    implements Sessionable, GuiProvider, ServerCaller, 
                               ToolProvider, Exportable, InitUser {
                    
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
    
    /**
     * @var OutlineInit
     */
    protected $symbols;

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

        $this->log->debug('loading session:');
        $this->log->debug($sessionObject);

        $this->outlineState = $sessionObject;
    }

    /**
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo, 
                                  InitialMapState $initialMapState) {

        $this->log->debug('creating session.');
             
        $this->outlineState = new OutlineState();

        $this->outlineState->shapes = array();
        $this->outlineState->maskMode = false;
        $this->outlineState->pointStyle = new StyleOverlay();
        $this->outlineState->lineStyle = new StyleOverlay();
        $this->outlineState->polygonStyle = new StyleOverlay();
    }

    /**
     * @see Sessionable::saveSession()
     */
    public function saveSession() {
        $this->log->debug('saving session:');
        $this->log->debug($this->outlineState);

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
                                     Shape $keymapShape) {}

    /**
     * @see ToolProvider::handleApplicationTool()
     */
    public function handleApplicationTool(ToolDescription $tool) {}

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

        /* update default ShapeStyle */
        $this->outlineState->pointStyle->symbol = 
            $this->getHttpValue($request, 'outline_point_symbol');
        $this->outlineState->pointStyle->size = 
            $this->getHttpValue($request, 'outline_point_size');
        $this->outlineState->pointStyle->color->setFromHex(
            $this->getHttpValue($request, 'outline_point_color'));

        $this->outlineState->lineStyle->symbol = 
            $this->getHttpValue($request, 'outline_line_symbol');
        $this->outlineState->lineStyle->size = 
            $this->getHttpValue($request, 'outline_line_size');
        $this->outlineState->lineStyle->outlineColor->setFromHex(
            $this->getHttpValue($request, 'outline_line_color'));
        $this->outlineState->lineStyle->transparency = 
            $this->getHttpValue($request, 'outline_line_transparency');

        $this->outlineState->polygonStyle->outlineColor->setFromHex(
            $this->getHttpValue($request, 'outline_polygon_outline_color'));
        $this->outlineState->polygonStyle->color->setFromHex(
            $this->getHttpValue($request, 'outline_polygon_background_color'));
        $this->outlineState->polygonStyle->transparency = 
            $this->getHttpValue($request, 'outline_polygon_transparency');

        $shape = $this->cartoclient->getHttpRequestHandler()->handleTools($this);

        if ($shape) {
            $styledShape = new StyledShape();

            // get options
            switch ($this->getCartoclient()->getClientSession()->selectedTool) {
            case self::TOOL_POINT:
                $styledShape->shapeStyle = clone $this->outlineState->pointStyle;
                break;
                
            case self::TOOL_LINE:
                $styledShape->shapeStyle = clone $this->outlineState->lineStyle;
                break;

            case self::TOOL_RECTANGLE:
            case self::TOOL_POLYGON:
                $styledShape->shapeStyle = clone $this->outlineState->polygonStyle;
                break;

            default:
                // we should never go here ...
                break;
            }

            $styledShape->shape = $shape;

            if ($this->getConfig()->labelMode
                && !empty($request['outline_label_text'])) {
                $styledShape->label = 
                    Encoder::encode(stripslashes($request['outline_label_text']), 
                                    'output');
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
    public function handleHttpGetRequest($request) {}
    
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
        // calls for default values
        $this->setDefaultValues($this->symbols->outlineDefaultValues
                                              ->outlineDefaultValuesList);

        if (!is_null($outlineResult)) {
            $this->area = $outlineResult->area;
        }
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

        $this->smarty->assign(array(
            'outline_mask_selected' => $maskSelected,
            'outline_area' => $this->area,
                       
            'outline_point_symbol_selected' => 
                $this->outlineState->pointStyle->symbol,
            'outline_point_size_selected' => $this->outlineState->pointStyle->size,
            'outline_point_color_selected' => 
                $this->outlineState->pointStyle->color->getHex(),
            
            'outline_line_symbol_selected' => $this->outlineState->lineStyle->symbol,
            'outline_line_size_selected' => $this->outlineState->lineStyle->size,
            'outline_line_color_selected' => 
                $this->outlineState->lineStyle->outlineColor->getHex(),
            'outline_line_transparency_selected' => 
                $this->outlineState->lineStyle->transparency,
            
            'outline_polygon_outline_color_selected' => 
                $this->outlineState->polygonStyle->outlineColor->getHex(),
            'outline_polygon_background_color_selected' => 
                $this->outlineState->polygonStyle->color->getHex(),
            'outline_polygon_transparency_selected' => 
                $this->outlineState->polygonStyle->transparency,
            'pathToSymbols' => $this->symbols->pathToSymbols,
            'symbolType' => $this->symbols->symbolType,            
            
            ));
        return $this->smarty->fetch('outline.tpl');
    }
    
    /**
     * Draws Outlinelabel form and returns Smarty generated HTML
     * @return string
     */    
    protected function drawOutlinelabel() {

        if (!$this->getConfig()->labelMode) {
            return '';
        }
        
        $this->smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        return $this->smarty->fetch('outlinelabel.tpl');
    }

    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {

        $transSymbols = array();

        foreach($this->symbols->pointLabels as $val) {
            $transSymbols[] = I18n::gt($val);
        }
        $template->assign(array('outline_active' => true,
                                'outline'        => $this->drawOutline(),
                                'outlinelabel'   => $this->drawOutlinelabel(),
                                'pathToSymbols' => $this->symbols->pathToSymbols,
                                'symbolType' => $this->symbols->symbolType,
                                'outline_point_available_symbols' => $this->symbols->point,
                                'outline_point_available_symbolsLabels' => $transSymbols,
                                'outline_line_available_symbols' => $this->symbols->line));
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
            $outlineRequest->shapes = $this->outlineState->shapes;
            $mapRequest->outlineRequest = $outlineRequest;
        }
    }

    /**
     * @see InitUser::handleInit
     */
    public function handleInit($outlineInit) {

        $this->symbols = $outlineInit;
    }

    /**
     * set default values for outlines if none exist alsready
     */
    public function setDefaultValues($valuesList) {
      $colorList = array('red', 'green', 'blue');
      foreach($valuesList as $outlineStyle) {
        $outlineType = substr($outlineStyle->type, 0, strpos($outlineStyle->type, 'Layer'));
        $objectType = $outlineType.'Style';
        if ($this->outlineState->$objectType->size == '')
          $this->outlineState->$objectType->size = $outlineStyle->shapeStyle->size;
        if ($this->outlineState->$objectType->symbol == '')
          $this->outlineState->$objectType->symbol = $outlineStyle->shapeStyle->symbol;
        if ($this->outlineState->$objectType->transparency == '')
          $this->outlineState->$objectType->transparency = $outlineStyle->shapeStyle->transparency;
        foreach($colorList as $color) {
          if ($this->outlineState->$objectType->color->$color == '')
            $this->outlineState->$objectType->color->$color = $outlineStyle->shapeStyle->color->$color;
        }
        foreach($colorList as $color) {
          if ($this->outlineState->$objectType->outlineColor->$color == '') {
            $this->outlineState->$objectType->outlineColor->$color = 
              $outlineStyle->shapeStyle->outlineColor->$color;
          }
        }
      }
    }
}

?>
