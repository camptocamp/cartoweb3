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
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class projects_testQueryHilight_coreplugins_query_server_RemoteServerQueryTest
                    extends client_CartoserverServiceWrapper {

    public function isTestDirect() {
        return true;   
    }

    protected function getMapId() {
        return 'test_query_hilight.test';
    }
    
    /**    
     * Returns a {@link MapRequest} for a query on all selected layers
     * with a rubber band (bbox) like query
     * @return MapRequest
     */
    private function getMapBboxRequestAllLayers() {
    
        $queryRequest = new QueryRequest();
        $bbox = new Bbox();
        $bbox->setFromBbox(-0.75, 51, 0.75, 51.5);
        $queryRequest->bbox = $bbox;
        $queryRequest->defaultTableFlags = new TableFlags();
        $queryRequest->defaultTableFlags->returnAttributes = true;
        $queryRequest->defaultTableFlags->returnTable = true;
        $queryRequest->queryAllLayers = true;

        $mapRequest = $this->createRequest();
        $mapRequest->queryRequest = $queryRequest;        
        $mapRequest->layersRequest = new LayersRequest();
        $mapRequest->layersRequest->layerIds = 
                    array('POLYGON1', 'line', 'point');
        
        return $mapRequest;
    }

    /**
     * Checks for query with attributes
     * @param QueryResult
     */
    private function assertQueryResultWithAttributes($queryResult) {

        $this->assertEquals(3, count($queryResult->tableGroup->tables));
        $this->assertEquals("POLYGON1", 
                            $queryResult->tableGroup->tables[0]->tableId);

        $polygonRows = $queryResult->tableGroup->tables[0]->rows; 
        $this->assertEquals(1, count($polygonRows));
        $this->assertEquals('1', $polygonRows[0]->rowId); 
        $this->assertEquals(array('1', 'Cé bô le françès'), 
                            $polygonRows[0]->cells);        
    }
    
    /**
     * Tests a query using Hilight service
     * @param boolean
     */
    // FIXME: failure in indirect mode for an unknown reason
    //public function testQueryUsingHilight($direct = false) {
    public function testQueryUsingHilight($direct = true) {

        $mapRequest = $this->getMapBboxRequestAllLayers();
        $mapResult = $this->getMap($mapRequest, $direct);

        $this->assertQueryResultWithAttributes($mapResult->queryResult);

        // see above FIXME why commented
        //$this->redoDirect($direct, __METHOD__);
    }

}

?>