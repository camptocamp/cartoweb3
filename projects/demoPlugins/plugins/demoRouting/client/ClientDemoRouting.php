<?php
/**
 * @package Plugins
 */

 
class ClientDemoRouting extends ClientRouting {

    /**
     * Param
     */
    private $vertices_options;
    
    
    
    public function __construct() {

        parent::__construct();
        $this->routingState = new RoutingState();
        $this->log =& LoggerManager::getLogger(__CLASS__);
        require_once('DB.php');
    }
    /**
     * Database connection
     * @param boolean
     */
    private function getDb() {
        if (!isset($this->db)) {
            //TODO set the dsn in the routing.ini
            $dsn = $this->getConfig()->dsn;
            $this->db = DB::connect($dsn);
            Utils::checkDbError($this->db);
        }
        return $this->db;
    }
    
    public function listeVilles (){
        // FIXME: table name should be in config
        $sql = "SELECT txt, geom_id FROM roads_europe_vertices WHERE txt != '' ORDER BY txt";
        $this->getDb();
        $res = $this->db->query($sql);
        
        Utils::checkDbError($res);

        while ($res->fetchInto($row)) {
            $list[$row[1]] = $row[0];
        }
        
        return $list;
    }
    
    private function makeSearch() {
        $this->vertices_options = $this->listeVilles();
    }
    

    
    protected function drawRouting() {
        $this->makeSearch();
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $smarty->assign(array('routing_options_values' => array(0,1),
                              'routing_options_labels' => array(I18n::gt('fastest'),
                                                                I18n::gt('shortest'))));        
        $smarty->assign('routing_from',    $this->routingState->from);
        $smarty->assign('routing_to',      $this->routingState->to);
        $smarty->assign('routing_options', $this->routingState->options);
        $smarty->assign('vertices_options', $this->vertices_options);
        return $smarty->fetch('routing.tpl');
    }
    
    public function replacePlugin() {
        return 'routing';
    }
}

?>