<?php
/**
 * Classes for SOAP transfers
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
 * Request sent from client to server
 * @package Common
 */
class MapRequest extends CwSerializable {

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
        $this->mapId = CwSerializable::unserializeValue($struct, 'mapId');
            
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
class MapResult extends CwSerializable {

    /**
     * @var int
     */
    public $timestamp;
    
    /**
     * Array of Message
     * @var array
     */
    public $serverMessages;

    /**
     * Sets object properties from $struct data.
     */
    public function unserialize($struct) {
        $this->timestamp = CwSerializable::unserializeValue($struct, 'timestamp',
                                                          'int');
        $this->serverMessages 
                         = CwSerializable::unserializeObjectMap($struct, 
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
