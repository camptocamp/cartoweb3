<?
/**
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 * @version $Id$
 */

/**
 * Plugin to test tables rules creation
 * @package Tests
 */
class ServerProjectTableRules extends ServerPlugin {
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

    function initialize() {
            
        $tablesPlugin = $this->serverContext->getPluginManager()->tables;        
        $registry = $tablesPlugin->getTableRulesRegistry();
        
        $registry->addTableFilter('*', '*', array('ServerProjectTable',
                                                  'prefixTableId1'));
        $registry->addTableFilter('*', 'table_*', array('ServerProjectTable',
                                                        'prefixTableId2'));
        
        $registry->addColumnSelector('*', 'table_1', array('column_1',
                                                           'toto',
                                                           'column_3',
                                                           'column_2'));
                
        $registry->addColumnAdder('*', 'table_1',
            new ColumnPosition(ColumnPosition::TYPE_ABSOLUTE, 1),
            array('column_4', 'column_5'), array('column_1', 'column_3'),
            array('ServerProjectTable', 'computeColumn1'));
        $registry->addColumnAdder('*', 'table_1',
            new ColumnPosition(ColumnPosition::TYPE_RELATIVE, -1, 'column_3'),
            array('column_5', 'column_6'), array('column_2', 'column_3', 'column_4'),
            array('ServerProjectTable', 'computeColumn2'));

        $registry->addColumnFilter('*', 'table_1', 'column_3', array('ServerProjectTable',
                                                             'prefixColumn'));
        $registry->addColumnFilter('*', 'table_1', 'column_4', array('ServerProjectTable',
                                                                'renameColumn'));
        $registry->addColumnFilter('*', 'table_1', 'column_5', array('ServerProjectTable',
                                                                'renameColumn'));
        
    }    
}

?>