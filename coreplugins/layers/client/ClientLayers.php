<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * @package CorePlugins
 */
class ClientLayers extends ClientCorePlugin {
    private $log;
    private $smarty;

    private $layersState;
    private $layers;
    private $selectedLayers = array();
    private $nodeId = 0;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    function loadSession($sessionObject) {
        $this->log->debug("loading session:");
        $this->log->debug($sessionObject);
        $this->layersState = $sessionObject;
    }

    function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->log->debug("creating session:");

        $this->layersState = array();
        foreach ($initialMapState->layers as $initialLayerState) {
            $this->layersState[$initialLayerState->id] = $initialLayerState;
        }
    }

    private function getLayers() {
        if(!is_array($this->layers)) {
            $mapInfo = $this->cartoclient->getMapInfo();
            $this->layers = array();
            foreach ($mapInfo->getLayers() as $layer)
                $this->layers[$layer->id] = $layer;
        }
        return $this->layers;
    }

    function handleHttpRequest($request) {
        $this->log->debug("update form :");
        $this->log->debug($this->layersState);

        if (!@$request['layers'])
            $request['layers'] = array();
        $this->log->debug("requ layers");
        $this->log->debug($request['layers']);
        
        // TODO: hidden layers
        // TODO: folded layers
        
        // disables all layers
        $this->getLayers();
        foreach ($this->layers as $layer) {
            $this->layersState[$layer->id]->selected = false;
        }
        
        foreach ($request['layers'] as $layerId) {
            $this->layersState[$layerId]->selected = true;
        }
    }

    private function getSelectedLayers() {
        $this->getLayers();
        foreach ($this->layers as $layer) {
            if (@$this->layersState[$layer->id]->selected)
                $this->selectedLayers[] = $layer->id;
        }
        return $this->selectedLayers;
    }

    function buildMapRequest($mapRequest) {

        $mapRequest->layersRequest = $this->getSelectedLayers();
    }

    function handleMapResult($mapResult) {}

    private function getLayerByName($layername) {
        if (isset($this->layers[$layername])) return $this->layers[$layername];
        else throw new CartoclientException("unknown layer name: $layername");
    }

    private function drawLayer($layer) {
        // TODO: build switch among various layout (tree, radio, etc.)

        // FIXME: instancing Smarty object for each layer: performance issue?
        $template = new Smarty_CorePlugin($this->cartoclient->getConfig(),
                    $this);

        $layerChecked = in_array($layer->id, $this->selectedLayers)
                        ? 'checked="checked"' : false;

        $template->assign('layerType', $layer->className);
        $template->assign('layerLabel', $layer->label);
        $template->assign('layerId', $layer->id);
        $template->assign('layerChecked', $layerChecked);
        $template->assign('nodeId', 'id' . $this->nodeId++);

        $childrenLayers = array();
        if (!empty($layer->children) && is_array($layer->children))
            foreach ($layer->children as $child) {
                $childLayer = $this->getLayerByName($child);
                $childrenLayers[] = $this->drawLayer($childLayer);
            }

        $template->assign('childrenLayers', $childrenLayers);

        return $template->fetch('node.tpl');
    }

    private function drawLayersList() {

        $this->smarty = new Smarty_CorePlugin($this->cartoclient->getConfig(),
                        $this);

        $this->getLayers();
        $this->getSelectedLayers();

        $rootLayer = $this->getLayerByName('root');
        $rootNode = $this->drawLayer($rootLayer);

        $this->smarty->assign('layerlist', $rootNode);
        return $this->smarty->fetch('layers.tpl');
    }

    function renderForm($template) {
        if (!$template instanceof Smarty) {
            throw new CartoclientException('unknown template type');
        }

        $layersOutput = $this->drawLayersList();
        $template->assign('layers', $layersOutput);
    }

    function saveSession() {
        $this->log->debug("saving session:");
        $this->log->debug($this->layersState);

        return $this->layersState;
    }
}
?>
