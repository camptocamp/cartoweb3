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

if (!defined('CARTOCLIENT_HOME'))
    define('CARTOCLIENT_HOME', realpath(dirname(__FILE__).'/..').'/');
if (!defined('CARTOCOMMON_HOME'))
    define('CARTOCOMMON_HOME', CARTOCLIENT_HOME);
if (!defined('CARTOSERVER_HOME'))
    define('CARTOSERVER_HOME', CARTOCLIENT_HOME);

// clears include_path, to prevent side effects
ini_set('include_path', '');

require_once(CARTOSERVER_HOME . 'common/Common.php');
Common::preInitializeCartoweb(array());

set_include_path(get_include_path() . PATH_SEPARATOR . 
                 CARTOCLIENT_HOME . 'tests/');

define('PHPUnit2_MAIN_METHOD', false);
require_once 'PHPUnit2/TextUI/TestRunner.php';

/**
 * @package Tests
 */
class PHPUnit2_TextUI_TestRunner_Web extends PHPUnit2_TextUI_TestRunner {

    /**
     * @var PHPUnit2_Framework_TestResult
     */
    public $testResult;

    /**
     * @return PHPUnit2_Framework_TestResult
     */
    protected function createTestResult() {
        $this->testResult = new PHPUnit2_Framework_TestResult;
        return $this->testResult;
    }
}

$errors = array ();

/**
 * Customized error handler.
 * @param int error number
 * @param string error message
 * @param string filename
 * @param int line number
 * @param array
 */
function test_error_handler($errno, $errmsg, $file, $line, $vars) {
    global $errors;

    if (defined('E_STRICT')) {
        if ($errno & E_STRICT && (error_reporting() & E_STRICT) != E_STRICT) {
            // Ignore E_STRICT notices unless they have been turned on
            return;
        }
    } else {
        define('E_STRICT', 2048);
    }

    if (strpos($file, 'include/log4php') !== FALSE)
        return;

    $errortype = array (E_ERROR           => 'Error', 
                        E_WARNING         => 'Warning',
                        E_PARSE           => 'Parsing Error',
                        E_NOTICE          => 'Notice',
                        E_CORE_ERROR      => 'Core Error',
                        E_CORE_WARNING    => 'Core Warning',
                        E_COMPILE_ERROR   => 'Compile Error',
                        E_COMPILE_WARNING => 'Compile Warning',
                        E_USER_ERROR      => 'User Error',
                        E_USER_WARNING    => 'User Warning',
                        E_USER_NOTICE     => 'User Notice',
                        E_STRICT          => 'Strict Notice',
                        );
    $prefix = $errortype[$errno];
    $msg = "\n$prefix: $errmsg in ".$file." on line $line\n";
    array_push($errors, $msg);
}

/**
 * @package Tests
 */
class CartowebTestRunner {

    /**
     * @var PHPUnit2_TextUI_TestRunner_Web
     */
    public $test_runner;

    /**
     * Constructor
     */
    public function __construct() {
        error_reporting(E_ALL);
        set_error_handler('test_error_handler');
    }

    /**
     * Runs given test suite.
     * @param string test suite name
     */
    public function runTests($testSuite = 'AllTests') {
        $test_runner = new PHPUnit2_TextUI_TestRunner_Web();
        $test = $test_runner->getTest($testSuite);
        $test_runner->doRun($test);
        $this->test_runner = $test_runner;
    }

    /**
     * Tells if run test was successful.
     * @return boolean
     */
    public function wasSuccessful() {
        return $this->test_runner->testResult->wasSuccessful();
    }

    /**
     * @return array
     */
    public function getErrors() {
        global $errors;
        return $errors;
    }
}
?>
