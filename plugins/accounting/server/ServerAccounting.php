<?php
/**
 * Server accounting plugin
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
 * @package CorePlugins
 * @version $Id$
 */
 
/**
 * Server part of Accounting plugin
 * @package CorePlugins
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com> 
 */
class ServerAccounting extends ServerPlugin {

    /**
     * @var Logger
     */
    private $log;

    /** 
     * Constructor
     */
    public function __construct() {
        
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * Perform general accounting recording 
     */
    public function doAccounting() {

        $accounting = Accounting::getInstance();
        $accounting->pluginLoaded();
        $accounting->account('general.server_version', 0);
    }
}

?>
