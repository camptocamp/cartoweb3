<?php
/**
 * Client location plugin
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
 * @package CorePlugins
 * @version $Id$
 */

/**
 * Client side state
 * @package CorePlugins
 */
class LocationState {
    
    /**
     * Current bbox being viewed
     * @var Bbox
     */
    public $bbox;
    
    /**
     * Current layer identifier selected in the recenter drop-down
     * @var string
     */
    public $idRecenterSelected;
    
    /**
     * Current crosshair beeing displayed.
     * @var StyledShape
     */
    public $crosshair;
}

/**
 * Client part of Location plugin
 * @package CorePlugins
 */
class ClientLocation extends ClientPlugin
                     implements Sessionable, GuiProvider, ServerCaller,
                                InitUser, ToolProvider, Exportable {
                                
    /**
     * @var Logger
     */
    private $log;
    
    /**
     * @var LocationState
     */
    protected $locationState;

    /**
     * @var LocationRequest
     */
    protected $locationRequest;
    
    /**
     * @var LocationResult
     */
    protected $locationResult;
    
    /**
     * @var array
     */
    protected $scales;
    
    /**
     * @var array
     */
    protected $shortcuts;

    /**
     * @var Bbox
     */
    protected $fullExtent;

    /**
     * Tool constants.
     */
    const TOOL_ZOOMIN     = 'zoomin';
    const TOOL_ZOOMOUT    = 'zoomout';
    const TOOL_PAN        = 'pan';
    const TOOL_FULLEXTENT = 'fullextent';

    /**
     * @var Smarty_Plugin
     */
    protected $smarty;

    /**
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    /**
     * Transforms {@link PanDirection} orientation to increments
     * @param string
     * @return int
     */
    protected function panDirectionToFactor($panDirection) {
        switch ($panDirection) {
        case PanDirection::VERTICAL_PAN_NORTH:
        case PanDirection::HORIZONTAL_PAN_EAST:
            return 1; break;
        case PanDirection::VERTICAL_PAN_NONE:
        case PanDirection::HORIZONTAL_PAN_NONE:
            return 0; break;
        case PanDirection::VERTICAL_PAN_SOUTH:
        case PanDirection::HORIZONTAL_PAN_WEST:
            return -1; break;
        default:
            throw new CartoserverException("unknown pan direction $panDirection");
        }
    }

    /**
     * Handles panning buttons
     * @return LocationRequest
     */
    protected function handlePanButtons() {
        $panButtonToDirection = array(
            'pan_nw' => array(PanDirection::VERTICAL_PAN_NORTH, 
                              PanDirection::HORIZONTAL_PAN_WEST),
            'pan_n' => array(PanDirection::VERTICAL_PAN_NORTH, 
                             PanDirection::HORIZONTAL_PAN_NONE),
            'pan_ne' => array(PanDirection::VERTICAL_PAN_NORTH, 
                              PanDirection::HORIZONTAL_PAN_EAST),

            'pan_w' => array(PanDirection::VERTICAL_PAN_NONE, 
                             PanDirection::HORIZONTAL_PAN_WEST),
            'pan_e' => array(PanDirection::VERTICAL_PAN_NONE,
                             PanDirection::HORIZONTAL_PAN_EAST),

            'pan_sw' => array(PanDirection::VERTICAL_PAN_SOUTH,
                              PanDirection::HORIZONTAL_PAN_WEST),
            'pan_s' => array(PanDirection::VERTICAL_PAN_SOUTH,
                             PanDirection::HORIZONTAL_PAN_NONE),
            'pan_se' => array(PanDirection::VERTICAL_PAN_SOUTH,
                              PanDirection::HORIZONTAL_PAN_EAST),
            );
                            
        foreach ($panButtonToDirection as $buttonName => $directions) {
            if (!HttpRequestHandler::isButtonPushed($buttonName))
                continue;
            $verticalPan = $directions[0];                
            $horizontalPan = $directions[1];                

            $panRatio = $this->getConfig()->panRatio;
            if (!$panRatio) {                
                $panRatio = 1.0;
            }
               
            $bbox = $this->locationState->bbox;
            $xOffset = $bbox->getWidth() * $panRatio * 
                $this->panDirectionToFactor($horizontalPan);
            $yOffset = $bbox->getHeight() * $panRatio *
                $this->panDirectionToFactor($verticalPan);

            $center = $bbox->getCenter();
            $point = new Point($center->x + $xOffset,
                         $center->y + $yOffset);
                
            return $this->buildZoomPointRequest(
                    ZoomPointLocationRequest::ZOOM_DIRECTION_NONE, $point);
        }
        return NULL;
    }

    /**
     * Handles clicks on key map
     * @return LocationRequest
     */
    protected function handleKeymapButton() {

        $cartoForm = $this->cartoclient->getCartoForm();
        
        $keymapShape = $cartoForm->keymapShape; 

        if (is_null($keymapShape))
            return;
        if (!$keymapShape instanceof Point) {
            throw new CartoclientException('shapes other than point ' .
                                           'unsupported for keymap');
            return;   
        } 

        $point = $keymapShape;

        return $this->buildZoomPointRequest(
                  ZoomPointLocationRequest::ZOOM_DIRECTION_NONE, $point);
    }

    /**
     * Handles recenter/scales HTTP request
     *
     * When useDoit parameter is true, scale is changed only if a scale 
     * selection was done on form. In this case, a form value ("doit") is
     * set to '1' using Javascript.      
     * @param array HTTP request
     * @param boolean 
     * @return LocationRequest
     */
    protected function handleRecenterScales($request,
                                          $useDoit = true, 
                                          $check = false) {

        $center = $this->locationState->bbox->getCenter();
        $point = clone($center); 
        
        $recenterX = $this->getHttpValue($request, 'recenter_x');
        $recenterY = $this->getHttpValue($request, 'recenter_y');

        $scale        = $this->getHttpValue($request, 'recenter_scale');
        $recenterDoit = $this->getHttpValue($request, 'recenter_doit');                            
                            
        if (!is_null($recenterX) && !is_null($recenterY)) {            
            $point->setXY($recenterX, $recenterY);
        }
        
        if ($check) {
            if (!$this->checkNumeric($recenterX, 'recenter_x'))
                return NULL;
            if (!$this->checkNumeric($recenterY, 'recenter_Y'))
                return NULL;
            if (!$this->checkInt($scale, 'recenter_scale'))
                return NULL;

            if ((is_null($recenterX) && !is_null($recenterY)) ||
                (!is_null($recenterX) && is_null($recenterY))) {
                $this->cartoclient->
                    addMessage('Parameters recenter_x and recenter_y ' .
                               'cannot be used alone');
                return NULL;
            }
        }
               
        if (is_null($scale) || ($recenterDoit != '1' && $useDoit)) {
            $scale = 0;
        }         
        if ($point == $center && $scale == 0) {
            return NULL;
        }
        
        $showCrosshair = $this->getHttpValue($request, 'show_crosshair');
        if (!is_null($showCrosshair) && 
            ($showCrosshair == 1 || $showCrosshair == 'on') &&
            $this->cartoclient->getPluginManager()->getPlugin('outline') != NULL) {

            $this->locationState->crosshair = new StyledShape();
            $this->locationState->crosshair->shapeStyle = new ShapeStyle();

            $symbol = $this->getConfig()->crosshairSymbol;
            if (!empty($symbol)) {
                $this->locationState->crosshair->shapeStyle->symbol = $symbol;
            }

            $size = $this->getConfig()->crosshairSymbolSize;
            if (!empty($size)) {
                $this->locationState->crosshair->shapeStyle->size = $size;
            }
            
            $color = $this->getConfig()->crosshairSymbolColor;
            if (!empty($color)) {
                list($r, $g, $b) = explode(',', $color);
                $this->locationState->crosshair->shapeStyle->color->setFromRGB($r, $g, $b);
            }
            
            $this->locationState->crosshair->shape = new Point($recenterX,
                                                               $recenterY);
        }

        if ($scale == 0) {
            return $this->buildZoomPointRequest(
                      ZoomPointLocationRequest::ZOOM_DIRECTION_NONE, $point);
        } else {
            return $this->buildZoomPointRequest(
                      ZoomPointLocationRequest::ZOOM_SCALE, $point, 0, $scale);        
        }
    }

    /**
     * Draws recenter form
     * @return string
     */
    protected function drawRecenter() {
        $this->smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        return $this->smarty->fetch('recenter.tpl');
    }

    /**
     * Draws scales form
     * @return string
     */
    protected function drawScales() {
        $this->smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $scaleValues = array(0);
        $scaleLabels = array('');
        $scales = $this->scales;
        if (!is_array($scales)) $scales = array();
        
        $noScales = count($scales) == 0;        
        foreach ($scales as $scale) {
            $scaleValues[] = $scale->value;
            $scaleLabels[] = I18n::gt($scale->label);            
        }
        $this->smarty->assign(array('recenter_noscales'    => $noScales,
                                    'recenter_scaleValues' => $scaleValues,
                                    'recenter_scaleLabels' => $scaleLabels,
                                    'recenter_scale'       => 
                                        $this->locationResult->scale));
        return $this->smarty->fetch('scales.tpl');
    }

    /**
     * Handles recenter on Ids HTTP request
     * @param array HTTP request
     * @param boolean 
     * @return LocationRequest
     */
    protected function handleIdRecenter($request, $check = false) {

        $center = $this->locationState->bbox->getCenter();
        $point = clone($center);
        
        $idRecenterLayer = $this->getHttpValue($request, 'id_recenter_layer');
        $idRecenterIds   = $this->getHttpValue($request, 'id_recenter_ids');    
               
        if (is_null($idRecenterLayer) || is_null($idRecenterIds)) {
            return NULL;
        }
        
        $ids = explode(',', $idRecenterIds);
        
        if ($check) {
            $found = false;
            $layersInit = $this->cartoclient->getMapInfo()->layersInit;
            foreach($layersInit->getLayers() as $layer) {
                if (! $layer instanceof Layer) {
                    continue;
                }
                if ($idRecenterLayer == $layer->id) {
                    $found = true;
                }
            }
            if (!$found) {
                $this->cartoclient->addMessage('ID recenter layer not found');
                return NULL;
            }
        }
        
        $recenterRequest = new RecenterLocationRequest();

        $lastMapResult = $this->cartoclient->getClientSession()->lastMapResult;
        if (!is_null($lastMapResult)) {
            $recenterRequest->fallbackBbox = $lastMapResult->locationResult->bbox;
        } else {
            $recenterRequest->fallbackBbox = $this->locationState->bbox;
        }
        
        $idSelection = new IdSelection();
        $idSelection->layerId = $idRecenterLayer;
        $this->locationState->idRecenterSelected = $idSelection->layerId;
        $idSelection->selectedIds = $ids;
        
        $recenterRequest->idSelections = array($idSelection);
        
        $locationRequest = new LocationRequest();              
        $locationType = $recenterRequest->type;
        $locationRequest->locationType = $locationType;
        $locationRequest->$locationType = $recenterRequest;
        
        return $locationRequest;
    }

    /**
     * Draws recenter on Ids form
     * @return string
     */
    protected function drawIdRecenter() {
        $this->smarty = new Smarty_Plugin($this->getCartoclient(), $this);

        $layersInit = $this->cartoclient->getMapInfo()->layersInit;
        $layersId = array();
        $layersLabel = array();
        $idRecenterLayersStr = $this->getConfig()->idRecenterLayers;
        if (!empty($idRecenterLayersStr)) {
            $idRecenterLayers = Utils::parseArray($idRecenterLayersStr);
        }
        foreach($layersInit->getLayers() as $layer) {
            if (! $layer instanceof Layer)
                continue;
            if (!empty($idRecenterLayers) && 
                !in_array($layer->id, $idRecenterLayers))
                continue;
            $layersId[] = $layer->id; 
            $layersLabel[] = I18n::gt($layer->label); 
        }

        if (!empty($this->locationState->idRecenterSelected))
            $idRecenterSelected = $this->locationState->idRecenterSelected;
        else
            $idRecenterSelected = $layersId[0];

        $this->smarty->assign(array('id_recenter_layers_id' => $layersId,
                                    'id_recenter_layers_label' => $layersLabel,
                                    'id_recenter_selected' => $idRecenterSelected));
        return $this->smarty->fetch('id_recenter.tpl');
    }

    /**
     * Handles shortcuts HTTP request
     * @param array HTTP request
     * @param boolean
     * @param boolean
     * @return LocationRequest
     */
    protected function handleShortcuts($request, $useDoit = true, 
                                                 $check = false) {
        
        $shortcut_id  = $this->getHttpValue($request, 'shortcut_id');
        $shortcutDoit = $this->getHttpValue($request, 'shortcut_doit');                            

        if (is_null($shortcut_id) || ($shortcutDoit != '1' && $useDoit)) {
            return NULL;
        }
        
        if ($check) {
            if (!$this->checkInt($shortcut_id, 'shortcut_id'))
                return NULL;
                
            if (!array_key_exists($shortcut_id, $this->shortcuts)) {
                $this->cartoclient->addMessage('Shortcut ID not found');
                return NULL;
            }
        }
                   
        $bboxRequest = new BboxLocationRequest();
        $bboxRequest->bbox = $this->shortcuts[$request['shortcut_id']]->bbox;

        $locationRequest = new LocationRequest();                
        $locationRequest->locationType = LocationRequest::LOC_REQ_BBOX;
        $locationRequest->bboxLocationRequest = $bboxRequest;
        
        return $locationRequest;        
    }

    /**
     * Draws shortcuts form
     * @return string
     */
    protected function drawShortcuts() {
        $this->smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $shortcutValues = array(-1);
        $shortcutLabels = array('');
        $shortcuts = $this->shortcuts;
        if (!is_array($shortcuts)) $shortcuts = array();
        foreach ($shortcuts as $key => $shortcut) {
            $shortcutValues[] = $key;
            $shortcutLabels[] = I18n::gt($shortcut->label);            
        }
        $this->smarty->assign(array('shortcut_values' => $shortcutValues,
                                    'shortcut_labels' => $shortcutLabels));
        return $this->smarty->fetch('shortcuts.tpl');
    }

    /**
     * Handles recenter on bbox HTTP request
     * @param array HTTP request
     * @param boolean
     * @return LocationRequest
     */
    protected function handleBboxRecenter($request, $check = false) {
        
        $recenterBbox = $this->getHttpValue($request, 'recenter_bbox');
        if (is_null($recenterBbox)) {
            return NULL;
        }
       
        $values = explode(',', $recenterBbox);
        if (count($values) != 4) {
            $this->cartoclient->
                addMessage('Parameter recenter_bbox should be 4 values ' .
                           'separated by commas');
            return NULL;
        }
        list($minx, $miny, $maxx, $maxy) = $values;

        if ($check) {
            if (!$this->checkNumeric($minx, 'recenter_bbox (minx)'))
                return NULL;
            if (!$this->checkNumeric($miny, 'recenter_bbox (miny)'))
                return NULL;
            if (!$this->checkNumeric($maxx, 'recenter_bbox (maxx)'))
                return NULL;
            if (!$this->checkNumeric($maxy, 'recenter_bbox (maxy)'))
                return NULL;
            
            if ($minx >= $maxx) {
                $this->cartoclient->
                    addMessage('Parameter recenter_bbox minx must be < maxx');
                return NULL;
            }
            if ($miny >= $maxy) {
                $this->cartoclient->
                    addMessage('Parameter recenter_bbox miny must be < maxy');
                return NULL;
            }
        }
        $bbox = new Bbox($minx, $miny, $maxx, $maxy);

        $bboxRequest = new BboxLocationRequest();
        $bboxRequest->bbox = $bbox;
        $locationRequest = new LocationRequest();                
        $locationType = $bboxRequest->type;
        $locationRequest->locationType = $locationType;
        $locationRequest->$locationType = $bboxRequest;
        
        return $locationRequest;
    }

    /**
     * Handles full extent 
     * @return LocationRequest
     */
    protected function handleFullExtent() {

        $bboxRequest = new BboxLocationRequest();
        $bboxRequest->bbox = $this->fullExtent;
        $locationRequest = new LocationRequest();                
        $locationType = $bboxRequest->type;
        $locationRequest->locationType = $locationType;
        $locationRequest->$locationType = $bboxRequest;

        return $locationRequest;
    }

    /**
     * @see Sessionable::loadSession()
     */
    public function loadSession($sessionObject) {
        $this->log->debug('loading session:');
        $this->log->debug($sessionObject);

        $this->locationState = $sessionObject;
    }

    /**
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo, 
                                  InitialMapState $initialMapState) {
        $this->log->debug('creating session:');

        $this->locationState = new LocationState();
        $this->locationState->bbox = $initialMapState->location->bbox;
    }

    /**
     * Returns shortcuts
     * @return array array of LocationShortcut
     */
    public function getShortcuts() {
        return $this->shortcuts;
    }

    /**
     * Returns current bbox
     * @return Bbox
     */
    public function getLocation() {

        if (!$this->locationState)
            throw new CartoclientException('location state not yet initialized');
        return $this->locationState->bbox;
    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {

        $this->locationRequest = $this->handleBboxRecenter($request);
        if (!is_null($this->locationRequest))
            return;

        $this->locationRequest = $this->handlePanButtons();
        if (!is_null($this->locationRequest))
            return;

        $this->locationRequest = $this->handleKeymapButton();
        if (!is_null($this->locationRequest))
            return;

        $this->locationRequest = $this->handleRecenterScales($request);
        if (!is_null($this->locationRequest))
            return;

        $this->locationRequest = $this->handleIdRecenter($request);
        if (!is_null($this->locationRequest))
            return;
        
        $this->locationRequest = $this->handleShortcuts($request);
        if (!is_null($this->locationRequest))
            return;

        $this->locationRequest = $this->cartoclient->getHttpRequestHandler()
                                                   ->handleTools($this);  
    }

    /**
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {
        
        $this->locationRequest = $this->handleBboxRecenter($request, true);
        if (!is_null($this->locationRequest))
            return;

        $this->locationRequest = $this->handleRecenterScales($request,
                                                             false, true);
        if (!is_null($this->locationRequest))
            return;

        $this->locationRequest = $this->handleIdRecenter($request, true);
        if (!is_null($this->locationRequest))
            return;

        $this->locationRequest = $this->handleShortcuts($request, false, true);
        if (!is_null($this->locationRequest))
            return;
    }
    
    /**
     * Returns zoom factor depending on selected rectangle
     * @param Rectangle
     * @return double
     */
    protected function getZoomInFactor(Rectangle $rectangle) {

        if ($rectangle->getWidth() == 0 ||
            $rectangle->getHeight() == 0) {
            return 0;
        }
        
        $bbox = $this->locationState->bbox;
        
        $widthRatio = $bbox->getWidth() / $rectangle->getWidth();
        $heightRatio = $bbox->getHeight() / $rectangle->getHeight();
        
        return min($widthRatio, $heightRatio);
    }

    /**
     * Constructs a {@link ZoomPointLocationRequest}
     * @param string
     * @param Point
     * @param double
     * @param double
     * @return LocationRequest
     */
    protected function buildZoomPointRequest($zoomType, Point $point, 
                                           $zoomFactor = 0, $scale = 0) {

        $zoomRequest = new ZoomPointLocationRequest();
        $zoomRequest->locationType = LocationRequest::LOC_REQ_ZOOM_POINT;
        $zoomRequest->point = $point; 
        $zoomRequest->zoomType = $zoomType;
        $zoomRequest->zoomFactor = $zoomFactor;
        $zoomRequest->scale = $scale;
        $zoomRequest->bbox = $this->locationState->bbox;
        $zoomRequest->crosshair = $this->locationState->crosshair;

        $locationRequest = new LocationRequest();                
        $locationType = $zoomRequest->locationType;
        $locationRequest->locationType = $locationType;
        $locationRequest->$locationType = $zoomRequest;
        
        return $locationRequest;
    }

    /**
     * @see ToolProvider::handleMainmapTool() 
     */
    public function handleMainmapTool(ToolDescription $tool, 
                               Shape $mainmapShape) {

        $toolToZoomType = array(
                self::TOOL_ZOOMIN =>
                  ZoomPointLocationRequest::ZOOM_DIRECTION_IN,
                self::TOOL_PAN => 
                  ZoomPointLocationRequest::ZOOM_DIRECTION_NONE,
                self::TOOL_ZOOMOUT =>
                  ZoomPointLocationRequest::ZOOM_DIRECTION_OUT);

        $zoomType = @$toolToZoomType[$tool->id];
        if (empty($zoomType))
            throw new CartoclientException('unknown mainmap tool ' . $tool->id);

        $point = $mainmapShape->getCenter();

        $zoomFactor = 0;
        if ($tool->id == self::TOOL_ZOOMIN && 
            $mainmapShape instanceof Rectangle) {
            $zoomType = ZoomPointLocationRequest::ZOOM_FACTOR;
            $zoomFactor = $this->getZoomInFactor($mainmapShape);
        }
        
        return $this->buildZoomPointRequest($zoomType, $point, $zoomFactor);
    }
    
    /**
     * @see ToolProvider::handleKeymapTool() 
     */
    public function handleKeymapTool(ToolDescription $tool, 
                              Shape $keymapShape) {}
    
    /**
     * @see ToolProvider::handleApplicationTool() 
     */
    public function handleApplicationTool(ToolDescription $tool) {

        if ($tool->id == self::TOOL_FULLEXTENT)
            return $this->handleFullExtent();
    }

    /**
     * @see ToolProvider::getTools() 
     */
    public function getTools() {
        
        return array(new ToolDescription(self::TOOL_ZOOMIN, true,
                        10),
                     new ToolDescription(self::TOOL_ZOOMOUT, true,
                        11),
                     new ToolDescription(self::TOOL_PAN, true,
                        12),
                     new ToolDescription(self::TOOL_FULLEXTENT, true,
                        14, ToolDescription::APPLICATION, true),
                   );
    }

    /**
     * @see ServerCaller::buildRequest()
     */
    public function buildRequest() {

        $locationRequest = NULL;
        if (!is_null($this->locationRequest)) 
            $locationRequest = $this->locationRequest;

        if (is_null($locationRequest)) // stay at the same location
            $locationRequest = $this->buildZoomPointRequest(
                        ZoomPointLocationRequest::ZOOM_DIRECTION_NONE, 
                        $this->locationState->bbox->getCenter());
        return $locationRequest;
    }

    /**
     * @see ServerCaller::initializeResult()
     */
    public function initializeResult($locationResult) {
        $this->locationState->bbox = $locationResult->bbox;
        $this->locationResult = $locationResult;
    }

    /**
     * @see ServerCaller::handleResult()
     */
    public function handleResult($locationResult) {}

    /**
     * Returns current scale
     * @return float
     */
    public function getCurrentScale() {
        return $this->locationResult->scale;
    }

    /**
     * @see InitUser::handleInit()
     */
    public function handleInit($locationInit) {
        $this->scales = $locationInit->scales;
        $this->minScale = $locationInit->minScale;
        $this->maxScale = $locationInit->maxScale;
        $this->shortcuts = $locationInit->shortcuts;
        $this->fullExtent = $locationInit->fullExtent;
    }
    
    /**
     * Returns a string with some location information (scale, bbox, etc.)
     * @return string
     */
    protected function getLocationInformation() {
        
        $delta = $this->maxScale - $this->minScale;
        if ($delta > 0) {
            $percent = (($this->locationResult->scale - $this->minScale) * 100) /
                        ($this->maxScale - $this->minScale);
            $percent = round($percent, 1);
        } else {
            $percent = '#ERR';
        }
        
        $locationInfo = sprintf('Bbox: %s  <br/> scale: min:%s current: %s ' .
                                'max: %s (percent: %s)', 
                    $this->locationState->bbox->__toString(),
                    $this->minScale, $this->locationResult->scale, 
                    $this->maxScale, $percent);
        
        return $locationInfo;
    }
    
    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {

        $scaleUnitLimit = $this->getConfig()->scaleUnitLimit;
        if ($scaleUnitLimit && $this->locationResult->scale >= $scaleUnitLimit)
            $factor = 1000;
        else $factor = 1;

        $recenter_active = $this->getConfig()->recenterActive;
        $scales_active = $this->getConfig()->scalesActive;
        $id_recenter_active = $this->getConfig()->idRecenterActive;
        $shortcuts_active = $this->getConfig()->shortcutsActive;
        $scale = number_format($this->locationResult->scale, 0, ',',"'");
               
        $template->assign(array('location_info' => $this->getLocationInformation(),
                                'bboxMinX' => $this->locationState->bbox->minx,
                                'bboxMinY' => $this->locationState->bbox->miny,
                                'bboxMaxX' => $this->locationState->bbox->maxx,
                                'bboxMaxY' => $this->locationState->bbox->maxy,
                                'factor' => $factor,
                                'currentScale' => $scale,
                                'recenter_active' => $recenter_active,
                                'scales_active' => $scales_active,
                                'id_recenter_active' => $id_recenter_active,
                                'shortcuts_active' => $shortcuts_active,
                                ));

        if ($recenter_active)
            $template->assign('recenter', $this->drawRecenter());
        if ($scales_active)
            $template->assign('scales', $this->drawScales());
        if ($id_recenter_active)
            $template->assign('id_recenter', $this->drawIdRecenter());
        if ($shortcuts_active)
            $template->assign('shortcuts', $this->drawShortcuts());
    }

    /**
     * @see Sessionable::saveSession()
     */
    public function saveSession() {
        $this->log->debug('saving session:');
        $this->log->debug($this->locationState);

        return $this->locationState;
    }

    /**
     * @see Exportable::adjustExportMapRequest()
     */
    public function adjustExportMapRequest(ExportConfiguration $configuration,
                                    MapRequest $mapRequest) {

        $locationRequest = $mapRequest->locationRequest;

        $type = $configuration->getLocationType();
        if (!is_null($type)) {
            $locationRequest->locationType = $type;

            $locationType = $locationRequest->locationType;
  
            $locationRequests = array('bboxLocationRequest',
                                      'panLocationRequest', 
                                      'zoomPointLocationRequest',
                                      'recenterLocationRequest');
            // FIXME: what if some new kind of request is added?
            foreach ($locationRequest as $name => $member) {
                if (in_array($name, $locationRequests)) 
                    $locationRequest->$name = NULL;
            }
   
            switch($locationType) {
                case 'zoomPointLocationRequest':
                    $locationRequest->$locationType = 
                        new ZoomPointLocationRequest;
                    
                    $bbox = $configuration->getBbox();
                    if (!is_null($bbox))
                        $locationRequest->$locationType->bbox = $bbox;
       
                    $point = $configuration->getPoint();
                    if (!is_null($point))
                        $locationRequest->$locationType->point = $point;
       
                    $scale = $configuration->getScale();
                    if (!is_null($scale))
                        $locationRequest->$locationType->scale = $scale;
       
                    $zoomType = $configuration->getZoomType();
                    // FIXME: use given zoomType instead of ZOOM_SCALE
                    if (!is_null($zoomType))
                        $locationRequest->$locationType->zoomType = 
                            ZoomPointLocationRequest::ZOOM_SCALE;
                    break;
            
            }
        }
    }
}

?>
