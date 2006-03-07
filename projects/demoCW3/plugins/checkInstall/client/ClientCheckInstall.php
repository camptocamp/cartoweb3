<?php
/**
 * Demo installation check plugin
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
 * @package Plugins
 * @version $Id$
 */

/**
 * Plugin which shows a failure message in case the demo data is not installed
 * @package Plugins
 */
class ClientCheckInstall extends ClientPlugin {

    /**
     * Checks if the demo data are available, and shows a failure if not.
     * 
     * WARNING: keep in sync with demoPlugins project!
     */
    protected function checkDemoData() {
        $projectHandler = $this->getCartoclient()->getProjectHandler();
        $project = $projectHandler->getProjectName();
        $mapId = $projectHandler->getMapName();

        if (strpos($mapId, '.')) {
            list($project, $mapId) = explode('.', $mapId);
        }

        if (!file_exists(CARTOWEB_HOME . 
                            "projects/$project/server_conf/$mapId/data")) {
            throw new CartoclientException('You need to install the demo data ' .
                    "in order to use the demo.\n Use the --fetch-demo parameter " .
                    "of the cw3setup.php installer, \n or have a look at the " .
                    'CartoWeb Installation section of the manual on ' .
                    'http://www.cartoweb.org/'); 
        }
    }

    /**
     * @see PluginBase::initialize()
     */
    public function initialize() {
        $this->checkDemoData();
    }    
}
?>
