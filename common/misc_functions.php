<?php
/**
 * @package Common
 * @version $Id$
 */

/**
 * For debugging purpose only
 */
function x1($a="__died__ \n") {
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
 */
function x($a="__died__\n") {
    x1($a);
    die($a."\n");
}

/**
 * For debugging purpose only
 */
function bt($a="__died__\n") {
    echo "<pre/>";
    debug_print_backtrace();
    x($a);
}

/**
 * For debugging purpose only
 */
function l($arg=false) {
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
function iniSetDeveloperMode() {
    ini_set('assert.bail', '1');
    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', '1');
}

/**
 * Perform various cartoweb initializations.
 */
function initializeCartoweb($config) {
    
    if ($config->developerMode) {  
        iniSetDeveloperMode();
    }
}

/**
 * uses reflection
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
 * only updates if destination var exists
 * does not use reflection
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
 * does not use reflection
 */
function copy_all_vars($from_object, $to_object) {

    $from_vars = get_object_vars($from_object);
    foreach ($from_vars as $from_var_name => $value) {
        $to_object->$from_var_name = $from_object->$from_var_name;
    }
    return $to_object;
}

/**
 * uses reflection
 */
function unserializeClass($obj, $className) {

    $class = new ReflectionClass($className);
    $newObj = $class->newInstance();
    
    copy_properties($obj, $newObj);
    return $newObj;
}

/**
 * does not use reflection
 */
function unserializeClassNoRefl($obj, $className) {

    $class = new ReflectionClass($className);
    $newObj = $class->newInstance();
    
    copy_all_vars($obj, $newObj);
    return $newObj;
}

/**
 * @package Common
 */
class ConfigParser {

    static function parseArray($value) {
        if (!$value)
            return array();
        $value = explode(',', $value);
        return array_map('trim', $value);
    }
    
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