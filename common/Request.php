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

    /**
     * Sets object properties from $struct data.
     */
    public function unserialize($struct) {
        $this->mapId = Serializable::unserializeValue($struct, 'mapId');
            
        foreach (get_object_vars($struct) as $attr => $value) {
            if (substr($attr, -7) == 'Request') {
                $this->$attr = self::unserializeObject($struct, $attr);
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
     * Array of Message
     * @var array
     */
    public $serverMessages;

    /**
     * Sets object properties from $struct data.
     */
    public function unserialize($struct) {
        $this->timeStamp = Serializable::unserializeValue($struct, 'timeStamp',
                                                          'int');
        $this->serverMessages 
                         = Serializable::unserializeObjectMap($struct, 
                                                              'serverMessages',
                                                              'Message');
            
        foreach (get_object_vars($struct) as $attr => $value) {
            if (substr($attr, -6) == 'Result') {
                $this->$attr = self::unserializeObject($struct, $attr);
            }
        }
    }
}

?>
