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
                              Exportable {
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
     * Default map width, if not specified in config
     */
    const DEF_MAP_WIDTH  = 400;

    /**
     * Default map height, if not specified in config
     */
    const DEF_MAP_HEIGHT = 200;

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
        }
    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {
        $this->log->debug('update form:');
        $this->log->debug($this->imagesState);
        
        $this->handleMapSize($request);

        if ($this->getConfig()->collapsibleKeymap) {
            $this->collapseKeymap = isset($request['collapse_keymap']) ?
                                    $request['collapse_keymap'] : 0;
        }
    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpGetRequest($request) {
        $this->handleMapSize($request, true);        

        if ($this->getConfig()->collapsibleKeymap) {
            $this->collapseKeymap = isset($request['collapse_keymap']) ?
                                    $request['collapse_keymap'] : 0;
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
                $mapSize =& $mapSizes[$this->imagesState->selectedSize];
                $mapWidth = $mapSize['width'];
                $mapHeight = $mapSize['height'];
            }
        } else {
            $mapWidth = $this->getConfig()->mapWidth;
            $mapHeight = $this->getConfig()->mapHeight;
        }
        
        if (!isset($mapWidth) || !$mapWidth)
            $mapWidth = self::DEF_MAP_WIDTH;
        if (!isset($mapHeight) || !$mapHeight) 
            $mapHeight = self::DEF_MAP_HEIGHT;
    }

    /**
     * @see ServerCaller::buildRequest()
     */
    public function buildRequest() {

        $images = new Images();

        $drawAll = ($this->getCartoclient()->getOutputType()
                    != Cartoclient::OUTPUT_IMAGE);

        $scalebar_image = new Image();
        $scalebar_image->isDrawn = ($drawAll 
            && !$this->getConfig()->noDrawScalebar);
        $images->scalebar = $scalebar_image;

        $keymap_image = new Image();
        $keymap_image->isDrawn = ($drawAll 
            && !$this->getConfig()->noDrawKeymap);
        $images->keymap = $keymap_image;

        $mainmap_image = new Image();
        $mainmap_image->isDrawn = true;
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
        $mapsizesOptions = array();
        foreach ($this->getMapSizes() as $id => $mapSize)
            $mapsizesOptions[$id] = I18n::gt($mapSize['label']);

        $mapsizeSelected = $this->imagesState->selectedSize;
        $this->smarty->assign(array('mapsizes_options' => $mapsizesOptions,
                                    'mapsize_selected' => $mapsizeSelected,
                                    ));
        return $this->smarty->fetch('mapsizes.tpl');
    }

    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {
       
        $template->assign(array(
            'mainmap_path' => 
                 $this->getImageUrl($this->imagesResult->mainmap->path, false),
            'mainmap_width' => $this->imagesResult->mainmap->width,
            'mainmap_height' => $this->imagesResult->mainmap->height,
                                ));
    
        if ($this->imagesResult->keymap->isDrawn) {
            $template->assign(array(
                'keymap_path' => 
                    $this->getImageUrl($this->imagesResult->keymap->path),
                'keymap_width' => $this->imagesResult->keymap->width,
                'keymap_height' => $this->imagesResult->keymap->height,
                                    ));
        }
        
        if ($this->imagesResult->scalebar->isDrawn) {
            $template->assign(array(
                'scalebar_path' => 
                    $this->getImageUrl($this->imagesResult->scalebar->path),
                'scalebar_width' => $this->imagesResult->scalebar->width,
                'scalebar_height' => $this->imagesResult->scalebar->height,
                                    ));
        }

        $mapSizesActive = $this->getConfig()->mapSizesActive;
        $template->assign(array('mapsizes_active' => $mapSizesActive,
                                'mapsizes' => $this->drawMapSizes(),
                                ));

        if ($this->getConfig()->collapsibleKeymap) {         
            $template->assign(array('collapseKeymap' => $this->collapseKeymap,
                                    'collapsibleKeymap' => true,
                                    ));
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
        if (!is_null($mapHeight))
            $mapRequest->imagesRequest->mainmap->height = $mapHeight;

        $mapWidth = $configuration->getMapWidth();
        if (!is_null($mapWidth))
            $mapRequest->imagesRequest->mainmap->width = $mapWidth;

        $mapAngle = $configuration->getMapAngle();
        if (!is_null($mapAngle))
            $mapRequest->imagesRequest->mainmap->angle = $mapAngle;
    }    

    /**
     * Outputs raw mainmap image.
     */
    public function outputMainmap() {
        if (empty($this->imagesResult)) {
            return false;
        }
        
        $mapPath = $this->getImageUrl($this->imagesResult->mainmap->path,
                                      false, true);
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
