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
    
    /**
     * Temporary function before the argument order is fixed.
     */
    private function BROKENassertEquals($result, $expected) {
        return $this->assertEquals($expected, $result);
    }
    
    /**
     * Returns an array of interesting {@link TableGroup}s
     * @return array
     */
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
    
    /**
     * Callback function to test title change
     * @param string
     * @param string
     */
    static function callbackTitleFilter1($id, $title) {
        return "*** $title ***";
    }

    /**
     * Callback function to test title change
     * @param string
     * @param string
     */
    static function callbackTitleFilter2($id, $title) {
        return "!!! $title !!!";
    }
    
    /**
     * Callback function to test title change
     * @param string
     * @param string
     */
    static function callbackTitleFilter3($id, $title) {
        return "%%% $title %%%";
    }
    
    /**
     * Tests group title change
     */
    function testGroupFilter1() {
        
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $registry->addGroupFilter('*', 
            array($this, 'callbackTitleFilter1'));
        
        $filteredTableGroups = $registry->applyRules($tableGroups);
        $this->BROKENassertEquals($filteredTableGroups[0]->groupTitle,
                            '*** Group 1 ***');
        $this->BROKENassertEquals($filteredTableGroups[1]->groupTitle,
                            '*** Group 2 ***');
    }
    
    /**
     * Tests group title change (with rule override)
     */
    function testGroupFilter2() {
        
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $registry->addGroupFilter('*', 
            array($this, 'callbackTitleFilter1'));
        $registry->addGroupFilter('group_2', 
            array($this, 'callbackTitleFilter2'));
        
        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->BROKENassertEquals($filteredTableGroups[0]->groupTitle,
                            '*** Group 1 ***');
        $this->BROKENassertEquals($filteredTableGroups[1]->groupTitle,
                            '!!! Group 2 !!!');
    }

    /**
     * Tests table title change (with rule override)
     */
    function testTableFilter() {
        
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $registry->addTableFilter('*', 'table*',
            array($this, 'callbackTitleFilter1'));
        $registry->addTableFilter('group_2', '*',
            array($this, 'callbackTitleFilter2'));
        $registry->addTableFilter('*', 'table_1',
            array($this, 'callbackTitleFilter3'));                  
        
        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->tableTitle,
                                                            '%%% Table 1 %%%');
        $this->BROKENassertEquals($filteredTableGroups[0]->tables[1]->tableTitle,
                                                            '*** Table 2 ***');
        $this->BROKENassertEquals($filteredTableGroups[1]->tables[0]->tableTitle,
                                                            '!!! Table 3 !!!');
    }
    
    /**
     * Callback function to test column title change
     * @param string
     * @param string
     * @param string
     */
    static function callbackColumnTitleFilter1($tableId, $columnId, $title) {
        return "*** $title ***";
    }

    /**
     * Callback function to test column title change
     * @param string
     * @param string
     * @param string
     */
    static function callbackColumnTitleFilter2($tableId, $columnId, $title) {
        return "!!! $title !!!";
    }
    
    /**
     * Callback function to test column title change
     * @param string
     * @param string
     * @param string
     */
    static function callbackColumnTitleFilter3($tableId, $columnId, $title) {
        return "%%% $title %%%";
    }
    
    /**
     * Tests column title change (with rule override)
     */
    function testColumnFilter() {
        
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $registry->addColumnFilter('*', '*', '*',
            array($this, 'callbackColumnTitleFilter1'));
        $registry->addColumnFilter('group_1', 'table*', '*',
            array($this, 'callbackColumnTitleFilter2'));
        $registry->addColumnFilter('*', '*', 'column_1',
            array($this, 'callbackColumnTitleFilter3'));
        
        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->columnTitles[0],
                                                            '!!! Column 1 !!!');
        $this->BROKENassertEquals($filteredTableGroups[1]->tables[0]->columnTitles[0],
                                                            '*** Column X ***');
    }
    
    /**
     * Tests selection of columns
     */
    function testColumnSelector() {
    
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $registry->addColumnSelector('*', '*', array('column_1',
                                                     'toto',
                                                     'column_3'));
        
        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->columnIds,
                                        array('column_1', 'column_3'));        
        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->columnTitles,
                                        array('Column 1', 'Column 3'));        
        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->rows[0]->cells,
                                        array('value_1', 'value_3'));        
        $this->BROKENassertEquals($filteredTableGroups[0]->tables[1]->columnIds,
                                        array());        
    }
    
    /**
     * Tests un-selection of columns
     */
    function testColumnUnselector() {
    
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $registry->addColumnUnselector('*', '*', array('toto',
                                                       'column_2'));
        
        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->columnIds,
                                        array('column_1', 'column_3'));        
        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->columnTitles,
                                        array('Column 1', 'Column 3'));        
        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->rows[0]->cells,
                                        array('value_1', 'value_3'));        
        $this->BROKENassertEquals($filteredTableGroups[0]->tables[1]->columnIds,
                                        array('column_A', 'column_B'));        
    }
    
    /**
     * Callback function to test cell content change
     * @param string
     * @param string
     * @param array
     */
    static function callbackCellFilter($tableId, $columnId, $inputValues) {
        return $inputValues['column_1'] . '-' . $inputValues['column_3'];
    }

    /**
     * Tests change in cell contents
     */
    function testCellFilter() {
    
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $registry->addCellFilter('*', 'table_1', 'column_2', 
                                 array('column_1', 'column_3'),
                                 array($this, 'callbackCellFilter'));

        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->rows[0]->cells[1],
                                        'value_1-value_3');        
        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->rows[1]->cells[1],
                                        'value_4-value_6');        
        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->rows[2]->cells[1],
                                        'value_7-value_9');        
    }
    
    /**
     * Callback function to test cell content change (batch mode)
     * @param string
     * @param string
     * @param array
     */
    static function callbackCellFilterBatch($tableId, $columnId, $inputValues) {
        $result = array();
        $oldValue = '|';
        foreach($inputValues as $inputValue) {
            $oldValue .= $inputValue['column_1'] . '-'
                       . $inputValue['column_3'] . '|';
            $result[] = $oldValue;
        }
        return $result;
    }

    /**
     * Tests change in cell contents (batch mode)
     */
    function testCellFilterBatch() {
    
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $registry->addCellFilterBatch('*', 'table_1', 'column_2', 
                                      array('column_1', 'column_3'),
                                      array($this, 'callbackCellFilterBatch'));

        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->rows[0]->cells[1],
                            '|value_1-value_3|');        
        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->rows[1]->cells[1],
                            '|value_1-value_3|value_4-value_6|');        
        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->rows[2]->cells[1],
                            '|value_1-value_3|value_4-value_6|value_7-value_9|');
        
    }

    /**
     * Tests row deletion
     */
    function testRowUnselector() {

        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();

        $registry->addRowUnselector('*', 'table_1', 'column_2', array('value_5'));
        $registry->addRowUnselector('*', 'table_2', 'column_a', array());
        $registry->addRowUnselector('*', 'table_3', 'row_id', array('Id1'));

        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->assertEquals(2, $filteredTableGroups[0]->tables[0]->numRows);        
        $this->assertEquals('Id1', $filteredTableGroups[0]->tables[0]->rows[0]->rowId);
        $this->assertEquals('Id3', $filteredTableGroups[0]->tables[0]->rows[1]->rowId);

        $this->assertEquals(2, $filteredTableGroups[0]->tables[1]->numRows);

        $this->assertEquals(0, $filteredTableGroups[1]->tables[0]->numRows);
    }

    /**
     * Callback function to test column addition
     * @param string
     * @param array
     */
    static function callbackColumnAdder($tableId, $inputValues) {
        return array('column_4' =>
                     $inputValues['column_1'] . '-' . $inputValues['column_3']);
    }
    
    /**
     * Tests single column addition (absolute position)
     */ 
    function testColumnAdderAbsolute() {
        
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $position = new ColumnPosition(ColumnPosition::TYPE_ABSOLUTE, 1);
        $registry->addColumnAdder('*', 'table_1', $position, array('column_4'), 
                                  array('column_1', 'column_3'),
                                  array($this, 'callbackColumnAdder'));

        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->columnIds[1],
                                        'column_4');        
        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->columnTitles[1],
                                        'column_4');        
        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->rows[0]->cells[1],
                                        'value_1-value_3');        
        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->rows[1]->cells[1],
                                        'value_4-value_6');        
        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->rows[2]->cells[1],
                                        'value_7-value_9');        
    }
    
    /**
     * Tests single column addition (relative position)
     */ 
    function testColumnAdderRelative() {
    
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $position = new ColumnPosition(ColumnPosition::TYPE_RELATIVE, -2,
                                       'column_3');
        $registry->addColumnAdder('*', 'table_1', $position, array('column_4'), 
                                  array('column_1', 'column_3'),
                                  array($this, 'callbackColumnAdder'));

        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->columnIds,
                            array('column_4', 'column_1', 'column_2', 'column_3'));        
        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->columnTitles,
                            array('column_4', 'Column 1', 'Column 2', 'Column 3'));        
        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->rows[0]->cells[0],
                                        'value_1-value_3');        
        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->rows[1]->cells[0],
                                        'value_4-value_6');        
        $this->BROKENassertEquals($filteredTableGroups[0]->tables[0]->rows[2]->cells[0],
                                        'value_7-value_9');        
    }
    
    /**
     * Checks results for a multiple column addition
     * @param array
     */ 
    private function assertColumnAdderMultipleColumn($tableGroups) {
        $this->BROKENassertEquals($tableGroups[0]->tables[0]->columnIds,
                            array('column_1', 'column_2', 'column_4',
                                  'column_5', 'column_3'));        
        $this->BROKENassertEquals($tableGroups[0]->tables[0]->columnTitles,
                            array('Column 1', 'Column 2', 'column_4',
                                  'column_5', 'Column 3'));        
        $this->BROKENassertEquals($tableGroups[0]->tables[0]->rows[0]->cells[2],
                                        'value_1-value_3');        
        $this->BROKENassertEquals($tableGroups[0]->tables[0]->rows[1]->cells[2],
                                        'value_4-value_6');        
        $this->BROKENassertEquals($tableGroups[0]->tables[0]->rows[2]->cells[2],
                                        'value_7-value_9');        
        $this->BROKENassertEquals($tableGroups[0]->tables[0]->rows[0]->cells[3],
                                        'value_1*value_3');        
        $this->BROKENassertEquals($tableGroups[0]->tables[0]->rows[1]->cells[3],
                                        'value_4*value_6');        
        $this->BROKENassertEquals($tableGroups[0]->tables[0]->rows[2]->cells[3],
                                        'value_7*value_9');        
    }

    /**
     * Callback function to test multiple column addition
     * @param string
     * @param array
     */
    static function callbackColumnAdderMultipleColumn($tableId, $inputValues) {
        return array('column_4' =>
                     $inputValues['column_1'] . '-' . $inputValues['column_3'],
                     'column_5' =>
                     $inputValues['column_1'] . '*' . $inputValues['column_3']);
    }
    
    /**
     * Tests multiple column addition
     */ 
    function testColumnAdderMultipleColumn() {
    
        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $position = new ColumnPosition(ColumnPosition::TYPE_RELATIVE, 0,
                                       'column_3');
        $registry->addColumnAdder('*', 'table_1', $position,
                                  array('column_4', 'column_5'), 
                                  array('column_1', 'column_3'),
                                  array($this,
                                        'callbackColumnAdderMultipleColumn'));

        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->assertColumnAdderMultipleColumn($filteredTableGroups);
    }
    
    /**
     * Callback function to test column addition (batch mode)
     * @param string
     * @param array
     */
    static function callbackColumnAdderBatch($tableId, $inputValues) {
        $result = array();
        foreach ($inputValues as $inputValue) {
            $result[] = array('column_4' =>
                     $inputValue['column_1'] . '-' . $inputValue['column_3'],
                     'column_5' =>
                     $inputValue['column_1'] . '*' . $inputValue['column_3']);
        }
        return $result;
    }
    
    /**
     * Tests column addition (batch mode)
     */ 
    function testColumnAdderBatch() {

        $registry = new TableRulesRegistry();
        $tableGroups = $this->getTableGroups();
        
        $position = new ColumnPosition(ColumnPosition::TYPE_RELATIVE, 0,
                                       'column_3');
        $registry->addColumnAdderBatch('*', 'table_1', $position,
                                  array('column_4', 'column_5'), 
                                  array('column_1', 'column_3'),
                                  array($this, 'callbackColumnAdderBatch'));

        $filteredTableGroups = $registry->applyRules($tableGroups);

        $this->assertColumnAdderMultipleColumn($filteredTableGroups);
    }
}

?>