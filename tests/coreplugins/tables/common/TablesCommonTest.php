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

        $this->assertEquals($filteredTableGroups[0]->groupTitle,
                            '*** Group 1 ***');
        $this->assertEquals($filteredTableGroups[1]->groupTitle,
                            '*** Group 2 ***');
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

        $this->assertEquals($filteredTableGroups[0]->groupTitle,
                            '*** Group 1 ***');
        $this->assertEquals($filteredTableGroups[1]->groupTitle,
                            '!!! Group 2 !!!');
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

        $this->assertEquals($filteredTableGroups[0]->tables[0]->tableTitle,
                                                            '%%% Table 1 %%%');
        $this->assertEquals($filteredTableGroups[0]->tables[1]->tableTitle,
                                                            '*** Table 2 ***');
        $this->assertEquals($filteredTableGroups[1]->tables[0]->tableTitle,
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

        $this->assertEquals($filteredTableGroups[0]->tables[0]->columnTitles[0],
                                                            '!!! Column 1 !!!');
        $this->assertEquals($filteredTableGroups[1]->tables[0]->columnTitles[0],
                                                            '*** Column X ***');
    }
    
    function testColumnSelector() {
    
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $registry->addColumnSelector('*', '*', array('column_1',
                                                     'toto',
                                                     'column_3'));
        
        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->assertEquals($filteredTableGroups[0]->tables[0]->columnIds,
                                        array('column_1', 'column_3'));        
        $this->assertEquals($filteredTableGroups[0]->tables[0]->columnTitles,
                                        array('Column 1', 'Column 3'));        
        $this->assertEquals($filteredTableGroups[0]->tables[0]->rows[0]->cells,
                                        array('value_1', 'value_3'));        
        $this->assertEquals($filteredTableGroups[0]->tables[1]->columnIds,
                                        array());        
    }
    
    function testColumnUnselector() {
    
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $registry->addColumnUnselector('*', '*', array('toto',
                                                       'column_2'));
        
        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->assertEquals($filteredTableGroups[0]->tables[0]->columnIds,
                                        array('column_1', 'column_3'));        
        $this->assertEquals($filteredTableGroups[0]->tables[0]->columnTitles,
                                        array('Column 1', 'Column 3'));        
        $this->assertEquals($filteredTableGroups[0]->tables[0]->rows[0]->cells,
                                        array('value_1', 'value_3'));        
        $this->assertEquals($filteredTableGroups[0]->tables[1]->columnIds,
                                        array('column_A', 'column_B'));        
    }
    
    static function callbackCellFilter($inputValues) {
        return $inputValues['column_1'] . '-' . $inputValues['column_3'];
    }

    function testCellFilter() {
    
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $registry->addCellFilter('*', 'table_1', 'column_2', 
                                 array('column_1', 'column_3'),
                                 array('coreplugins_tables_common_TablesCommonTest',
                                       'callbackCellFilter'));

        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->assertEquals($filteredTableGroups[0]->tables[0]->rows[0]->cells[1],
                                        'value_1-value_3');        
        $this->assertEquals($filteredTableGroups[0]->tables[0]->rows[1]->cells[1],
                                        'value_4-value_6');        
        $this->assertEquals($filteredTableGroups[0]->tables[0]->rows[2]->cells[1],
                                        'value_7-value_9');        
    }
    
    static function callbackCellFilterBatch($inputValues) {
        $result = array();
        $oldValue = '|';
        foreach($inputValues as $inputValue) {
            $oldValue .= $inputValue['column_1'] . '-'
                       . $inputValue['column_3'] . '|';
            $result[] = $oldValue;
        }
        return $result;
    }

    function testCellFilterBatch() {
    
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $registry->addCellFilterBatch('*', 'table_1', 'column_2', 
                                      array('column_1', 'column_3'),
                                      array('coreplugins_tables_common_TablesCommonTest',
                                            'callbackCellFilterBatch'));

        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->assertEquals($filteredTableGroups[0]->tables[0]->rows[0]->cells[1],
                            '|value_1-value_3|');        
        $this->assertEquals($filteredTableGroups[0]->tables[0]->rows[1]->cells[1],
                            '|value_1-value_3|value_4-value_6|');        
        $this->assertEquals($filteredTableGroups[0]->tables[0]->rows[2]->cells[1],
                            '|value_1-value_3|value_4-value_6|value_7-value_9|');
        
    }

    static function callbackColumnAdder($inputValues) {
        return array('column_4' =>
                     $inputValues['column_1'] . '-' . $inputValues['column_3']);
    }
    
    function testColumnAdderAbsolute() {
        
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $position = new ColumnPosition(ColumnPosition::TYPE_ABSOLUTE, 1);
        $registry->addColumnAdder('*', 'table_1', $position, array('column_4'), 
                                  array('column_1', 'column_3'),
                                  array('coreplugins_tables_common_TablesCommonTest',
                                        'callbackColumnAdder'));

        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->assertEquals($filteredTableGroups[0]->tables[0]->columnIds[1],
                                        'column_4');        
        $this->assertEquals($filteredTableGroups[0]->tables[0]->columnTitles[1],
                                        'column_4');        
        $this->assertEquals($filteredTableGroups[0]->tables[0]->rows[0]->cells[1],
                                        'value_1-value_3');        
        $this->assertEquals($filteredTableGroups[0]->tables[0]->rows[1]->cells[1],
                                        'value_4-value_6');        
        $this->assertEquals($filteredTableGroups[0]->tables[0]->rows[2]->cells[1],
                                        'value_7-value_9');        
    }
    
    function testColumnAdderRelative() {
    
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $position = new ColumnPosition(ColumnPosition::TYPE_RELATIVE, -2,
                                       'column_3');
        $registry->addColumnAdder('*', 'table_1', $position, array('column_4'), 
                                  array('column_1', 'column_3'),
                                  array('coreplugins_tables_common_TablesCommonTest',
                                        'callbackColumnAdder'));

        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->assertEquals($filteredTableGroups[0]->tables[0]->columnIds,
                            array('column_4', 'column_1', 'column_2', 'column_3'));        
        $this->assertEquals($filteredTableGroups[0]->tables[0]->columnTitles,
                            array('column_4', 'Column 1', 'Column 2', 'Column 3'));        
        $this->assertEquals($filteredTableGroups[0]->tables[0]->rows[0]->cells[0],
                                        'value_1-value_3');        
        $this->assertEquals($filteredTableGroups[0]->tables[0]->rows[1]->cells[0],
                                        'value_4-value_6');        
        $this->assertEquals($filteredTableGroups[0]->tables[0]->rows[2]->cells[0],
                                        'value_7-value_9');        
    }
    
    private function assertColumnAdderMultipleColumn($tableGroups) {
        $this->assertEquals($tableGroups[0]->tables[0]->columnIds,
                            array('column_1', 'column_2', 'column_4',
                                  'column_5', 'column_3'));        
        $this->assertEquals($tableGroups[0]->tables[0]->columnTitles,
                            array('Column 1', 'Column 2', 'column_4',
                                  'column_5', 'Column 3'));        
        $this->assertEquals($tableGroups[0]->tables[0]->rows[0]->cells[2],
                                        'value_1-value_3');        
        $this->assertEquals($tableGroups[0]->tables[0]->rows[1]->cells[2],
                                        'value_4-value_6');        
        $this->assertEquals($tableGroups[0]->tables[0]->rows[2]->cells[2],
                                        'value_7-value_9');        
        $this->assertEquals($tableGroups[0]->tables[0]->rows[0]->cells[3],
                                        'value_1*value_3');        
        $this->assertEquals($tableGroups[0]->tables[0]->rows[1]->cells[3],
                                        'value_4*value_6');        
        $this->assertEquals($tableGroups[0]->tables[0]->rows[2]->cells[3],
                                        'value_7*value_9');        
    }

    static function callbackColumnAdderMultipleColumn($inputValues) {
        return array('column_4' =>
                     $inputValues['column_1'] . '-' . $inputValues['column_3'],
                     'column_5' =>
                     $inputValues['column_1'] . '*' . $inputValues['column_3']);
    }
    
    function testColumnAdderMultipleColumn() {
    
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $position = new ColumnPosition(ColumnPosition::TYPE_RELATIVE, 0,
                                       'column_3');
        $registry->addColumnAdder('*', 'table_1', $position,
                                  array('column_4', 'column_5'), 
                                  array('column_1', 'column_3'),
                                  array('coreplugins_tables_common_TablesCommonTest',
                                        'callbackColumnAdderMultipleColumn'));

        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->assertColumnAdderMultipleColumn($filteredTableGroups);
    }
    
    static function callbackColumnAdderBatch($inputValues) {
        $result = array();
        foreach ($inputValues as $inputValue) {
            $result[] = array('column_4' =>
                     $inputValue['column_1'] . '-' . $inputValue['column_3'],
                     'column_5' =>
                     $inputValue['column_1'] . '*' . $inputValue['column_3']);
        }
        return $result;
    }

    function testColumnAdderBatch() {

        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $position = new ColumnPosition(ColumnPosition::TYPE_RELATIVE, 0,
                                       'column_3');
        $registry->addColumnAdderBatch('*', 'table_1', $position,
                                  array('column_4', 'column_5'), 
                                  array('column_1', 'column_3'),
                                  array('coreplugins_tables_common_TablesCommonTest',
                                        'callbackColumnAdderBatch'));

        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->assertColumnAdderMultipleColumn($filteredTableGroups);
    }
}

?>