<?php
/**
 * Documentation testing
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

require_once(CARTOCLIENT_HOME . 'client/Internationalization.php');
require_once(CARTOCLIENT_HOME . 'client/Cartoclient.php');

/**
 * Unit tests for the documentation (XML validation and such)
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class misc_DocumentationTest extends PHPUnit2_Framework_TestCase {

    public function testXmlValid() {

        $ret = exec('xmllint', $dummy, $status);
        if ($status == 127)
            return $this->fail('You need to install xmllint ' . 
                               'to complete documentation tests');
        $ret = exec('xmllint --xinclude --noout --postvalid ' . 
                    '../documentation/user_manual/source/book.xml 2>&1',
                    $output, $status);
        if ($status != 0)
            return $this->fail('Docbook XML is not valid: ' . 
                               implode("\n", $output));
    }
}

?>
