<?php
/**
 * General configuration classes
 *
 * @package Common
 * @version $Id$
 */

/**
 * Main configuration 
 *
 * @package Common
 */
abstract class Config {

    public $basePath;
    public $projectHandler;

    protected $ini_array;

    /**
     * Returns type of config ('client' or 'server')
     */
    abstract function getKind();

    /**
     * Property access method
     *
     * Will return value set in .ini files or NULL if it doesn't exist
     * !! WARNING: do not use empty() to test agains properties returned
     * by __get(). It will be always empty !!
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
     */
    function __construct($projectHandler) {

        $this->projectHandler = $projectHandler;

        $kind = $this->getKind();

        $file = $kind . '.ini';
        $path = $kind . '_conf/'; 
        if (!@$this->configPath) {
            $this->configPath = $this->basePath
                . $this->projectHandler->getPath($this->basePath, $path, $file);
        }

        $defaultPath = $this->basePath . $path;
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
            $this->writablePath = $this->basePath . 'www-data/';

        if (!@$this->pluginsPath)
            $this->pluginsPath = $this->basePath . 'plugins/';
    }
}

/**
 * Configuration for plugins
 * 
 * @package Common
 */
abstract class PluginConfig extends Config {

    protected $plugin;

    /**
     * Returns directory where .ini are located
     *
     * Directory returned is relative to client_conf/server_conf.
     */
    abstract function getPath(); 

    /**
     * Constructor
     *
     * Reads project's and default plugin .ini file, if they exist.
     */
    function __construct($plugin, $projectHandler) {

        $this->projectHandler = $projectHandler;

        $this->plugin = $plugin;
        
        $kind = $this->getKind();
        $path = $this->getPath();

        $file = $plugin . '.ini';
        $path = $kind . '_conf/' . $path; 
        if (!@$this->configPath) {
            $this->configPath = $this->basePath
                . $this->projectHandler->getPath($this->basePath, $path, $file);
        }

        $this->ini_array = array();
        
        $defaultPath = $this->basePath . $path;
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
