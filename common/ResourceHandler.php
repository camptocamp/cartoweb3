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
 * @version $Id$
 * @package Common
 */

/**
 * Class to manage resource accesses
 * 
 * It handles the registration of Url provider objects.
 * @package Common
 */
class ResourceHandler {

    /**
     * The project handler to use for getting current project name
     * @var ProjectHandler
     */
    protected $projectHandler;
    
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

        $this->projectHandler = $projectHandler;    

        if (!class_exists('ClientConfig') || !$config instanceof ClientConfig)
            return;
        $this->directAccess = $config->cartoserverDirectAccess;
        $this->cartoclientBaseUrl = $config->cartoclientBaseUrl;
        $this->cartoserverBaseUrl = $config->cartoserverBaseUrl;
    }

    /**
     * Replaces some URL characters by XHTML-proof ones.
     * @param string URL to filter
     * @param boolean if true reverts conversion: from XHTML to plain
     * @return string filtered URL
     */
    public static function convertXhtml($url, $back = false) {
        if ($back) {
            // back from XHTML to plain
            return str_replace('&amp;', '&', $url);
        }
        
        return str_replace('&', '&amp;', $url);
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
     * @param boolean True to make URL XHTML-compliant
     * @return A relative URL to the resource if possible, or an absolute one
     */
     public function getFinalUrl($relativeUrl, $client, $forceAbsolute = false,
                                 $useXhtml = true) {
    
        $base = $client ? $this->cartoclientBaseUrl : $this->cartoserverBaseUrl;

        // TODO: handle reverseProxyPrefix for resources on server
        // (in ClientImages.php cvs history)
            
        if ($useXhtml) {
            $relativeUrl = self::convertXhtml($relativeUrl);
        }

        // If resource is on client, or we are in directAccess, 
        // we can use relative URL.
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
       
        if (is_readable(CARTOWEB_HOME . 'www-data/' .$relativeUrl))
            return CARTOWEB_HOME . 'www-data/' .$relativeUrl;
        if (is_readable(CARTOWEB_HOME . 'htdocs/' .$relativeUrl))
            return CARTOWEB_HOME . 'htdocs/' .$relativeUrl;
        return $this->getFinalUrl($relativeUrl, $client, true);        
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
    public function getHtdocsUrl($plugin, $project, $resource) {
        
        $path = $resource;
        if (!empty($plugin))
            $path = $plugin . '/' . $resource;

        $pluginPath = '';
        if (!empty($plugin)) {
            $pluginPath = $this->projectHandler->getPath('coreplugins/' . $plugin);
            $isCorePlugin = is_dir(CARTOWEB_HOME . $pluginPath);
            $pluginPath = $isCorePlugin ? 'coreplugins/' . $plugin . '/'
                                        : 'plugins/' . $plugin . '/';
        }         

        if ($this->projectHandler->isProjectFile($pluginPath 
                                                 . 'htdocs/' . $resource)) {
            return $this->projectHandler->getProjectName() . '/' . $path;
        } else {
            return $path;
        }
    }
      
    /**
     * Returns an URL to access icon images inside icons subdirectory where the
     * mapfile is located.
     * This is only relevent for server resources.
     * @param string the project name
     * @param string the mapId to use
     * @param string the resource to access (icon name, without path)
     */
    public function getIconUrl($project, $mapId, $resource) {
        
        return sprintf('gfx/icons/%s/%s/%s', $project, $mapId, $resource);
    }
    
    /**
     * Returns an URL to access files inside the directory of generated files
     * (like generated mapserver images, pdf files, ...)
     * This can be used for server or client resources.
     * 
     * @param string the resource to access (this is the resource name relative
     * to the directory of generated files (www-data usually))
     */
    public function getGeneratedUrl($resource) {

        return 'generated/' . $resource;
    }    
    
}

?>
