<?php


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

    private function getValue($typeDescription, $value, $context) {

        $typeTokens = explode(',', $typeDescription);

        $type = $typeTokens[0];
        $newTypeDescription = implode(',', array_slice($typeTokens, 1));

        switch($type) {
        case 'obj':
            return self::unserialize($value, $typeTokens[1], $context);
        case 'objarray':
            $ret = array();
            foreach ($value as $key => $val) {
                $ret[$key] = self::unserialize($val, $typeTokens[1], $context);
            }
            return $ret;
        case 'map':
            $ret = array();
            if (is_null($value))
                $ret;
            foreach ($value as $key => $val) {
                $v = self::getValue($newTypeDescription, $val, $context);
                if (empty($v->id))
                    $v->id = $key;
                /*
                if ($context == self::CONTEXT_OBJ)
                    $key = $v->id;
                    */
                $ret[$key] = $v;
            }
            return $ret;
        case 'bbox':

            return ConfigParser::parseBbox($value);
        case 'array':

            $values = ConfigParser::parseArray($value);
            if ($newTypeDescription == '') 
                return $values;

            $ret = array();
            foreach ($values as $value) {
                $ret[] = self::getValue($newTypeDescription, $value, $context);
            }

            return $ret;
        case 'boolean':
            return (boolean)$value;
        case 'int':
            return (int)$value;
        default:
            return $value;
        }

    }

    static function unserialize($struct, $className, $context=StructHandler::CONTEXT_INI) {

        if (!class_exists($className))
            throw new CartocommonException("Unserialiazing failure, type \"$className\" does not exists");

        $object = new $className;

        if (in_array('getVarInfo', get_class_methods($object)))
            $varInfo = $object->getVarInfo($context);

        if (empty($varInfo))
            $varInfo = array();

        foreach ($struct as $prop => $value) {
            if (in_array($prop, array_keys($varInfo)))
                $value = self::getValue($varInfo[$prop], $value, $context);
            
            $object->$prop = $value;
        }

        if (in_array('endSerialize', get_class_methods($object)))
            $object = $object->endSerialize();

        return $object;
    }

    static function serialize($object) {
        // TODO: maybe hide server specific properties (for direct access only)
        
        return $object;
    }
}
?>