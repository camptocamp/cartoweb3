<?php
/**
 * @package Plugins
 * @version $Id$
 */
 
/**
 * @package Plugins
 */
class SelectionRequest {

    const POLICY_XOR = 'POLICY_XOR';
    const POLICY_UNION = 'POLICY_UNION';
    const POLICY_INTERSECTION = 'POLICY_INTERSECTION';

    //public $shape;
    
    public $rectangle;
    
    public $policy;
}

/**
 * @package Plugins
 */
class SelectionResult {
 
    public $layerId;
    public $selectedIds;   
}

?>