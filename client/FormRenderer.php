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
     * @var Smarty the smarty instance used to template rendering
     */
    private $smarty;
    
    /**
     * @var string the name of a Smarty template resource (in templates
     * directory). To be used instead of the default one.
     */
    private $customForm;
    
    /**
     * @param Cartoclient
     */
    function __construct($cartoclient) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->cartoclient = $cartoclient;

        $this->smarty = $this->getSmarty();
    }

    /**
     * Returns the Smarty template object used for template rendering. It may be
     * used by plugins if they want to override the template display.
     * 
     * @return Smarty_Cartoclient
     */
    public function getSmarty() {
        if (is_null($this->smarty))
            $this->smarty = new Smarty_Cartoclient($this->cartoclient);
        
        return $this->smarty;
    }

    /**
     * Draws tool bar
     *
     * Tools are ordered thanks to weight system.
     * @param Cartoclient Cartoclient
     */
    private function drawTools($cartoclient) {
        
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
     * Sets some variables in the template about the current user
     *  and its roles.
     */
    private function drawUserAndRoles() {
        
        $sm = SecurityManager::getInstance();
        $user = $sm->getUser();
        if (empty($user))
            $user = 'anonymous';
        $this->smarty->assign('username', $user);
        $this->smarty->assign('roles', implode(',', $sm->getRoles()));
    }

    /**
     * Sets a different template resource to be used for display. If false, the
     * Smarty display will be skipped. This is needed if a plugin does the html
     * output on its own.
     * 
     * @param string the string name of a Smarty template resource file to use
     * instead of the default cartoclient.tpl.
     */
    public function setCustomForm($customForm) {
        $this->customForm = $customForm;
    }
    
    /**
     * Displays GUI using cartoclient.tpl Smarty template
     * @param Cartoclient Cartoclient
     */
    public function showForm(Cartoclient $cartoclient) {

        $smarty = $this->smarty;

        if (!isset($GLOBALS['headless']))
            header('Content-Type: text/html; charset=' . Encoder::getCharset());
        $smarty->assign('charset', Encoder::getCharset());

        if (!$this->cartoclient->isInterruptFlow()) {

            $this->drawTools($cartoclient);
    
            $messages = array_merge($cartoclient->getMapResult()->serverMessages,
                                    $cartoclient->getMessages());
            $this->drawMessages($messages);
            $this->drawJavascriptFolders();
            $this->drawProjectsChooser();
            $this->drawUserAndRoles();
    
            // debug printing
            $smarty->assign('debug_request', var_export($_REQUEST, true));
    
            // handle plugins
            $cartoclient->callPluginsImplementing('GuiProvider', 'renderForm', $smarty);
        }
        
        $form = 'cartoclient.tpl';
        // if set to false, smarty display is skipped
        if ($this->customForm === false)
            return;
        if (!is_null($this->customForm))
            $form = $this->customForm;
        $smarty->display($form);
    }

    /**
     * Displays failure using failure.tpl Smarty templates
     * @param Exception exception to display
     */
    public function showFailure($exception) {

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
