<?php
/**
 * @package Common
 * @version $Id$
 */

/**
 * Project handler
 */
require_once(CARTOSERVER_HOME . 'coreplugins/project/common/ProjectHandler.php');

/**
 * Project handler for the server
 *
 * Project name is know by mapId.
 * @package Common
 */
class ServerProjectHandler extends ProjectHandler {

    public $projectName;
    public $mapName;

    function __construct ($mapId) {
        $this->setByMapId($mapId);
    }

    function getProjectName () {
        return $this->projectName;
    }
    
    function getMapName () {
        return $this->mapName;
    }
    
    function setByMapId ($mapId) {
        if (strpos($mapId, '.')) {
            list($this->projectName, $this->mapName) = explode('.', $mapId);
        } else {
            $this->projectName = NULL;
            $this->mapName = $mapId;
        }      
    }

}
?>