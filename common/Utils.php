<?php
/**
 * General functions used in CartoWeb 
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
 * @package Common
 * @version $Id$
 */

require_once ('log4php/LoggerManager.php');

/**
 * Utiliy class containing static methods for various common tasks.
 * @package Common
 */
class Utils {
    
    /**
     * Copies values from an objet to another. It uses reflection for reading the 
     * properties of each objects.
     * 
     * @param mixed
     * @param mixed
     * @return mixed
     */
    public static function copyProperties($from_object, $to_object) {
    
        $fromReflectionClass = new ReflectionClass(get_class($from_object));
        $toReflectionClass = new ReflectionClass(get_class($to_object));
        $fromProperties = $fromReflectionClass->getProperties();
    
        foreach ($fromProperties as $fromProperty) {
    
            try {
                $toProperty = $toReflectionClass->getProperty($fromProperty->getName());
            } catch (ReflectionException $e) {
                continue;
            }
            $toProperty->setValue($to_object, $fromProperty->getValue($from_object));
        }
        return $to_object;
    }
    
    /**
     * Copies values from an objet to another. It only updates if 
     * destination var exists. Does not use reflection.
     * 
     * @param mixed
     * @param mixed
     * @return mixed
     */
    public static function copyVars($from_object, $to_object) {
    
        $from_vars = get_object_vars($from_object);
        $to_vars = get_object_vars($to_object);
        foreach ($to_vars as $to_var_name => $value) {
            if (!in_array($to_var_name, array_keys($from_vars))) {
                continue;
            }
            $to_object->$to_var_name = $from_object->$to_var_name;
        }
        return $to_object;
    }
    
    /**
     * Converts a path to unix path delmitors.
     * 
     * @param string
     * @return string
     */
    public static function pathToUnix($path) {
        if (PATH_SEPARATOR == '/')
            return $path;
        return str_replace('\\', '/', $path);
    }

    /**
     * Converts a path with any delimitors to a path with delimitors used by the
     * current platform.
     * 
     * @param string
     * @return string
     */
    public static function pathToPlatform($path) {
        // by default, all paths are with '/'
        if (PATH_SEPARATOR == '/')
            return $path;
        return str_replace('/', '\\', $path);
    }
}

/**
 * Tools for configuration files parsing
 * @package Common
 */
class ConfigParser {

    /**
     * Converts a comma-separated string to an array
     * @param string
     * @return array
     */
    static public function parseArray($value) {
        if (!$value)
            return array();
        $value = explode(',', $value);
        return array_map('trim', $value);
    }
    
    /**
     * Converts a list of values taken from a configuration file to an array
     * of objects
     *
     * File structure example:
     * <pre>
     *   scales.0.label = 1/2
     *   scales.0.value = 2
     *   scales.0.visible = false
     *   scales.1.label = 1/5
     *   scales.1.value = 5
     * </pre>
     *
     * Parameter $suffixes contains an array of possible suffixes 
     * (array('label','value','visible') in the example).
     * @param Config configuration
     * @param string prefix
     * @param string possible suffixes
     */
    static public function parseObjectArray($config, $prefix, $suffixes) {
        $result = array();        
        for ($i = 0; ; $i++) {
            $object = new stdClass();
            $found = false;
            foreach ($suffixes as $suffix) {            
                $key = $prefix . '.' . $i . '.' . $suffix;
                $object->$suffix = $config->$key;
                if (!is_null($config->$key)) {
                    $found = true;
                }
            }
            
            if (!$found) {
                break;
            }
            $result[] = $object;
        }   
        return $result;
    }
}

/////////////////   Misc functions for debugging   /////////////////

/**
 * For debugging purpose only
 * @param mixed
 */
function x1($a = "__died__ \n") {
    $log =& LoggerManager::getLogger('__x__');
    
    print "<pre> type ".gettype($a)."\n";
    if (is_object($a) or is_array($a)) {
        print "obj\n";
        $log->debug($a);
        var_dump($a);
        x1();
    }
    //debug_print_backtrace();
    $log->debug($a);
}

/**
 * For debugging purpose only
 * @param mixed
 */
function x($a = "__died__\n") {
    x1($a);
    die($a."\n");
}

/**
 * For debugging purpose only
 * @param mixed
 */
function bt($a = "__died__\n") {
    echo "<pre/>";
    debug_print_backtrace();
    x($a);
}

/**
 * For debugging purpose only
 * @param mixed
 */
function l($arg = false) {
    $i = 100;
    if ($arg !== false)
        var_dump($arg);
    while ($i--)
        print "x";
    print "\n";
}

?>
