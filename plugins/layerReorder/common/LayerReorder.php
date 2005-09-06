<?php
/**
 * LayerReorder plugin CwSerializable objects
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

/**
 * Abstract serializable
 */
require_once(CARTOWEB_HOME . 'common/CwSerializable.php');


/**
 * @package Plugins
 */
class LayerReorderInit extends CwSerializable {

    /**
     * @var array array of Layer
     */
    public $layers;

    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->layers = self::unserializeObjectMap($struct, 'layers', 'LayerInit');
    }
}

/**
 * @package Plugins
 */
class LayerReorderRequest extends CwSerializable {

    /**
     * @var array array of LayerId
     */
    public $layerIds;
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->layerIds  = self::unserializeArray($struct, 'layerIds');
    }
}

/**
 * @package Plugins
 */
class LayerInit extends CwSerializable {
    
    /**
     * @var string Layer Id
     */
    public $id;

    /**
     * @var string Layer label
     */
    public $label;

    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->id = self::unserializeValue($struct, 'id'); 
        $this->label = self::unserializeValue($struct, 'label');
    }
}

?>
