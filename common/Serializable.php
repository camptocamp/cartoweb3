<?php
/**
 * Unserialization tools
 * @package Common
 * @version $Id$
 */
 
require_once(CARTOCOMMON_HOME . 'common/Utils.php');
require_once(CARTOCOMMON_HOME . 'common/MapInfo.php'); // for CartocommonException

/**
 * Abstract class for all classes that can be serialized
 *
 * Serializable classes are typically used to transfer objects through SOAP.
 * @package Common
 */
abstract class Serializable {
    public $className;
    
    /**
     * Constructor
     *
     * Stores class name to use it during object unserialization in
     * {@link Serializable::unserializeObject()} or
     * {@link Serializable::unserializeObjectMap()}.
     */
    function __construct() {
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
        if (!$struct)
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
    static function unserializeValue($struct, $property = NULL, $type = 'string') {
        
        $value = self::getValue($struct, $property);
        if (is_null($value))
            return $value;
        
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
     * serialized in a string (see {@link Serializable::unserializeStringArray()}).
     * @param stdClass
     * @param string
     * @param string
     * @return array 
     */
    static function unserializeArray($struct, $property = NULL, $type = 'string') {

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
     * Uses {@link ConfigParser::parseArray()}.
     * @param stdClass
     * @param string
     * @param string
     * @return array 
     */
    static function unserializeStringArray($struct, $property, $type = 'string') {

        $value = self::getValue($struct, $property);
        if (is_null($value))
            return $value;
        
            
        $values = ConfigParser::parseArray($value);
        $array = array();
        foreach ($values as $val) {
            $array[] = self::unserializeValue($val, NULL, $type);
        }
        return $array;
    }
    
    /**
     * Returns an unserialized object from a stdClass structure
     *
     * If object is an instance of {@link Serializable}, calls 
     * {@link Serializable::unserialize()}. If not, all structure's properties
     * are copied into object.
     * @param stdClass
     * @param string
     * @param string
     * @return mixed 
     */
    static function unserializeObject($struct, $property = NULL, $className = NULL) {

        $value = self::getValue($struct, $property);
        if (is_null($value))
            return $value;
        
        if (empty($className)) {
            $type = $value->className;
        } else {
            $type = $className;
        }      
        if (!class_exists($type)) {
            throw new CartocommonException("unserializing non existant class $type");
        }
        
        $obj = new $type;
     
        if ($obj instanceof Serializable) {
            $obj->unserialize($value);
        } else {
            copy_all_vars($value, $obj);
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
    static function unserializeObjectMap($struct, $property = NULL, $className = NULL) {

        $value = self::getValue($struct, $property);
        if (is_null($value))
            return $value;
        

        $array = array();
        foreach ($value as $key => $val) {
        
            if (empty($val->id))
                $val->id = $key;
            $array[$key] = self::unserializeObject($val, NULL, $className);
        }        
        return $array;
    }
}

?>