<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * @package CorePlugins
 */
class ServerImages extends ServerCoreplugin {

    private $log;

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    function setupSizes($requ) {
        $msMapObj = $this->serverContext->msMapObj;

        $msMapObj->set('height', $requ->mainmap->height);
        $msMapObj->set('width', $requ->mainmap->width);
    }

    private function getImage($ms_image) {
        $image = new Image();
        
        $image->isDrawn = true;

        $msMapObj = $this->serverContext->msMapObj;

        $image->path = $ms_image->saveWebImage();
        $image->width = $ms_image->width;
        $image->height = $ms_image->height;
        
        return $image;
    }

    private function getImageUrl() {
        $mapInfo = $this->serverContext->mapInfo;
        $config = $this->serverContext->config;
            
        $imageUrl = NULL;

        if (@$config->imageUrl)
            return $config->imageUrl;
        
        return 'images/';
    }

    private function isDrawQuery() {

        $plugins = $this->serverContext->pluginManager;
        if (empty($plugins->query))
            return false;
        return $plugins->query->drawQuery();
    }

    function drawMainmap($requ) {
        $msMapObj = $this->serverContext->msMapObj;

        if (!$msMapObj->web->imagepath) {
            $imagePath = $this->serverContext->config->writablePath .
                'images/';
            $msMapObj->web->set('imagepath', $imagePath);
        }

        if (!$msMapObj->web->imageurl) {
            $msMapObj->web->set('imageurl', $this->getImageUrl());
        }

        if ($requ->mainmap->isDrawn) { 
            if ($this->isDrawQuery())
                $this->serverContext->msMainmapImage = $msMapObj->drawQuery();
            else
                $this->serverContext->msMainmapImage = $msMapObj->draw();
        }
        $this->serverContext->checkMsErrors();

        $this->log->info("mainmap saved");
        $this->log->info($this->serverContext->msMainmapImage);
    }

    function getResultFromRequest($requ) {

        $msMapObj = $this->serverContext->msMapObj;

        $imagesResult = new Images();

        $notdrawnImage = new Image();
        $notdrawnImage->isDrawn = false;

        // mainmap
        if ($requ->mainmap->isDrawn) {
            $ms_mainmap = $this->serverContext->msMainmapImage;
            if (!$ms_mainmap) 
                throw new CartoserverException("drawMainmap was not called before getResult");
            $this->serverContext->msMainmapImage = $ms_mainmap;
            $msMapObj->drawLabelCache($ms_mainmap);
            $imagesResult->mainmap = $this->getImage($ms_mainmap);
        } else {
            $imagesResult->mainmap = $notdrawnImage;
        }

        if ($requ->keymap->isDrawn) {
            $ms_keymap = $msMapObj->drawreferencemap();
            $imagesResult->keymap = $this->getImage($ms_keymap);
        } else {
            $imagesResult->keymap = $notdrawnImage;
        }

        if ($requ->scalebar->isDrawn) {
            $ms_scalebar = $msMapObj->drawScalebar();
            $imagesResult->scalebar = $this->getImage($ms_scalebar);
        } else {
            $imagesResult->scalebar = $notdrawnImage;
        }

        return $imagesResult;
    }
}
?>