<?php 
/**
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

set_include_path(get_include_path() . PATH_SEPARATOR . 
                CARTOCLIENT_HOME . 'tests/' . PATH_SEPARATOR . 
                CARTOCLIENT_HOME . 'include/' . PATH_SEPARATOR . 
                CARTOCLIENT_HOME . 'include/pear/');

define('PHPUnit2_MAIN_METHOD', false);
require_once 'PHPUnit2/TextUI/TestRunner.php';

/**
 * @package Tests
 */
class PHPUnit2_TextUI_TestRunner_Web extends PHPUnit2_TextUI_TestRunner {

    public $testResult;

    protected function createTestResult() {
        $this->testResult = new PHPUnit2_Framework_TestResult;
        return $this->testResult;
    }
}

$errors = array ();

/**
 * @package Tests
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

    $errortype = array (E_ERROR => 'Error', E_WARNING => 'Warning', E_PARSE => 'Parsing Error', E_NOTICE => 'Notice', E_CORE_ERROR => 'Core Error', E_CORE_WARNING => 'Core Warning', E_COMPILE_ERROR => 'Compile Error', E_COMPILE_WARNING => 'Compile Warning', E_USER_ERROR => 'User Error', E_USER_WARNING => 'User Warning', E_USER_NOTICE => 'User Notice', E_STRICT => 'Strict Notice',);
    $prefix = $errortype[$errno];
    $msg = "\n$prefix: $errmsg in ".$file." on line $line\n";
    array_push($errors, $msg);
}

/**
 * @package Tests
 */
class CartowebTestRunner {

    public $test_runner;

    function __construct() {
        error_reporting(E_ALL);
        set_error_handler('test_error_handler');
    }

    function runTests($testSuite = 'AllTests') {
        $test_runner = new PHPUnit2_TextUI_TestRunner_Web();
        $test = $test_runner->getTest($testSuite);
        $test_runner->doRun($test);
        $this->test_runner = $test_runner;
    }

    function wasSuccessful() {
        return $this->test_runner->testResult->wasSuccessful();
    }

    function getErrors() {
        global $errors;
        return $errors;
    }
}
?>