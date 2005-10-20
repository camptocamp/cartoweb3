<?php
/**
 * Unserialization tools
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
 
require_once(CARTOWEB_HOME . 'common/Utils.php');
require_once(CARTOWEB_HOME . 'common/Common.php'); // for CartocommonException

/**
 * Abstract class for all classes that can be serialized
 *
 * CwSerializable classes are typically used to transfer objects through SOAP.
 * @package Common
 */
abstract class CwSerializable {
    
    /**
     * @var string
     */
    public $className;
    
    /**
     * Constructor
     *
     * Stores class name to use it during object unserialization in
     * {@link CwSerializable::unserializeObject()} or
     * {@link CwSerializable::unserializeObjectMap()}.
     */
    public function __construct() {
        $this->className = get_class($this);
    } 
        
    /**
     * Unserializes from a stdClass structure 
     *
     * Each subclass knows how to unserialize.
     * @param stdClass structure to unserialize
     */
    abstract function unserialize($struct);

    /**
     * Returns structure's property value if property exists
     * @param stdClass
     * @param string
     * @return mixed value
     */
    private static function getValue($struct, $property) {
        if (is_null($struct))
            return NULL;
        if ($property) {
            $objVars = get_object_vars($struct);
            if (!array_key_exists($property, $objVars))
                return NULL;
            return $objVars[$property];
        } else {
            return $struct;
        }
        return NULL;        
    }

    /**
     * Returns a typed value from a structure property
     * @param stdClass
     * @param string
     * @param string
     * @return mixed value
     */
    static public function unserializeValue($struct, $property = NULL, 
                                            $type = 'string') {
        
        $value = self::getValue($struct, $property);
        if (is_null($value))
            return NULL;
  
        switch($type) {
        case 'boolean':
            return (strtolower($value) == 'true' || $value == '1');
        case 'int':
            return (int)$value;
        case 'double':
            return (double)$value;
        default:
            return $value;
        }        
    }

    /**
     * Returns an array of typed values
     *
     * If structure property is a string, considers that it is an array
     * serialized in a string (see {@link CwSerializable::unserializeStringArray()}).
     * @param stdClass
     * @param string
     * @param string
     * @return array 
     */
    static public function unserializeArray($struct, $property = NULL, 
                                            $type = 'string') {

        $value = self::getValue($struct, $property);
        if (is_null($value))
            return $value;

        $array = array();
        
        // Arrays are stored as strings in .ini files
        if (is_string($value)) {
            $array = self::unserializeStringArray($value, NULL, $type);
        } else if (!empty($value)) {
            $array = $value;
        }
        return $array;
    }

    /**
     * Returns an array of typed values from a string
     *
     * Uses {@link Utils::parseArray()}.
     * @param stdClass
     * @param string
     * @param string
     * @return array 
     */
    static public function unserializeStringArray($struct, $property, 
                                                  $type = 'string') {

        $value = self::getValue($struct, $property);
        if (is_null($value))
            return $value;
        
            
        $values = Utils::parseArray($value);
        $array = array();
        foreach ($values as $val) {
            $array[] = self::unserializeValue($val, NULL, $type);
        }
        return $array;
    }
    
    /**
     * Tries to guess the class to use from the property being unserialised.
     * It is useful when dealing with non-php client who to not put "className"
     * fields in requests, containing the object class to use.
     * If the property finishes with "Request", it is used as the class name.
     * 
     * @param string
     * @return string
     * @throws CartoserverException if name could not be guessed
     */
    static private function guessClassName($property) {
        if (strpos($property, 'Request') === false) {
            throw new CartocommonException('Object to unserialize has no ' .
                                           'className attribute, and no class '
                                           . 'name was given' . $type);
        }
        return $property;
    }

    /**
     * Copy all properties from one object to another (overwriting previous ones)
     *
     * @param mixed Source object to take properties from
     * @param mixed Destination object where properties are copied
     * @return mixed The destination object, with copied properties
     */
    static private function copyAllVars($from_object, $to_object) {
    
        $from_vars = get_object_vars($from_object);
        foreach ($from_vars as $from_var_name => $value) {
            $to_object->$from_var_name = $from_object->$from_var_name;
        }
        return $to_object;
    }

    /**
     * Returns an unserialized object from a stdClass structure
     *
     * If object is an instance of {@link Serializable}, calls 
     * {@link CwSerializable::unserialize()}. If not, all structure's properties
     * are copied into object.
     * @param stdClass
     * @param string
     * @param string
     * @return mixed 
     */
    static public function unserializeObject($struct, $property = NULL, 
                                             $className = NULL) {

        $value = self::getValue($struct, $property);
        if (is_null($value))
            return $value;
        
        if (empty($className)) {
            if (empty($value->className)) {
                $type = self::guessClassName($property);
            } else
                $type = $value->className;
        } else {
            $type = $className;
        }      
        if (!class_exists($type)) {
            // Class does not exist: 
            // This can be the case when matching client plugin is not active.
            return null;
        }        
        $obj = new $type;
     
        if ($obj instanceof CwSerializable || !is_object($value)) {
            $obj->unserialize($value);
        } else {
            self::copyAllVars($value, $obj);
        }
        return $obj;
    }
    
    /**
     * Returns an array of unserialized objects from a stdClass structure
     * @param stdClass
     * @param string
     * @param string
     * @return array 
     */
    static public function unserializeObjectMap($struct, $property = NULL, 
                                                $className = NULL) {

        $value = self::getValue($struct, $property);
        if (is_null($value))
            return $value;        

        $array = array();
        foreach ($value as $key => $val) {
        
            if (is_object($val) && empty($val->id))
                $val->id = $key;
            $array[$key] = self::unserializeObject($val, NULL, $className);          
        }        
        return $array;
    }
}

?>
