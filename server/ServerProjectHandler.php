<?php
/**
 * @package Common
 * @version $Id$
 */

/**
 * Project handler
 */
require_once(CARTOSERVER_HOME . 'common/ProjectHandler.php');

/**
 * Project handler for the server
 *
 * Project name is know by mapId.
 * @package Common
 */
class ServerProjectHandler extends ProjectHandler {

    /**
     * @var string
     */
    public $projectName;
    
    /**
     * @var string
     */
    public $mapId;

    /**
     * Constructor
     * @param string map id
     */
    public function __construct ($mapId) {
        $this->setByMapId($mapId);
        $this->mapId = $mapId;
    }
    
    /**
     * @see ProjectHandler::getRootPath()
     * @return string
     */
    public function getRootPath() {
        return CARTOSERVER_HOME;
    }
    
    /**
     * @return string
     */
    public function getProjectName() {
        return $this->projectName;
    }
   
    /**
     * Sets projectName and mapName, extracted from mapId value.
     * @param string
     */
    private function setByMapId ($mapId) {
        if (strpos($mapId, '.')) {
            list($this->projectName, $this->mapName) = explode('.', $mapId);
        } else {
            $this->projectName = ProjectHandler::DEFAULT_PROJECT;
            $this->mapName = $mapId;
        }      
    }

}
?>
