<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * Project handler
 */
require_once(CARTOCLIENT_HOME . 'coreplugins/project/client/ClientProjectHandler.php');

/**
 * @package CorePlugins
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class ClientProject extends ClientPlugin {

    private $projectHandler;
   
    const TEMPL_VARS_INI_FILE = 'client_conf/project_templ_vars.ini';
    const RESOURCE_NAME_PREFIX = 'project_';
   
    private $log;
    
    private $projectResources; 

    function __construct() {
        parent::__construct();

        $this->log =& LoggerManager::getLogger(__CLASS__);
        
        $this->projectResources = parse_ini_file(CARTOCLIENT_HOME . self::TEMPL_VARS_INI_FILE);
        
        $this->projectHandler = new ClientProjectHandler();
    }

    function loadSession($sessionObject) {
    }

    function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
    }
    function saveSession() {
    }
    
    function handleHttpRequest($request) {
    }

    function buildMapRequest($mapRequest) {}

    function handleMapResult($mapResult) {}

    function renderForm($template) {
        if (!$template instanceof Smarty) {
            throw new CartoclientException('unknown template type');
        }

        foreach ($this->projectResources as $resourceName => $resourceFile) {
            $truePath = $this->projectHandler->getWebPath(CARTOCLIENT_HOME, $resourceFile);
            
            $template->assign(self::RESOURCE_NAME_PREFIX . $resourceName, $truePath);
        }
    }
}
?>