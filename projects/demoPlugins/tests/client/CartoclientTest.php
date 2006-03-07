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
 * @package Tests
 * @version $Id$
 */

require_once('client/CartoclientTest.php');

/**
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */ 
 class projects_demoPlugins_client_CartoclientTest extends client_CartoclientTest {
    
    protected function getProjectName() {
    
        return 'demoPlugins';
    }
    
    protected function getMapId() {

        return 'demoPlugins';
    }    
}

?>