<?php

class ClientImages extends ClientCorePlugin {
    private $log;

    private $imagesResult;
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
        $template->assign("mainmap_path", 
                          $this->getImagePath($this->imagesResult->mainmap->path));
    }

    function saveSession() {
        $this->log->debug("saving session:");
    }
}
?>