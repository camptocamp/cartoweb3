<?php
/**
 * @package Common
 * @version $Id$
 */

/**
 * Project handler
 */
require_once(CARTOCOMMON_HOME . 'coreplugins/project/common/ProjectHandler.php');

/**
 * @package Common
 */
abstract class Config {
    public $basePath;
    public $projectHandler;

    private $ini_array;

    abstract function getKind();

    function __get($nm) {
        if (isset($this->ini_array[$nm])) {
            $r = $this->ini_array[$nm];
            return $r;
        } else {
            return NULL;
        }
    }

    // !! WARNING: do not use empty() to test agains properties returned
    //  by __get(). It will be always empty !!

    function __construct() {

        $kind = $this->getKind();

        $file = $kind . '.ini';
        if (!@$this->configPath) {
            $path = $kind . '_conf/'; 
            $this->configPath = $this->basePath
                . $this->projectHandler->getPath($this->basePath, $path, $file);
        }

        $this->ini_array = parse_ini_file($this->configPath . $file);

        // Set MapId to projectName.mapId
        $projectName = $this->projectHandler->getProjectName();
        if ($projectName) {
            $this->ini_array['mapId'] = $projectName . '.' . $this->ini_array['mapId'];
        }
        
        if (!@$this->writablePath)
            $this->writablePath = $this->basePath . 'www-data/';

        if (!@$this->pluginsPath)
            $this->pluginsPath = $this->basePath . 'plugins/';
    }
}
?>