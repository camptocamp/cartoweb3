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
 * @package Plugins
 * @version $Id$
 */
 

class ImagemapFeature {
    
    /**
     * index of the feature in the area
     */
    protected $index;
    
    /**
     * id of the feature (id_attribute_string)
     * @var string
     */
    public $id;
    
    /**
     * layer id of the feature
     * @var string
     */
    public $layer;
    
    /**
     * attributes
     * @var array
     * TODO protected
     */
    public $attributes = null;
    
    public function setId($id) {
        $this->id = $id;
    }
    
    public function setLayer($layerId) {
        $this->layer = $layerId;
    }
}

/**
 * @package Plugins
 */
class ToolTipsRequest extends CWSerializable {
    
    /**
     * List of imagemapable layers
     * @var array
     */
    public $imagemapLayers;
    
    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->imagemapLayers = self::unserializeValue($struct, 'imagemapLayers');
    }
}

/**
 * @package CorePlugins
 */
class ToolTipsResult extends CWSerializable {

    /**
     * @var string
     */
    public $imagemapFeatures;
    
    /**
     * @var string
     */
    public $imagemapHtmlCode;
    
    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {    
        $this->imagemapHtmlCode = self::unserializeValue($struct, 'imagemapHtmlCode');
        $this->imagemapFeatures = self::unserializeValue($struct, 'imagemapFeatures');
    }
}

?>
