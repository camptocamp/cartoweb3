<?php

abstract class Serializable {
    public $className;
    
    function __construct() {
        $this->className = get_class($this);
    } 
        
    abstract function unserialize ($struct);

    static function unserializeArray ($struct, $type = 'string') {
    
        if (!$struct)
            return array();
            
        $values = ConfigParser::parseArray($struct);
        $array = array();
        foreach ($values as $value) {
            switch($type) {
            case 'boolean':
                $array[] = (boolean)$value;
                break;            
            case 'int':
                $array[] = (int)$value;
                break;
            default:
                $array[] = $value;
            }
        }
        return $array;
    }
    
    static function unserializeObject ($struct, $className = NULL) {
        
        if (!$struct) {
            return NULL;
        }
        
        if (empty($className)) {
            $type = $struct->className;
        } else {
            $type = $className;
        }      
        if (!class_exists($type)) {
            return $struct;
        }
        
        $obj = new $type;
     
        if ($obj instanceof Serializable) {
            $obj->unserialize($struct);
        } else {
            copy_all_vars($struct, $obj);
        }
        return $obj;
    }
    
    static function unserializeObjectMap ($struct, $className = NULL) {
        
        if (!$struct)
            return array();
        $array = array();
        foreach ($struct as $key => $value) {
        
            if (empty($value->id))
                $value->id = $key;
            $array[$key] = self::unserializeObject($value, $className);
        }        
        return $array;
    }
}

?>