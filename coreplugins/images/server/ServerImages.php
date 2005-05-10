<?php
/**
 *
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
 * Server part of Images plugin
 * @package CorePlugins
 */
class ServerImages extends ClientResponderAdapter 
                   implements CoreProvider {

    /**
     * the number of generated images before issuing a warning
     */
    const MAX_IMAGES_WARNING = 500;

    /**
     * Path where to write images, relative to the writable server path.
     */
    const IMAGES_WRITE_PATH = 'images/';
    
    /**
     * @var Logger
     */
    private $log;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * Sets main map dimensions into MapObj
     * @param ImagesRequest
     */
    public function setupSizes($requ) {
       
        $mapRequest = $this->serverContext->getMapRequest();
        if (empty($mapRequest->layersRequest) || 
            empty($mapRequest->layersRequest->resolution)) {
            // if resolution is specified (PDF export), image size check is
            // skipped
            $this->log->debug('Checking image dimensions');
            $this->checkMapDimensions($requ);
        }
        
        $msMapObj = $this->serverContext->getMapObj();

        $msMapObj->set('height', $requ->mainmap->height);
        $msMapObj->set('width', $requ->mainmap->width);
    }

    /**
     * Checks main map dimensions
     *
     * If limit mainmap dimensions are available, checks if asked map dims
     * fit, else sets them to max authorized sizes.
     * @param ImagesRequest
     */
    private function checkMapDimensions($requ) {
        $maxWidth = $this->getConfig()->maxMapWidth;
        $maxHeight = $this->getConfig()->maxMapHeight;

        if ($maxWidth && $requ->mainmap->width > $maxWidth)
            $requ->mainmap->width = $maxWidth;
        if ($maxHeight && $requ->mainmap->height > $maxHeight)
            $requ->mainmap->height = $maxHeight;
    }

    /**
     * @return string The base URL where the generated images can be found.
     */
    private function getImageBaseUrl() {
        $config = $this->serverContext->getConfig();
        if ($config->imageUrl)
            return $config->imageUrl;
        return '';
    }

    /**
     * Returns the complete URL to the imge which is sent to the client. It 
     * uses the ResourceHandler to build this URL.
     *
     * @param string the original path to the image
     * @return string The complete URL of the generated image.
     */
    private function getImageUrl($imagePath) {
        if (strpos($imagePath, '/') !== false)
            return $imagePath;
        
        $resourceHandler = $this->serverContext->getResourceHandler();
        $imagePath = self::IMAGES_WRITE_PATH . $imagePath;
        return $resourceHandler->getUrlProvider()->getGeneratedUrl($imagePath);
    }

    /**
     * Draws an image and returns an {@link Image} object
     * @param ms_Image MapServer image
     * @return Image
     */
    private function getImage($ms_image) {
        $image = new Image();
        
        $image->isDrawn = true;
        
        $image->path = $this->getImageUrl($ms_image->saveWebImage());
        $image->width = $ms_image->width;
        $image->height = $ms_image->height;
                
        return $image;
    }

    /**
     * Returns true if query must be drawn using MapServer
     *
     * Gets the information from Query plugin.
     * @return boolean
     * @see ServerQuery::drawQuery()
     */
    private function isDrawQuery() {

        $plugins = $this->serverContext->getPluginManager();
        if (empty($plugins->query))
            return false;
        return $plugins->query->drawQuery();
    }

    /**
     * Returns the image type for main map. Image type is the outputformat
     * identifier declared in mapfile.
     *
     * Gets the information from Layers plugin.
     * @return string The image type to use for drawing.
     * @see ServerLayers::getImageType()
     */
    private function getImageType() {

        $plugins = $this->serverContext->getPluginManager();
        if (empty($plugins->layers))
            return null;
        return $plugins->layers->getImageType();
    }

    /**
     * Draws mainmap
     *
     * Uses MapServer draw() or drawQuery().
     * @param ImagesRequest
     */
    public function drawMainmap($requ) {
        $msMapObj = $this->serverContext->getMapObj();

        if (!$msMapObj->web->imagepath) {
            $imagePath = $this->serverContext->getConfig()->writablePath .
                self::IMAGES_WRITE_PATH;
            $msMapObj->web->set('imagepath', $imagePath);
        }

        if (!$msMapObj->web->imageurl) {
            $msMapObj->web->set('imageurl', $this->getImageBaseUrl());
        }

        if ($requ->mainmap->isDrawn) { 
            if ($this->isDrawQuery())
                $this->serverContext->setMsMainmapImage($msMapObj->drawQuery());
            else
                $this->serverContext->setMsMainmapImage($msMapObj->draw());
        }
        $this->serverContext->checkMsErrors();
        
        if ($requ->mainmap->isDrawn) {
            $this->log->info('mainmap saved');
            $this->log->info($this->serverContext->getMsMainmapImage());
        }
    }

    /**
     * Checks number of images in directory
     *
     * Adds a developper message if too many images.
     * @param ServerContext     
     */
    private function checkMaxImages($serverContext) {
        
        $imgPath = $this->serverContext->getMapObj()->web->imagepath;
        $imgCount = count(scandir($imgPath));
        if ($imgCount > self::MAX_IMAGES_WARNING) {
            $msg = sprintf('Warning: you have a high number of generated ' .
                           'images (%u [warning threshold %u]]). You should ' .
                           'run the cleaning script. ' .
                    'See http://dev.camptocamp.com/c2cwiki/CartowebScripts', 
                    $imgCount, self::MAX_IMAGES_WARNING);
            $serverContext->addMessage($this, 'tooManyImages', $msg, 
                                                    Message::CHANNEL_DEVELOPER);
        }
    }

    /**
     * @see CoreProvider::handleCorePlugin()
     */
    public function handleCorePlugin($requ) {

        $msMapObj = $this->serverContext->getMapObj();

        $imagesResult = new ImagesResult();

        $notdrawnImage = new Image();
        $notdrawnImage->isDrawn = false;

        // mainmap
        if ($requ->mainmap->isDrawn) {
            $ms_mainmap = $this->serverContext->getMsMainmapImage();
            if (!$ms_mainmap) 
                throw new CartoserverException('drawMainmap was not called ' .
                                               'before getResult');
            $this->serverContext->setMsMainmapImage($ms_mainmap);
            $msMapObj->drawLabelCache($ms_mainmap);
                        
            $imagesResult->mainmap = $this->getImage($ms_mainmap);
        } else {
            $imagesResult->mainmap = $notdrawnImage;
        }

        $msMapObj->selectOutputFormat($this->serverContext->getImageType());            

        if ($requ->keymap->isDrawn) {
            $ms_keymap = $msMapObj->drawreferencemap();
            $imagesResult->keymap = $this->getImage($ms_keymap);
        } else {
            $imagesResult->keymap = $notdrawnImage;
        }

        if ($requ->scalebar->isDrawn) {
            $layersPlugin = $this->serverContext->getPluginManager()->
                            getPlugin('layers');
            $resRatio = $layersPlugin->getResRatio();
            if ($resRatio && $resRatio != 1) {
                $msMapObj->scalebar->set('width', $msMapObj->scalebar->width
                                                  * $resRatio);
                $msMapObj->scalebar->set('height', $msMapObj->scalebar->height
                                                   * $resRatio);
            }
            
            $ms_scalebar = $msMapObj->drawScalebar();
            $imagesResult->scalebar = $this->getImage($ms_scalebar);
        } else {
            $imagesResult->scalebar = $notdrawnImage;
        }
        
        $imageType = $this->getImageType();        
        if (!empty($imageType)) {
            $msMapObj->selectOutputFormat($imageType);            
        }

        $serverContext = $this->getServerContext();        
        if ($serverContext->isDevelMessagesEnabled()) {
            $this->checkMaxImages($serverContext);
        }
        
        return $imagesResult;
    }
}

?>
