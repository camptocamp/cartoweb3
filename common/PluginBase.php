<?php
/**
 * Base class for plugins
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
 * @version $Id$
 */

/**
 * Base class for plugins
 * @package Common
 */
abstract class PluginBase {
    
    /** 
     * @var Logger
     */
    private $log;

    /**
     * Root path for plugin files
     * @var string
     */
    private $basePath;
    
    /**
     * @var string
     */
    private $name;

    /**
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * Initializes plugin
     * 
     * Internal call, use {@link PluginBase::initialize()} to extend
     * initialization in custom plugins.
     * @param mixed
     */
    abstract public function initializeConfig($initArgs);
    
    /**
     * @return string
     */
    public function getBasePath() {
        if (!$this->basePath)
            throw new CartocommonException("Base path not defined");
        return $this->basePath;
    }

    /**
     * @param string
     */
    public function setBasePath($basePath) {
        $this->basePath = $basePath;
    }

    /**
     * @param string
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }
    
    /**
     * @param string
     */
    public function setExtendedName($extendedName) {
        $this->extendedName = $extendedName;
    }

    /**
     * @return string
     */
    public function getExtendedName() {
        return $this->extendedName;
    }
    
    /**
     * Get plugin's request or result out of MapRequest or MapResult
     *
     * The name of the request|result field is selected according to this
     * current plugin name (see {@link getName()}). Although this is called
     * getRequest, it is not tied to a request. Unserialization is done
     * globally (not for each plugin). 
     *
     * @param boolean true if a mapRequest, false if mapResult
     * @param mixed mapRequest or mapResult
     * @return mixed mapRequest|Result or NULL if no such request|result
     */
    public function getRequest($isRequest, $mapRequest) {

        $type = $isRequest ? 'Request' : 'Result';
        
        $name = $this->getName();
        $field = "${name}${type}";

        if (empty($mapRequest->$field))
            return NULL;

        return $mapRequest->$field;
    }

    /** 
     * Returns name of parent plugin in case of plugin extension
     *
     * Must be overridden in child plugin class
     * @return string
     */
    public function replacePlugin() {
        return null;
    }

    /**
     * Plugin initialization (can be extended in custom plugins)
     */
    public function initialize() {}
    
}

?>
