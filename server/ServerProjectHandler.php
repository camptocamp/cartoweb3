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

    function __construct ($mapId) {
        $this->setByMapId($mapId);
    }

    function getProjectName () {
        return $this->projectName;
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