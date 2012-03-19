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

define('OUTLINE_SESSION_VERSION', 3);

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

    /**
     * Circle radius
     * @var radius
     */
    public $radius;
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
 * Upgrades from V2 to V3
 */
class OutlineV2ToV3 extends ViewUpgrader {

    protected function callFilters() {
        $this->remove('labelMode');
    }
}

/**
 * Client Outline class
 * @package Plugins
 */
class ClientOutline extends ClientPlugin 
                    implements Sessionable, GuiProvider, ServerCaller, 
                               ToolProvider, Exportable, InitUser, Ajaxable, FilterProvider {
                    
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

    /** 
     * @var string geometry type
     */
    protected $geomType;

    const TOOL_POINT     = 'outline_point';
    const TOOL_LINE      = 'outline_line';
    const TOOL_RECTANGLE = 'outline_rectangle';
    const TOOL_POLYGON   = 'outline_poly';
    const TOOL_CIRCLE    = 'outline_circle';

    /**
     * Constructor
     */
    public function __construct() {
        $this->log = LoggerManager::getLogger(__CLASS__);
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
        $this->outlineState->radius = 0;
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
                     new ToolDescription(self::TOOL_CIRCLE, true, 74),
                     );
    }

    /**
     * @see FilterProvider::filterPostRequest()
     */
    public function filterPostRequest(FilterRequestModifier $request) {}

    /**
     * @see FilterProvider::filterGetRequest()
     */
    public function filterGetRequest(FilterRequestModifier $request) {
        // gets geometry type from GET request
        $this->geomType = '';
        $poly = $request->getValue(self::TOOL_POLYGON);
        $line = $request->getValue(self::TOOL_LINE);
        $point = $request->getValue(self::TOOL_POINT);
        $circle = $request->getValue(self::TOOL_CIRCLE);

        // set correct parameters for new shape depending on type
        if (!empty($poly)) {
            $this->geomType = self::TOOL_POLYGON;
            $selection_coords = $poly;
            $selection_type = 'polygon';
        } elseif (!empty($line)) {
            $this->geomType = self::TOOL_LINE;
            $selection_coords = $line;
            $selection_type = 'polyline';
        } elseif (!empty($point)) {
            $this->geomType = self::TOOL_POINT;  
            $selection_coords = $point;
            $selection_type = 'point';
        } elseif (!empty($circle)) {
            $this->geomType = self::TOOL_CIRCLE;  
            $selection_coords = $circle;
            $selection_type = 'circle';
			//@todo we should pick the radius, and correct coords with it In case off radius use  
        } else {
            return;
        }

        if (is_array($selection_coords)) {
            if (count($selection_coords) > 1) {
                return;
            }
            $selection_coords = $selection_coords[0];
        }

        $request->setValue('selection_coords', $selection_coords);
        $request->setValue('selection_type', $selection_type);
        $request->setValue('tool', $this->geomType);
    }
    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {

        if (!empty($request['outline_clear'])) {
            $this->outlineState->shapes = array();
            $this->outlineState->radius = 0;
            return;
        }

        if (!empty($request['outline_mask'])) {
            $this->outlineState->maskMode = ($request['outline_mask'] == 'yes');
        }

        // Updates default ShapeStyle
        $this->outlineState->pointStyle->symbol = 
            $this->getHttpValue($request, 'outline_point_symbol');
        $this->outlineState->pointStyle->size = 
            $this->getHttpValue($request, 'outline_point_size');
        $this->outlineState->pointStyle->color->setFromHex(
            $this->getHttpValue($request, 'outline_point_color'));
        $this->outlineState->pointStyle->transparency = 
            $this->getHttpValue($request, 'outline_point_transparency');

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
        
        // allow circle radius to be set by hand, under conditions
        if (isset($request['tool']) && $request['tool'] == self::TOOL_CIRCLE && 
            !empty($request['outline_circle_radius']) && ($shape) ) {
				if ( $shape->radius == 0 ){
            		$shape->radius = $this->outlineState->radius = 
                	$this->getHttpValue($request, 'outline_circle_radius');
				}
        } 

        if ($shape) {
            $this->handleShape($shape, $request);
        }
    }

    /**
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {
        $types = array(self::TOOL_POLYGON, self::TOOL_LINE,
                       self::TOOL_POINT, self::TOOL_CIRCLE);
        foreach ($types as $type) {
            if (!empty($request[$type])) {
                $this->addShapes($type, $request);
            }
        }
    }

    protected function addShapes($type, $request) {
        $data = $request[$type];
        if (!is_array($data)) {
            $data = array($data);
        }

        foreach ($data as $data_item) { 
            $pipepos = strpos($data_item, '|');
            $shapedata = substr($data_item, 0, $pipepos);
            $shapelabel = substr($data_item, $pipepos + 1);

            $shape = $this->getShape($type, $shapedata);

            if ($shape) {
                $label = !empty($shapelabel) ? $shapelabel : NULL;
                $this->handleShape($shape, $request, $label);
            }
        }
    }

    /**
     * common new shape handling for HttpPostRequet and HttpGetRequest
     */
    protected function handleShape($shape, $request, $label = NULL) {
    
        $styledShape = new StyledShape();

        // Gets options
        switch ($this->getCartoclient()->getClientSession()->selectedTool) {
        case self::TOOL_POINT:
            $styledShape->shapeStyle = clone $this->outlineState->pointStyle;
            break;
            
        case self::TOOL_LINE:
            $styledShape->shapeStyle = clone $this->outlineState->lineStyle;
            break;

        case self::TOOL_RECTANGLE:
        case self::TOOL_POLYGON:
        case self::TOOL_CIRCLE:
            $styledShape->shapeStyle = clone $this->outlineState->polygonStyle;
            break;

        default:
            // We should never come here...
            break;
        }

        $styledShape->shape = $shape;

        if ($this->getConfig()->labelMode) {
            if (!empty($label)) {
                $styledShape->label = Encoder::encode(stripslashes($label),
                                                      'output');
            } elseif (!empty($request['outline_label_text'])) {
                $styledShape->label =
                    Encoder::encode(stripslashes($request['outline_label_text']),
                                    'output');
            }
        }
        if (!is_null($this->getConfig()->multipleShapes) &&
            !$this->getConfig()->multipleShapes) {
            $this->outlineState->shapes = array();
        }
        $this->outlineState->shapes[] = $styledShape;    
    }

    /**
     * @see ServerCaller::buildRequest()
     */
    public function buildRequest() {

        if ($this->hasShapes()) {
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

        // Calls for default values
        $this->setDefaultValues($this->symbols->outlineDefaultValues);
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
            'outline_area'          => $this->area,
                       
            'outline_point_symbol_selected' => 
                $this->outlineState->pointStyle->symbol,
            'outline_point_size_selected'   => 
                $this->outlineState->pointStyle->size,
            'outline_point_color_selected'  => 
                $this->outlineState->pointStyle->color->getHex(),
            'outline_point_transparency_selected' => 
                $this->outlineState->pointStyle->transparency,  
            
            'outline_line_symbol_selected'       => 
                $this->outlineState->lineStyle->symbol,
            'outline_line_size_selected'         => 
                $this->outlineState->lineStyle->size,
            'outline_line_color_selected'        => 
                $this->outlineState->lineStyle->outlineColor->getHex(),
            'outline_line_transparency_selected' => 
                $this->outlineState->lineStyle->transparency,
            
            'outline_polygon_outline_color_selected'    => 
                $this->outlineState->polygonStyle->outlineColor->getHex(),
            'outline_polygon_background_color_selected' => 
                $this->outlineState->polygonStyle->color->getHex(),
            'outline_polygon_transparency_selected'     => 
                $this->outlineState->polygonStyle->transparency,

            'outline_circle_radius' => $this->outlineState->radius,
            
            'pathToSymbols' => $this->symbols->pathToSymbols,
            'symbolType'    => $this->symbols->symbolType,            
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
        $this->smarty->assign(array('outline_displayMeasures' => 
                                    $this->getConfig()->displayMeasures));
        return $this->smarty->fetch('outlinelabel.tpl');
    }

    /**
     * This method factors the plugin output for both GuiProvider::renderForm()
     * and Ajaxable::ajaxGetPluginResponse().
     * @return array array of variables and html code to be assigned
     */
    protected function renderFormPrepare() {

        $transSymbols = array();

        foreach($this->symbols->pointLabels as $val) {
            $transSymbols[] = I18n::gt($val);
        }

        return array('outline_active' => true,
                     'outline'        => $this->drawOutline(),
        			 'outline_area'   => $this->area,
                     'outlinelabel'   => $this->drawOutlinelabel(),
                     'pathToSymbols'  => $this->symbols->pathToSymbols,
                     'symbolType'     => $this->symbols->symbolType,
                     'outline_point_available_symbols' 
                                      => $this->symbols->point,
                     'outline_point_available_symbolsLabels' 
                                      => $transSymbols,
                     'outline_line_available_symbols' 
                                      => $this->symbols->line,
                     'symbolPickerHilight'
                                      => $this->symbols->symbolPickerHilight
                    );
    }

    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {
        $template->assign($this->renderFormPrepare());
    }

    /**
     * @see Ajaxable::ajaxGetPluginResponse()
     */
    public function ajaxGetPluginResponse(AjaxPluginResponse $ajaxPluginResponse) {
        $output = $this->renderFormPrepare();
        $ajaxPluginResponse->addHtmlCode('outline', $output['outline']);
        $ajaxPluginResponse->addVariable('outlineFolderId', $this->getFolderId());
        $ajaxPluginResponse->addVariable('outlineArea', $this->area);
    }
    
    /**
     * @see Ajaxable::ajaxHandleAction()
     */
    public function ajaxHandleAction($actionName, PluginEnabler $pluginEnabler) {
        switch ($actionName) {
            case 'Outline.AddFeature':
            case 'Outline.Clear':
                $pluginEnabler->disableCoreplugins();
                $pluginEnabler->enablePlugin('images');
                $pluginEnabler->enablePlugin('outline');
            break;
            case 'Outline.ChangeMode':            
                $pluginEnabler->disableCoreplugins();
                $pluginEnabler->enablePlugin('images');
            break;
        }
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
     * Sets default values for outlines if none exist already
     */
    public function setDefaultValues($valuesList) {
        $colorList = array('red', 'green', 'blue');

        foreach ($valuesList as $outlineStyle) {
            $outlineType = substr($outlineStyle->type, 0, 
                                  strpos($outlineStyle->type, 'Layer'));
            $objectType = $outlineType . 'Style';

            if (strval($this->outlineState->$objectType->size) == '') {
                $this->outlineState->$objectType->size = 
                    $outlineStyle->shapeStyle->size;
            }
        
            if (strval($this->outlineState->$objectType->symbol) == '') {
                $this->outlineState->$objectType->symbol = 
                    $outlineStyle->shapeStyle->symbol;
            }
        
            if (strval($this->outlineState->$objectType->transparency) == ''){
                $this->outlineState->$objectType->transparency = 
                    $outlineStyle->shapeStyle->transparency;
            }
        
            foreach ($colorList as $color) {
                if (strval($this->outlineState->$objectType->color->$color) == '') {
                    $this->outlineState->$objectType->color->$color = 
                        $outlineStyle->shapeStyle->color->$color;
                }
            }
        
            foreach ($colorList as $color) {
                if (strval($this->outlineState->$objectType
                                ->outlineColor->$color) == '') {
                    $this->outlineState->$objectType->outlineColor->$color = 
                        $outlineStyle->shapeStyle->outlineColor->$color;
                }
            }
        }
    }

    /**
     * Tells if shapes are stored in session.
     * @return boolean
     */
    public function hasShapes() {
        return !empty($this->outlineState->shapes);
    }

    /**
     * Gets a geometry type (point, line, polygon)
     * and the points coordinates of the geometry
     * @param string type : type of feature (polygon, line or point)
     * @param string values : list of point coordinates, x1,y1;x2,y2...
     * @return Shape
     */
    protected function getShape($type, $values) {

        switch ($type) {
            case self::TOOL_CIRCLE :
                $points = Utils::parseArray($values, ';');
                if (sizeOf($points) != 2) return false;

                $shape = new Circle;
                $xy = Utils::parseArray($points[0], ',');
                $shape->x = $xy[0];
                $shape->y = $xy[1];
                $shape->radius = $points[1];
                return $shape;
            break;
            case self::TOOL_POLYGON :
                $points = Utils::parseArray($values, ';');
                $nb_points = count($points);
                if ($nb_points <= 0) return false;

                // make polygon "loops" if last point is different from first one
                if ($points[0] != $points[$nb_points - 1]) {
                    array_push($points, $points[0]);
                    $nb_points++;
                }

                $shape = new Polygon;
                for ($i = 0; $i < $nb_points; $i++) {
                    $point = new Point;
                    $pointXY = Utils::parseArray($points[$i]);
                    if (count($pointXY) != 2) return false;

                    $point->setXY($pointXY[0], $pointXY[1]);
                    $shape->points[] = $point;
                }
                return $shape;
            break;
            case self::TOOL_LINE :
                $points = Utils::parseArray($values, ';');
                if (sizeOf($points) <= 0) return false;
            
                $shape = new Line;
                for ($i = 0; $i < sizeOf($points); $i++) {
                    $point = new Point;
                    $pointXY = Utils::parseArray($points[$i]);
                    if (sizeOf($pointXY) != 2) return false;

                    $point->setXY($pointXY[0], $pointXY[1]);
                    $shape->points[] = $point;
                }
                return $shape;
            break;
            case self::TOOL_POINT :
                $shape = new Point;
                $pointXY = Utils::parseArray($values);
                if (sizeOf($pointXY) != 2) return false;

                $shape->setXY($pointXY[0], $pointXY[1]);
                return $shape;
            break;
            default :
                return false;
        }
    }
}
