<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * Abstract serializable
 */
require_once(CARTOCOMMON_HOME . 'common/Serializable.php');
require_once(CARTOCOMMON_HOME . 'coreplugins/tables/common/Tables.php');

/**
 * All infos needed to query one layer
 * It extends the IdSelection object defined in the Location core plugin.
 * @package CorePlugins
 */
class QuerySelection extends IdSelection {
    
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
     * @var TableFlags
     */
    public $tableFlags; 

    function unserialize($struct) {
    
        $this->useInQuery = self::unserializeValue($struct, 'useInQuery',
                                                   'boolean');
        $this->policy     = self::unserializeValue($struct, 'policy');
        $this->maskMode   = self::unserializeValue($struct, 'maskMode',
                                                   'boolean');
        $this->tableFlags = self::unserializeObject($struct, 'tableFlags',
                                                    'TableFlags');
                                                    
        parent::unserialize($struct);                                                    
    }
}

/**
 * @package CorePlugins
 */
class QueryRequest extends Serializable {

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
     * - tableFlags = defaultTableFlags
     * @var boolean
     */
    public $queryAllLayers;
     
    /** 
     * @var boolean
     */
    public $defaultMaskMode;
    
    /**
     * @var TableFlags
     */
    public $defaultTableFlags;
       
    /**
     * @var array array of QuerySelection
     */
    public $querySelections;
    
    function unserialize($struct) {
    
        $this->bbox              = self::unserializeObject($struct,
                                       'bbox', 'Bbox');
        $this->queryAllLayers    = self::unserializeValue($struct,
                                       'queryAllLayers', 'boolean');
        $this->defaultMaskMode   = self::unserializeValue($struct,
                                       'defaultMaskMode', 'boolean');
        $this->defaultTableFlags = self::unserializeObject($struct,
                                       'defaultTableFlags', 'TableFlags');
        $this->querySelections   = self::unserializeObjectMap($struct,
                                       'querySelections', 'QuerySelection');
    }
}

/**
 * @package CorePlugins
 */
class QueryResult extends Serializable {

    /**
     * @var TableGroup
     */
    public $tableGroup;
    
    function unserialize($struct) {
    
        $this->tableGroup = Serializable::unserializeObject($struct, 
                                        'tableGroup', 'TableGroup');
    }
}

?>