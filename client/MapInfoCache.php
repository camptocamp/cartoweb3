<?php
/**
 * MapInfo caching
 * @package Client
 * @version $Id$
 */

/**
 * Class to manage the caching of the MapInfo server returned value.
 * @package Client
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com> 
 */
class MapInfoCache {

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var Cartoclient
     */
    private $cartoclient;
    
    /**
     * @var int
     */
    private $timeStamp;

    /**
     * Constructor
     * @param Cartoclient
     */
    public function __construct(CartoClient $cartoclient) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        
        $this->cartoclient = $cartoclient;
    }

    /**
     * Gets MapInfo from the server
     * @param string mapId
     * @return MapInfo MapInfo
     */
    private function getMapInfoWithService($mapId) {

        return $this->cartoclient->getCartoserverService()->getMapInfo($mapId);
    }

    /**
     * Gets MapInfo cache file name
     * @param string mapId
     * @return MapInfo MapInfo
     */
    private function getMapInfoFile($mapId) {
        
        return $this->cartoclient->getConfig()->writablePath . 
                            'mapinfo_cache/mapInfo.' . $mapId;    
    }

    /**
     * Writes MapInfo cache file and returns MapInfo
     * @param string mapId
     * @return MapInfo MapInfo
     */
    private function cacheMapInfo($mapId) {
         
        $mapInfo = $this->getMapInfoWithService($mapId);
        $mapInfoFile = $this->getMapInfoFile($mapId);
        $mapInfoSerialized = serialize($mapInfo);        
        $amount = file_put_contents($mapInfoFile, $mapInfoSerialized);
        if ($amount != strlen($mapInfoSerialized)) {
            throw new CartoclientException('could not write mapInfo cache');
        }
        return $mapInfo;
    }

    /**
     * Reads MapInfo from file and unserializes it
     * @param string mapId
     * @return MapInfo MapInfo
     */
    private function readMapInfo($mapId) {

        $mapInfoFile = $this->getMapInfoFile($mapId);
        $mapInfoSerialized = file_get_contents($mapInfoFile);
        if ($mapInfoSerialized === FALSE) {
            throw new CartoclientException('could not read cartoclient cached file'); 
        }
        return unserialize($mapInfoSerialized);
    }

    /**
     * Returns true if cache is disabled
     * @return boolean
     */
    private function skipCache() {
        // TODO: check if the mapInfo cache is useful in direct mode
        $cartoclient = $this->cartoclient;
        return $cartoclient->getConfig()->noMapInfoCache;
    }

    /**
     * Checks if MapInfo is up-to-date, reload it from server if it's not
     * @param int timestamp
     * @param string mapId
     */
    public function checkMapInfoTimestamp($timeStamp, $mapId) {

        if ($this->skipCache()) {
            $this->log->debug('not caching mapInfo, skipping test');
            return;
        }
        if ($timeStamp == $this->timeStamp) {
            $this->log->debug("mapInfo in sync with cache, no refetch");
            return;
        }
        
        $this->log->debug('Timestamp changed, invalidating cache');   
        $this->cacheMapInfo($mapId);   
    }

    /**
     * Returns MapInfo using cache
     * @param string mapId
     * @return MapInfo MapInfo
     */
    private function doGetMapInfo($mapId) {
        
        $cartoclient = $this->cartoclient;
        $mapInfoFile = $this->getMapInfoFile($mapId);    

        if ($this->skipCache()) {
            $this->log->debug('not caching mapInfo, calling service');
            return $this->getMapInfoWithService($mapId);
        }
        
        $forceUpdate = false; 
        if ($forceUpdate || !file_exists($mapInfoFile)) {
            return $this->cacheMapInfo($mapId);   
        }
        
        return $this->readMapInfo($mapId);   
    }   
    
    /**
     * Returns MapInfo and update time stamp for next check
     * @param string mapId
     * @return MapInfo MapInfo
     */
    public function getMapInfo($mapId) {
     
        $mapInfo = $this->doGetMapInfo($mapId);
        $this->timeStamp = $mapInfo->timeStamp;
        return $mapInfo;
    }
}

?>
