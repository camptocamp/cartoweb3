<?php
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * @package Plugins
 */
class HilightRequest {
    // maybe have a IdSelection Object used also by the selection module
    //  and have the hilightRequest be capable of ShapeSelection and others. 
    
    public $layerId;
    public $idAttribute;
    public $idType; // (string|integer) 
    public $selectedIds;
}

/* no HilightResult */

?>