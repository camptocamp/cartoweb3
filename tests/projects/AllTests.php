<?php
/**
 * Loads projects unit tests
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
 * Abstract test suite
 */
require_once 'PHPUnit2/Framework/TestSuite.php';
require_once 'client/CartoclientTest.php';

require_once(CARTOWEB_HOME . 'client/ClientProjectHandler.php');

/**
 * Unit tests aggregator for projects
 * The environment variable CARTOWEB_TEST_PROJECT can be used to specify
 * which project to test. Otherwise, all projects are tested.
 *
 * @package Tests
 */
class projects_AllTests {

    private static $suite;
    private static $clientProjectHandler;

    /**
     * Converts a name one_toto_two ==> OneTotoTwo
     * @param string input name
     * @return string converted name
     */
    public static function convertName($name) {
        $n = explode('_', $name);
        $n = array_map('ucfirst', $n);
        return implode($n);
    }

    private function automaticAddSuite($project, $path, $class) {
    
        $path = CARTOWEB_HOME . "tests/client/CartoclientTest.php";
        $file = 'tests/client/CartoclientTest.php';
        if (self::$clientProjectHandler->isProjectFile($file)) {
            require_once(CARTOWEB_HOME . self::$clientProjectHandler->getPath($file));
            self::$suite->addTestSuite($class);
        }       
    }

    // TODO: to be completed
    private function automaticTestLoad($project, $kind) {
        
        $projectConvertedName = self::convertName($project);

        switch($kind) {
            case 'client':
                self::automaticAddSuite($project, "tests/client/CartoclientTest.php", 
                   "projects_{$projectConvertedName}_client_CartoclientTest");
            break;    

            case 'coreplugins':
            case 'plugins':

                // XXX turned off
                return;

                $plugins = array();
                $directory = CARTOWEB_HOME . "projects/$project/tests/coreplugins";
                if (!is_dir($directory))
                    break;
                $d = dir($directory);
                while (false !== ($entry = $d->read())) {
                    if (is_dir($directory . $entry) && $entry != '.'
                        && $entry != '..' && $entry != 'CVS') {
                        $plugins[] = $entry;
                    }
                }
                var_dump($plugins);
            break;
            default:

            break;
        }
                   
    }

    private function loadAllTests($project, $kind) {
        
        $projectConvertedName = self::convertName($project);

        $path = CARTOWEB_HOME . "projects/$project/tests/$kind/AllTests.php";
        if (file_exists($path)) {
            require_once($path);

            $class = "projects_{$projectConvertedName}_{$kind}_AllTests";
            if (!class_exists($class)) {
                print "Failed to load class $class on path $path\n";
                exit();   
            }
                
            self::$suite->addTest(call_user_func(array($class, 'suite'), array()));
        } else {
            self::automaticTestLoad($project, $kind);
        }
    }

    private function loadProjectSuite($project) {
    
        $projectConvertedName = self::convertName($project);

        self::loadAllTests($project, 'client');
        self::loadAllTests($project, 'common');

        self::loadAllTests($project, 'coreplugins');
        self::loadAllTests($project, 'plugins');
        
        self::loadAllTests($project, 'server');
        self::loadAllTests($project, 'misc');
    }

    public static function suite() {
    
        self::$suite = new PHPUnit2_Framework_TestSuite();
        self::$clientProjectHandler = new ClientProjectHandler();

        if (isset($_ENV['CARTOWEB_TEST_PROJECT'])) {
            $testProjects = array($_ENV['CARTOWEB_TEST_PROJECT']);
        } else {
            $testProjects = self::$clientProjectHandler->getAvailableProjects(true); 
        }

        foreach($testProjects as $project) {
            //print "Testing project $project\n";
            self::loadProjectSuite($project);
        }
        
        return self::$suite;
    }
}

?>