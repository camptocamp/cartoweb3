<?php
/**
 * Classes used to store server configuration on client
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
 * @package Common
 * @version $Id$
 */

/**
 * Abstract serializable
 */
require_once(CARTOCOMMON_HOME . 'common/Serializable.php');

/**
 * Standard classes
 */
require_once(CARTOCOMMON_HOME . 'common/BasicTypes.php');

/**
 * Position on a map
 * @package Common
 */
class Location extends Serializable {
    
    /**
     * @var Bbox
     */
    public $bbox;
    
    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->bbox = self::unserializeObject($struct, 'bbox', 'Bbox');
    }
}

/**
 * Initial position
 * @package Common
 * @see InitialMapState
 */
class InitialLocation extends Location {

}

/**
 * Layer display state
 * @package Common
 */
class LayerState extends Serializable {

    /**
     * State's ID
     * @var string
     */
    public $id;
    
    /**
     * @var boolean
     */
    public $hidden;

    /**
     * @var boolean
     */
    public $frozen;

    /**
     * @var boolean
     */
    public $selected;

    /**
     * @var boolean
     */
    public $unfolded;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->id       = self::unserializeValue($struct, 'id');
        $this->hidden   = self::unserializeValue($struct, 'hidden', 'boolean');
        $this->frozen   = self::unserializeValue($struct, 'frozen', 'boolean');
        $this->selected = self::unserializeValue($struct, 'selected', 
                                                     'boolean');
        $this->unfolded = self::unserializeValue($struct, 'unfolded', 
                                                     'boolean');        
    }
}

/**
 * Initial display state for a mapfile 
 * @package Common
 */
class InitialMapState extends Serializable {

    /**
     * State's ID
     * @var string
     */
    public $id;
    
    /**
     * Initial position on map
     * @var InitialLocation
     */
    public $location;
    
    /**
     * Array of layer states.
     * @var array array of LayerState objects
     */
    public $layers;

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->id       = self::unserializeValue($struct, 'id');
        $this->location = self::unserializeObject($struct, 'location', 
                              'InitialLocation');
        $this->layers   = self::unserializeObjectMap($struct, 'layers', 
                              'LayerState'); 
    }
}

/**
 * Main MapInfo class
 * @package Common
 */
class MapInfo extends Serializable {

    /**
     * Timestamp for cache check
     * @var int
     */
    public $timestamp;
    
    /**
     * @var string
     */
    public $mapLabel;
    
    /**
     * IDs of plugins to be loaded
     * @var array
     */
    public $loadPlugins;
    
    /**
     * @var array
     */
    public $initialMapStates;
    
    /**
     * @var Bbox
     */
    public $extent;
    
    /**
     * @var Location
     */
    public $location;
    
    /**
     * @var GeoDimension
     */
    public $keymapGeoDimension;

    /**
     * Returns a map state identified by its ID
     * @param string
     * @return InitialMapState
     */
    public function getInitialMapStateById($mapStateId) {

        foreach ($this->initialMapStates as $mapState) {
            if ($mapState->id == $mapStateId)
                return $mapState;
        }
        return NULL;
    }
    
    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($struct) {
        $this->timestamp        = self::unserializeValue($struct, 'timestamp');
        $this->mapLabel         = self::unserializeValue($struct, 'mapLabel');
  
        $this->loadPlugins      = self::unserializeArray($struct, 'loadPlugins');
        $this->initialMapStates = self::unserializeObjectMap($struct, 
                                      'initialMapStates', 
                                      'InitialMapState');
        $this->extent           = self::unserializeObject($struct, 'extent', 
                                      'Bbox');
        $this->location         = self::unserializeObject($struct, 'location',
                                      'Location');
        $this->keymapGeoDimension = self::unserializeObject($struct, 
                                      'keymapGeoDimension', 'GeoDimension');
        
        foreach (get_object_vars($struct) as $attr => $value) {
            if (substr($attr, -4) == 'Init') {
                $this->$attr = self::unserializeObject($struct, $attr, 
                                   ucfirst($attr));
            }
        }
    }
}

?>
