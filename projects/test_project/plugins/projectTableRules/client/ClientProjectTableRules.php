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
class ClientProjectTableRules extends ClientPlugin {
    private $log;

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    static function computeQueryUrl($inputValues) {
        return array('url' => '<a href="'
                              . $inputValues['FNAME']
                              . '">go to url</a>'); 
    }

    function initialize() {
            
        $tablesPlugin = $this->cartoclient->getPluginManager()->tables;        
        $registry = $tablesPlugin->getTableRulesRegistry();
                                                                        
        $registry->addColumnAdder('query', '*',
            new ColumnPosition(ColumnPosition::TYPE_ABSOLUTE, -1),
            array('url'), array('FNAME'),
            array('ClientProjectTableRules', 'computeQueryUrl'));                                                                
        
    }    
}

?>