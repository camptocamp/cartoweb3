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

    static function loadFromIni($iniFile) {

        $ini_array = parse_ini_file($iniFile);
        $struct = new stdclass();

        foreach($ini_array as $key => $value) {
            $tokens = explode('.', $key);
            $path = implode('->', $tokens);
            $expr = "\$struct->$path = \"$value\";";
            eval($expr);
        }
        return $struct;
    }

    // Maybe does not belong to struct handler, as it can be unsed on
    // any structure (to be tested).
    static function mergeOverride($object, $override) {
         $new_object = clone $object;
         
         foreach(get_object_vars($override) as $property => $value) {
            
            if (in_array($property, 
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