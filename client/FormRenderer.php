<?php
/**
 * Rendering classes
 * @package Client
 * @version $Id$
 */

/**
 * Project handler
 */
require_once(CARTOCLIENT_HOME . 'client/ClientProjectHandler.php');
require_once(CARTOCLIENT_HOME . 'client/Smarty_Cartoclient.php');

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
