<?php

// only for debug
function x($a="__died__\n") {
    $log =& LoggerManager::getLogger('__x__');
    
    print "type ".gettype($a)."\n";
    if (is_object($a) or is_array($a)) {
        print "obj\n";
        $log->debug($a);
        var_dump($a);
        x();
    }
    //debug_print_backtrace();
    $log->debug($a);
    die($a."\n");
}

function l($arg=false) {
    $i = 100;
    if ($arg !== false)
        var_dump($arg);
    while ($i--)
        print "x";
    print "\n";
}

// uses reflection
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

// only updates if destination var exists
// does not use reflection
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

// To be removed, using rather copy_properties
// does not use reflection
function copy_all_vars($from_object, $to_object) {

    $from_vars = get_object_vars($from_object);
    foreach ($from_vars as $from_var_name => $value) {
        $to_object->$from_var_name = $from_object->$from_var_name;
    }
    return $to_object;
}

// uses reflection
function unserializeClass($obj, $className) {

    $class = new ReflectionClass($className);
    $newObj = $class->newInstance();
    
    copy_properties($obj, $newObj);
    return $newObj;
}

// does not use reflection
function unserializeClassNoRefl($obj, $className) {

    $class = new ReflectionClass($className);
    $newObj = $class->newInstance();
    
    copy_all_vars($obj, $newObj);
    return $newObj;
}

class ConfigParser {

    static function parseArray($value) {
        if (!$value)
            return array();
        $value = explode(',', $value);
        return array_map('trim', $value);
    }

    static function parseBbox($value) {
        $bbox = new Bbox();
        list($minx, $miny, $maxx, $maxy) = explode(',', $value);

        $bbox->setFromBbox((double)$minx, (double)$miny, 
                           (double)$maxx, (double)$maxy);
        return $bbox;
    }
}
?>