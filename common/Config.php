<?php
/**
 * General configuration classes
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
    abstract function getKind();

    /**
     * Returns the client or server root path
     * @return string
     */
    abstract function getBasePath();

    /**
     * Property access method
     *
     * Will return value set in .ini files or NULL if it doesn't exist
     * !! WARNING: do not use empty() to test agains properties returned
     * by __get(). It will be always empty !!
     * @param string index
     * @return string value
     */
    function __get($nm) {
        if (isset($this->ini_array[$nm])) {
            $r = $this->ini_array[$nm];
            return $r;
        } else {
            return NULL;
        }
    }

    /**
     * Constructor
     *
     * Reads project's and default .ini file, sets project handler's mapId
     * and initializes paths.
     * @param ProjectHandler
     */
    function __construct($projectHandler) {

        $this->projectHandler = $projectHandler;

        $kind = $this->getKind();

        $file = $kind . '.ini';
        $path = $kind . '_conf/'; 
        if (!@$this->configPath) {
            $this->configPath = $this->getBasePath()
                . $this->projectHandler->getPath($this->getBasePath(), $path, $file);
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
            
            $mapId = $this->ini_array['mapId'];
            // Set MapName in projectHandler
            $this->projectHandler->mapName = $mapId;
                
            // Set MapId to projectName.mapId if mapId does not contain a project already
            $projectName = $this->projectHandler->getProjectName();
            if ($projectName && strpos($mapId, '.') === false) {
                $this->ini_array['mapId'] = $projectName . '.' . $mapId;
            }
        }
        
        if (!@$this->writablePath)
            $this->writablePath = $this->getBasePath() . 'www-data/';

        if (!@$this->pluginsPath)
            $this->pluginsPath = $this->getBasePath() . 'plugins/';
    }

    /**
     * Returns protected var $ini_array.
     */
    function getIniArray() {
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
    abstract function getPath(); 

    /**
     * Constructor
     *
     * Reads project's and default plugin .ini file, if they exist.
     * @param BasePlugin
     * @param ProjectHandler
     */
    function __construct($plugin, $projectHandler) {

        $this->projectHandler = $projectHandler;

        $this->plugin = $plugin;
        
        $kind = $this->getKind();
        $path = $this->getPath();

        $file = $plugin . '.ini';
        $path = $kind . '_conf/' . $path; 
        if (!@$this->configPath) {
            $this->configPath = $this->getBasePath()
                . $this->projectHandler->getPath($this->getBasePath(), $path, $file);
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
