<?php
/**
 * @package CorePlugins
 * @version $Id$
 */
require_once(CARTOCOMMON_HOME . 'common/basic_types.php');

class ImagesState {
 
    public $mainmapDimension;
    public $selectedSize;
}

/**
 * @package CorePlugins
 */
class ClientImages extends ClientCorePlugin {
    private $log;
    private $imagesState;

    private $imagesResult;
    private $mapSizes;

    const DEF_MAP_WIDTH  = 400;
    const DEF_MAP_HEIGHT = 200;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    function loadSession($sessionObject) {
        $this->log->debug('loading session:');
        $this->imagesState = $sessionObject;
    }

    function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->log->debug('creating session:');
        
        $this->imagesState = new ImagesState();

        $this->imagesState->selectedSize = $this->getConfig()->mapSizesDefault;
        
        $mapWidth = $mapHeight = 0;
        $this->setMapDimensions($mapWidth, $mapHeight);
        $this->imagesState->mainmapDimension = 
            new Dimension($mapWidth, $mapHeight);
    }

    function handleHttpRequest($request) {
        $this->log->debug('update form:');
        $this->log->debug($this->imagesState);
        
        if (isset($request['mapsize']) && strlen($request['mapsize'])) {
            $this->imagesState->selectedSize = $request['mapsize'];

            $mapWidth = $mapHeight = 0;
            $this->setMapDimensions($mapWidth, $mapHeight);
            $this->imagesState->mainmapDimension->width  = $mapWidth;
            $this->imagesState->mainmapDimension->height = $mapHeight;
        }
    }

    function getMainmapDimensions() {
        return $this->imagesState->mainmapDimension;
    }    

    private function getMapSizes() {
        if (is_array($this->mapSizes)) return $this->mapSizes;

        return $this->initMapSizes();
    }

    private function setMapDimensions(&$mapWidth, &$mapHeight) {
        $mapSizes = $this->getMapSizes();
        if (isset($mapSizes[$this->imagesState->selectedSize])) {
            $mapSize =& $mapSizes[$this->imagesState->selectedSize];
            $mapWidth = $mapSize['width'];
            $mapHeight = $mapSize['height'];
        }
        if (!isset($mapWidth) || !$mapWidth) $mapWidth = self::DEF_MAP_WIDTH;
        if (!isset($mapHeight) || !$mapHeight) 
            $mapHeight = self::DEF_MAP_HEIGHT;
    }

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

    function handleResult($imagesResult) {
        assert($imagesResult instanceof ImagesResult);
        $this->imagesResult = $imagesResult;

        $this->imagesState->mainmapDimension->width = $imagesResult->mainmap->width; 
        $this->imagesState->mainmapDimension->height = $imagesResult->mainmap->height; 
    }

    /**
     * Taken from php manual. By anonymous.
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
 
        if (isset($parsed['host']))    $uri .= $parsed['host'];
        if (isset($parsed['port']))    $uri .= ":$parsed[port]";
        if (isset($parsed['path']))    $uri .= $parsed['path'];
        if (isset($parsed['query']))    $uri .= "?$parsed[query]";
        if (isset($parsed['fragment'])) $uri .= "#$parsed[fragment]";
 
        return $uri;
    }

    private function isPathAbsolute($path) {

        return strpos($path, '/') === 0;
    }

    private function getDirectAccessImagePath($path) {

        if ($this->isPathAbsolute($path)) 
            return $path;

        $config = $this->cartoclient->getConfig();
            
        if (@$config->directAccessImagesUrl)
            return $config->directAccessImagesUrl . $path;

        return $path;
    }

    private function getCartoserverDirname($cartoserverParsedUrl) {
        
        $config = $this->cartoclient->getConfig();

        assert(!is_null($config->cartoserverUrl));

        return dirname($cartoserverParsedUrl['path']) . '/';
    }

    private function getImagePath($path) {

        $config = $this->cartoclient->getConfig();

        if ($config->cartoserverDirectAccess)
            return $this->getDirectAccessImagePath($path);

        $cartoserverParsedUrl = parse_url($config->cartoserverUrl);

        $absolutePath = $path;
        if (!$this->isPathAbsolute($path))
            $absolutePath = $this->getCartoserverDirname($cartoserverParsedUrl) 
                               . $path;

        if (@$config->reverseProxyPrefix) {
            return $config->reverseProxyPrefix . $absolutePath;
        }

        $imageParsedUrl = $cartoserverParsedUrl;
        $imageParsedUrl['path'] = $absolutePath;

        return $this->glue_url($imageParsedUrl);
    }

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

    private function drawMapSizes() {
        $this->smarty = new Smarty_CorePlugin($this->cartoclient->getConfig(),
                                              $this);
        $mapsizesOptions = array();
        foreach ($this->getMapSizes() as $id => $mapSize)
            $mapsizesOptions[$id] = I18n::gt($mapSize['label']);

        $mapsizeSelected = $this->imagesState->selectedSize;
        $this->smarty->assign(array('mapsizes_options' => $mapsizesOptions,
                                    'mapsize_selected' => $mapsizeSelected,
                                    ));
        return $this->smarty->fetch('mapsizes.tpl');
    }

    function renderForm($template) {
       
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

    function saveSession() {
        $this->log->debug("saving session:");
        return $this->imagesState;
    }
}
?>
