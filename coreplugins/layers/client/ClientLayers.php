<?php

require_once('smarty/Smarty.class.php');

class Smarty_CorePlugin extends Smarty_Cartoclient {

    function __construct($config) {
        parent::__construct($config);
    }
}

class LayerState {

    public $selectedLayers = array();
    public $foldedLayers = array();
}

class ClientLayers extends ClientCorePlugin {
    private $log;

    private $layerState;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    function loadSession($sessionObject) {
        $this->log->debug("loading session:");
        $this->log->debug($sessionObject);

        $this->layerState = $sessionObject;
        
    }

    function createSession($mapInfo) {
        $this->log->debug("creating session:");

        $this->layerState = new LayerState();
        
        foreach ($mapInfo->layers as $layer) {
            if (!$layer instanceof LayerContainer)
                continue;
            if (@$layer->selected)
                $this->layerState->selectedLayers[] = $layer->id;
            if ($layer instanceof LayerGroup) {
                if (@$layer->folded)
                    $this->layerState->foldedLayers[] = $layer->id;
            }
        }
    }

    function handleHttpRequest($request) {
        $this->log->debug("update form :");
        $this->log->debug($this->layerState);

        
        if (!@$request['layers'])
            $request['layers'] = array();
        $this->log->debug("requ layers");
        $this->log->debug($request['layers']);
        $this->layerState->selectedLayers = $request['layers'];

        $this->log->debug("selected layers: ");
        $this->log->debug($this->layerState->selectedLayers);
    }

    function buildMapRequest($mapRequest) {

        $mapRequest->layerSelectionRequest = $this->layerState->selectedLayers;

    }

    function handleMapResult($mapResult) {}

    private function drawLayers() {

        $smarty = new Smarty_CorePlugin($this->cartoclient->getConfig());

        $mapInfo = $this->cartoclient->getMapInfo();
        $layers = $mapInfo->getLayersByType(LayerBase::TYPE_LAYER);
        foreach ($layers as $layer) {
            $checkboxLayerMap[$layer->id] = $layer->label;
        }

        $smarty->assign('layers', $checkboxLayerMap);
        $smarty->assign('selected_layers', $this->layerState->selectedLayers);

        return $smarty->fetch('layers.tpl');
    }

    function renderForm($template) {
        if (!$template instanceof Smarty) {
            throw new CartoclientException('unknown template type');
        }

        $layersOutput = $this->drawLayers();

        $template->assign('layers3', $layersOutput);
        $template->assign('layers4', 'LLL4');
    }

    function saveSession() {
        $this->log->debug("saving session:");
        $this->log->debug($this->layerState);

        return $this->layerState;
    }
}
?>