<?php
/**
 * Tests for Tables plugin
 * @package Tests
 * @version $Id$
 */

/**
 * Abstract test case
 */
require_once 'PHPUnit2/Framework/TestCase.php';

require_once(CARTOCLIENT_HOME . 'coreplugins/tables/common/Tables.php');
require_once(CARTOCLIENT_HOME . 'coreplugins/tables/common/TableRulesRegistry.php');

/**
 * Unit tests for Tables plugin
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class coreplugins_tables_common_TablesCommonTest
                                    extends PHPUnit2_Framework_TestCase {
    
    private function getTableGroups() {
        
        $myTableGroups = array();
    
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
        
        $myTableGroups[] = $myTableGroup;
        
        $myTableGroup = new TableGroup();
        $myTableGroup->groupId = "group_2";
        $myTableGroup->groupTitle = "Group 2";
        $myTableGroup->tables = array();
    
        $myTable = new Table();
        $myTable->tableId = "table_3";
        $myTable->tableTitle = "Table 3";
        $myTable->numRows = 1;
        $myTable->columnIds = array("column_X", "column_Y", "column_Z");
        $myTable->columnTitles = array("Column X", "Column Y", "Column Z");
        $row1 = new TableRow();
        $row1->rowId = "Id1";
        $row1->cells = array("value_x", "value_y", "value_z");
        $myTable->rows = array($row1);
        
        $myTableGroup->tables[] = $myTable;

        $myTableGroups[] = $myTableGroup;
        
        return $myTableGroups;
    }
    
    static function callbackTitleFilter1($id, $title) {
        return "*** $title ***";
    }

    static function callbackTitleFilter2($id, $title) {
        return "!!! $title !!!";
    }
    
    static function callbackTitleFilter3($id, $title) {
        return "%%% $title %%%";
    }
    
    function testGroupFilter1() {
        
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $registry->addGroupFilter('*', 
            array('coreplugins_tables_common_TablesCommonTest',
                  'callbackTitleFilter1'));
        
        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->assertEquals($tableGroups[0]->groupTitle, '*** Group 1 ***');
        $this->assertEquals($tableGroups[1]->groupTitle, '*** Group 2 ***');
    }
    
    function testGroupFilter2() {
        
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $registry->addGroupFilter('*', 
            array('coreplugins_tables_common_TablesCommonTest',
                  'callbackTitleFilter1'));
        $registry->addGroupFilter('group_2', 
            array('coreplugins_tables_common_TablesCommonTest',
                  'callbackTitleFilter2'));
        
        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->assertEquals($tableGroups[0]->groupTitle, '*** Group 1 ***');
        $this->assertEquals($tableGroups[1]->groupTitle, '!!! Group 2 !!!');
    }

    function testTableFilter() {
        
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $registry->addTableFilter('*', 'table*',
            array('coreplugins_tables_common_TablesCommonTest',
                  'callbackTitleFilter1'));
        $registry->addTableFilter('group_2', '*',
            array('coreplugins_tables_common_TablesCommonTest',
                  'callbackTitleFilter2'));
        $registry->addTableFilter('*', 'table_1',
            array('coreplugins_tables_common_TablesCommonTest',
                  'callbackTitleFilter3'));                  
        
        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->assertEquals($tableGroups[0]->tables[0]->tableTitle,
                                                            '%%% Table 1 %%%');
        $this->assertEquals($tableGroups[0]->tables[1]->tableTitle,
                                                            '*** Table 2 ***');
        $this->assertEquals($tableGroups[1]->tables[0]->tableTitle,
                                                            '!!! Table 3 !!!');
    }
    
    function testColumnFilter() {
        
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $registry->addColumnFilter('*', '*', '*',
            array('coreplugins_tables_common_TablesCommonTest',
                  'callbackTitleFilter1'));
        $registry->addColumnFilter('group_1', 'table*', '*',
            array('coreplugins_tables_common_TablesCommonTest',
                  'callbackTitleFilter2'));
        $registry->addColumnFilter('*', '*', 'column_1',
            array('coreplugins_tables_common_TablesCommonTest',
                  'callbackTitleFilter3'));
        
        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->assertEquals($tableGroups[0]->tables[0]->columnTitles[0],
                                                            '!!! Column 1 !!!');
        $this->assertEquals($tableGroups[1]->tables[0]->columnTitles[0],
                                                            '*** Column X ***');
    }
    
    function testColumnSelector() {
    
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $registry->addColumnSelector('*', '*', array('column_1',
                                                     'toto',
                                                     'column_3'));
        
        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->assertEquals($tableGroups[0]->tables[0]->columnIds,
                                        array('column_1', 'column_3'));        
        $this->assertEquals($tableGroups[0]->tables[0]->columnTitles,
                                        array('Column 1', 'Column 3'));        
        $this->assertEquals($tableGroups[0]->tables[0]->rows[0]->cells,
                                        array('value_1', 'value_3'));        
        $this->assertEquals($tableGroups[0]->tables[1]->columnIds,
                                        array());        
    }
    
    function testColumnUnselector() {
    
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $registry->addColumnUnselector('*', '*', array('toto',
                                                       'column_2'));
        
        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->assertEquals($tableGroups[0]->tables[0]->columnIds,
                                        array('column_1', 'column_3'));        
        $this->assertEquals($tableGroups[0]->tables[0]->columnTitles,
                                        array('Column 1', 'Column 3'));        
        $this->assertEquals($tableGroups[0]->tables[0]->rows[0]->cells,
                                        array('value_1', 'value_3'));        
        $this->assertEquals($tableGroups[0]->tables[1]->columnIds,
                                        array('column_A', 'column_B'));        
    }
}

?>