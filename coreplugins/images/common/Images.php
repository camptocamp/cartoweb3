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
 * @package CorePlugins
 */
class Image extends Serializable {

    public $isDrawn;
    public $path;
    // FIXME: use dimension here
    public $height;
    public $width;
    public $format;
    
    function unserialize($struct) {
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
 * 
 * @package CorePlugins
 */
class Images extends Serializable {

    public $mainmap;
    public $keymap;
    public $scalebar;
    
    function unserialize($struct) {
        $this->mainmap  = self::unserializeObject($struct, 'mainmap', 'Image');
        $this->keymap   = self::unserializeObject($struct, 'keymap', 'Image');
        $this->scalebar = self::unserializeObject($struct, 'scalebar', 'Image');
    }
}

/**
 * Request for drawing images.
 * 
 * @package CorePlugins
 */
class ImagesRequest extends Images { }

/**
 * Result for images returned by the server.
 * 
 * @package CorePlugins
 */
class ImagesResult extends Images { }

?>