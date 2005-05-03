<?php
/**
 * Structure management tools
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

/**
 * Structure management class
 * @package Common
 */
class StructHandler {

    const CONTEXT_INI = 1;
    const CONTEXT_OBJ = 2;

    /**
     * Returns a structure from an array loaded from a .ini
     *
     * Value of key my.little.key will be stored in structure->my->little->key. 
     * @param array
     * @return stdClass
     */
    static public function loadFromArray($array) {
        $struct = new stdclass();

        foreach($array as $key => $value) {
            $tokens = explode('.', $key);
            $path = implode('->', $tokens);
            $expr = "\$struct->$path = \"$value\";";
            eval($expr);
        }
        return $struct;
    }

    /**
     * Returns a structure with content of a .ini file
     * @see loadFromArray()
     * @param string file path
     * @return stdClass
     */
    static public function loadFromIni($iniFile) {
        $ini_array = parse_ini_file($iniFile);

        return self::loadFromArray($ini_array);
    }

    /**
     * Merges two objects
     * 
     * Maybe does not belong to struct handler, as it can be used on
     * any structure (to be tested).
     * @param mixed first object
     * @param mixed second object
     * @param boolean if true, prints no warnings
     * @return mixed result of merge
     */
    static public function mergeOverride($object, $override, $mute = false) {
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

    /**
     * Performs full object cloning.
     * @param mixed object to clone
     * @return mixed cloned object
     */
    static public function deepClone($obj) {
        $newObj = clone $obj;

        foreach (get_object_vars($obj) as $propName => $propVal) {
            if (is_object($propVal)) {
                $newObj->$propName = self::deepClone($propVal);
            }       
        }       

        return $newObj; 
    }
}

?>
