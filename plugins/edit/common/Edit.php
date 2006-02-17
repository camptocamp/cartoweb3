<?php
/**
 * Edit plugin
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
 * Edit plugin Serializable objects
 * @package Plugins
 * @author Pierre Giraud <pierre.giraud@camptocamp.com>
 * @version $Id$
 */

/**
 * Request
 * @package Plugins
 */
class EditRequest extends CwSerializable {
    
    /** 
     * Shapes to be stored in postGIS
     * @var array
     */
    public $shapes;
    
    /** 
     * Cartoweb layer set in the my_project.ini file
     * @var string
     */
    public $layer;
    
    /** 
     * features to insert, update or delete
     * @var array
     */
    public $features;
    
    /** 
     * id of the object
     * @var string
     */
    public $featuresIds;
    
    /** 
     * is validate button clicked
     * @var boolean
     */
    public $validateAll;
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->shapes   = CwSerializable::unserializeObjectMap($struct, 'shapes');
        $this->layer    = CwSerializable::unserializeValue($struct, 'layer', 'string');
        $this->features = CwSerializable::unserializeArray($struct, 'features', 'Feature');
        $this->featuresIds    = CwSerializable::unserializeValue($struct, 'featuresIds', 'string');
        $this->validateAll = CwSerializable::unserializeValue($struct, 'validateAll', 'boolean');
    }    
}

/**
 * Result
 * @package Plugins
 */
class EditResult extends CwSerializable {

    /**
     * Type of shapes of the mapserver layer
     * @var string
     */
    public $shapeType;
    
    /**
     * Features
     * @var array of Feature
     */
    public $features;
    
    /**
     * List of fields names to display in table
     * @var array array of string
     */
    public $attributeNames;
    
    /**
     * List of fields types to display in table
     * @var array array of string
     */
    public $attributeTypes;
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->shapeType = CwSerializable::unserializeValue($struct, 'shapeType', 'string');
        $this->features = CwSerializable::unserializeArray($struct, 'features', 'Feature');
        $this->attributeNames = CwSerializable::unserializeValue($struct, 'attributeNames');
        $this->attributeTypes = CwSerializable::unserializeValue($struct, 'attributeTypes');
    }
}

?>