<?php
/**
 * Classes for user and developer messages
 * @package Common
 * @version $Id$
 */

/**
 * A class for messages
 *
 * Theses messages are intended to be displayed to the client.
 * @package Common
 */
class Message extends Serializable {

    /**
     * Constants to define the different the purpose of the message. Used for 
     * instance to distinguate user and developer targeted messages.
     */
    const CHANNEL_USER = 1;
    const CHANNEL_DEVELOPER = 2;
    
    /**
     * The name of the plugin generation this message, or null if not created 
     * from a plugin
     * @var string 
     */
    public $plugin;
    
    /**
     * Optional message identifier for machine parsing of messages. For 
     * instance, a message labeled "Too many results found", can have a 
     * message identifier name "tooManyResults"
     * @var string
     */
    public $messageId;

    /**
     * The text of the message to show to the user.
     * @var string
     */
    public $message;
    
    /**
     * The channel identifier of this message. See the constants named
     * CHANNEL_... .
     * @var int
     */
    public $channel;

    /**
     * Constructor
     * @param string the text of the message
     * @param int the channel identifier of the message
     * @param string optinal plugin name attached to this message
     * @param string optional message identifier, for machine message parsing
     */
    public function __construct($message = NULL, $channel = self::CHANNEL_USER, 
                                $plugin = NULL, $messageId = NULL) {
        parent::__construct();
        $this->plugin = $plugin;
        $this->messageId = $messageId;
        $this->message = $message;
        $this->channel = $channel;
    }

    /**
     * Sets object properties from $struct data.
     */
    public function unserialize($struct) {
        $this->plugin = Serializable::unserializeValue($struct, 'plugin');
        $this->messageId = Serializable::unserializeValue($struct, 'messageId');
        $this->message = Serializable::unserializeValue($struct, 'message');
        $this->channel = Serializable::unserializeValue($struct, 'channel', 
                                                        'int');
    }
}

?>
