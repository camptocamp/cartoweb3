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

    public function computeColumn1($tableId, $inputValues) {
        return array('column_4' => $inputValues['column_1']
                                   . '/' . $inputValues['column_3'],
                     'column_5' => 'value1'); 
    }

    public function computeColumn2($tableId, $inputValues) {
        return array('column_5' => $inputValues['row_id'] . '-value2',
                     'column_6' => $inputValues['column_2']
                                   . '-' . $inputValues['column_3']               
                                   . '-' . $inputValues['column_4']); 
    }

    public function renameColumn($tableId, $columnId, $columnTitle) {
        return str_replace('_', ' ', ucfirst($columnId));
    }

    public function initialize() {
            
        $tablesPlugin = $this->serverContext->getPluginManager()->tables;        
        $registry = $tablesPlugin->getTableRulesRegistry();
        
        $registry->addTableFilter('*', '*', array($this,
                                                  'prefixTableId1'));
        $registry->addTableFilter('*', 'table_*', array($this,
                                                        'prefixTableId2'));
        
        $registry->addColumnSelector('*', 'table_1', array('row_id',
                                                           'column_1',
                                                           'toto',
                                                           'column_3',
                                                           'column_2'));
        $registry->addColumnUnselector('*', 'table_2', array('row_id'));
                
        $registry->addColumnAdder('*', 'table_1',
            new ColumnPosition(ColumnPosition::TYPE_ABSOLUTE, 1),
            array('column_4', 'column_5'), array('column_1', 'column_3'),
            array($this, 'computeColumn1'));
        $registry->addColumnAdder('*', 'table_1',
            new ColumnPosition(ColumnPosition::TYPE_RELATIVE, -1, 'column_3'),
            array('column_5', 'column_6'), array('row_id', 'column_2', 'column_3', 'column_4'),
            array($this, 'computeColumn2'));

        $registry->addColumnFilter('*', 'table_1', 'column_3', array($this,
                                                             'prefixColumn'));
        $registry->addColumnFilter('*', 'table_1', 'column_4', array($this,
                                                                'renameColumn'));
        $registry->addColumnFilter('*', 'table_1', 'column_5', array($this,
                                                                'renameColumn'));
        
    }    
}

?>