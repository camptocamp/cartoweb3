<?php
/**
 * General functions used in CartoWeb 
 * @package Common
 * @version $Id$
 */

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
 * Sets ini directives useful during development
 */
function setDeveloperIniConfig() {
    ini_set('assert.bail', '1');
    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', '1');
}

/**
 * Perform various cartoweb initializations.
 * @param Config
 */
function initializeCartoweb($config) {
    
    if ($config->developerIniConfig) {  
        setDeveloperIniConfig();
    }
}

/**
 * Copies values from an objet to another
 *
 * Uses reflection.
 * @param mixed
 * @param mixed
 * @return mixed
 */
function copy_properties($from_object, $to_object) {

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
 * Copies values from an objet to another
 *
 * Only updates if destination var exists. Does not use reflection.
 * @param mixed
 * @param mixed
 * @return mixed
 */
function copy_vars($from_object, $to_object) {

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
 * To be removed, using rather copy_properties
 *
 * Does not use reflection.
 * @param mixed
 * @param mixed
 * @return mixed
 */
function copy_all_vars($from_object, $to_object) {

    $from_vars = get_object_vars($from_object);
    foreach ($from_vars as $from_var_name => $value) {
        $to_object->$from_var_name = $from_object->$from_var_name;
    }
    return $to_object;
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
    static function parseArray($value) {
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
    static function parseObjectArray($config, $prefix, $suffixes) {
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

?>