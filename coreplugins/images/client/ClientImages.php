<?php
/**
 * @package CorePlugins
 * @version $Id$
 */
require_once(CARTOCOMMON_HOME . 'common/BasicTypes.php');

/**
 * Informations to save in session
 * @package CorePlugins
 */
class ImagesState {
 
    /**
     * Main map height ans width
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
    private $imagesState;

    /**
     * Last server result
     * @var ImagesResult
     */
    private $imagesResult;
    
    /**
     * Possible map sizes
     * @var array
     */
    private $mapSizes;

    const DEF_MAP_WIDTH  = 400;
    const DEF_MAP_HEIGHT = 200;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }
    /**
     * @see Sessionable::loadSession()
     */
    function loadSession($sessionObject) {
        $this->log->debug('loading session:');
        $this->imagesState = $sessionObject;
    }

    /**
     * @see Sessionable::createSession()
     */
    function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
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
    private function handleMapSize($request, $check = false) {
    
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
    function handleHttpPostRequest($request) {
        $this->log->debug('update form:');
        $this->log->debug($this->imagesState);
        
        $this->handleMapSize($request);        
    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    function handleHttpGetRequest($request) {
        $this->handleMapSize($request, true);        
    }
    
    /**
     * @return Dimension
     */
    function getMainmapDimensions() {
        return $this->imagesState->mainmapDimension;
    }    

    /**
     * @return array 
     */ 
    private function getMapSizes() {
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
    private function setMapDimensions(&$mapWidth, &$mapHeight) {
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
     * @see ServerCaller::buildMapRequest()
     */
    function buildMapRequest($mapRequest) {

        $images = new Images();

        // TODO: read from config if drawn        
        $scalebar_image = new Image();
        $scalebar_image->isDrawn = true;
        $images->scalebar = $scalebar_image;

        // TODO: read from config if drawn        
        $keymap_image = new Image();
        $keymap_image->isDrawn = true;
        $images->keymap = $keymap_image;

        $mainmap_image = new Image();
        $mainmap_image->isDrawn = true;
        $mainmap_image->width = $this->imagesState->mainmapDimension->width;
        $mainmap_image->height = $this->imagesState->mainmapDimension->height;
        $images->mainmap = $mainmap_image;

        $mapRequest->imagesRequest = $images;
    }

    /**
     * @see ServerCaller::initializeResult()
     */
    function initializeResult($imagesResult) {
        assert($imagesResult instanceof ImagesResult);
        $this->imagesResult = $imagesResult;

        $this->imagesState->mainmapDimension->width = $imagesResult->mainmap->width; 
        $this->imagesState->mainmapDimension->height = $imagesResult->mainmap->height; 
    }

    /**
     * @see ServerCaller::handleResult()
     */
    function handleResult($imagesResult) {}

    /**
     * Returns full URL from parsed URL (results of parse_url function)
     *
     * Taken from php manual. By anonymous.
     * @param array
     * @return string
     */
    private function glue_url($parsed) {
  
        if (! is_array($parsed)) return false;

        if (isset($parsed['scheme'])) {
            $sep = (strtolower($parsed['scheme']) == 'mailto' ? ':' : '://');
            $uri = $parsed['scheme'] . $sep;
        } else {
            $uri = '';
        }
 
        if (isset($parsed['pass'])) {
            $uri .= "$parsed[user]:$parsed[pass]@";
        } elseif (isset($parsed['user'])) {
            $uri .= "$parsed[user]@";
        }
 
        if (isset($parsed['host']))     $uri .= $parsed['host'];
        if (isset($parsed['port']))     $uri .= ":$parsed[port]";
        if (isset($parsed['path']))     $uri .= $parsed['path'];
        if (isset($parsed['query']))    $uri .= "?$parsed[query]";
        if (isset($parsed['fragment'])) $uri .= "#$parsed[fragment]";
 
        return $uri;
    }

    /**
     * Returns true if path is absolute (starts with '/')
     * @param string
     * @return boolean
     */
    private function isPathAbsolute($path) {

        return strpos($path, '/') === 0;
    }

    /**
     * Returns full path to images (direct mode)
     * @param string
     * @return string
     */
    private function getDirectAccessImagePath($path) {

        if ($this->isPathAbsolute($path)) 
            return $path;

        $config = $this->cartoclient->getConfig();
            
        // FIXME: is this really used ?
        if (isset($config->directAccessImagesUrl))
            return $config->directAccessImagesUrl . $path;

        return $path;
    }

    /**
     * Returns Cartoserver directory
     * @param array parsed URL
     * @return string 
     */
    private function getCartoserverDirname($cartoserverParsedUrl) {
        
        return dirname($cartoserverParsedUrl['path']) . '/';
    }

    /**
     * Returns full path to images
     *
     * 3 cases:
     * - Absolute path if direct mode
     * - Through direct proxy if installed
     * - Server URL otherwise
     * @param string 
     * @return string
     */
    private function getImagePath($path) {

        $config = $this->cartoclient->getConfig();

        if ($config->cartoserverDirectAccess)
            return $this->getDirectAccessImagePath($path);

        $cartoserverParsedUrl = parse_url($config->cartoserverBaseUrl . 'foo');

        $absolutePath = $path;
        if (!$this->isPathAbsolute($path))
            $absolutePath = $this->getCartoserverDirname($cartoserverParsedUrl) 
                               . $path;

        if (isset($config->reverseProxyPrefix)) {
            return $config->reverseProxyPrefix . $absolutePath;
        }

        $imageParsedUrl = $cartoserverParsedUrl;
        $imageParsedUrl['path'] = $absolutePath;

        return $this->glue_url($imageParsedUrl);
    }

    /**
     * Reads map sizes from configuration
     * @return array map sizes
     */
    private function initMapSizes() {
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
    private function drawMapSizes() {
        $this->smarty = new Smarty_CorePlugin($this->getCartoclient(), $this);
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
    function renderForm(Smarty $template) {
       
        $template->assign(array(
            'mainmap_path' => 
                $this->getImagePath($this->imagesResult->mainmap->path),
            'mainmap_width' => $this->imagesResult->mainmap->width,
            'mainmap_height' => $this->imagesResult->mainmap->height,
                                ));
    
        if ($this->imagesResult->keymap->isDrawn) {
            $template->assign(array(
                'keymap_path' => 
                    $this->getImagePath($this->imagesResult->keymap->path),
                'keymap_width' => $this->imagesResult->keymap->width,
                'keymap_height' => $this->imagesResult->keymap->height,
                                    ));
        }
        
        if ($this->imagesResult->scalebar->isDrawn) {
            $template->assign(array(
                'scalebar_path' => 
                    $this->getImagePath($this->imagesResult->scalebar->path),
                'scalebar_width' => $this->imagesResult->scalebar->width,
                'scalebar_height' => $this->imagesResult->scalebar->height,
                                    ));
        }

        $mapSizesActive = $this->getConfig()->mapSizesActive;
        $template->assign(array('mapsizes_active' => $mapSizesActive,
                                'mapsizes' => $this->drawMapSizes(),
                                ));
    }

    /**
     * @see Sessionable::saveSession()
     */
    function saveSession() {
        $this->log->debug('saving session:');
        return $this->imagesState;
    }
    
    /**
     * @see Exportable::adjustExportMapRequest()
     */
    function adjustExportMapRequest(ExportConfiguration $configuration, 
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
    }    
}

?>
