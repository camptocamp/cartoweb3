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
 * A request for layers.
 *
 * @package CorePlugins
 */
class LayersRequest extends Serializable {
    
    /**
     * The list of layers to draw
     * @var array
     */
    public $layerIds;

    /**
     * Resolution used to draw the images. Another good place for this would
     * have been in ImagesRequest.
     * @var int
     */
    public $resolution;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->layerIds   = self::unserializeArray($struct, 'layerIds');
        $this->resolution = self::unserializeValue($struct, 'resolution',
                                                   'int');
    }
}

/**
 * Result of a layers request. It is empty.
 *
 * @package CorePlugins
 */
class LayersResult {}

?>
