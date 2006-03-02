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
 * Utility class containing static methods for various common tasks.
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
                $toProperty = $toReflectionClass->getProperty(
                                                     $fromProperty->getName());
            } catch (ReflectionException $e) {
                continue;
            }
            $toProperty->setValue($to_object, 
                                  $fromProperty->getValue($from_object));
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
        if (DIRECTORY_SEPARATOR == '/')
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
        if (DIRECTORY_SEPARATOR == '/')
            return $path;
        return str_replace('/', '\\', $path);
    }

    /**
     * Creates a directory recursively. The permissions of the newly created
     * directories are the same as the permission of the given $permsFrom file
     * or directory.
     * @param string The directory to create (can create recursively)
     * @param string Permissions of the newly created directory are the same 
     * as this file or directory.
     */
    public static function makeDirectoryWithPerms($directory, $permsFrom) {
        
        if (is_dir($directory))
            return;
        
        $oldUmask = umask();
        umask(0000);

        $stat = stat($permsFrom);
        $perms = $stat['mode'] & 0777;
        // Looks like mkdir() does not like mixed / and \ delimiters
        $directory = Utils::pathToPlatform($directory);
        mkdir($directory, $perms, true);

        umask($oldUmask);  
    }
    
    /**
     * Escapes special characters taking into account if magic_quotes_gpc
     * is ON or not. Multidimensional arrays are accepted.
     * @param mixed
     * @param boolean (optional) magic_quotes_gpc status. Detected if missing.
     * @return mixed
     */
    public static function addslashes($data, $magic_on = NULL) {
        if (!isset($magic_on)) {
            $magic_on = get_magic_quotes_gpc();
        }
        
        if (!$magic_on) {
            if (is_array($data)) {
                foreach ($data as $key => &$val) {
                    $val = self::addslashes($val, false);
                }
            } else {
                $data = addslashes($data);
            }
        }

        return $data;
    }
    
    /**
     * Wrapper for PEAR::isError, which throws an exception in case of failure
     * @param object Database object to test for error 
     * @param string optional error message condition
     */
    public static function checkDbError($db, $msg = '') {
        if (PEAR::isError($db)) {
            $errorMsg = sprintf('%s Message: %s  Userinfo: %s', $msg, 
                           $db->getMessage(), $db->userinfo);
            throw new CartocommonException($errorMsg);
        }
    }

    /**
     * Affects and returns a PEAR::DB object.
     *
     * Set connection if not already available.
     * @param PEAR::DB
     * @param string DSN (Data Source Name)
     * @param array connection options
     * @return PEAR::DB
     */
    public static function getDb(&$db, $dsn, $options = array()) {
        if (!isset($db)) {
            if (empty($dsn)) {
                throw new CartocommonException('DSN is missing');
            }
            
            if (!is_array($options)) {
                throw new CartocommonException(
                    "'options' parameter is not an array");
            }

            require_once 'DB.php';
            
            $db = DB::connect($dsn, $options);
            self::checkDbError($db, 'Failed opening DB connection');
        }
        return $db;
    }


    /**
     * Converts a character-separated string to an array.
     * @param string
     * @param string string divider
     * @return array
     */
    static public function parseArray($string, $divider = ',') {
        if (is_null($string)) {
            return array();
        }
        
        $array = explode($divider, $string);
        $array = array_map('trim', $array); // removes white spaces
        $array = array_diff($array, array('')); // removes empty values
        return array_values($array);
    }

    /**
     * Inverts and saves image.
     *
     * For a color negative image, set the optional flag:
     * invertImage($input, '', true);
     *
     * For a black and withe negative image use like this:
     * invertImage($input, '');
     *
     * If you want to save the output instead of just showing it,
     * set the output to the path where you want to save the inverted image:
     * invertImage('path/to/original/image.jpg','path/to/save/inverted-image.jpg');
     *
     * If you want to use png you have to set the color flag as
     * true or false and define the imagetype in the function call:
     * invertImage('path/to/image.png','',false,'png');
     *
     * @param string the input image path
     * @param string the output image path
     * @param boolean 
     * @param string jpeg or png
     * @return array
     */
    static public function invertImage($input, $output, 
                                       $color = false, $type = 'jpeg') {
        // check if GD is installed in PHP
        if(!function_exists('gd_info'))
           throw new CartocommonException(
                      'GD library not found! The GD library in PHP is required'
                      . ' for symbol creation in outline plugin');        

        switch ($type) {
            case 'jpeg':
            case 'jpg':
                $img = imagecreatefromjpeg($input);
                break;
            case 'png':
                $img = imagecreatefrompng($input);
                break;
            default:
                throw new CartocommonException("$type is not a valid image type");
        }

        $x = imagesx($img);
        $y = imagesy($img);

        for ($i = 0; $i < $y; $i++) {
            for ($j = 0; $j < $x; $j++) {
                $pos = imagecolorat($img, $j, $i);
                $f = imagecolorsforindex($img, $pos);
                if ($color == true) {
                    $col = imagecolorresolve($img, 
                                             255 - $f['red'], 
                                             255 - $f['green'],
                                             255 - $f['blue']);
                } else {
                    $gst =  $f['red']   * 0.15;
                    $gst += $f['green'] * 0.5;
                    $gst += $f['blue']  * 0.35;
                    $agst = 255 - $gst;
                    $col = imagecolorclosesthwb($img, $agst, $agst, $agst);
                }
                imagesetpixel($img, $j, $i, $col);
            }
        }
        switch ($type) {
            case 'jpeg':
            case 'jpg':
                imagejpeg($img, $output, 90);
                break;
            case 'png':
                imagepng($img, $output);
                break;
            default:
                throw new CartocommonException("$type is not a valid image type");
        }
    }

    /**
     * Converts #xxyyzz hexadecimal color codes into RGB.
     * @param string hexadecimal color code
     * @return array array of RGB codes
     */
    static public function switchHexColorToRgb($color) {
        return array(hexdec(substr($color, 1, 2)),
                     hexdec(substr($color, 3, 2)),
                     hexdec(substr($color, 5, 2))
                     );
    }

    /**
     * Converts passed color in RGB codes.
     * @param mixed
     * @return array array of RGB codes
     */
    static public function switchColorToRgb($color) {
        if ($color{0} == '#')
            return self::switchHexColorToRgb($color);

        if (is_array($color))
            return $color;

        switch($color) {
            case 'black': return array(0, 0, 0);
            case 'red': return array(255, 0, 0);
            case 'green': return array(0, 255, 0);
            case 'blue': return array(0, 0, 255);
            case 'white': default: return array(255, 255, 255);
        }
    }

    /**
     * Tells if given path is relative.
     * @param string path
     * @return boolean true if relative
     */
    static public function isRelativePath($path) {
        return (substr($path, 0, 4) != 'http' && substr($path, 0, 1) != '/' &&
                substr($path, 1, 1) != ':');
    }

    /**
     * Converts radians angle to degrees even if value is < 0.
     * @param double
     * @return double
     */
    static public function negativeRad2Deg($rad) {
        if (is_null($rad) || !is_numeric($rad)) {
            return 0;
        }
        $angle = $rad;
        while ($angle < 0) {
            $angle += 2 * pi();
        }
        $angle = rad2deg($angle);
        return $angle;
    }
}

/**
 * Tools for configuration files parsing
 * @package Common
 */
class ConfigParser {

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
     * @return array of stdClass objects
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

/**
 * For debugging purpose only
 * @param mixed
 */
function pre($var) {
    print '<pre>';
    print_r($var);
    print "</pre>\n";
}
?>
