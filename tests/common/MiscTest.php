<?php
/**
 * @package Tests
 * @version $Id$
 */

/**
 * Abstract test case
 */
require_once 'PHPUnit2/Framework/TestCase.php';

/**
 * Miscellaneous unit tests for cartoweb.
 *
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class common_MiscTest extends PHPUnit2_Framework_TestCase {

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

    public function testFunctionModifiers() {

        $ret = shell_exec('cd ../scripts/; /bin/sh ./checkmodifiers.sh 2>&1');
        $this->assertTrue(strlen($ret) == 0, "Some functions have no " .
                "(public|private|protected) modifiers:\n$ret");
    }
}
