<?php
/**
 * Server part of PDF export plugin.
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
 * @copyright 2005 Camptocamp SA
 * @package Plugins
 * @version $Id$
 */

/**
 * Server part of ExportPdf plugin
 * @package Plugins
 */
class ServerExportPdf extends ServerPlugin
                      implements InitProvider {

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
     * @see InitProvider::getInit()
     */
    public function getInit() {
        $this->log->debug('setting exportPdf init');

        $msMapObj = $this->serverContext->getMapObj();
        
        $init = new ExportPdfInit();
        $init->mapServerResolution = (int)$msMapObj->resolution;
        $init->legendIconWidth = (int)$msMapObj->keysizex;
        $init->legendIconHeight = (int)$msMapObj->keysizey;
        return $init;
    }
}

?>
