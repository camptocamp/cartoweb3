<?php
/**
 * @package Common
 * @version $Id$
 */

/**
 * @package Common
 */
class MapRequest extends Serializable {

    public $mapId;
    public $locationRequest;
    public $imagesRequest;
    public $layersRequest;

    function unserialize($struct) {
        $this->mapId           = Serializable::unserializeValue($struct, 'mapId');
        $this->locationRequest = Serializable::unserializeObject($struct, 'locationRequest', 'LocationRequest');
        $this->imagesRequest   = Serializable::unserializeObject($struct, 'imagesRequest', 'ImagesRequest');
        $this->layersRequest   = Serializable::unserializeObject($struct, 'layersRequest', 'LayersRequest');
            
        foreach (get_object_vars($struct) as $attr => $value) {
            if (substr($attr, -7) == 'Request') {
                $this->$attr = self::unserializeObject($struct, $attr, ucfirst($attr));
            }
        }
    }
}

/**
 * @package Common
 */
class MapResult extends Serializable {

    public $timeStamp;
    public $locationResult;
    public $imagesResult;

    function unserialize($struct) {
        $this->timeStamp      = Serializable::unserializeValue($struct, 'timeStamp', 'int');
        $this->locationResult = Serializable::unserializeObject($struct, 'locationResult', 'LocationResult');
        $this->imagesResult   = Serializable::unserializeObject($struct, 'imagesResult', 'ImagesResult');
            
        foreach (get_object_vars($struct) as $attr => $value) {
            if (substr($attr, -6) == 'Result') {
                $this->$attr = self::unserializeObject($struct, $attr, ucfirst($attr));
            }
        }
    }
}

?>