<?
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

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    static function prefixTableId1($tableId, $tableTitle) {
        return 'toto_' . $tableTitle;
    }

    static function prefixTableId2($tableId, $tableTitle) {
        return 'titi_' . $tableTitle;
    }

    static function prefixColumn($columnId, $columnTitle) {
        return 'tata_' . $columnTitle;
    }

    static function computeColumn1($inputValues) {
        return array('column_4' => $inputValues['column_1']
                                   . '/' . $inputValues['column_3'],
                     'column_5' => 'value1'); 
    }

    static function computeColumn2($inputValues) {
        return array('column_5' => 'value2',
                     'column_6' => $inputValues['column_2']
                                   . '-' . $inputValues['column_3']               
                                   . '-' . $inputValues['column_4']); 
    }

    static function renameColumn($columnId, $columnTitle) {
        return str_replace('_', ' ', ucfirst($columnId));
    }

    function handlePreDrawing($requ) {
        $result = new ProjectTableResult();

        $myTableGroup = new TableGroup();
        $myTableGroup->groupId = "group_1";
        $myTableGroup->groupTitle = "Group 1";
        $myTableGroup->tables = array();

        $myTable = new Table();
        $myTable->tableId = "table_1";
        $myTable->tableTitle = "Table 1";
        $myTable->numRows = 3;
        $myTable->columnTitles = array("column_1" => "Column 1",
                                       "column_2" => "Column 2",
                                       "column_3" => "Column 3");
        $row1 = new TableRow();
        $row1->cells = array("column_1" => "value_1",
                             "column_2" => "value_2",
                             "column_3" => "value_3");
        $row2 = new TableRow();
        $row2->cells = array("column_1" => "value_4",
                             "column_2" => "value_5",
                             "column_3" => "value_6");
        $row3 = new TableRow();
        $row3->cells = array("column_1" => "value_7",
                             "column_2" => "value_8",
                             "column_3" => "value_9");
        $myTable->rows = array($row1, $row2, $row3);
        
        $myTableGroup->tables[] = $myTable;
        
        $myTable = new Table();
        $myTable->tableId = "table_2";
        $myTable->tableTitle = "Table 2";
        $myTable->numRows = 2;
         
        $myTable->columnTitles = array("column_A" => "Column A",
                                       "column_B" => "Column B");
        $row1 = new TableRow();
        $row1->cells = array("column_A" => "value_a",
                             "column_B" => "value_b");
        $row2 = new TableRow();
        $row2->cells = array("column_A" => "value_c",
                             "column_B" => "value_d");
        $myTable->rows = array($row1, $row2);
        
        $myTableGroup->tables[] = $myTable;
        
        $tablesPlugin = $this->serverContext->getPluginManager()->tables;        
        $resultTableGroups = $tablesPlugin->applyRules($myTableGroup);

        $result->tableGroup = $resultTableGroups[0];

        return $result;
    }    
}
?>