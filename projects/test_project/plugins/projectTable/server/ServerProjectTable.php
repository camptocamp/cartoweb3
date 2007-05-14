<?php
/**
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 * @version $Id$
 */

/**
 * Plugin to test tables management
 * @package Tests
 */
class ServerProjectTable extends ClientResponderAdapter {
    private $log;

    /** 
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    public function prefixTableId1($tableId, $tableTitle) {
        return 'toto_' . $tableTitle;
    }

    public function prefixTableId2($tableId, $tableTitle) {
        return 'titi_' . $tableTitle;
    }

    public function prefixColumn($columnId, $columnTitle) {
        return 'tata_' . $columnTitle;
    }

    public function computeColumn1($inputValues) {
        return array('column_4' => $inputValues['column_1']
                                   . '/' . $inputValues['column_3'],
                     'column_5' => 'value1'); 
    }

    public function computeColumn2($inputValues) {
        return array('column_5' => 'value2',
                     'column_6' => $inputValues['column_2']
                                   . '-' . $inputValues['column_3']               
                                   . '-' . $inputValues['column_4']); 
    }

    public function renameColumn($columnId, $columnTitle) {
        return str_replace('_', ' ', ucfirst($columnId));
    }

    public function handlePreDrawing($requ) {
        $result = new ProjectTableResult();

        $myTableGroup = new TableGroup();
        $myTableGroup->groupId = "group_1";
        $myTableGroup->groupTitle = "Group 1";
        $myTableGroup->tables = array();

        $myTable = new Table();
        $myTable->tableId = "table_1";
        $myTable->tableTitle = "Table 1";
        $myTable->numRows = 3;
        $myTable->columnIds = array("column_1", "column_2", "column_3");
        $myTable->columnTitles = array("Column 1", "Column 2", "Column 3");
        $row1 = new TableRow();
        $row1->rowId = "Id1";
        $row1->cells = array("value_1", "value_2", "value_3");
        $row2 = new TableRow();
        $row2->rowId = "Id2";
        $row2->cells = array("value_4", "value_5", "value_6");
        $row3 = new TableRow();
        $row3->rowId = "Id3";
        $row3->cells = array("value_7", "value_8", "value_9");
        $myTable->rows = array($row1, $row2, $row3);
        
        $myTableGroup->tables[] = $myTable;
        
        $myTable = new Table();
        $myTable->tableId = "table_2";
        $myTable->tableTitle = "Table 2";
        $myTable->numRows = 2;
         
        $myTable->columnIds = array("column_A", "column_B");
        $myTable->columnTitles = array("Column A", "Column B");
        $row1 = new TableRow();
        $row1->rowId = "Id1";
        $row1->cells = array("value_a", "value_b");
        $row2 = new TableRow();
        $row2->rowId = "Id2";
        $row2->cells = array("value_c", "value_d");
        $myTable->rows = array($row1, $row2);
        
        $myTableGroup->tables[] = $myTable;
        
        $tablesPlugin = $this->serverContext->getPluginManager()->tables;        
        $resultTableGroups = $tablesPlugin->applyRules($myTableGroup);

        $result->tableGroup = $resultTableGroups[0];

        return $result;
    }    
}
?>
