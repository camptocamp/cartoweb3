<?php
/**
 * Classes for managing access to resources (URL computation, ...)
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
     * The project handler to use for getting current project name
     * @var ProjectHandler
     */
    protected $projectHandler;

    /**
     * Constructor
     * @param ProjectHandler
     */
    public function __construct(ProjectHandler $projectHandler) {
        $this->projectHandler = $projectHandler;    
    }

    /**
     * Returns the URL for resources in htdocs directory (may be in projects
     * and plugins)
     * This is only relevent for client resources.
     * @param string the plugin name
     * @param string the project name
     * @param string the resource to access. It may contain a path, like
     * css/style.css, or gfx/my_icon.png
     */
    abstract function getHtdocsUrl($plugin, $project, $resource);
    
    /**
     * Returns an URL to access icon images inside icons subdirectory where the
     * mapfile is located.
     * This is only relevent for server resources.
     * @param string the project name
     * @param string the mapId to use
     * @param string the resource to access (icon name, without path)
     */
    abstract function getIconUrl($project, $mapId, $resource);
    
    /**
     * Returns an URL to access files inside the directory of generated files
     * (like generated mapserver images, pdf files, ...)
     * This can be used for server or client resources.
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

        if ($this->projectHandler->isProjectFile('htdocs/' . $path)) {
            return $this->projectHandler->getProjectName() . '/' . $path;
        } else {
            return $path;
        }
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
        if (!empty($project) && $project != ProjectHandler::DEFAULT_PROJECT)
            $queryParams['pr'] = $project;
        
        return $this->buildQuery($queryParams, $resource);
    }

    /**
     * @see UrlProvider::getIconUrl()
     */    
    public function getIconUrl($project, $mapId, $resource) {

        $queryParams = array();
        $queryParams['k'] = 'i';
        if (!empty($project) && $project != ProjectHandler::DEFAULT_PROJECT)
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
     * The current URL provider to use for generating URL's
     * @var UrlProvider
     */
    private $urlProvider;
    
    /**
     * True if the client is in direct access mode.
     * @var boolean
     */
    private $directAccess;
    
    /**
     * The URL to the cartoclient base
     * @var string
     */
    private $cartoclientBaseUrl;

    /**
     * The URL to the cartoserver base.
     * @var string
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
        $this->directAccess = $config->cartoserverDirectAccess;
        $this->cartoclientBaseUrl = $config->cartoclientBaseUrl;
        $this->cartoserverBaseUrl = $config->cartoserverBaseUrl;
    }
 
    /**
     * @return UrlProvider the current URL provider to use for getting url's.
     */
    public function getUrlProvider() {
        return $this->urlProvider;   
    }

    /**
     * Processes a relative URL to a resource, and convert it so that it is 
     * accessible on the client templates.
     * The relative url may be relative to the client or the server base Url.
     * Whenever possible, the returned URL will be relative to the cartoclient.
     * The relative URL is possible if the $forceAbsolute parameter is false and 
     * the resource is on the client or directAccess is enabled.
     * 
     * @param string The relative URL to a resource to convert
     * @param boolean True for resources on the client, false for server
     * @param boolean True to obtain an absolute URL in any case
     * @return A relative URL to the resource if possible, or an absolute one
     */
     public function getFinalUrl($relativeUrl, $client, $forceAbsolute=false) {
    
        $base = $client ? $this->cartoclientBaseUrl : $this->cartoserverBaseUrl;

        // TODO: handle reverseProxyPrefix for resources on server (in ClientImages.php cvs history)

        // if resource is on client, or we are in directAccess, we can use relative URL
        if (!$forceAbsolute && ($client || $this->directAccess)) {
            return $relativeUrl;
        }
        return $base . $relativeUrl;
    }
    
    /**
     * From a relative resource URL, returns either a  path to the corresponding 
     * file on the file system, if accessible (only for client resources, or 
     * server in direct access mode). Otherwise, it will return the absolute 
     * URL to the resource.
     * 
     * @param string The relative URL to a resource
     * @param boolean True for resources on the client, false for server
     * @return string The path to the resource file on the filesystem, if
     * accessible, or the absolute URL to the resource
     */
    public function getPathOrAbsoluteUrl($relativeUrl, $client=false) {

        // FIXME: images on server should return filesystem path, for use in direct 
        //  access mode, so that we can avoid this crude hack !
       
        if (is_readable(CARTOCOMMON_HOME . 'www-data/' .$relativeUrl))
            return CARTOCOMMON_HOME . 'www-data/' .$relativeUrl;
        if (is_readable(CARTOCOMMON_HOME . 'htdocs/' .$relativeUrl))
            return CARTOCOMMON_HOME . 'htdocs/' .$relativeUrl;
        return $this->getFinalUrl($relativeUrl, $client, true);        
    } 
}

?>
