<?php
/**
 * @package CorePlugins
 * @version $Id$
 */
require_once(CARTOCOMMON_HOME . 'common/basic_types.php');

class ImagesState {
 
    public $mainmapDimension;
}

/**
 * @package CorePlugins
 */
class ClientImages extends ClientCorePlugin {
    private $log;
    private $imagesState;

    private $imagesResult;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    function loadSession($sessionObject) {
        $this->log->debug("loading session:");
        $this->imagesState = $sessionObject;
    }

    function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->log->debug("creating session:");
        
        $this->imagesState = new ImagesState();
        
        // FIXME: put this in config
        $this->imagesState->mainmapDimension = new Dimension(400, 200);
    }

    function handleHttpRequest($request) {
    }

    function getMainmapDimensions() {
        return $this->imagesState->mainmapDimension;
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

    function handleMapResult($mapResult) {
        $this->imagesResult = $mapResult->imagesResult;
        $imgRes = $mapResult->imagesResult;

        $this->imagesState->mainmapDimension->width = $imgRes->mainmap->width; 
        $this->imagesState->mainmapDimension->height = $imgRes->mainmap->height; 
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
    }

    function saveSession() {
        $this->log->debug("saving session:");
        return $this->imagesState;
    }
}
?>
