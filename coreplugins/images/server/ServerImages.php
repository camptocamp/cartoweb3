<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

// the number of generated images before issuing a warning
define('MAX_IMAGES_WARNING', 500);

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
        $this->checkMapDimensions($requ);
        
        $msMapObj = $this->serverContext->getMapObj();

        $msMapObj->set('height', $requ->mainmap->height);
        $msMapObj->set('width', $requ->mainmap->width);
    }

    /**
     * If limit mainmap dimensions are available, checks if asked map dims
     * fit, else sets them to max authorized sizes.
     */
    private function checkMapDimensions($requ) {
        $maxWidth = $this->getConfig()->maxMapWidth;
        $maxHeight = $this->getConfig()->maxMapHeight;

        if ($maxWidth && $requ->mainmap->width > $maxWidth)
            $requ->mainmap->width = $maxWidth;
        if ($maxHeight && $requ->mainmap->height > $maxHeight)
            $requ->mainmap->height = $maxHeight;
    }

    private function getImage($ms_image) {
        $image = new Image();
        
        $image->isDrawn = true;

        $msMapObj = $this->serverContext->getMapObj();

        $image->path = $ms_image->saveWebImage();
        $image->width = $ms_image->width;
        $image->height = $ms_image->height;
        
        return $image;
    }

    private function getImageUrl() {
        $mapInfo = $this->serverContext->getMapInfo();
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
        $msMapObj = $this->serverContext->getMapObj();

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
                $this->serverContext->setMsMainmapImage($msMapObj->drawQuery());
            else
                $this->serverContext->setMsMainmapImage($msMapObj->draw());
        }
        $this->serverContext->checkMsErrors();

        $this->log->info("mainmap saved");
        $this->log->info($this->serverContext->getMsMainmapImage());
    }

    private function checkMaxImages($serverContext) {
        
        $imgPath = $this->serverContext->getMapObj()->web->imagepath;
        $imgCount = count(scandir($imgPath));
        if ($imgCount > MAX_IMAGES_WARNING) {
            $msg = sprintf('Warning: you have a high number of generated images' .
                    ' (%u [warning threshold %u]]). You should run the cleaning script. ' .
                    'See http://dev.camptocamp.com/c2cwiki/CartowebScripts', 
                    $imgCount, MAX_IMAGES_WARNING);
            $serverContext->addMessage($msg, ServerMessage::CHANNEL_DEVELOPER);
        }
    }

    function handleCorePlugin($requ) {

        $msMapObj = $this->serverContext->getMapObj();

        $imagesResult = new ImagesResult();

        $notdrawnImage = new Image();
        $notdrawnImage->isDrawn = false;

        // mainmap
        if ($requ->mainmap->isDrawn) {
            $ms_mainmap = $this->serverContext->getMsMainmapImage();
            if (!$ms_mainmap) 
                throw new CartoserverException("drawMainmap was not called before getResult");
            $this->serverContext->setMsMainmapImage($ms_mainmap);
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
        
        $serverContext = $this->getServerContext();        
        if ($serverContext->isDevelMessagesEnabled()) {
            $this->checkMaxImages($serverContext);
        }
        
        return $imagesResult;
    }
}
?>
