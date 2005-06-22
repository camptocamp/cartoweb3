<?php
/**
 * Common abstract MapInfo caching
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
 
/**
 * Class to manage the caching of the MapInfo server returned value.
 * 
 * @package Common
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com> 
 */
abstract class MapInfoCache {

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var string
     */
    private $mapId;
    
    /**
     * @var int
     */
    private $cacheTimestamp;
    
    /**
     * Constructor
     * @param Config
     */
    public function __construct(Config $config, $mapId) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        
        $this->config = $config;
        $this->mapId = $mapId;
    }

    /**
     * Returns the current mapId
     * @return string
     */
    protected final function getMapId() {
        return $this->mapId;
    }

    /**
     * Method called to fetch the Mapinfo. On the client, it will issue a SOAP
     * request, and on the server, it will build the structure out of 
     * configuration files.
     * @return MapInfo
     */
    protected abstract function computeMapInfo();

    /**
     * Gets MapInfo cache file name
     * @param string mapId
     * @return MapInfo MapInfo
     */
    protected function getMapInfoFile() {
        
        return $this->config->writablePath . 
                            'mapinfo_cache/mapInfo.' . $this->mapId;    
    }

    /**
     * Writes MapInfo cache file and returns MapInfo. It can be used by implementing
     * classes to refill the cache when not valid anymore.
     * @param string mapId
     * @return MapInfo MapInfo
     */
    protected function cacheMapInfo() {
        
        $this->log->debug('Caching mapInfo for future use');
        $mapInfo = $this->computeMapInfo();
        $mapInfoFile = $this->getMapInfoFile();
        $mapInfoSerialized = serialize($mapInfo);        
        $amount = file_put_contents($mapInfoFile, $mapInfoSerialized);
        if ($amount != strlen($mapInfoSerialized)) {
            throw new CartocommonException('could not write mapInfo cache');
        }
        return $mapInfo;
    }

    /**
     * Reads MapInfo from file and unserializes it
     * @param string mapId
     * @return MapInfo MapInfo
     */
    private function readCachedMapInfo() {

        $mapInfoFile = $this->getMapInfoFile($this->mapId);
        $mapInfoSerialized = file_get_contents($mapInfoFile);
        if ($mapInfoSerialized === FALSE) {
            throw new CartocommonException('could not read cartoclient cached file'); 
        }
        return unserialize($mapInfoSerialized);
    }

    /**
     * Returns true if cache is disabled. It can be used by implementing classes
     * to check if they should skip the caching.
     * @return boolean
     */
    protected function skipCache() {
        // TODO: check if the mapInfo cache is useful in direct mode
        return $this->config->noMapInfoCache;
    }

    /**
     * Returns the timestamp of the last MapInfo from cache
     * @return int the timestamp of the cached MapInfo, or null if not read
     *   from cache
     */
    protected function getCacheTimestamp() {
        return $this->cacheTimestamp;
    }

    /**
     * This method may be overrided by sublclasses to assert the validity
     * if the MapInfo object read from cache (typically by comparing the 
     * timestamp field: see getCacheTimestamp() ). If it returns false,
     * The MapInfo will be requested again, and put into the cache.
     * @return boolean true if the cache is still valid
     */
    protected function isCacheValid() {
        return true;
    }

    /**
     * Returns MapInfo using cache
     * @param string mapId
     * @return MapInfo MapInfo
     */
    public function getMapInfo() {
        
        if ($this->skipCache()) {
            $this->log->debug('not caching mapInfo, calling computeMapInfo');
            return $this->computeMapInfo();
        }

        $mapInfoFile = $this->getMapInfoFile();    

        if (!file_exists($mapInfoFile)) {
            return $this->cacheMapInfo();   
        }

        $this->log->debug('Reading mapinfo from cache');

        $mapInfo = $this->readCachedMapInfo();   
        $this->cacheTimestamp = $mapInfo->timestamp;
        
        if (!$this->isCacheValid()) {
            $this->log->debug('mapInfo from cache not valid, refetching');
            return $this->cacheMapInfo();
        }
        return $mapInfo;
    }
}
?>