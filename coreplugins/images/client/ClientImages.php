<?php

class ClientImages extends ClientCorePlugin {
    private $log;

    private $images;
    private $mainmapDimensions;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();

        $this->mainmapDimensions = new Dimension(400, 200);
    }

    function loadSession($sessionObject) {
        $this->log->debug("loading session:");
    }

    function createSession($mapInfo) {
        $this->log->debug("creating session:");
    }

    function handleHttpRequest($request) {
    }

    function getMainmapDimensions() {
        return $this->mainmapDimensions;
    }    

    function buildMapRequest($mapRequest) {

        //$mapRequest->imagesRequest = $this->getImagesRequest();

        // images
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

        $mapRequest->images = $images;
    }

    function handleMapResult($mapResult) {
        $this->imagesResult = $mapResult->images;
    }

    function renderForm($template) {
    }

    function saveSession() {
        $this->log->debug("saving session:");
    }
}
?>