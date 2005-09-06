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
require_once(CARTOWEB_HOME . 'common/CwSerializable.php');
require_once(CARTOWEB_HOME . 'coreplugins/tables/common/Tables.php');

/**
 * All infos needed to query one layer
 * It extends the IdSelection object defined in the Location core plugin.
 * @package CorePlugins
 */
class QuerySelection extends IdSelection {
    
    /**
     * Policy constants
     */
    const POLICY_XOR = 'POLICY_XOR';
    const POLICY_UNION = 'POLICY_UNION';
    const POLICY_INTERSECTION = 'POLICY_INTERSECTION';

    /**
     * @var boolean
     */
    public $useInQuery;

    /**
     * @var string
     */
    public $policy;
    
    /**
     * @var boolean
     */
    public $maskMode;

    /**
     * @var boolean
     */
    public $hilight;

    /**
     * @var TableFlags
     */
    public $tableFlags; 

    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
    
        $this->useInQuery = self::unserializeValue($struct, 'useInQuery',
                                                   'boolean');
        $this->policy     = self::unserializeValue($struct, 'policy');
        $this->maskMode   = self::unserializeValue($struct, 'maskMode',
                                                   'boolean');
        $this->hilight    = self::unserializeValue($struct, 'hilight',
                                                   'boolean');
        $this->tableFlags = self::unserializeObject($struct, 'tableFlags',
                                                    'TableFlags');
                                                    
        parent::unserialize($struct);                                                    
    }
}

/**
 * @package CorePlugins
 */
class QueryRequest extends CwSerializable {

    /**
     * @var Bbox
     */
    public $bbox;
       
    /**   
     * If true, will query all selected layers
     *
     * Default values:
     * - useInQuery = true
     * - policy = POLICY_UNION
     * - maskMode = defaultMaskMode
     * - hilight = defaultHilight
     * - tableFlags = defaultTableFlags
     * @var boolean
     */
    public $queryAllLayers;
     
    /** 
     * @var boolean
     */
    public $defaultMaskMode;

    /**
     * @var boolean
     */
    public $defaultHilight;
    
    /**
     * @var TableFlags
     */
    public $defaultTableFlags;
       
    /**
     * @var array array of QuerySelection
     */
    public $querySelections;
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
    
        $this->bbox              = self::unserializeObject($struct,
                                       'bbox', 'Bbox');
        $this->queryAllLayers    = self::unserializeValue($struct,
                                       'queryAllLayers', 'boolean');
        $this->defaultMaskMode   = self::unserializeValue($struct,
                                       'defaultMaskMode', 'boolean');
        $this->defaultHilight    = self::unserializeValue($struct,
                                       'defaultHilight', 'boolean');
        $this->defaultTableFlags = self::unserializeObject($struct,
                                       'defaultTableFlags', 'TableFlags');
        $this->querySelections   = self::unserializeObjectMap($struct,
                                       'querySelections', 'QuerySelection');
    }
}

/**
 * @package CorePlugins
 */
class QueryResult extends CwSerializable {

    /**
     * @var TableGroup
     */
    public $tableGroup;
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
    
        $this->tableGroup = CwSerializable::unserializeObject($struct, 
                                        'tableGroup', 'TableGroup');
    }
}

?>
