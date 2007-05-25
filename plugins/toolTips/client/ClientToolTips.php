<?php
/**
 * Client toolTips plugin
 * Note that it is a unstable version
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
 * @package CorePlugins
 * @version $Id$
 */
require_once 'ToolTipsService.php';

/**
 * Informations to save in session
 * @package Plugins
 */
class ToolTipsState {
    /* TODO : add a switchOn button */
}

/**
 * Client part of ClientToolTips plugin
 * @package Plugins
 */
class ClientToolTips extends ClientPlugin
                      implements GuiProvider, Ajaxable {

    /**
     * @var Logger
     */
    private $log;

    /**
     * True when self::createSession() has been called
     * @var bool
     */
    protected $sessionCreated = false;

    /**
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    /**
     * Handles Get and Post requests
     */
    protected function handleHttpCommonRequest($request) {
        // Interrupt the standard flow if toolTips is set in request
        if (isset ($request['toolTips'])) {
            $formRenderer = $this->getCartoclient()->getFormRenderer();
            $formRenderer->setCustomForm(false);
            $this->getCartoclient()->setInterruptFlow(true);
            $this->drawCustomForm();
        }
    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {
        $this->handleHttpCommonRequest($request);
    }

    /**
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {
        $this->handleHttpCommonRequest($request);
    }

    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {
        $template->assign('toolTips_active', $this->isToolTipsActive());
    }

    /**
     * Handles a toolTipsService, run it and build toolTips HTML
     * code (timeout_async or area_async)
     * Used when flow is interrupted
     * @return string
     */
    private function drawCustomForm() {
        if ($this->cartoclient->getConfig()->profile != 'development') {
            set_error_handler(array('ClientToolTips', 'errorHandler'), E_ALL);
        }

        $toolTipsService = new ToolTipsService($this->cartoclient);
        $toolTipsService->run();
        $response = $toolTipsService->getResponse();
        try {
            if (empty($response)) {
                die();
            } else {
                $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
                $smarty->assign('layersHtmlResult', $response);
                print $smarty->fetch('results.tpl');
            }
        } catch(Exception $e) {
            print "Error... ";
            if (isset($_REQUEST['debug'])) print $e->__toString();
        }
    }
    
    /**
     * Tells if tooltips can be activate
     * for example don't send ajax request if no tooltipsable layer is visible
     * @return boolean 
     */
    private function isToolTipsActive() {
        $toolTipsService = new ToolTipsService($this->cartoclient);
        
        return true;
    }
    
    /**
     * @see Ajaxable::ajaxGetPluginResponse()
     */
    public function ajaxGetPluginResponse(AjaxPluginResponse $ajaxPluginResponse) {
        $ajaxPluginResponse->addVariable('toolTips_active', $this->isToolTipsActive());
    }
    
    /**
     * @see Ajaxable::ajaxHandleAction()
     */
    public function ajaxHandleAction($actionName, PluginEnabler $pluginEnabler) {
        switch ($actionName) {
            case 'Layers.LayerShowHide':
                $pluginEnabler->enablePlugin('toolTips');
            break;
            case 'Layers.LayerDropDownChange':
                $pluginEnabler->enablePlugin('toolTips');
             break;
         }
    }
    
    /**
     * Error handler for tooltips plugin
     */
    public static function errorHandler($errno, $errstr, $errfile, 
                                                $errline) {
        $log =& LoggerManager::getLogger(__METHOD__);

        if (Common::isErrorIgnored($errno, $errstr, $errfile, $errline))
            return;
    
        $log->warn(sprintf("Error in php: errno: %i\n errstr: %s\n" .
                           " errfile: %s (line %i)", 
                           $errno, $errstr, $errfile, $errline));
    }
    
}

?>
