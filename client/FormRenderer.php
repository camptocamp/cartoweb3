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
     * @param ClientConfig configuration
     */
    function __construct($config) {
        parent::__construct();

        $this->template_dir = $config->basePath . 'templates/';
        $this->compile_dir = $config->basePath . 'templates_c/';
        $this->config_dir = $config->basePath . 'configs/';
        $this->cache_dir = $config->basePath . 'cache/';
        
        $this->caching = $config->smartyCaching;
        $this->compile_check = $config->smartyCompileCheck;
        $this->debugging = $config->smartyDebugging;
        
        $this->projectHandler = new ClientProjectHandler();
        
        // Block function for ressources
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
        $this->template_dir = CARTOCLIENT_HOME 
                              . $this->projectHandler->getPath(CARTOCLIENT_HOME,
                                            $oldPath, $resource_name);
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
    function __construct(ClientConfig $config, ClientPlugin $plugin) {
        parent::__construct($config);
        
        $this->template_dir = $plugin->getBasePath() . 'templates/';
    }
}

// TODO: eventually create a class SmartyFormRender, and add a plugin mechanism
// in configuration file to choose the templating sytem to use, as a class 
// which extends the abstract class FormRenderer

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
        $smarty = new Smarty_Cartoclient($this->cartoclient->getConfig());
        
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

        $jsFolderIdx = (isset($_REQUEST['js_folder_idx']) &&
                        is_numeric($_REQUEST['js_folder_idx']))
                        ? $_REQUEST['js_folder_idx'] : '0';
        $smarty->assign('jsFolderIdx', $jsFolderIdx);

        // ------------- debug

        $smarty->assign('debug_request', var_export($_REQUEST, true));

        // Print problems : recursive structure
        //$smarty->assign('debug_cartoclient', var_export($cartoclient, true));

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
