<?php
/**
 * @package Common
 */

/**
 * Abstract class for URL provider objects
 * 
 * Extending classes have to provide URL's from a set of parameters in
 * different contexts. For instance, return an URL for accessing files in a
 * plugin htdocs directory.
 * @package Common
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
 * Url provider for accessing files directly through the web server
 *
 * Such files are thus accessed via the htdocs directory of cartoweb. The files
 * may be put there using symbolic links, or may be copied directly.
 * @package Common
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
 * Url provider for accessing files through a mini-proxy
 * 
 * This is a php script which reads the requested files directly from
 * filesystem, and returns them to the client.
 * @package Common
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
 * Class to manage resource accesses
 * 
 * It handles the registration of Url provider objects.
 * @package Common
 */
class ResourceHandler {
    /**
     * @var UrlProvider the current URL provider to use for generating URL's
     */
    private $urlProvider;
    
    /**
     * @var boolean true if the client is in direct access mode.
     */
    private $directAcess;
    
    /**
     * @var string the URL to the cartoserver base.
     */
    private $cartoserverBaseUrl;

    /**
     * Constructor
     * 
     * @param Config the current configuration object
     * @param ProjectHandler the current project handler.
     */
    public function __construct(Config $config, ProjectHandler $projectHandler) {

        $provider = 'Miniproxy';
        if ($config->urlProvider)
            $provider = $config->urlProvider;
        $className = $provider . 'UrlProvider';
        if (!class_exists($className))
            throw new CartocommonException("Unknown urlProvider named $provider");

        $this->urlProvider = new $className($projectHandler);
        
        if (!class_exists('ClientConfig') || !$config instanceof ClientConfig)
            return;
        $this->directAcess = $config->cartoserverDirectAccess;
        $this->cartoserverBaseUrl = $config->cartoserverBaseUrl;
    }
 
    /**
     * @return UrlProvider the current URL provider to use for getting url's.
     */
    public function getUrlProvider() {
        return $this->urlProvider;   
    }
    
    /**
     * Convert a relative resource URL to an absolute one.
     * 
     * @param string The relative URL to the resource
     * @return string The absolute URL to a resource
     */
    public function getAbsoluteUrl($relativeUrl) {
        if (empty($this->cartoserverBaseUrl))
            throw new CartocommonException('cartoserverBaseUrl not set');
        return $this->cartoserverBaseUrl . $relativeUrl;
    }

    /**
     * Processes a relative URL to a resource, so that when inserted in the html
     * template, the URL is correct. 
     * In direct mode, it keeps relative URLs, and returns absolute URL's when
     * in non direct access mode.
     * 
     * @param string The relative URL to a resource to convert
     * @return string The URL to be used in the html template.
     */
    public function convertUrl($relativeUrl) {

        // TODO: handle reverseProxyPrefix (in ClientImages.php cvs history)

        if ($this->directAcess)
            return $relativeUrl;
        else
            return $this->getAbsoluteUrl($relativeUrl);
    }
    
    /**
     * From a relative resource URL, as returned by the server, returns either a
     * path to the corresponding file on the file system, if accessible (only
     * for direct access mode). Otherwise, it will return the absolute URL to
     * the resource.
     * 
     * @param string The relative URL to a resource
     * @return string The path to the resource file on the filesystem, if
     * accessible, or the absolute URL to the resource
     */
    public function getPathOrAbsoluteUrl($relativeUrl) {

        // FIXME: images on server should return filesystem path, for use in direct 
        //  access mode, so that we can avoid this crude hack !
       
        if (is_readable(CARTOCOMMON_HOME . 'www-data/' .$relativeUrl))
            return CARTOCOMMON_HOME . 'www-data/' .$relativeUrl;
        if (is_readable(CARTOCOMMON_HOME . 'htdocs/' .$relativeUrl))
            return CARTOCOMMON_HOME . 'htdocs/' .$relativeUrl;
        return $this->getAbsoluteUrl($relativeUrl);
    }    
}

?>
