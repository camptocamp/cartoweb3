<?php
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * Contains the state of a selection.
 */
class SelectionState {
 
    public $layerId;
    public $idAttribute;
    public $idType; 
    public $selectedIds;
    public $maskMode;
    public $retrieveAttributes;
}

/**
 * Client plugin for managing selection and hilight of a set of objects.
 *
 * @package Plugins
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class ClientSelection extends ClientPlugin
                      implements Sessionable, GuiProvider, ServerCaller, ToolProvider {

    private $selectionState;
    private $selectedShape;
    private $layerResult;

    const TOOL_SELECTION = 'selection';

    function __construct() {
        parent::__construct();

        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    function loadSession($sessionObject) {
        $this->selectionState = $sessionObject;
    }

    function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {

        $this->selectionState = new SelectionState();
        $this->layerResult = null;
    
        $this->clearSession();
    }
    
    function saveSession() {
        return $this->selectionState;
    }

    private function clearSession() {
        $this->selectionState->selectedIds = array();
    }
    
    function getTools() {
        return array(new ToolDescription(self::TOOL_SELECTION, true,
                       new JsToolAttributes(JsToolAttributes::SHAPE_RECTANGLE,
                                            JsToolAttributes::CURSOR_HELP),
                                            60));
    }
    
    function handleMainmapTool(ToolDescription $tool, 
                            Shape $mainmapShape) {

        if ($mainmapShape instanceof Point) {
            $bbox = new Bbox();
            $bbox->setFrom2Points($mainmapShape, $mainmapShape);
            $mainmapShape = $bbox;   
        } 
        
        if (!$mainmapShape instanceof Bbox) 
            throw new CartoclientException('Only bbox shapes are supported for selection');

        return $mainmapShape;
    }
    
    function handleKeymapTool(ToolDescription $tool, 
                            Shape $keymapShape) {
        /* nothing to do */
    }

    function handleHttpRequest($request) {

        if (!empty($request['selection_layerid'])) {
            if ($this->selectionState->layerId != $request['selection_layerid']) { 
                $this->selectionState->layerId = $request['selection_layerid'];
                $this->clearSession();
            }
        }

        if (!empty($request['selection_unselect'])) {
            $unselectId = urldecode($request['selection_unselect']);
            $this->selectionState->selectedIds = array_diff(
                    $this->selectionState->selectedIds, array($unselectId));
        }

        if (!empty($request['selection_clear'])) {
            $this->clearSession();
        }

        $this->selectionState->maskMode = !empty($request['selection_maskmode']);

        $this->selectedShape = $this->cartoclient->getHttpRequestHandler()
                    ->handleTools($this);

        $this->selectionState->retrieveAttributes = 
                            !empty($request['selection_retrieve_attributes']);                    
    }

    function buildMapRequest($mapRequest) {

        if (empty($this->selectionState->layerId) || 
                $this->selectionState->layerId == 'no_layer')
            return;

        $selectionRequest = new SelectionRequest();
        $selectionRequest->layerId     = $this->selectionState->layerId; 
        $selectionRequest->selectedIds = $this->selectionState->selectedIds; 
        $selectionRequest->maskMode    = $this->selectionState->maskMode;
        $selectionRequest->retrieveAttributes =
                                    $this->selectionState->retrieveAttributes;
        // FIXME: this should be customizable
        $selectionRequest->idType = 'string';
        
        // If retrieveAttributes = true, result must always be returned
        $selectionRequest->returnResults =
                                    $this->selectionState->retrieveAttributes;

        if (!empty($this->selectedShape)) {
            $selectionRequest->policy = SelectionRequest::POLICY_XOR;
            assert($this->selectedShape instanceof Bbox);
            $selectionRequest->bbox = $this->selectedShape;
            $selectionRequest->returnResults = true;
        }
        $mapRequest->selectionRequest  = $selectionRequest;
    }

    function handleResult($selectionResult) {
        if (!$selectionResult instanceof SelectionResult)
            return;

        $this->selectionState->selectedIds = $selectionResult->selectedIds;
        $this->layerResult = $selectionResult->layerResults[0];
    }

    private function drawSelectionResult() {
        $smarty = new Smarty_CorePlugin($this->cartoclient->getConfig(), $this);

        $selectionLayersStr = $this->getConfig()->selectionLayers;
        if (!empty($selectionLayersStr)) {
            $selectionLayers = explode(',', $selectionLayersStr);
            $selectionLayers = array_map('trim', $selectionLayers);
            $selectionLayersLabel = array();
            foreach ($selectionLayers as $layer)
                $selectionLayersLabel[] = I18n::gt($layer);
        } else {
            // takes all layers 
            $mapInfo = $this->cartoclient->getMapInfo();
            $selectionLayers = array();
            $selectionLayersLabel = array();
            foreach($mapInfo->getLayers() as $layer) {
                if (! $layer instanceof Layer)
                    continue;
                $selectionLayers[] = $layer->id; 
                $selectionLayersLabel[] = I18n::gt($layer->label); 
            }
        }
        
        if (!$this->selectionState->retrieveAttributes) {
        }
        
        $selectionLayers = array_merge(array('no_layer'), $selectionLayers);
        $smarty->assign('selection_selectionlayers', $selectionLayers); 
        $smarty->assign('selection_selectionlayers_label', $selectionLayersLabel); 

        $smarty->assign('selection_layerid', $this->selectionState->layerId); 
        $smarty->assign('selection_idattribute', $this->selectionState->idAttribute); 
        $smarty->assign('selection_idtype', $this->selectionState->idType); 
        
        if (is_null($this->layerResult)) {
            $layerResult = new stdClass();
            $layerResult->resultElements = array();
            foreach($this->selectionState->selectedIds as $id) {
                $element = new stdClass();
                $element->id = $id;
                $layerResult->resultElements[] = $element;
            }
        } else {
            $layerResult = $this->decodeResults($this->layerResult);
        }
        $smarty->assign('selection_layer_result', $layerResult); 
        
        $smarty->assign('selection_selectedids', $this->selectionState->selectedIds); 
        $smarty->assign('selection_maskmode', $this->selectionState->maskMode); 

        $smarty->assign('selection_hilightattr_active', $this->getConfig()->retrieveAttributesActive);
        $smarty->assign('selection_retrieve_attributes', 
                                       $this->selectionState->retrieveAttributes); 
        
        return $smarty->fetch('selection.tpl');          
    }

    private function decodeResults(LayerResult $layerResult) {
        
        $labelIndex = array_search('label', $layerResult->fields);
        if ($labelIndex === false)
            return null;
        foreach ($layerResult->resultElements as $resultElement) {
            $resultElement->values[$labelIndex] = 
                                    utf8_decode($resultElement->values[0]);
        }
        return $layerResult;
    }

    function renderForm($template) {
    
        if (!$template instanceof Smarty) {
            throw new CartoclientException('unknown template type');
        }

        $selectionOutput = $this->drawSelectionResult();
        $template->assign('selection_result', $selectionOutput);
    }
}
?>
