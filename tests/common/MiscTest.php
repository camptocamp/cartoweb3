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

    public function testTabs() {

        $ret = shell_exec('python -V 2>&1');
        $this->assertContains("Python", $ret, 'You need a python interpreter in you path');
        
        $ret = shell_exec('python ../scripts/checktabs.py');
        $this->assertTrue(strlen($ret) == 0, "Some files contain tabs:\n$ret");        
    }

}