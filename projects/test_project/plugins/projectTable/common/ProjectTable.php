<?php
/**
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 * @version $Id$
 */

/**
 * @package Tests
 */
class ProjectTableRequest extends CwSerializable {

    public function unserialize($struct) {
    }
}

/**
 * @package Tests
 */
class ProjectTableResult extends CwSerializable {

    /**
     * @var Table
     */
    public $tableGroup;

    public function unserialize($struct) {
        $this->tableGroup = self::unserializeObject($struct, 'tableGroup', 'TableGroup');
    }
}

?>
