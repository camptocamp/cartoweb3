<?php
/**
 * @package Tests
 * @version $Id$
 */

/**
 * Abstract test case
 */
require_once 'PHPUnit2/Framework/TestCase.php';
require_once 'common/Config.php';

require_once(CARTOCLIENT_HOME . 'client/Cartoclient.php');

/**
 * Unit tests for Cartoclient.
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class client_CartoclientTest extends PHPUnit2_Framework_TestCase {

    private function getClientUrl() {
        if (!isset($_SERVER['HTTP_HOST'])) {
            return Common_config::getInstance()->cartoclientUrl;
        }
        
        $url = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . 
                $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']); 
        $url .= '/client.php';
        
        return $url;
    }
    
    public function testHttpUnit() {
        
        // A list of path where to look for Java binary
        //  add your path here according to your system
        $check_paths = array('/home/sypasche/myfiles/java/jdk/bin');
        $java_path = NULL;
        foreach ($check_paths as $path) {
            if (file_exists($path . '/java')) {
                $java_path = $path . '/java';
                break;   
            }
        }
        $java_path = is_null($java_path) ? 'java' : $java_path;

        $ret = shell_exec("$java_path -version 2>&1");
        $this->assertContains("java version", $ret, 'You need a java virtual maching in your path');

        $httpunit_tests_path = realpath(dirname(__FILE__) . '/../httpunit');
        
        $httpunit_jar = $httpunit_tests_path . '/httpunit_all.jar';
        $classpath = "$httpunit_jar:$httpunit_tests_path/bin";

        $clientUrl = $this->getClientUrl();
        if (is_null($clientUrl)) {
            $this->fail("Warning: client Url not found: skipping test\n");
            return;   
        }

        $java_output = shell_exec("$java_path -classpath $classpath CartowebTest $clientUrl 2>&1");

        $this->assertContains("\nOK ", $java_output, "HttpUnit failure:\n $java_output");
    }

}

?>
