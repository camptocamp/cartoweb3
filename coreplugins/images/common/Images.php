<?php
/**
 *
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * @copyright 2005 Camptocamp SA
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
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->isDrawn = self::unserializeValue($struct, 'isDrawn', 'boolean'); 
        $this->path = self::unserializeValue($struct, 'path');
        
        $this->height = self::unserializeValue($struct, 'height', 'int');
        $this->width  = self::unserializeValue($struct, 'width', 'int');
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