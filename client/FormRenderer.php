<?php
/**
 * Smarty and rendering classes
 * @package Client
 * @version $Id$
 */

/**
 * Smarty templates
 */
require_once('smarty/Smarty.class.php');

/**
 * Project handler
 */
require_once(CARTOCLIENT_HOME . 'client/ClientProjectHandler.php');

/**
 * Specific Smarty engine for Cartoclient
 * @package Client
 */
class Smarty_Cartoclient extends Smarty {

    /**
     * @var ClientProjectHandler
     */
    private $projectHandler;

    /** 
     * Constructor
     * 
     * Initializes dirs and cache, ans registers block functions (resources
     * and i18n).
     * @param Cartoclient the current cartoclient
     */
    function __construct(Cartoclient $cartoclient) {
        parent::__construct();

        $config = $cartoclient->getConfig();
        $this->template_dir = $config->getBasePath() . 'templates/';
        $this->compile_dir = $config->getBasePath() . 'templates_c/';
        $this->config_dir = $config->getBasePath() . 'configs/';
        $this->cache_dir = $config->getBasePath() . 'cache/';
        
        $this->caching = $config->smartyCaching;
        $this->compile_check = $config->smartyCompileCheck;
        $this->debugging = $config->smartyDebugging;
        
        $this->projectHandler = $cartoclient->getProjectHandler();
        
        // Block function for resources
        $this->register_block('r', 'smartyResource');
        
        // Block function for translation
        $this->register_block('t', 'smartyTranslate');        
    }

    /**
     * Overrides Smarty's resource compile path
     *
     * Updates template dir to point to the right project and insert a compile
     * id to have one cache file per project and per template.
     * @param string resource name
     * @return string path to resource  
     */    
    function _get_compile_path($resource_name)
    {
        $oldPath = $this->template_dir;
        $oldPath = substr($oldPath, strlen(CARTOCLIENT_HOME) - strlen($oldPath));
        // FIXME: should not hardcode projects constant !
        if (substr($oldPath, 0, 9) == 'projects/') {
            $oldPath = substr($oldPath,
                strlen($this->projectHandler->getProjectName()) + 10 - strlen($oldPath));
        }
        $this->template_dir = CARTOCLIENT_HOME 
                              . $this->projectHandler->getPath($oldPath, 
                                                               $resource_name);
        $this->_compile_id = md5($this->template_dir);
        
        return $this->_get_auto_filename($this->compile_dir, $resource_name,
                                         $this->_compile_id) . '.php';
    }
    
}

/**
 * Specific Smarty engine for core plugins
 * @package Client
 */
class Smarty_CorePlugin extends Smarty_Cartoclient {

    /**
     * @param ClientConfig
     * @param ClientPlugin
     */
    function __construct(Cartoclient $cartoclient, ClientPlugin $plugin) {
        parent::__construct($cartoclient);
        
        $this->template_dir = $plugin->getBasePath() . 'templates/';

        $this->assignCommonVariables($cartoclient);
    }

    /**
     * Fills some smarty variables common to all core plugins.
     * 
     * @param Cartoclient cartoclient object used to fill common smarty variables.
     */
    private function assignCommonVariables(Cartoclient $cartoclient) {
        // sets the project name, as it is propagated through hidden variables.
        $this->assign('project', $cartoclient->getProjectHandler()->
                      getProjectName());

    }
}

/**
 * Class responsible for GUI display
 * @package Client
 */
class FormRenderer {

    /**
     * @var Logger
     */
    private $log;
    
    /**
     * @var Cartoclient
     */
    private $cartoclient;

    /**
     * @param Cartoclient
     */
    function __construct($cartoclient) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->cartoclient = $cartoclient;

        $this->smarty = $this->getSmarty();
    }

    /**
     * @return Smarty_Cartoclient
     */
    private function getSmarty() {
        $smarty = new Smarty_Cartoclient($this->cartoclient);
        
        return $smarty;
    }

    /**
     * Draws tool bar
     *
     * Tools are ordered thanks to weight system.
     * @param Cartoclient Cartoclient
     */
    private function drawTools($cartoclient) {
        
        $cartoForm = $cartoclient->getCartoForm();
        $clientSession = $cartoclient->getClientSession();
        $smarty = $this->smarty;
        $plugins = $cartoclient->getPluginManager()->getPlugins();
        
        $tools = array();
        foreach ($plugins as $plugin) {
            if ($plugin instanceof ToolProvider) {

                $toolsDescription = $cartoclient->getPluginManager()->
                        callPluginImplementing($plugin, 'ToolProvider', 'getTools');

                foreach($toolsDescription as $toolDescription) {

                    $jsAttr = $toolDescription->jsAttributes;
                    assert(is_object($jsAttr));

                    $toolDescription->js = new stdClass();
                    $toolDescription->js->shapeType = $jsAttr->getShapeTypeString();
                    $toolDescription->js->cursorStyle = $jsAttr->getCursorStyleString();
                    $toolDescription->js->action = $jsAttr->getActionString();
                    
                    $tools[$toolDescription->weight] = $toolDescription;
                }
            }
        }
        ksort($tools);

        if (empty($clientSession->selectedTool)) {
            if ($this->cartoclient->getConfig()->initialTool) {
                $clientSession->selectedTool =
                    $this->cartoclient->getConfig()->initialTool;
            } else {
                $toolsIds = array_keys($tools);
                if (!empty($toolsIds))
                    $clientSession->selectedTool = $tools[$toolsIds[0]]->id;
            }
        }       
        $smarty->assign('selected_tool', $clientSession->selectedTool);
                
        $smarty->assign('tools', $tools);        
    }

    /**
     * Draws user and developer messages
     * @param array array of messages
     */
    private function drawMessages($messages) {
        
        if (empty($messages))
            return;
        
        $userMessages = array();
        $developerMessages = array();
        foreach ($messages as $message) {
            if ($message->channel == Message::CHANNEL_USER)
                $userMessages[] = I18N::gt($message->message);
            if ($message->channel == Message::CHANNEL_DEVELOPER)
                $developerMessages[] = I18N::gt($message->message);
        }

        $smarty = $this->smarty;
        
        if (!empty($userMessages))
            $smarty->assign('user_messages', $userMessages);
        if (!empty($developerMessages) &&
            $this->cartoclient->getConfig()->showDevelMessages)
            $smarty->assign('developer_messages', $developerMessages);
    }
    
    /**
     * Sets template variables for displaying the javascript folders.
     */
    private function drawJavascriptFolders() {
        $smarty = $this->smarty;
            
        $jsFolderIdx = (isset($_REQUEST['js_folder_idx']) &&
                        is_numeric($_REQUEST['js_folder_idx']))
                        ? $_REQUEST['js_folder_idx'] : '0';
        $smarty->assign('jsFolderIdx', $jsFolderIdx);
    }
    
    /**
     * Draw a drop-down list with project names.
     */
    private function drawProjectsChooser() {
               
        // sets the project name
        // templates should at least have a hidden 'project' parameter to 
        //  keep the project while reloading (if using the GET/POST project name).
        $this->smarty->assign('project', $this->cartoclient->getProjectHandler()->
                                        getProjectName());

        $chooserActive =  $this->cartoclient->getConfig()->showProjectChooser;
        $this->smarty->assign('projects_chooser_active', $chooserActive);

        // no more drawing if no project chooser
        if (!$chooserActive)
            return;
        
        if (!is_null($this->cartoclient->getConfig()->availableProjects))
            $projects = ConfigParser::parseArray($this->cartoclient->
                                            getConfig()->availableProjects);
        else
            $projects = $this->cartoclient->getProjectHandler()->getAvailableProjects();

        // TODO: associate project name to a label (in config, in project dir ?, ...)
        $this->smarty->assign(array('project_values' => $projects,
                                    'project_output' => $projects));
    }
    
    /**
     * Displays GUI using cartoclient.tpl Smarty template
     * @param Cartoclient Cartoclient
     */
    function showForm($cartoclient) {

        $cartoForm = $cartoclient->getCartoForm();
        $smarty = $this->smarty;

        $this->drawTools($cartoclient);

        $messages = array_merge($cartoclient->getMapResult()->serverMessages,
                                $cartoclient->getMessages());
        $this->drawMessages($messages);

        $this->drawJavascriptFolders();

        $this->drawProjectsChooser();

        // debug printing

        $smarty->assign('debug_request', var_export($_REQUEST, true));

        // handle plugins

        $cartoclient->callPluginsImplementing('GuiProvider', 'renderForm', $smarty);

        // TODO: plugins should be able to change the flow

        $smarty->display('cartoclient.tpl');
    }

    /**
     * Displays failure using failure.tpl Smarty templates
     * @param Exception exception to display
     */
    function showFailure($exception) {

        if ($exception instanceof SoapFault) {
            $message = $exception->faultstring;
        } else {
            $message = $exception->getMessage();
        }
        $smarty = $this->smarty;

        $smarty->assign('exception_class', get_class($exception));
        $smarty->assign('failure_message', $message);
        $smarty->display('failure.tpl');
    }
}

?>
