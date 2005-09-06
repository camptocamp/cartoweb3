<?php
/**
 *
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
 * Project handler
 */
require_once(CARTOWEB_HOME . 'common/ProjectHandler.php');

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
        return CARTOWEB_HOME;
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
            throw new CartoserverException('Mapid should be in projectName.mapName syntax');
        }      
    }

}
?>
