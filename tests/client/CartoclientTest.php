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
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
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
    
    private function getJavaPath() {
        
        // A list of path where to look for Java binary
        //  add your path here according to your system
        $check_paths = array('/home/sypasche/myfiles/java/jdk/bin',
                             '/usr/local/jdk/bin');
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
        
        return $java_path;
    }
    
    public function testHttpUnit() {

        $java_path = $this->getJavaPath();
        
        $ret = shell_exec("$java_path -version 2>&1");
        $this->assertContains("java version", $ret, 'You need a java virtual maching in your path');

        $httpunit_tests_path = realpath(dirname(__FILE__) . '/httpunit');
        
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

    /**
     * Delete a file, or a folder and its contents
     *
     * @author      Aidan Lister <aidan@php.net>
     * @version     1.0.2
     * @param       string   $dirname    Directory to delete
     * @return      bool     Returns TRUE on success, FALSE on failure
     */
    function rmdirr($dirname)
    {
        // Sanity check
        if (!file_exists($dirname)) {
            return false;
        }
     
        // Simple delete for a file
        if (is_file($dirname)) {
            return unlink($dirname);
        }
     
        // Loop through the folder
        $dir = dir($dirname);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }
     
            // Recurse
            $this->rmdirr("$dirname/$entry");
        }
     
        // Clean up
        $dir->close();
        return rmdir($dirname);
    }

    private function getCartoserverBaseUrl() {
        $ini_array = parse_ini_file(CARTOCLIENT_HOME . 'client_conf/client.ini');
        return $ini_array['cartoserverBaseUrl'];
    }
                    
    public function _testWsdl() {

        $java_path = $this->getJavaPath();
        $axis_tests_path = realpath(dirname(__FILE__) . '/axis');
        $classpath = $axis_tests_path . '/axis.jar';
        
        $clientUrl = 'http://c2cpc4.camptocamp.com/gdp/1.0m7/cartoserver/cartoserver.wsdl.php?mapId=toposhop1'; 

        if (is_null($clientUrl)) {
            $this->fail("Warning: client Url not found: skipping test\n");
            return;   
        }
        $temp_dir = '/tmp/axis_tmp';
        if (is_dir($temp_dir))
            $this->rmdirr($temp_dir);
        mkdir($temp_dir);
        exec("$java_path -classpath $classpath " .
                "org.apache.axis.wsdl.WSDL2Java " .
                "-o $temp_dir -a $clientUrl 2>&1", $output, $ret);
        $output = implode("\n", $output);
        $this->rmdirr($temp_dir);
        $output = "The wsdl file is invalid !!\n\n $output";
        $this->assertTrue($ret == 0, $output);
    }
}

?>
