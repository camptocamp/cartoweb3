<?php
/**
 * Client MapInfo caching
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
 * Client side MapInfo cache implementation
 * 
 * @package Client
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com> 
 */
class ClientMapInfoCache extends MapInfoCache {

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var Cartoclient
     */
    private $cartoclient;

    /**
     * Constructor
     * @param Cartoclient
     */
    public function __construct(CartoClient $cartoclient) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->cartoclient = $cartoclient;
        parent::__construct($cartoclient->getConfig(), 
                        $cartoclient->getConfig()->mapId);
    }    

    /**
     * @see MapInfoCache::computeMapInfo()
     */
    protected function computeMapInfo() {
        return $this->cartoclient->getCartoserverService()
                                        ->getMapInfo($this->getMapId());
    }
    
    /**
     * Checks if MapInfo is up-to-date, reload it from server if it's not
     * @param int timestamp
     * @param string mapId
     */
    public function checkMapInfoTimestamp($timestamp) {

        if (!$this->getCacheTimestamp()) {
            $this->log->debug('no timestamp from cache, skipping');
            return;
        }
        if ($this->skipCache()) {
            $this->log->debug('not caching mapInfo, skipping test');
            return;
        }
        if ($timestamp == $this->getCacheTimestamp()) {
            $this->log->debug("mapInfo in sync with cache, no refetch");
            return;
        }
        
        $this->log->debug('Timestamp changed, invalidating cache');   
        $this->cacheMapInfo();   
    }
}
?>
