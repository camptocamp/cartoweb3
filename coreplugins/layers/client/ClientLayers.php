<?php

class ClientLayers extends ClientCorePlugin {
    private $log;

    private $layersState;

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
        $mapInfo = $this->cartoclient->getMapInfo();
        $layers = $mapInfo->getLayersByType(LayerBase::TYPE_LAYER);
        foreach ($layers as $layer) {
            $this->layersState[$layer->id]->selected = false;
        }
        
        foreach ($request['layers'] as $layerId) {
            $this->layersState[$layerId]->selected = true;
        }
    }

    private function getSelectedLayers() {
        $selectedLayers = array();
        $mapInfo = $this->cartoclient->getMapInfo();
        $layers = $mapInfo->getLayersByType(LayerBase::TYPE_LAYER);
        foreach ($layers as $layer) {
            if (@$this->layersState[$layer->id]->selected)
                $selectedLayers[] = $layer->id;
        }
        return $selectedLayers;
    }

    function buildMapRequest($mapRequest) {

        $mapRequest->layersRequest = $this->getSelectedLayers();
    }

    function handleMapResult($mapResult) {}

    private function drawLayers() {

        $smarty = new Smarty_CorePlugin($this->cartoclient->getConfig(),
                        $this);

        $mapInfo = $this->cartoclient->getMapInfo();
        $layers = $mapInfo->getLayersByType(LayerBase::TYPE_LAYER);
        foreach ($layers as $layer) {
            $checkboxLayerMap[$layer->id] = $layer->label;
        }

        $smarty->assign('layers', $checkboxLayerMap);
        $smarty->assign('selected_layers', $this->getSelectedLayers());

        return $smarty->fetch('layers.tpl');
    }

    function renderForm($template) {
        if (!$template instanceof Smarty) {
            throw new CartoclientException('unknown template type');
        }

        $layersOutput = $this->drawLayers();
        $template->assign('layers', $layersOutput);
    }

    function saveSession() {
        $this->log->debug("saving session:");
        $this->log->debug($this->layersState);

        return $this->layersState;
    }
}
?>