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

        $cartoserver = new Cartoserver();

        $result = $cartoserver->$function($argument);
    
        if ($result instanceof SoapFault) {
            throw $result;
        }
        return $result;
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

    private function callFunction($function, $argument) {

        if ($this->config->cartoserverDirectAccess) {
            $mapResult = $this->callDirect($function, $argument);
        } else {

            // FIXME: put in config
            ini_set("soap.wsdl_cache_enabled", "0");

            $client = new SoapClient($this->getCartoserverUrl());

            $mapResult = $client->$function($argument);
            
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