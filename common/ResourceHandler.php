<?php

/**
 * Abstract class for URL provider objects. Extending classes have to provide
 * URL's from a set of parameters in different contexts. For instance, return an
 * URL for accessing files in a plugin htdocs directory.
 */
abstract class UrlProvider {
    /**
     * @var ProjectHandler the project handler to use for getting current
     * project name
     */
    protected $projectHandler;

    /**
     * Constructor
     */
    public function __construct(ProjectHandler $projectHandler) {
        $this->projectHandler = $projectHandler;    
    }

    /**
     * Returns the URL for resources in htdocs directory (may be in projects
     * and plugins)
     * @param string the plugin name
     * @param string the project name
     * @param string the resource to access. It may contain a path, like
     * css/style.css, or gfx/my_icon.png
     */
    abstract function getHtdocsUrl($plugin, $project, $resource);
    
    /**
     * Returns an URL to access icon images inside icons subdirectory where the
     * mapfile is located.
     * @param string the project name
     * @param string the mapId to use
     * @param string the resource to access (icon name, without path)
     */
    abstract function getIconUrl($project, $mapId, $resource);
    
    /**
     * Returns an URL to access files inside the directory of generated files
     * (like generated mapserver images, pdf files, ...)
     * 
     * @param string the resource to access (this is the resource name relative
     * to the directory of generated files (www-data usually))
     */
    abstract function getGeneratedUrl($resource);
}

/**
 * Url provider for accessing files directly through the web server. Such files
 * are thus accessed via the htdocs directory of cartoweb. The files may be put
 * there using symbolic links, or may be copied directly.
 */
class SymlinkUrlProvider extends UrlProvider {

    /**
     * @see UrlProvider::getHtdocsUrl()
     */
    public function getHtdocsUrl($plugin, $project, $resource) {
        
        $path = $resource;
        if (!empty($plugin))
            $path = $plugin . '/' . $resource;
        // FIXME: put getWebPath there
        return $this->projectHandler->getWebPath($path);
    }
    
    /**
     * @see UrlProvider::getIconUrl()
     */    
    public function getIconUrl($project, $mapId, $resource) {
        
        return sprintf('gfx/icons/%s/%s/%s', $project, $mapId, $resource);
    }

    /**
     * @see UrlProvider::getGeneratedUrl()
     */
    public function getGeneratedUrl($resource) {

        return $resource;
    }
}

/**
 * Url provider for accessing files through a mini-proxy. This is a php script
 * which reads the requested files directly from filesystem, and returns them
 * to the client.
 */
class MiniproxyUrlProvider extends UrlProvider {

    /**
     * build the final URL to the miniproxy.
     */
    private function buildQuery($queryParams, $resource) {
    
        $queryParams['r'] = $resource;
        $query = http_build_query($queryParams);
        return 'r.php?' . $query;
    }

    /**
     * @see UrlProvider::getHtdocsUrl()
     */
    public function getHtdocsUrl($plugin, $project, $resource) {

        $queryParams = array();
        $queryParams['k'] = 'h'; 
        if (!empty($plugin))
            $queryParams['pl'] = $plugin;
        if (!empty($project) && $project != 'default')
            $queryParams['pr'] = $project;
        
        return $this->buildQuery($queryParams, $resource);
    }

    /**
     * @see UrlProvider::getIconUrl()
     */    
    public function getIconUrl($project, $mapId, $resource) {

        $queryParams = array();
        $queryParams['k'] = 'i';
        if (!empty($project) && $project != 'default')
            $queryParams['pr'] = $project;
        $queryParams['m'] = $mapId;

        return $this->buildQuery($queryParams, $resource);
    }
    
    /**
     * @see UrlProvider::getGeneratedUrl()
     */
    public function getGeneratedUrl($resource) {

        $queryParams = array();
        $queryParams['k'] = 'g'; 

        return $this->buildQuery($queryParams, $resource);
    }  
}

/**
 * Class to manage resource accesses. It handles the registration of Url
 * provider objects.
 */
class ResourceHandler {
    /**
     * @var UrlProvider the current URL provider to use for generating URL's
     */
    private $urlProvider;

    /**
     * Constructor
     */
    public function __construct(Config $config, ProjectHandler $projectHandler) {

        $provider = 'Miniproxy';
        if ($config->urlProvider)
            $provider = $config->urlProvider;
        $className = $provider . 'UrlProvider';
        if (!class_exists($className))
            throw new CartocommonException("Unknown urlProvider named $provider");

        $this->urlProvider = new $className($projectHandler);
    }
 
    /**
     * @return UrlProvider the current URL provider to use for getting url's.
     */
    public function getUrlProvider() {
        return $this->urlProvider;   
    }
}

?>
