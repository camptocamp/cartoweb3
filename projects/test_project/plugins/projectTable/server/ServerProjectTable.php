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
        
        $tablesPlugin = $this->serverContext->getPluginManager()->tables;
        
        $registry = $tablesPlugin->getTableRulesRegistry();
        
        $registry->addTableFilter('*', array('ServerProjectTable',
                                             'prefixTableId1'));
        $registry->addTableFilter('table_*', array('ServerProjectTable',
                                                   'prefixTableId2'));
        
        $registry->addColumnSelector('table_2', array('column_2', 'toto'));
        $registry->addColumnSelector('*', array('column_1',
                                                'toto',
                                                'column_3',
                                                'column_2'));
                
        $registry->addColumnAdder('*',
            new ColumnPosition(ColumnPosition::TYPE_ABSOLUTE, 1),
            array('column_4', 'column_5'), array('column_1', 'column_3'),
            array('ServerProjectTable', 'computeColumn1'));
        $registry->addColumnAdder('table_1',
            new ColumnPosition(ColumnPosition::TYPE_RELATIVE, -1, 'column_3'),
            array('column_5', 'column_6'), array('column_2', 'column_3', 'column_4'),
            array('ServerProjectTable', 'computeColumn2'));

        $registry->addColumnFilter('tab*', 'column_3', array('ServerProjectTable',
                                                             'prefixColumn'));
        $registry->addColumnFilter('table_1', 'column_4', array('ServerProjectTable',
                                                                'renameColumn'));
        $registry->addColumnFilter('table_1', 'column_5', array('ServerProjectTable',
                                                                'renameColumn'));
        
        $resultTables = $registry->applyRules($myTable);

        $result->table = $resultTables[0];
        return $result;
    }    
}
?>