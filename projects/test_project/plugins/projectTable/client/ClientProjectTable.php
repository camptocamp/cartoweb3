<?php
/**
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 * @version $Id$
 */

/**
 * Plugin to test tables management
 * @package Tests
 */
class ClientProjectTable extends ClientPlugin 
                         implements ServerCaller {

    function buildMapRequest($mapRequest) {
           
        $mapRequest->projectTableRequest = new ProjectTableRequest();
    }

    function initializeResult($queryResult) {
        if (empty($queryResult))
            return;
        
        $tablesPlugin = $this->cartoclient->getPluginManager()->tables;
        $tablesPlugin->addTables($queryResult->table);
    }

    function handleResult($queryResult) {}                       
}

?>