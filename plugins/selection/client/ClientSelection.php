<?php
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * Contains the state of a selection.
 */
// FIXME: same as HilightRequest, maybe encapsulate it inside.
class SelectionState {
 
    public $layerId;
    public $idAttribute;
    public $idType; 
    public $selectedIds;
}

/**
 * Client plugin for managing selection and hilight of a set of objects.
 *
 * @package Plugins
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class ClientSelection extends ClientPlugin implements ToolProvider {

    private $selectionState;

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

        $this->selectionState->selectedIds = array();

    }
    function saveSession() {
        return $this->selectionState;
    }
    
    function getTools() {
        $weight = $this->getConfig()->weightSelection;
        if (!$weight) $weight = 60;
        
        return array(new ToolDescription(self::TOOL_SELECTION, 
                                         self::TOOL_SELECTION, 
                                         'Selection', 
                                         ToolDescription::MAINMAP,
                                         $weight,
                                         'selection',
                                         'query'));
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
            $this->selectionState->layerId = $request['selection_layerid'];
        }
        
        if (!empty($request['selection_unselect'])) {
            $unselectId = urldecode($request['selection_unselect']);
            $this->selectionState->selectedIds = array_diff(
                    $this->selectionState->selectedIds, array($unselectId));
        }

        if (!empty($request['selection_clear'])) {
            $this->selectionState->selectedIds = array();
        }

        $this->selectedShape = $this->cartoclient->getHttpRequestHandler()
                    ->handleTools($this);
    }

    function buildMapRequest($mapRequest) {

        if (empty($this->selectionState->layerId) || 
                $this->selectionState->layerId == 'no_layer')
            return;
            
        $hilightRequest = new HilightRequest();
        $hilightRequest->layerId = $this->selectionState->layerId; 
        $hilightRequest->selectedIds = $this->selectionState->selectedIds; 
        // FIXME: this should be customizable
        $hilightRequest->idType = 'string';
        $mapRequest->hilightRequest = $hilightRequest;

        if (!empty($this->selectedShape)) {
            $selectionRequest = new SelectionRequest();
            $selectionRequest->policy = SelectionRequest::POLICY_XOR;
            assert($this->selectedShape instanceof Bbox);
            $selectionRequest->bbox = $this->selectedShape;
            $mapRequest->selectionRequest = $selectionRequest;
        }
    }

    function handleResult($selectionResult) {
        if (!$selectionResult instanceof SelectionResult)
            return;
        
        $this->selectionState->selectedIds = $selectionResult->selectedIds;
    }

    private function drawSelectionResult($selectionResult) {
        $smarty = new Smarty_CorePlugin($this->cartoclient->getConfig(),
                        $this);

        $this->log->debug("selection result::");        
        $this->log->debug($selectionResult);        

        $selectionLayersStr = $this->getConfig()->selectionLayers;
        if (empty($selectionLayersStr))
            throw new CartoclientException('you need to set the selectionLayers ' .
                    'parameter in selection client plugin');

        $selectionLayers = explode(',', $selectionLayersStr);
        $selectionLayers = array_map('trim', $selectionLayers);
        
        $selectionLayers = array_merge(array('no_layer'), $selectionLayers);
        $smarty->assign('selection_selectionlayers', $selectionLayers); 

        $smarty->assign('selection_layerid', $this->selectionState->layerId); 
        $smarty->assign('selection_idattribute', $this->selectionState->idAttribute); 
        $smarty->assign('selection_idtype', $this->selectionState->idType); 
        
        $smarty->assign('selection_selectedids', $this->selectionState->selectedIds); 

        return $smarty->fetch('selection.tpl');          
    }

    function renderForm($template) {
        if (!$template instanceof Smarty) {
            throw new CartoclientException('unknown template type');
        }
        
        $selectionOutput = $this->drawSelectionResult(NULL);
        $template->assign('selection_result', $selectionOutput);
    }
}
?>
