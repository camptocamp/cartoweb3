<?php
/**
 * I18n tests
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

///////////////////////////////////////////////////////
///////////////////////////////////////////////////////
///////////////////////////////////////////////////////
///////////////////////////////////////////////////////
// This is currently not included in tests. Add to the tests once completed.
///////////////////////////////////////////////////////
///////////////////////////////////////////////////////
///////////////////////////////////////////////////////
///////////////////////////////////////////////////////

/**
 * Abstract test case
 */
require_once 'PHPUnit/Framework/TestCase.php';

require_once(CARTOWEB_HOME . 'client/Internationalization.php');
require_once(CARTOWEB_HOME . 'client/Cartoclient.php');

/**
 * Unit tests for Internationalization
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class client_InternationalizationTest extends PHPUnit_Framework_TestCase {

    public function testGettextFr() {
        $cartoclient = new Cartoclient();
        $config = new ClientConfig($cartoclient->getProjectHandler());
        I18n::init($config);
        
        //var_dump(I18n::getLocales());
        $translated = I18n::gt('Scalebar');
        $this->assertEquals('Echelle', $translated);
    }
}
