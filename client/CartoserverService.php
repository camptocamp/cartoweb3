<?php
/**
 * Service for server calls
 * @package Client
 * @version $Id$
 */
 
require_once('log4php/LoggerManager.php');
require_once(CARTOCOMMON_HOME . 'common/Serializable.php');

/**
 * Wrapper for server calls
 *
 * Hides the calling method (direct or SOAP) from the client.
 * @package Client
 */
class CartoserverService {

    /**
     * @var Logger
     */
    private $log;
    
    /**
     * @var ClientConfig
     */
    private $config;

    /**
     * @var Cartoserver
     */
    private $cartoserver;

    /**
     * @param ClientConfig
     */
    function __construct($config) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->config = $config;
    }

    /**
     * Calls function using direct mode
     * @param string function name
     * @param mixed argument
     * @return mixed function result
     */
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

    /**
     * Returns Cartoserver object, creates it if needed
     * @return Cartoserver Cartoserver object
     */
    private function getCartoserver() {
        if (!$this->cartoserver) {
            $this->cartoserver = new Cartoserver();
        }
        return $this->cartoserver;
    }
    
    /**
     * Constructs Cartoserver URL depending on configuration base URL
     * @param string the script to call (typically server.php or
     * cartoserver.wsdl.php)
     * @return string url
     */
    private function getCartoserverUrl($script) {

        $url = '';
        if (!is_null($this->config->cartoserverBaseUrl))
            $url = $this->config->cartoserverBaseUrl;

        if ($url == '' || $url == '/')
            throw new CartoclientException('No cartoserverBaseUrl set in config file');
        else
            return $url . $script . '?mapId=' . $this->config->mapId;
    }

    /**
     * Calls a function using either direct or SOAP mode
     *
     * In case of SOAP mode:
     * - Calls using WSDL or not depending on configuration
     * - Unserializes result
     * - Generates a understandable message in case of error
     * @param string function name
     * @param mixed argument
     * @param boolean if true, retrieves trace in case of error
     * @return mixed function result
     */
    private function callFunction($function, $argument, $replayTrace=false) {

        if ($this->config->cartoserverDirectAccess) {
            $mapResult = $this->callDirect($function, $argument);
        } else {

            $options = $replayTrace === true ? array('trace' => 1) : array();
            
            if (@$this->config->useWsdl) {
                if (!$this->config->noWsdlCache) {
                    ini_set('soap.wsdl_cache_enabled', '0');
                }
                $wsdlCacheDir = $this->config->writablePath . 'wsdl_cache';
                if (is_writable($wsdlCacheDir))
                    ini_set("soap.wsdl_cache_dir", $wsdlCacheDir);

                $client = new SoapClient($this->getCartoserverUrl('cartoserver.wsdl.php'),
                                         $options);
            } else {
                $options['location'] = $this->getCartoserverUrl('server.php');
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

    /**
     * Retrieves MapInfo from server
     * @param string mapId
     * @return MapInfo MapInfo returned by server
     */
    function getMapInfo($mapId) {
        return $this->callFunction('getMapInfo', $mapId);
    }

    /**
     * Retrieves MapResult from server
     * @param MapRequest map request
     * @return MapResult MapResult returned by server
     */
    function getMap($mapRequest) {
        return $this->callFunction('getMap', $mapRequest);
    }
}
?>
