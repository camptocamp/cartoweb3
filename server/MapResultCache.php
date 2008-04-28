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
 * @package Server
 * @version $Id$
 */

/**
 * Class to manage the caching of the MapResults.
 * 
 * Cache cleaning is done by images cache cleaning script.
 *
 * @package Server
 * @author Yves Bolognini <yves.bolognini@camptocamp.com> 
 */
class MapResultCache {

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var Cartoserver
     */
    private $cartoserver;

    /**
     * True if the the last mapResult should not be cached.
     * @var boolean
     */
    private $skipCaching = false;

    /**
     * Constructor
     * @param Cartoserver
     */
    public function __construct(CartoServer $cartoserver) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        
        $this->cartoserver = $cartoserver;
    }

    /**
     * Returns MD5 of serialized map request.
     * @param MapRequest
     * @return string
     */
    private function getDigest($mapRequest) {
    
        return md5(serialize($mapRequest));
    }

    /**
     * @param MapRequest
     * @return MapResult
     */
    private function getMapResultFromServer($mapRequest) {

        return $this->cartoserver->cacheGetMap($mapRequest);
    }

    /**
     * Returns map result cache file location.
     * @param MapRequest
     * @return string
     */
    private function getMapResultFile($mapRequest) {
        
        return $this->cartoserver->getServerContext($mapRequest->mapId)->
                      getConfig()->writablePath . 'mapresult_cache/mapResult.' .
                      $this->getDigest($mapRequest);
    }

    /**
     * Sets the skipCaching variable, which will skip the cache of the mapResult
     * if skipCaching is true. 
     * It can be used not to put error messages in cache.
     */
    public function setSkipCaching($skipCaching) {
        $this->skipCaching = $skipCaching;
    }

    /**
     * Saves map result in cache file.
     * @param MapRequest
     * @param string
     * @return MapResult
     */
    private function cacheMapResult($mapRequest, $mapResultFile) {
         
        $mapResult = $this->getMapResultFromServer($mapRequest);
        if ($this->skipCaching) {
            $this->log->debug('SkipCaching is true, result will not be put ' .
                    'into the cache');
            return $mapResult;
        }
        $mapResultSerialized = serialize($mapResult);        
        $amount = file_put_contents($mapResultFile, $mapResultSerialized);
        if ($amount != strlen($mapResultSerialized)) {
            throw new CartoserverException('could not write mapResult cache');
        }
        return $mapResult;
    }

    /**
     * Reads map result from cache file.
     * @param MapRequest
     * @param string
     * @return MapResult
     */
    private function readMapResult($mapRequest, $mapResultFile) {

        $mapResultSerialized = file_get_contents($mapResultFile);
        if ($mapResultSerialized === FALSE) {
            throw new CartoserverException('could not read cached mapResult');
        }
        return unserialize($mapResultSerialized);
    }

    /**
     * Tells if map result caching is deactivated.
     * @param MapRequest
     * @return boolean true => cache is OFF
     */
    private function skipCache($mapRequest) {

        if ($this->cartoserver->getServerContext($mapRequest->mapId)->
               getConfig()->noMapResultCache)
            return true;

        // If we are not in direct mode (coreplugin classes not loaded will 
        //  tell us), then we skip the caching, as it will fail because the
        //  plugin manager has not loaded required classes used for 
        //  deserialisation of the mapResult.
        //  => SoapXml cache should be used instead    
        if (!class_exists('ImagesResult')) {
            $this->log->warn('Non direct mode detected. MapResult cache will not ' .
                    'be used (core plugin classes not loaded for deserialisation)');
            return true;
        }
        return false;
    }

    /**
     * Retrieved map result.
     *
     * If cache is OFF: computes map result.
     * If cache is ON and first call: computes map result and prepares caching
     * for a possible second call.
     * If cache is ON and second call: computes map result and caches it.
     * If cache is ON and Nth call (N > 2): return cached map result.
     * @param MapRequest
     * @return MapResult
     */
    public function getMap($mapRequest) {
    
        $mapResultFile = $this->getMapResultFile($mapRequest);
        
        if ($this->skipCache($mapRequest)) {
            $this->log->debug('not caching mapResult, calling server');
            return $this->getMapResultFromServer($mapRequest);
        }

        if (!file_exists($mapResultFile)) {
            $this->log->debug('first call, not caching mapResult, calling server');
            touch($mapResultFile);
            return $this->getMapResultFromServer($mapRequest);
        }
        
        if (filesize($mapResultFile) == 0) {
            $this->log->debug('second call, caching mapResult');            
            Accounting::getInstance()->account('general.cache_id', md5($mapResultFile));         
            return $this->cacheMapResult($mapRequest, $mapResultFile);
        }
        
        $this->log->debug('Returning cached mapResult');

        Accounting::getInstance()->account('general.cache_hit', md5($mapResultFile));         
        $mapResult = $this->readMapResult($mapRequest, $mapResultFile);
        // FIXME: there is no config loaded there, messages are always sent.
        // PERFORMANCE: remove this if too much impact (time + network size)
        if (isset($mapResult->serverMessages) && 
            is_array($mapResult->serverMessages)) {
            $mapResult->serverMessages[] = 
                new Message('mapResult returned from cache', 
                            Message::CHANNEL_DEVELOPER);
        }
        return $mapResult;
    }   
}

?>
