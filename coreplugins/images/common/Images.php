<?php
/**
 * @package CorePlugins
 * @version $Id$
 */
/**
 * Abstract serializable
 */
require_once(CARTOCOMMON_HOME . 'common/Serializable.php');

/**
 * An Image
 * @package CorePlugins
 */
class Image extends Serializable {

    /**
     * True is this image has to be drawn by server (request) or is drawn (results)
     * @var boolean
     */
    public $isDrawn;
    
    /**
     * Relative path to the image. The Path is relative to the 
     * cartoserverBaseUrl URL
     * @var string
     */
    public $path;
    
    /**
     * Height of the image
     * FIXME: use dimension here
     * @var int
     */
    public $height;
    
    /**
     * Width of the image
     * @var int
     */
    public $width;
    
    /**
     * Format of the image
     * FIXME: this is not used now, nor the the content of this format defined
     *  (mime type, ... ?)
     * @var string
     */
    public $format;
    
    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->isDrawn = self::unserializeValue($struct, 'isDrawn', 'boolean'); 
        $this->path = self::unserializeValue($struct, 'path');
        
        $this->height = self::unserializeValue($struct, 'height', 'int');
        $this->width  = self::unserializeValue($struct, 'width', 'int');
        $this->format = self::unserializeValue($struct, 'format');
    }
}

/**
 * Images object common to requests and results, which contains all cartoweb
 * images used during a map display.
 * @package CorePlugins
 */
class Images extends Serializable {

    /**
     * Main map
     * @var Image
     */
    public $mainmap;

    /**
     * Overview key map
     * @var Image
     */
    public $keymap;

    /**
     * The scalebar
     * @var Image
     */
    public $scalebar;
    
    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->mainmap  = self::unserializeObject($struct, 'mainmap', 'Image');
        $this->keymap   = self::unserializeObject($struct, 'keymap', 'Image');
        $this->scalebar = self::unserializeObject($struct, 'scalebar', 'Image');
    }
}

/**
 * Request for drawing images.
 * @package CorePlugins
 */
class ImagesRequest extends Images { }

/**
 * Result for images returned by the server.
 * @package CorePlugins
 */
class ImagesResult extends Images { }

?>