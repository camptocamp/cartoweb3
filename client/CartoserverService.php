<?php

class CartoserverService {
    private $log;
    private $cartoclient;

    function __construct($cartoclient) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->cartoclient = $cartoclient;
    }

    private function callDirect($function, $argument) {

        // read by cartoserver to tell its mode
        $direct_access = true;

        $config = $this->cartoclient->getConfig();
        $cartoserverHome = $config->cartoserverHome;
        if (!$cartoserverHome)
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

        $config = $this->cartoclient->getConfig();
        
        if (@$config->cartoserverUrl)
            return $config->cartoserverUrl;

        // in config ?
        $guessCartoserver = true;

        if ($guessCartoserver && $_SERVER['PHP_SELF'] != '') {

            $url = (isset($_SERVER['HTTPS']) ? "https://" : "http://" ) . 
                $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . 
                '/cartoserver.wsdl.php';
            return $url;
        }

        throw new CartoclientException("No cartoserver Url set in config file");
    }

    private function callFunction($function, $argument) {
        $config = $this->cartoclient->getConfig();

        if ($config->cartoserverDirectAccess) {
            $mapResult = $this->callDirect($function, $argument);
        } else {

            // FIXME: put in config
            ini_set("soap.wsdl_cache_enabled", "0");

            $client = new SoapClient($this->getCartoserverUrl());

            $mapResult = $client->$function($argument);
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