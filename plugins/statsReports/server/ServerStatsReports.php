<?php
/**
 * StatsReports plugin server part 
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
 * @copyright 2007 Camptocamp SA
 * @package Plugins
 * @version $Id$
 */

/**
 * Server StatsReports class
 * @package Plugins
 */
class ServerStatsReports extends ClientResponderAdapter {
    
    /**
     * @var Logger
     */
    private $log;

    /** 
     * Constructor
     */
    public function __construct() {
        $this->log = LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }
    
    /**
     * @param StatsReportsRequest
     * @return StatsReportsResult
     */
    public function initializeRequest($requ) {
      
        $msMapObj = $this->serverContext->getMapObj();
        
        $layerName = 'stats';
        if (!is_null($this->getConfig()->layer)) {
            $layerName = $this->getConfig()->layer;
        }        
        $statsLayer = $msMapObj->getLayerByName($layerName);
        if (empty($statsLayer)) {
            throw new CartoserverException("Layer $layerName not found");        
        }
        
        if ($requ->imageFile == '') {
            $statsLayer->set('status', MS_OFF);
            return new StatsReportsResult();
        }
        
        $statsLayer->set('data', $requ->imageFile);
        
        return new StatsReportsResult();
    }

}
