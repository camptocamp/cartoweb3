<?php

class ClientImages extends ClientCorePlugin {
    private $log;

    private $images;
    private $mainmapDimensions;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();

        // FIXME: put this in config
        $this->mainmapDimensions = new Dimension(400, 200);
    }

    function loadSession($sessionObject) {
        $this->log->debug("loading session:");
    }

    function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->log->debug("creating session:");
    }

    function handleHttpRequest($request) {
    }

    function getMainmapDimensions() {
        return $this->mainmapDimensions;
    }    

    function getKeymapDimensions() {
        // TODO
        return new Dimension(-1, -1);
    }

    function buildMapRequest($mapRequest) {

        // TODO: keymap, scalebar, ...

        $images = new Images();
        $mainmap_image = new Image();
        $mainmap_image->isDrawn = true;
        $mainmap_image->width = 400;
        $mainmap_image->height = 200;
        $images->mainmap = $mainmap_image;
        $no_image = new Image();
        $no_image->isDrawn = false;
        $images->keymap = $no_image;
        $images->scalebar = $no_image;

        $mapRequest->imagesRequest = $images;
    }

    function handleMapResult($mapResult) {
        $this->imagesResult = $mapResult->imagesResult;
    }

    function renderForm($template) {
    }

    function saveSession() {
        $this->log->debug("saving session:");
    }
}
?>