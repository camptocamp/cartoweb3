<?php
/**
 * Server side accounting management
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
 * @copyright 2006 Camptocamp SA
 * @package Common
 * @version $Id$
 */

require_once(CARTOWEB_HOME . 'common/Accounting.php');

/**
 * Server implementation of accounting management.
 * @package Server
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com> 
 */
class ServerAccountingImpl extends Accounting {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * @see Accounting::getKind()
     */
    protected function getKind() {
        return 'server';
    }

    /**
     * @see Accounting::getMapId()
     */
    protected function getMapId() {

        $serverContext = ServerContext::getInstance();
        return $serverContext->getMapId();
    }

    /**
     * @see Accounting::getConfig()
     */
    protected function getConfig() {

        $serverContext = ServerContext::getInstance();
        return $serverContext->getConfig();
    }    
}

?>
