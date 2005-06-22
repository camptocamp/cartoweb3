<?php
/**
 * Server MapInfo caching
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
 * @package Client
 * @version $Id$
 */

require_once(CARTOCOMMON_HOME . 'common/MapInfoCache.php');

/**
 * Server side MapInfo cache implementation
 * 
 * @package Server
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com> 
 */
class ServerMapInfoCache extends MapInfoCache {

    /**
     * @var Logger
     */
    private $log;
 
    /**
     * @var MapInfoHandler
     */
    private $mapInfoHandler;

    /**
     * @see MapInfoCache::computeMapInfo()
     */
    protected function computeMapInfo() {

        return $this->mapInfoHandler->loadMapInfo();
    }
 
    /**
     * @see MapInfoCache::getMapInfoFile()
     */
    protected function getMapInfoFile() {
        
        return parent::getMapInfoFile() . '.server';    
    }
 
    /**
     * @see MapInfoCache::computeMapInfo()
     */
    protected function isCacheValid() {

        $nowTimestamp = $this->mapInfoHandler->getServerContext()->getTimestamp();
        return $nowTimestamp == $this->getCacheTimestamp();
    }
 
    /**
     * Constructor
     * @param Cartoclient
     */
    public function __construct(MapInfoHandler $mapInfoHandler) {

        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->mapInfoHandler = $mapInfoHandler;
        parent::__construct($mapInfoHandler->getServerContext()->getConfig(),
                            $mapInfoHandler->getServerContext()->getMapId());
    }    
}

?>