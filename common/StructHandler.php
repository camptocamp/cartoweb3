<?php
/**
 * @package Common
 * @version $Id$
 */

/**
 * @package Common
 */
class StructHandler {
    const CONTEXT_INI = 1;
    const CONTEXT_OBJ = 2;

    static function loadFromArray($array) {
        $struct = new stdclass();

        foreach($array as $key => $value) {
            $tokens = explode('.', $key);
            $path = implode('->', $tokens);
            $expr = "\$struct->$path = \"$value\";";
            eval($expr);
        }
        return $struct;
    }

    static function loadFromIni($iniFile) {
        $ini_array = parse_ini_file($iniFile);

        return self::loadFromArray($ini_array);
    }

    // Maybe does not belong to struct handler, as it can be used on
    // any structure (to be tested).
    static function mergeOverride($object, $override, $mute = false) {
         $new_object = clone $object;
         
         foreach(get_object_vars($override) as $property => $value) {
            
            if (!$mute && in_array($property, 
                array_keys(get_object_vars($object)))) {
                
                print "Warning: overriding property $property\n";
            }
            
            if (in_array($property, 
                array_keys(get_object_vars($object))) &&
                is_object($value)) {
                $new_object->$property = 
                  $this->mergeOverride($object->$property, 
                                       $value);
            } else {
                $new_object->$property = $value;
            }
         }
         return $new_object;
    }
}
?>
