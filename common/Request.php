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
 * A class for messages returned by the server.
 * Theses messages are intended to be displayed to the client.
 */
class ServerMessage extends Serializable {
    const CHANNEL_USER = 1;
    const CHANNEL_DEVELOPER = 2;
    
    public $message;   
    public $channel;

    function __construct($message = NULL, $channel = self::CHANNEL_USER) {
        $this->message = $message;
        $this->channel = $channel;
    }

    function unserialize($struct) {
        $this->message = Serializable::unserializeValue($struct, 'message');
        $this->channel = Serializable::unserializeValue($struct, 'channel', 'int');
    }
}

/**
 * @package Common
 */
class MapResult extends Serializable {

    public $timeStamp;
    public $locationResult;
    public $imagesResult;
    public $serverMessages;

    function unserialize($struct) {
        $this->timeStamp      = Serializable::unserializeValue($struct, 'timeStamp', 'int');
        $this->locationResult = Serializable::unserializeObject($struct, 'locationResult', 'LocationResult');
        $this->imagesResult   = Serializable::unserializeObject($struct, 'imagesResult', 'ImagesResult');
        $this->serverMessages = Serializable::unserializeObjectMap($struct, 'serverMessages', 'ServerMessage');
            
        foreach (get_object_vars($struct) as $attr => $value) {
            if (substr($attr, -6) == 'Result') {
                $this->$attr = self::unserializeObject($struct, $attr, ucfirst($attr));
            }
        }
    }
}

?>