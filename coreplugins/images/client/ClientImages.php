<?php
/**
 * Client images plugin
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
 * Basic types
 */
require_once(CARTOWEB_HOME . 'common/BasicTypes.php');

/**
 * Informations to save in session
 * @package CorePlugins
 */
class ImagesState {
 
    /**
     * Main map height and width
     * @var Dimension
     */
    public $mainmapDimension;
    
    /**
     * ID of selected map size
     * @var int
     */
    public $selectedSize;
}

/**
 * Client part of Images plugin
 * @package CorePlugins
 */
class ClientImages extends ClientPlugin
                   implements Sessionable, GuiProvider, ServerCaller, 
                              Exportable, Ajaxable {
    /**
     * @var Logger
     */
    private $log;
    
    /**
     * Current session state
     * @var ImagesState
     */
    protected $imagesState;

    /**
     * Last server result
     * @var ImagesResult
     */
    protected $imagesResult;
    
    /**
     * Possible map sizes
     * @var array
     */
    protected $mapSizes;

    /**
     * Indicates if keymap must be collapsible
     * @var boolean
     */
    protected $collapseKeymap = 0;

    /**
     * Indicates if scalebar must be drawn.
     * @var boolean
     */
    protected $drawScalebar;

    /**
     * Indicates if keymap must be drawn.
     * @var boolean
     */
    protected $drawKeymap;
    
    /**
     * Indicates if mainmap must be drawn.
     * @var boolean
     */
    protected $drawMainmap;

    /**
     * Default map width, if not specified in config
     */
    const DEF_MAP_WIDTH  = 400;

    /**
     * Default map height, if not specified in config
     */
    const DEF_MAP_HEIGHT = 200;

    /**
     * Default max map width, if not specified in config.
     * Test is performed only when using $_REQUEST customMapsize.
     */
    const DEF_MAX_MAP_WIDTH  = 1500;

    /**
     * Default max map height, if not specified in config.
     * Test is performed only when using $_REQUEST customMapsize.
     */
    const DEF_MAX_MAP_HEIGHT = 1000;

    /**
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }
    /**
     * @see Sessionable::loadSession()
     */
    public function loadSession($sessionObject) {
        $this->log->debug('loading session:');
        $this->imagesState = $sessionObject;
    }

    /**
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->log->debug('creating session:');
        
        $this->imagesState = new ImagesState();

        $this->imagesState->selectedSize = $this->getConfig()->mapSizesDefault;
        
        $mapWidth = $mapHeight = 0;
        $this->setMapDimensions($mapWidth, $mapHeight);
        $this->imagesState->mainmapDimension = 
            new Dimension($mapWidth, $mapHeight);
    }

    /**
     * Returns the last server result
     * @return ImagesResult
     */
    public function getImagesResult() {
        return $this->imagesResult;
    }

    /**
     * Handles map sizes dropdown box
     * @param array HTTP request
     * @param boolean checks?
     */
    protected function handleMapSize($request, $check = false) {
    
        $mapSize = $this->getHttpValue($request, 'mapsize');

        if (!is_null($mapSize)) {
            if ($check) {
                if (!$this->checkInt($mapSize, 'mapsize'))
                    return NULL;
                
                if (!array_key_exists($mapSize, $this->getMapSizes())) {
                    $this->cartoclient->addMessage('Mapsize ID not found');
                    return NULL;
                }
            }
            $this->imagesState->selectedSize = $mapSize;

            $mapWidth = $mapHeight = 0;
            $this->setMapDimensions($mapWidth, $mapHeight);
            $this->imagesState->mainmapDimension->width  = $mapWidth;
            $this->imagesState->mainmapDimension->height = $mapHeight;
            return NULL;
        }
        
        $customMapSize = $this->getHttpValue($request, 'customMapsize');

        if (!empty($customMapSize)) {
            // customMapsize parameter is something like [width]x[height]
            // with [width] and [height] being integers.
            $mapSize = Utils::parseArray($customMapSize, 'x');
            if (count($mapSize) != 2 || 
                !$this->checkMapDimensions($mapSize[0], $mapSize[1])) {
                return NULL;
            }
            $this->imagesState->mainmapDimension->width  = $mapSize[0];
            $this->imagesState->mainmapDimension->height = $mapSize[1];
            // reset stored predefined mapsize, otherwise it will conflict
            $this->imagesState->selectedSize = NULL;
        }
    }

    /**
     * Checks if required map width and height are allowed.
     * @param mixed width
     * @param mixed height
     * @return boolean
     */
    protected function checkMapDimensions($width, $height) {
        // tests if dimensions are integer and positive
        if (!Utils::isInteger($width,  true) ||
            !Utils::isInteger($height, true)) {
            return false;
        }

        // tests if dimensions are too big
        $maxWidth = $this->getConfig()->maxMapWidth;
        if (empty($maxWidth)) {
            $maxWidth = self::DEF_MAX_MAP_WIDTH;
        }
        if ($width > $maxWidth) {
            return false;
        }

        $maxHeight = $this->getConfig()->maxMapHeight;
        if (empty($maxHeight)) {
            $maxHeight = self::DEF_MAX_MAP_HEIGHT;
        }
        if ($height > $maxHeight) {
            return false;
        }

        return true;
    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {
        $this->log->debug('update form:');
        $this->log->debug($this->imagesState);
        
        $this->handleMapSize($request);
        $this->handleHttpRequest($request);
    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpGetRequest($request) {
        $this->handleMapSize($request, true);        
        $this->handleHttpRequest($request);
    }

    /**
     * Common part of self::handleHttpPostRequest() and
     * self::handleHttpGetRequest()
     */
    protected function handleHttpRequest($request) {
        if ($this->getConfig()->collapsibleKeymap) {
            $this->collapseKeymap = isset($request['collapse_keymap']) ?
                                    $request['collapse_keymap'] : 0;
        }

        $this->detectIfImageDrawn('scalebar', $request);
        $this->detectIfImageDrawn('keymap',   $request);
        $this->detectIfImageDrawn('mainmap',  $request);
    }
    
    /**
     * Tries to decide if the mainmap (resp. scalebar, keymap) must be drawn
     * or not.
     * @param string image name
     * @param array request data
     */
    protected function detectIfImageDrawn($image, &$request) {
        if (!in_array($image, array('scalebar', 'keymap', 'mainmap'))) {
            return;
        }

        $drawImage = 'draw' . ucfirst($image); // eg. drawMainmap
        if (isset($request[$drawImage])) {
            if (!is_null($request[$drawImage])) {
                $this->$drawImage = (bool)$request[$drawImage];
            } else {
                $this->$drawImage = true;
            }
        }
    }

    /**
     * Returns the dimensions of the main map. It may be used by other plugins.
     * @return Dimension
     */
    public function getMainmapDimensions() {
        return $this->imagesState->mainmapDimension;
    }    

    /**
     * Returns the list of map sizes to display.
     * @return array Array of map sizes (Map with label, with and height keys) 
     */ 
    protected function getMapSizes() {
        if (is_array($this->mapSizes)) return $this->mapSizes;

        return $this->initMapSizes();
    }

    /**
     * Sets map width and height
     * 
     * Sets from:
     * - Selected size from predefined list, if config's mapSizesActive = true
     * - Fixed size from config file
     * - Default size
     * @param int width to be set
     * @param int height to be set
     */
    protected function setMapDimensions(&$mapWidth, &$mapHeight) {
        if ($this->getConfig()->mapSizesActive) {
            $mapSizes = $this->getMapSizes();
            if (isset($mapSizes[$this->imagesState->selectedSize])) {
                $mapSize   =& $mapSizes[$this->imagesState->selectedSize];
                $mapWidth  =  $mapSize['width'];
                $mapHeight =  $mapSize['height'];
            }
        } else {
            $mapWidth  = $this->getConfig()->mapWidth;
            $mapHeight = $this->getConfig()->mapHeight;
        }
        
        if (empty($mapWidth)) {
            $mapWidth = self::DEF_MAP_WIDTH;
        }
        if (empty($mapHeight)) {
            $mapHeight = self::DEF_MAP_HEIGHT;
        }
    }

    /**
     * @see ServerCaller::buildRequest()
     */
    public function buildRequest() {

        $images = new Images();

        $drawAll = ($this->getCartoclient()->getOutputType()
                    != Cartoclient::OUTPUT_IMAGE);

        if (!isset($this->drawMainmap)) {
            $this->drawMainmap = $drawAll;
        }
        if (!isset($this->drawKeymap)) {
            $this->drawKeymap = $drawAll;
        }
        if (!isset($this->drawScalebar)) {
            $this->drawScalebar = $drawAll;
        }

        if (!$drawAll && !$this->drawScalebar && !$this->drawKeymap) {
            // sets mainmap output by default if image mode and
            // if neither scalebar nor keymap are requested
            $this->drawMainmap = true;
        }

        $scalebar_image = new Image();
        $scalebar_image->isDrawn = ($this->drawScalebar
            && !$this->getConfig()->noDrawScalebar);
        $images->scalebar = $scalebar_image;

        $keymap_image = new Image();
        $keymap_image->isDrawn = ($this->drawKeymap
            && !$this->getConfig()->noDrawKeymap);
        $images->keymap = $keymap_image;

        $mainmap_image = new Image();
        $mainmap_image->isDrawn = $this->drawMainmap;
        $mainmap_image->width = $this->imagesState->mainmapDimension->width;
        $mainmap_image->height = $this->imagesState->mainmapDimension->height;
        $images->mainmap = $mainmap_image;

        return $images;
    }

    /**
     * @see ServerCaller::initializeResult()
     */
    public function initializeResult($imagesResult) {
        assert($imagesResult instanceof ImagesResult);
        $this->imagesResult = $imagesResult;

        $this->imagesState->mainmapDimension->width = $imagesResult->mainmap->width; 
        $this->imagesState->mainmapDimension->height = $imagesResult->mainmap->height; 
    }

    /**
     * @see ServerCaller::handleResult()
     */
    public function handleResult($imagesResult) {}

    /**
     * @param string minimal image path
     * @param boolean True to make URL XHTML-compliant
     * @return string The URL to the image, as put inside the html template
     */
    protected function getImageUrl($path, $useXhtml = true, $forceAbsolute = false) {

        $resourceHandler = $this->getCartoclient()->getResourceHandler();
        return $resourceHandler->getFinalUrl($path, false, $forceAbsolute,
                                             $useXhtml);
    }

    /**
     * Reads map sizes from configuration
     * @return array Array of map sizes (Map with label, with and height keys)
     */
    protected function initMapSizes() {
        $this->mapSizes = array();
        $config = $this->getConfig();

        for ($i = 0; ; $i++) {
            $prefix = 'mapSizes.' . $i;
            $width = $prefix . '.width';
            $width = $config->$width;
            if (!$width) break;

            $height = $prefix . '.height';
            $height = $config->$height;
            $label = $prefix . '.label';
            $label = $config->$label;
            if (!$label) $label = $width . 'x' . $height;

            $this->mapSizes[$i] = array('label'  => $label,
                                        'width'  => $width,
                                        'height' => $height);
        }
        return $this->mapSizes;
    }

    /**
     * Draws map sizes choice
     * @return string Smarty generated HTML content
     */
    protected function drawMapSizes() {
        $this->smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        /* we need to add an empty option because, if the mapsize is custom, none of the 
        predefined mapsize value must be selected, and by default a select will have the first 
        option selected */
        $mapsizesOptions = array('' => '');
        foreach ($this->getMapSizes() as $id => $mapSize)
            $mapsizesOptions[$id] = I18n::gt($mapSize['label']);

        $mapsizeSelected = $this->imagesState->selectedSize;
        $this->smarty->assign(array('mapsizes_options' => $mapsizesOptions,
                                    'mapsize_selected' => $mapsizeSelected,
                                    ));
        return $this->smarty->fetch('mapsizes.tpl');
    }

    /**
     * This method factors the plugin output for both GuiProvider::renderForm()
     * and Ajaxable::ajaxGetPluginResponse().
     * @return array array of variables and html code to be assigned
     */
    protected function renderFormPrepare() {
        $assignArray['variables'] = array(
            'mainmap_path' => 
                 $this->getImageUrl($this->imagesResult->mainmap->path, false),
            'mainmap_width' => $this->imagesResult->mainmap->width,
            'mainmap_height' => $this->imagesResult->mainmap->height,
        ); 

        if ($this->imagesResult->keymap->isDrawn) {
            $assignArray['variables']['keymap_path'] =
                $this->getImageUrl($this->imagesResult->keymap->path);
            $assignArray['variables']['keymap_width'] =
                $this->imagesResult->keymap->width;
            $assignArray['variables']['keymap_height'] =
                $this->imagesResult->keymap->height;
        }
        
        if ($this->imagesResult->scalebar->isDrawn) {
            $assignArray['variables']['scalebar_path'] =
                $this->getImageUrl($this->imagesResult->scalebar->path);
            $assignArray['variables']['scalebar_width'] =
                $this->imagesResult->scalebar->width;
            $assignArray['variables']['scalebar_height'] =
                $this->imagesResult->scalebar->height;
        }

        $assignArray['variables']['mapsizes_active'] =
            $this->getConfig()->mapSizesActive;
        $assignArray['htmlCode']['mapsizes'] =
            $this->drawMapSizes();

        if ($this->getConfig()->collapsibleKeymap) {
            $assignArray['variables']['collapseKeymap'] =
                $this->collapseKeymap;
            $assignArray['variables']['collapsibleKeymap'] = true;
        }
        
        return $assignArray;
    }

    /**
     * @see GuiProvider::renderForm()
     * FIXME: when all the values in the $assignArray are to be assigned,
     *        an automatism will be created to avoid coding the same piece
     *        of code all the time. @see bug #1354
     */
    public function renderForm(Smarty $template) {
        $assignArray = $this->renderFormPrepare();
        $template->assign($assignArray['variables']);
        $template->assign($assignArray['htmlCode']);
    }
    
    /**
     * @see Ajaxable::ajaxGetPluginResponse()
     * FIXME: when all the values in the $assignArray are to be assigned,
     *        an automatism will be created to avoid coding the same piece
     *        of code all the time. @see bug #1354
     */
    public function ajaxGetPluginResponse(AjaxPluginResponse $ajaxPluginResponse) {
        $assignArray = $this->renderFormPrepare();
        foreach ($assignArray['variables'] as $assignKey => $assignValue) {
            $ajaxPluginResponse->addVariable($assignKey, $assignValue);
        }        
    }

    /**
     * @see Ajaxable::ajaxHandleAction()
     */
    public function ajaxHandleAction($actionName, PluginEnabler $pluginEnabler) {
        switch ($actionName) {
            case 'Images.changeMapSize':
                $pluginEnabler->disableCoreplugins();
                $pluginEnabler->enablePlugin('location');
                $pluginEnabler->enablePlugin('images');
            break;
        }            
    }

    /**
     * @see Sessionable::saveSession()
     */
    public function saveSession() {
        $this->log->debug('saving session:');
        return $this->imagesState;
    }
    
    /**
     * @see Exportable::adjustExportMapRequest()
     */
    public function adjustExportMapRequest(ExportConfiguration $configuration, 
                                    MapRequest $mapRequest) {
        
        $isRenderMap = $configuration->isRenderMap();
        if (!is_null($isRenderMap))
            $mapRequest->imagesRequest->mainmap->isDrawn = $isRenderMap;

        $isRenderKeyMap = $configuration->isRenderKeyMap();
        if (!is_null($isRenderKeyMap))
            $mapRequest->imagesRequest->keymap->isDrawn = $isRenderKeyMap;

        $isRenderScalebar = $configuration->isRenderScalebar();
        if (!is_null($isRenderScalebar))
            $mapRequest->imagesRequest->scalebar->isDrawn = $isRenderScalebar;

        $mapHeight = $configuration->getMapHeight();
        if (!empty($mapHeight))
            $mapRequest->imagesRequest->mainmap->height = $mapHeight;

        $mapWidth = $configuration->getMapWidth();
        if (!empty($mapWidth))
            $mapRequest->imagesRequest->mainmap->width = $mapWidth;

        $mapAngle = $configuration->getMapAngle();
        if (!is_null($mapAngle))
            $mapRequest->imagesRequest->mainmap->angle = $mapAngle;
    }    

    /**
     * Outputs raw mainmap image.
     */
    public function outputMap() {
        if (empty($this->imagesResult)) {
            return false;
        }

        if ($this->drawKeymap) {
            $mapPath = $this->getImageUrl($this->imagesResult->keymap->path,
                                          false, true);
        } elseif ($this->drawScalebar) {
            $mapPath = $this->getImageUrl($this->imagesResult->scalebar->path,
                                          false, true);
        } else {
            $mapPath = $this->getImageUrl($this->imagesResult->mainmap->path,
                                          false, true);
        }        
        
        $infos = getimagesize($mapPath);
        $mime = !empty($infos['mime']) ? $infos['mime'] : '';
        if (!$mime) {
            $type = !empty($infos[2]) ? $infos[2] : IMAGETYPE_JPEG;
            $mime = image_type_to_mime_type($type);
        }
        
        header('Content-type: ' . $mime);
        echo file_get_contents($mapPath);
    }
}

?>
