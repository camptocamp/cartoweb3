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

    public $projectName;
    public $mapId;

    function __construct ($mapId) {
        $this->setByMapId($mapId);
        $this->mapId = $mapId;
    }
    
    /**
     * @see ProjectHandler::getRootPath()
     */
    function getRootPath() {
        return CARTOSERVER_HOME;
    }
    
    function getProjectName () {
        return $this->projectName;
    }
    
    function setByMapId ($mapId) {
        if (strpos($mapId, '.')) {
            list($this->projectName, $this->mapName) = explode('.', $mapId);
        } else {
            $this->projectName = 'default';
            $this->mapName = $mapId;
        }      
    }

}
?>