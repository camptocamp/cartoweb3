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

    public function buildMapRequest($mapRequest) {
           
        $mapRequest->projectTableRequest = new ProjectTableRequest();
    }

    public function initializeResult($queryResult) {
        if (empty($queryResult))
            return;
        
        $tablesPlugin = $this->cartoclient->getPluginManager()->tables;
        $tablesPlugin->addTableGroups($queryResult->tableGroup);
    }

    public function handleResult($queryResult) {}                       
}

?>