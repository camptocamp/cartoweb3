<?php
/**
 * @package Tests
 * @version $Id$
 */

/**
 * Abstract test case
 */
require_once 'PHPUnit2/Framework/TestCase.php';
require_once('client/CartoserverServiceWrapper.php');

require_once(CARTOCOMMON_HOME . 'coreplugins/query/common/Query.php');
require_once(CARTOCOMMON_HOME . 'coreplugins/layers/common/Layers.php');
require_once(CARTOCOMMON_HOME . 'common/BasicTypes.php');

/**
 * Unit test for server query plugin via webservice. 
 *
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class coreplugins_query_server_RemoteServerQueryTest
                    extends client_CartoserverServiceWrapper {

    function isTestDirect() {
        return true;   
    }
    
    private function getQueryRequest1() {
        $queryRequest = new QueryRequest();

        $bbox = new Bbox();
        $bbox->setFromBbox(0, 51.5, 0, 51.5);
        $queryRequest->bbox = $bbox;
        
        return $queryRequest;
    }

    private function assertQueryResult1($queryResult) {
        // FIXME: result should be unserialized
        $this->assertEquals(count($queryResult->tableGroup->tables), 3);
        $this->assertEquals($queryResult->tableGroup->tables[0]->tableId,
                            "polygon");

        $polygonRows = $queryResult->tableGroup->tables[0]->rows; 
        $this->assertEquals(count($polygonRows), 1);
        $this->assertEquals($polygonRows[0]->cells, 
                                        array("id" => "1",
                                              "FID" => "1",
                                              "FNAME" => 'Cé bô le françès'));
        
    }

    function testQueryRequest1($direct = false) {

        $queryRequest = $this->getQueryRequest1(); 

        $queryRequest->layerIds = array('polygon', 'line', 'point');

        $mapRequest = $this->createRequest();
        $mapRequest->queryRequest = $queryRequest;
        
        $mapResult = $this->getMap($mapRequest);

        $this->assertQueryResult1($mapResult->queryResult);

        $this->redoDirect($direct, __METHOD__);
    }

    function testQueryRequest1_using_hilight($direct = false) {

        $this->setMapId('test_query_hilight.test');
        
        $queryRequest = $this->getQueryRequest1(); 

        $queryRequest->layerIds = array('polygon', 'line', 'point');

        $mapRequest = $this->createRequest();
        $mapRequest->queryRequest = $queryRequest;
        
        $mapResult = $this->getMap($mapRequest);

        $this->assertQueryResult1($mapResult->queryResult);

        $this->redoDirect($direct, __METHOD__);
    }

    function testQueryRequestWithLayersRequest($direct = false) {

        $queryRequest = $this->getQueryRequest1(); 
        
        $queryRequest->layerIds = NULL;

        $mapRequest = $this->createRequest();

        $mapRequest->queryRequest = $queryRequest;
        
        $mapRequest->layersRequest = new LayersRequest();
        $mapRequest->layersRequest->layerIds = 
                    array('polygon', 'line', 'point');
        
        $mapResult = $this->getMap($mapRequest);

        $this->assertQueryResult1($mapResult->queryResult);
        
        $this->redoDirect($direct, __METHOD__);
    }

}
?>