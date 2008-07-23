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
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Miscellaneous unit tests for cartoweb.
 *
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class misc_MiscTest extends PHPUnit_Framework_TestCase {

    /* uncomment if you have python > 2.3
    public function testTabs() {

        $ret = shell_exec('python -V 2>&1');
        $this->assertContains("Python", $ret, 'You need a python interpreter in you path');
        
        $ret = shell_exec('python ../scripts/checktabs.py 2>&1');
        $this->assertTrue(strlen($ret) == 0, "Some files contain tabs:\n$ret");        
    }
    */

    public function testTabs() {

        $ret = shell_exec('cd ../scripts/; /bin/sh ./checktabs.sh 2>&1');
        $this->assertTrue(strlen($ret) == 0, "Some files contain tabs:\n$ret");
    }

    public function testDosLineEndings() {

        $ret = shell_exec('cd ../scripts/; /bin/sh ./checkdos.sh 2>&1');
        $this->assertTrue(strlen($ret) == 0, 
                                 "Some files contain dos file endings:\n$ret");
    }

    public function testFunctionModifiers() {

        $ret = shell_exec('cd ../scripts/; /bin/sh ./checkmodifiers.sh 2>&1');
        $this->assertTrue(strlen($ret) == 0, "Some functions have no " .
                "(public|private|protected) modifiers:\n$ret");
    }
}
