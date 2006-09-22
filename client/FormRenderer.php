<?php
/**
 * Rendering classes
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * @copyright 2005 Camptocamp SA
 * @package Client
 * @version $Id$
 */

/**
 * Project handler
 */
require_once(CARTOWEB_HOME . 'client/ClientProjectHandler.php');
require_once(CARTOWEB_HOME . 'client/Smarty_Cartoclient.php');

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
     * @var string the page title. To be used instead of the default one.
     */
    private $customTitle;

    /**
     * @var string some string to output in addition to regular output
     */
    private $specialOutput = '';

    /**
     *  @var array of activated tools appearing in the interface
     */
    private $tools;
    
    /**
     * Constructor
     * @param Cartoclient
     */
    public function __construct(Cartoclient $cartoclient) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->cartoclient = $cartoclient;

        $this->smarty = $this->getSmarty();
    }

    /**
     * Sets special output to display in addition of standard output.
     * @param string
     * @param bool if true, resets special output container before adding
     * given content (default: false)
     */
    public function setSpecialOutput($output, $reset = false) {
        if ($reset) {
            $this->specialOutput = $output;
        } else {
            $this->specialOutput .= $output;
        }
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
        
        if ($this->cartoclient->getConfig()->toolbarRendering) {
            $toolbarRendering = $this->cartoclient->getConfig()->toolbarRendering;
        } else {
            $toolbarRendering = 'radio';
        }

        $this->tools =& $tools;
            
        $smarty->assign(array('selected_tool' => $clientSession->selectedTool,
                              'tools' => $tools,
                              'toolbar_rendering' => $toolbarRendering));
                
    }

    /**
     * Returns CartoWeb's user and developer messages
     * @return array array of messages (user and developer)
     * @todo Factor getUserMessages() and getDeveloperMessages (bug #1345)
     */
    private function getMessages() {
        $messages = array_merge(
                             $this->cartoclient->getMapResult()->serverMessages,
                             $this->cartoclient->getMessages());
        return $messages;
    }

    /**
     * Returns a user messages array
     * @param array array of messages
     * @return array user messages if any, empty array otherwise
     */
    private function getUserMessages($messages) {
        
        if (empty($messages))
            return array();
        
        $userMessages = array();
        foreach ($messages as $message) {
            if ($message->channel == Message::CHANNEL_USER)
                $userMessages[] = I18N::gt($message->message);
        }

        if (!empty($userMessages))
            return $userMessages;
        else
            return array();
    }

    /**
     * Returns a developers messages array
     * @param array array of messages
     * @return array array of developer messages if any, empty array otherwise
     */
    private function getDeveloperMessages($messages) {
        
        if (empty($messages))
            return;
        
        $developerMessages = array();
        foreach ($messages as $message) {
            if ($message->channel == Message::CHANNEL_DEVELOPER)
                $developerMessages[] = I18N::gt($message->message);
        }

        if (!empty($developerMessages) &&
            $this->cartoclient->getConfig()->showDevelMessages)
            return $developerMessages;
        else
            return array();
    }

    /**
     * Draws user and developer messages
     * @param array array of messages
     */
    private function drawMessages($messages) {
        
        if (empty($messages))
            return;
        
        $userMessages = $this->getUserMessages($messages);
        $developerMessages = $this->getDeveloperMessages($messages);

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
                        ? $_REQUEST['js_folder_idx'] : '1';
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

        $chooserActive = $this->cartoclient->getConfig()->showProjectChooser;
        $this->smarty->assign('projects_chooser_active', $chooserActive);

        // no more drawing if no project chooser
        if (!$chooserActive)
            return;
        
        if (!is_null($this->cartoclient->getConfig()->availableProjects))
            $projects = Utils::parseArray($this->cartoclient->
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
     * instead of the default cartoclient.tpl. If false, Smarty template 
     * rendering will be completely skipped (usefull if a plugin manages the
     * html display itself).
     * 
     */
    public function setCustomForm($customForm) {
        $this->customForm = $customForm;
    }
    
    /**
     * Sets a different title
     * @param string new title
     */
    public function setCustomTitle($customTitle) {
        $this->customTitle = $customTitle;
    }
    
    public function render() {
        if ($this->cartoclient->isAjaxMode()) {
            return $this->showAjaxPluginResponse();
        } else {
            return $this->showForm();
        }
    }
    
    /**
     * Displays GUI using cartoclient.tpl Smarty template
     * @return string
     */
    public function showForm() {

        if (!$this->cartoclient->isInterruptFlow()) {

            $this->drawTools($this->cartoclient);
    
            $messages = array_merge($this->cartoclient->getMapResult()
                                                ->serverMessages,
                                    $this->cartoclient->getMessages());
            $this->drawMessages($messages);
            $this->drawJavascriptFolders();
            $this->drawProjectsChooser();
            $this->drawUserAndRoles();

            // Profile for AjaxHandler
            // Note: usage of {$profile} is deprecated, use {$cartoclient_profile}
            $this->smarty->assign('profile',
                                  $this->cartoclient->getConfig()->profile);
            $this->smarty->assign('cartoclient_profile',
                                  $this->cartoclient->getConfig()->profile);

            // Ajax switch
            $this->smarty->assign('ajaxOn',
                                  $this->cartoclient->getConfig()->ajaxOn);
    
            // ToolPicker
            $this->smarty->assign('toolpicker_active', 
                                  $this->cartoclient->getConfig()->toolPickerOn);
            
            // Lang links
            $this->smarty->assign(array('locales'     => I18n::getLocales(),
                                        'currentLang' => LANG,
                                        ));
            
            // Debug printing
            $this->smarty->assign('debug_request', var_export($_REQUEST, 
                                                              true));
    
            // Handles plugins
            $this->cartoclient->callPluginsImplementing('GuiProvider', 
                                                        'renderForm',
                                                        $this->smarty);
                                                                                                       
            // Sets title
            $cartoclient_title = !is_null($this->customTitle) 
                                 ? $this->customTitle 
                                 : I18n::gt('Cartoclient Title');
            $this->smarty->assign('cartoclient_title', $cartoclient_title); 
        }

        // If set to false, smarty display is skipped
        if (!is_null($this->customForm) && $this->customForm === false) {
            return $this->specialOutput;
        }
        
        $form = !is_null($this->customForm) ? $this->customForm 
                : 'cartoclient.tpl';
        
        return $this->specialOutput . $this->smarty->fetch($form);
    }

    /**
     * Polls and displays all Ajaxable plugins responses,
     * using the XML basaed smarty template
     */
    public function showAjaxPluginResponse() {

        $plugins = $this->cartoclient->getPluginManager()->getPlugins();

        /* 
         * Creates AjaxPluginResponse objects given as argument
         * to enabled plugins' renderAjaxResponse() method to be populated.
         */
        $ajaxPluginResponses = array();
        foreach ($plugins as $plugin) {
            $ajaxPluginResponse = new AjaxPluginResponse();
            $ajaxAction = $this->cartoclient->getAjaxAction(); 
            $this->cartoclient->callEnabledPluginImplementing(
                                                ClientPlugin::ENABLE_LEVEL_FULL,
                                                $plugin->getName(),
                                                'Ajaxable',
                                                'ajaxGetPluginResponse',
                                                &$ajaxPluginResponse,
                                                $ajaxAction);
            if (!$ajaxPluginResponse->isEmpty())
                $ajaxPluginResponses[$plugin->getName()] = $ajaxPluginResponse;
        }
        
        /* 
         * The logic below generates a response for a pseudo-plugin
         * named 'cartoMessages', used to send user and developer messages to
         * the javascript AjaxHandler.
         */           
        $ajaxPluginResponse = new AjaxPluginResponse();
        $messages = $this->getMessages();
        $userMessages = $this->getUserMessages($messages);
        $developerMessages = $this->getDeveloperMessages($messages);
        $ajaxPluginResponse->addVariable('userMessages',
                             Json::arrayFromPhp($userMessages, false));
        $ajaxPluginResponse->addVariable('developerMessages',
                             Json::arrayFromPhp($developerMessages, false));
        $ajaxPluginResponses['cartoMessages'] = $ajaxPluginResponse;
        /*
         * End of the pseudo-plugin logic
         */        

        $this->smarty->assign('encoding', Encoder::getCharset());
        $this->setCustomForm('ajaxPluginResponse.xml.tpl');
        
        // Populates the AjaxPluginResponse.XML template using
        // the populated AjaxPluginResponse objects array
        $this->smarty->assign('pluginResponses', $ajaxPluginResponses);
        header ('Content-Type: text/xml');
        
        return $this->smarty->fetch($this->customForm);
        
    }

    /**
     * Displays failure using failure.tpl Smarty templates
     * @param Exception exception to display
     * @return string
     */
    public function showFailure($exception) {
        
        if (!isset($GLOBALS['headless']))
            header('HTTP/1.1 500 Internal Server Error');

        if ($exception instanceof SoapFault) {
            $message = $exception->faultstring;
        } else {
            $message = $exception->getMessage();
        }
        $smarty = $this->smarty;

        $smarty->assign('exception_class', get_class($exception));
        $smarty->assign('failure_message', $message);
        return $smarty->fetch('failure.tpl');
    }

    /**
     * Return the list of activated tools, see drawTools()
     * @return array
     */
    public function getTools() {
        return $this->tools;
    }
}

?>
