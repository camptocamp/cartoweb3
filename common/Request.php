<?php
/**
 * Classes for SOAP transfers
 * @package Common
 * @version $Id$
 */

/**
 * Request sent from client to server
 * @package Common
 */
class MapRequest extends Serializable {

    /** 
     * @var string
     */
    public $mapId;
    
    /**
     * @var LocationRequest
     */
    public $locationRequest;
    
    /**
     * @var ImagesRequest
     */
    public $imagesRequest;
    
    /**
     * @var LayersRequest
     */
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
 * Result sent from server to client
 * @package Common
 */
class MapResult extends Serializable {

    /**
     * @var int
     */
    public $timeStamp;
    
    /**
     * @var LocationResult
     */
    public $locationResult;
    
    /**
     * @var ImagesResult
     */
    public $imagesResult;
    
    /**
     * Array of Message
     * @var array
     */
    public $serverMessages;

    function unserialize($struct) {
        $this->timeStamp      = Serializable::unserializeValue($struct, 'timeStamp', 'int');
        $this->locationResult = Serializable::unserializeObject($struct, 'locationResult', 'LocationResult');
        $this->imagesResult   = Serializable::unserializeObject($struct, 'imagesResult', 'ImagesResult');
        $this->serverMessages = Serializable::unserializeObjectMap($struct, 'serverMessages', 'Message');
            
        foreach (get_object_vars($struct) as $attr => $value) {
            if (substr($attr, -6) == 'Result') {
                $this->$attr = self::unserializeObject($struct, $attr, ucfirst($attr));
            }
        }
    }
}

?>