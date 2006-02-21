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
 * Class to manage the caching of the SOAP XML
 * 
 * Cache cleaning is done by cleaning script.
 * @package Server
 * @author Yves Bolognini <yves.bolognini@camptocamp.com> 
 */
class SoapXMLCache {

    /**
     * @var Logger
     */
    private $log;

    /**
     * Cache file name
     * @var string
     */
    private $soapXMLFile;
    
    /**
     * @var string
     */
    private $mapId;
    
    /**
     * @var Cartoserver
     */
    private $cartoserver;

    /**
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        
        if (array_key_exists('mapId', $_REQUEST))
            $this->mapId = $_REQUEST['mapId'];  
            
        $this->cartoserver = new Cartoserver();             
    }

    /**
     * Returns MD5 code of XML soap request     
     * @param string        
     * @return string
     */
    private function getDigest($soapRequest) {
    
        return md5($soapRequest);
    }

    /**
     * Retrieves and prints XML data from server
     * @param string
     * @return string
     */
    private function printSoapXMLFromServer($soapRequest) {

        $server = setupSoapService($this->cartoserver);

        ob_start();
        $server->handle();
        $data = ob_get_contents();

        ob_end_flush();
        
        return $data;
    }
    
    /**
     * Computes cache file name
     * @param string
     * @return string
     */
    private function getSoapXMLFile($soapRequest) {
        
        if (!$this->soapXMLFile) {
            $this->soapXMLFile = $this->cartoserver->
                                     getServerContext($this->mapId)->getConfig()->
                                     writablePath 
                                 . 'soapxml_cache/soapXML.' 
                                 . $this->getDigest($soapRequest);
        }
        return $this->soapXMLFile;    
    }

    /**
     * Retrieves data and stores in cache
     * @param string
     */
    private function cacheSoapXML($soapRequest) {
         
        $soapXML = $this->printSoapXMLFromServer($soapRequest);
        $amount = file_put_contents($this->getSoapXMLFile($soapRequest), 
                                    $soapXML);
        if ($amount != strlen($soapXML)) {
            throw new CartoserverException('could not write soapXML cache');
        }
    }

    /**
     * Reads and prints cached content
     * @param string
     * @return string
     */
    private function readSoapXML($soapRequest) {

        $soapXML = file_get_contents($this->getSoapXMLFile($soapRequest));
        if ($soapXML === FALSE) {
            throw new CartoserverException('could not read cached soapXML'); 
        }
        print $soapXML;
    }

    /** 
     * Returns true if cache shouldn't be used
     * @param string
     * @return boolean
     */
    private function skipCache($soapRequest) {
        return $this->cartoserver->getServerContext($this->mapId)->getConfig()->
                   noSoapXMLCache;
    }

    /**
     * Prints SOAP XML, cache if necessary
     * 
     * Possible cases:
     * - Cache not used --> reads from server
     * - Still not cached, first call --> reads from server
     * - Still not cached, second call --> reads from server, writes cache
     * - Cached --> reads from cache
     * @param string
     */
    public function printSoapXML($soapRequest) {

        if ($this->skipCache($soapRequest)) {
            $this->log->debug('not caching soapXML, calling server');
            $this->printSoapXMLFromServer($soapRequest);
            return;
        }

        $soapXMLFile = $this->getSoapXMLFile($soapRequest);

        if (!file_exists($soapXMLFile)) {
            $this->log->debug('first call, not caching soapXML, calling server');
            touch($soapXMLFile);
            $this->printSoapXMLFromServer($soapRequest);
            return;
        }
        
        if (filesize($soapXMLFile) == 0) {
            $this->log->debug('second call, caching soapXML');
            Accounting::getInstance()->account('general.cache_id', md5($soapXMLFile));         
            $this->cacheSoapXML($soapRequest);   
            return;
        }
        
        Accounting::getInstance()->account('general.cache_hit', md5($soapXMLFile));         
        Accounting::getInstance()->setCacheHit();
        $this->log->debug('Returning cached soapXML');
        $soapXML = $this->readSoapXML($soapRequest);   
    }   
}

?>
