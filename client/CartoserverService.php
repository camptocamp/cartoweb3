<?php
/**
 * @package Client
 * @version $Id$
 */
require_once('log4php/LoggerManager.php');
require_once(CARTOCOMMON_HOME . 'common/Serializable.php');

/**
 * @package Client
 */
class CartoserverService {
    private $log;
    private $config;

    private $cartoserver;

    function __construct($config) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->config = $config;
    }

    private function callDirect($function, $argument) {

        // read by cartoserver to tell its mode
        $direct_access = true;

        if (isset($this->config->cartoserverHome))
            $cartoserverHome = $this->config->cartoserverHome;
        else
            $cartoserverHome = CARTOCLIENT_HOME;

        require_once($cartoserverHome . 'server/Cartoserver.php');

        $result = $this->getCartoserver()->$function($argument);
    
        if ($result instanceof SoapFault) {
            throw $result;
        }
        return $result;
    }

    private function getCartoserver() {
        if (!$this->cartoserver) {
            $this->cartoserver = new Cartoserver();
        }
        return $this->cartoserver;
    }
    
    private function getCartoserverUrl() {

        $url = '';
        if (@$this->config->cartoserverUrl)
            $url = $this->config->cartoserverUrl;

        // in config ?
        $guessCartoserver = true;

        if ($url == '' && $guessCartoserver && $_SERVER['PHP_SELF'] != '') {
            $url = (isset($_SERVER['HTTPS']) ? "https://" : "http://" ) . 
                $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . 
                '/cartoserver.wsdl.php';
        }

        if ($url == '' )
            throw new CartoclientException("No cartoserver Url set in config file");
        else
            return $url . '?mapId=' . $this->config->mapId;
    }

    private function getCartoserverScriptUrl() {

        $url = '';
        if (@$this->config->cartoserverScriptUrl)
            $url = $this->config->cartoserverScriptUrl;

        if ($url == '' && $_SERVER['PHP_SELF'] != '') {
            $url = (isset($_SERVER['HTTPS']) ? "https://" : "http://" ) . 
                $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . 
                '/server.php';
        }

        if ($url == '' )
            throw new CartoclientException("No cartoserver Script Url set in config file");
        else
            return $url . '?mapId=' . $this->config->mapId;
    }

    private function callFunction($function, $argument, $replayTrace=false) {

        if ($this->config->cartoserverDirectAccess) {
            $mapResult = $this->callDirect($function, $argument);
        } else {

            if (!$this->config->noWsdlCache) {
                ini_set('soap.wsdl_cache_enabled', '0');
            }
            $wsdlCacheDir = $this->config->writablePath . 'wsdl_cache';
            if (is_writable($wsdlCacheDir))
                ini_set("soap.wsdl_cache_dir", $wsdlCacheDir);

            $options = $replayTrace === true ? array('trace' => 1) : array();
            
            if (@$this->config->cartoserverUseWsdl) {
                $client = new SoapClient($this->getCartoserverUrl(), $options);
            } else {
                $options['location'] = $this->getCartoserverScriptUrl();;
                $options['uri'] = 'foo';
                $client = new SoapClient(null, $options);
            }            

            try {
                $mapResult = $client->$function($argument);
            } catch (SoapFault $fault) {
                if ($fault->faultstring != "looks like we got no XML document")
                    throw $fault;

                // the rest of this handler launches the SOAP request again, 
                //  and concatenats the bad xml received to the error message.
            
                if ($replayTrace == true) {
                    return $client->__getLastResponse();
                }

                $xmlOutput = $this->callFunction($function, $argument, true);
                $fault->faultstring = "looks like we got no XML document : $xmlOutput";
                throw $fault;
            }
            
            $unserializeMap = array('getMapInfo' => 'MapInfo',
                                    'getMap' => 'MapResult');
            if (array_key_exists($function, $unserializeMap)) {
                $targetType = $unserializeMap[$function];
                $mapResult = Serializable::unserializeObject($mapResult, 
                                                            NULL, $targetType);
            }
        }
        return $mapResult;
    }

    function getMapInfo($mapId) {
        return $this->callFunction('getMapInfo', $mapId);
    }

    function getMap($mapRequest) {
        return $this->callFunction('getMap', $mapRequest);
    }
}
?>