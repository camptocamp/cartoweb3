<?php
/**
 * General configuration classes
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
 * Main configuration 
 * @package Common
 */
abstract class Config {

    /**
     * @var ProjectHandler
     */
    public $projectHandler;

    /**
     * Array of string which contains contents of .ini configuration file
     * @var array
     */
    protected $ini_array;

    /**
     * Returns type of config ('client' or 'server')
     * @return string
     */
    abstract public function getKind();

    /**
     * Returns the client or server root path
     * @return string
     */
    abstract public function getBasePath();

    /**
     * Property access method
     *
     * Will return value set in .ini files or NULL if it doesn't exist
     * !! WARNING: do not use empty() to test agains properties returned
     * by __get(). It will be always empty !!
     * @param string index
     * @return string value
     */
    public function __get($nm) {
        if (isset($this->ini_array[$nm])) {
            $r = $this->ini_array[$nm];
            return $r;
        } else {
            return NULL;
        }
    }

    /**
     * Return the name of the parameters which will be automatically set 
     * from the current profile (overriding the one from configuration).
     * This method should be overriden to add more parameters. The overrider
     * should merge the array with its parent. 
     * 
     * @return array Parameters which should be true when profile is development
     *   or false in production.
     */
    protected function getProfileParameters() {
        return array('useWsdl', 'noWsdlCache', 'noMapInfoCache', 
                     'developerIniConfig', 'allowTests', 'showDevelMessages');   
    }

    /**
     * Set parameters values according to the current profile
     * @see getProfileParameters()
     */
    private function updateProfileParameters() {
        
        if (!$this->profile)
            return;
        
        switch ($this->profile) {
            case 'development':
                $parametersValues = true;               
                break;
            case 'production':
                $parametersValues = false;               
                break;
            case 'custom':
                return;
            default:
                throw new CartocommonException('Invalid profile value: ' . 
                                                $this->profile);
                break;
        }
        
        foreach($this->getProfileParameters() as $profileParameter) {
            $this->ini_array[$profileParameter] = $parametersValues;   
        }
    }
    
    /**
     * Sets mapId
     *
     * Should be called in plugins initialize method.
     * @param string
     */
    public function setMapId($mapId) {

        // Set MapName in projectHandler
        $this->projectHandler->mapName = $mapId;
             
        // Set MapId to projectName.mapId if mapId does not contain a project already
        $projectName = $this->projectHandler->getProjectName();
        if ($projectName && strpos($mapId, '.') === false) {
            $this->ini_array['mapId'] = $projectName . '.' . $mapId;
        }
    }

    /**
     * Constructor
     *
     * Reads project's and default .ini file, sets project handler's mapId
     * and initializes paths.
     * @param ProjectHandler
     */
    public function __construct($projectHandler) {

        $this->projectHandler = $projectHandler;

        $kind = $this->getKind();

        $file = $kind . '.ini';
        $path = $kind . '_conf/'; 
        if (!@$this->configPath) {
            $this->configPath = $this->getBasePath()
                . $this->projectHandler->getPath($path, $file);
        }

        $defaultPath = $this->getBasePath() . $path;
        if ($defaultPath != $this->configPath) {
            $this->ini_array = parse_ini_file($defaultPath . $file);
        } else {
            $this->ini_array = array();
        }
        $this->ini_array = array_merge($this->ini_array,
                                       parse_ini_file($this->configPath . $file));

        if (array_key_exists('mapId', $this->ini_array)) {
            
            $this->setMapId($this->ini_array['mapId']);
        }
        
        if (!@$this->writablePath)
            $this->writablePath = $this->getBasePath() . 'www-data/';

        if (!@$this->pluginsPath)
            $this->pluginsPath = $this->getBasePath() . 'plugins/';
    
        $this->updateProfileParameters();
    }

    /**
     * Returns protected var $ini_array.
     * @return array
     */
    public function getIniArray() {
        return $this->ini_array;
    }
}

/**
 * Configuration for plugins
 * @package Common
 */
abstract class PluginConfig extends Config {

    /** 
     * @var BasePlugin
     */
    protected $plugin;

    /**
     * Returns directory where .ini are located
     *
     * Directory returned is relative to client_conf/server_conf.
     * @return string path
     */
    abstract public function getPath(); 

    /**
     * Constructor
     *
     * Reads project's and default plugin .ini file, if they exist.
     * @param BasePlugin
     * @param ProjectHandler
     */
    public function __construct($plugin, $projectHandler) {

        $this->projectHandler = $projectHandler;

        $this->plugin = $plugin;
        
        $kind = $this->getKind();
        $path = $this->getPath();

        $file = $plugin . '.ini';
        $path = $kind . '_conf/' . $path; 
        if (!@$this->configPath) {
            $this->configPath = $this->getBasePath()
                . $this->projectHandler->getPath($path, $file);
        }

        $this->ini_array = array();
        
        $defaultPath = $this->getBasePath() . $path;
        if ($defaultPath != $this->configPath && file_exists($defaultPath . $file)) {
            $this->ini_array = parse_ini_file($defaultPath . $file);
        }

        if (file_exists($this->configPath . $file)) {
            $this->ini_array = array_merge($this->ini_array,
                                           parse_ini_file($this->configPath . $file));
        }
    }
}

?>
