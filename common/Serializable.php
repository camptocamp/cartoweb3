<?php
/**
 * @package Common
 * @version $Id$
 */
require_once(CARTOCOMMON_HOME . 'common/misc_functions.php');
require_once(CARTOCOMMON_HOME . 'common/MapInfo.php'); // for CartocommonException

/**
 * @package Common
 */
abstract class Serializable {
    public $className;
    
    function __construct() {
        $this->className = get_class($this);
    } 
        
    abstract function unserialize ($struct);

    static function unserializeValue ($struct, $property = NULL, $type = 'string') {
        
        if (!$struct)
            return NULL;
        if ($property) {
            $objVars = get_object_vars($struct);
            if (!array_key_exists($property, $objVars))
                return NULL;
            $value = $objVars[$property];
        } else {
            $value = $struct;
        }
        
        switch($type) {
        case 'boolean':
            return (strtolower($value) == 'true' || $value == '1');
        case 'int':
            return (int)$value;
        default:
            return $value;
        }        
    }

    static function unserializeArray ($struct, $property = NULL, $type = 'string') {
        
        if (!$struct)
            return NULL;
        if ($property) {
            $objVars = get_object_vars($struct);
            if (!array_key_exists($property, $objVars))
                return NULL;
            $value = $objVars[$property];
        } else {
            $value = $struct;
        }

        $array = array();
        
        // Arrays are stored as strings in .ini files
        if (is_string($value)) {
            $array = self::unserializeStringArray($value, NULL, $type);
        } else if (!empty($value)) {
            $array = $value;
        }
        return $array;
    }

    static function unserializeStringArray ($struct, $property, $type = 'string') {
    
        if (!$struct)
            return NULL;
        if ($property) {
            $objVars = get_object_vars($struct);
            if (!array_key_exists($property, $objVars))
                return NULL;
            $value = $objVars[$property];
        } else {
            $value = $struct;
        }
            
        $values = ConfigParser::parseArray($value);
        $array = array();
        foreach ($values as $val) {
            $array[] = self::unserializeValue($val, NULL, $type);
        }
        return $array;
    }
    
    static function unserializeObject ($struct, $property = NULL, $className = NULL) {
        
        if (!$struct)
            return NULL;
        if ($property) {
            $objVars = get_object_vars($struct);
            if (!array_key_exists($property, $objVars))
                return NULL;
            $value = $objVars[$property];
        } else {
            $value = $struct;
        }
        
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
    
    static function unserializeObjectMap ($struct, $property = NULL, $className = NULL) {
        
        if (!$struct)
            return NULL;
        if ($property) {
            $objVars = get_object_vars($struct);
            if (!array_key_exists($property, $objVars))
                return NULL;
            $value = $objVars[$property];
        } else {
            $value = $struct;
        }

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