<?php
/**
 * @package Server
 * @version $Id$
 */

/**
 * Class to manage the caching of the SOAP XML.
 * 
 * Cache cleaning is done by cleaning script.
 *
 * @package Server
 * @author Yves Bolognini <yves.bolognini@camptocamp.com> 
 */
class SoapXMLCache {
    private $log;

    private $soapXMLFile;
    private $mapId;
    private $cartoserver;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        
        if (array_key_exists('mapId', $_REQUEST))
            $this->mapId = $_REQUEST['mapId'];  
            
        $this->cartoserver = new Cartoserver();             
    }

    private function getDigest($soapRequest) {
    
        return md5($soapRequest);
    }

    private function printSoapXMLFromServer($soapRequest) {

        $server = setupSoapService($this->cartoserver);

        ob_start();
        $server->handle();
        $data = ob_get_contents();

        ob_end_flush();
        
        return $data;
    }

    private function getSoapXMLFile($soapRequest) {
        
        if (!$this->soapXMLFile) {
       
            $this->soapXMLFile = $this->cartoserver->getServerContext($this->mapId)->config->writablePath . 
                            'soapxml_cache/soapXML.' . $this->getDigest($soapRequest);
        }
        return $this->soapXMLFile;    
    }

    private function cacheSoapXML($soapRequest) {
         
        $soapXML = $this->printSoapXMLFromServer($soapRequest);
        $amount = file_put_contents($this->getSoapXMLFile($soapRequest), $soapXML);
        if ($amount != strlen($soapXML)) {
            throw new CartoserverException('could not write soapXML cache');
        }
    }

    private function readSoapXML($soapRequest) {

        $soapXML = file_get_contents($this->getSoapXMLFile($soapRequest));
        if ($soapXML === FALSE) {
            throw new CartoserverException('could not read cached soapXML'); 
        }
        print $soapXML;
    }

    private function skipCache($soapRequest) {
        return $this->cartoserver->getServerContext($this->mapId)->config->noSoapXMLCache;
    }

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
            $this->cacheSoapXML($soapRequest);   
            return;
        }
        
        $this->log->debug('Returning cached soapXML');
        $soapXML = $this->readSoapXML($soapRequest);   
    }   
}

?>
