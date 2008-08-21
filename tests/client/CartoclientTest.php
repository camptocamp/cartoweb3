<?php
/**
 * Abstract unit tests for the Cartoclient
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
require_once 'projects/AllTests.php';

require_once(CARTOWEB_HOME . 'client/Cartoclient.php');

// There is an issue when running Java inside OpenVZ. This option is
// required to avoid out of memory errors.
define(JAVA_ARGS, "-Xmx256m");

/**
 * Abstract unit tests for Cartoclient.
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
abstract class client_CartoclientTest extends PHPUnit_Framework_TestCase {

    protected abstract function getProjectName();

    // TODO: add a way to return multiple mapId's
    protected abstract function getMapId();

    private  $cartoclient;

    private function getCartoclient() {
        if (!$this->cartoclient) {
            $GLOBALS['headless'] = true;
            $_ENV['CW3_PROJECT'] = $this->getProjectName();
            $this->cartoclient = new Cartoclient();
        }
        return $this->cartoclient;
    }

    private function getJavaPath() {

        // A list of path where to look for Java binary
        //  add your path here according to your system
        $check_paths = array('/usr/lib/j2sdk1.5-sun/bin/',
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

        // FIXME temporary disabling of httpunit test because httpunit conflict with prototype 1.6
        return;
        // END OF FIXME

        $java_path = $this->getJavaPath();
        
        $httpunitBaseTestsPath = realpath(dirname(__FILE__) . '/httpunit');
        $project = $this->getProjectName();
        $httpunitProjectTestsPath = realpath(dirname(__FILE__)) . 
                    "/../../projects/$project/tests/client/httpunit/bin/";

        $httpunitJar = $httpunitBaseTestsPath . '/httpunit_all.jar';
        $classpath = "$httpunitJar:$httpunitBaseTestsPath/bin:$httpunitProjectTestsPath";

        $clientUrl = $this->getCartoclient()->getConfig()->cartoclientBaseUrl;
        $clientUrl .= 'client.php?project=' . $this->getProjectName();

        $projectConvertedName = projects_AllTests::convertName($this->getProjectName());
        $class = "Cartoweb{$projectConvertedName}Test";
        $javaArgs = JAVA_ARGS;
        $javaCmd = "$java_path $javaArgs -classpath $classpath $class $clientUrl 2>&1";
        //print "HttpUnit command: $javaCmd\n";
        $java_output = shell_exec($javaCmd);

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
    private function rmdirr($dirname)
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
        
    public function testWsdl() {

        $java_path = $this->getJavaPath();
        $axis_tests_path = realpath(dirname(__FILE__) . '/axis');
        $classpath = $axis_tests_path . '/axis.jar';
        
        $serverUrl = $this->getCartoclient()->getConfig()->cartoserverBaseUrl;
        $serverUrl .=  'cartoserver.wsdl.php?mapId=' . $this->getProjectName() . 
                                                    '.' . $this->getMapId();

        if (is_null($serverUrl)) {
            $this->fail("Warning: server Url not found: skipping test\n");
            return;   
        }
        $temp_dir = '/tmp/axis_tmp';
        if (is_dir($temp_dir))
            $this->rmdirr($temp_dir);
        mkdir($temp_dir);
        $javaArgs = JAVA_ARGS;
        $javaCmd = "$java_path $javaArgs -classpath $classpath " .
                "org.apache.axis.wsdl.WSDL2Java " .
                "-o $temp_dir -a $serverUrl 2>&1"; 
        //print "CheckWsdl command: $javaCmd\n";
        exec($javaCmd, $output, $ret);
        $output = implode("\n", $output);
        $this->rmdirr($temp_dir);
        $output = "The wsdl file is invalid!!\n\n $output";
        $this->assertTrue($ret == 0, $output);
    }

    public function testImageMode() {
        $cartoclient = new Cartoclient;
        $url = $cartoclient->getConfig()->cartoclientBaseUrl;
        $url .= 'client.php?mode=image';

        if (extension_loaded('curl')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            curl_close($ch);

            // Checks content-type header
            if (preg_match("/Content-Type: ([-a-z\/]+)/im", $result, $match)) {
                $this->assertEquals('image/', substr($match[1], 0, 6));
            }
        
            // Checks if opened file contains some image format keyword
            // (JFIF => JPEG) 
            $this->assertTrue((bool)preg_match("/(GIF|PNG|JFIF)/m", $result),
                              'Failed detecting valid image format!');
        } /*else {
            // TODO: handle case when Curl is not available
            // following code may fail because of filesize crash
            $handle = fopen($url, 'rb');
            $result = fread($handle, filesize($url));
            fclose($handle);
        }*/
    }
}

?>
