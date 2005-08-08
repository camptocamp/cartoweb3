<?php
/**
 * Views tests
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

require_once(CARTOCLIENT_HOME . 'client/Views.php');
require_once(CARTOCLIENT_HOME . 'client/Cartoclient.php');

class TestViewFileContainer extends ViewFileContainer {
    
    public function setViewId($viewId) {
        $this->viewId = $viewId;
    }
}

/**
 * Unit tests for Views
 * @package Tests
 */
class client_ViewsTest extends PHPUnit2_Framework_TestCase {

    const VIEW_ID = 123456;
    const DATA_1 = 'Some serialized data';
    const DATA_2 = 'Some other serialized data';
    const TITLE_1 = 'Some title';
    const TITLE_2 = 'Some other title';

    private function getVfc() {
        $cartoclient = new Cartoclient();
        return new TestViewFileContainer($cartoclient);
    }

    public function testSaveView() {
        $vfc = $this->getVfc();

        $metas = array('viewTitle'      => self::TITLE_1, 
                       'viewShow'       => 1,
                       'viewLocationId' => 0);

        $vfc->setViewId(self::VIEW_ID);
        $vfc->insert(self::DATA_1, $metas);
        $this->assertTrue($vfc->getActionSuccess());
    }

    public function testLoadView() {
        $vfc = $this->getVfc();

        $data = $vfc->select(self::VIEW_ID);
        $this->assertTrue($vfc->getActionSuccess());
        $this->assertEquals(self::DATA_1, $data);
        
        $metas = $vfc->getMetas();
        $this->assertEquals(self::TITLE_1, $metas['viewTitle']);
    }
    
    public function testUpdateView() {
        $vfc = $this->getVfc();

        $metas = array('viewTitle'      => self::TITLE_2,
                       'viewShow'       => 1,
                       'viewLocationId' => 0);

        $vfc->update(self::VIEW_ID, self::DATA_2, $metas);
        $this->assertTrue($vfc->getActionSuccess());

        $data = $vfc->select(self::VIEW_ID);
        $this->assertTrue($vfc->getActionSuccess());
        $this->assertEquals(self::DATA_2, $data);

        $newMetas = $vfc->getMetas();
        $this->assertEquals(self::TITLE_2, $metas['viewTitle']);
    }
    
    public function testDeleteView() {
        $vfc = $this->getVfc();
        
        $success = $vfc->delete(self::VIEW_ID);
        $this->assertTrue($success);
    }
}

?>
