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
                      implements GuiProvider, ServerCaller, Ajaxable {

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
     * html code containing map and area tags
     * @var string
     */
    protected $imagemap = '';

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
        $template->assign(
            array('toolTips_active' => true,
                  'imagemap_active' => true,
                  'imagemap' => $this->drawImagemap($template)));
    }

    /**
     * Renders imagemap javascript code
     * @return string
     */
    protected function drawImagemapJavascriptCode() {
        $this->smarty2 = new Smarty_Plugin($this->getCartoclient(), $this);

        $this->smarty2->assign('imagemapFeatures', $this->imagemapFeatures);

        return $this->smarty2->fetch('imagemapJavascript.tpl');
    }

    /**
     * Renders imagemap HTML code (map and area tags)
     * Used in standard flow (i.e. no interrupt flow)
     * @return string
     */
    protected function drawImagemap() {
        $this->smarty = new Smarty_Plugin($this->getCartoclient(), $this);

        $this->smarty->assign(array(
            'imagemapHtmlCode' => $this->imagemapHtmlCode,
            'imagemapJavascriptCode' => $this->drawImagemapJavascriptCode()));

        return $this->smarty->fetch('imagemap.tpl');
    }



    /**
     * Handles a toolTipsService, run it and build toolTips HTML
     * code (timeout_async or area_async)
     * Used when flow is interrupted
     * @return string
     */
    private function drawCustomForm() {
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
     * Gets the list of imagemapable layers (area_async, area_direct)
     * TODO get it from saved session
     * @return array
     */
    protected function getImagemapLayers() {
        $imagemapLayers = array();

        $iniArray = $this->getConfig()->getIniArray();
        $configStruct = StructHandler::loadFromArray($iniArray);

        //TODO : rename $imagemapLayers -> areaLayers ?
        if (isset($configStruct->area_async)) {
            $area_async = $configStruct->area_async;
            foreach ($area_async as $layerId => $layer) {
                $imagemapLayer = array();
                $imagemapLayer['id'] = $layerId;
                $imagemapLayer['retrieveAttributes'] = false;
                $imagemapLayers[] = $imagemapLayer;
            }
        }
        if (isset($configStruct->area_direct)) {
            $area_direct = $configStruct->area_direct;
            foreach ($area_direct as $layerId => $layer) {
                $imagemapLayer = array();
                $imagemapLayer['id'] = $layerId;
                $imagemapLayer['retrieveAttributes'] = true; 
                $imagemapLayers[] = $imagemapLayer;
            }
        }
        return $imagemapLayers;
    }

    /**
     * @see ServerCaller::buildRequest()
     */
    public function buildRequest() {
        $toolTipsRequest = new TooltipsRequest();

        $toolTipsRequest->imagemapLayers = $this->getImagemapLayers();

        return $toolTipsRequest;
    }

    /**
     * @see ServerCaller::initializeResult()
     */
    public function initializeResult($toolTipsResult) {}

    /**
     * @see ServerCaller::handleResult()
     */
    public function handleResult($toolTipsResult) {
        if (!$toolTipsResult) {
            throw new Exception ("ToolTips plugin is not loaded on server side");
        }
        $this->imagemapHtmlCode = $toolTipsResult->imagemapHtmlCode;
        $this->imagemapFeatures = $toolTipsResult->imagemapFeatures;
    }

    /**
     * @see Ajaxable::ajaxGetPluginResponse()
     */
    public function ajaxGetPluginResponse(AjaxPluginResponse $ajaxPluginResponse) {
        $ajaxPluginResponse->addHtmlCode('imagemapHtmlCode',
            $this->imagemapHtmlCode);
        $ajaxPluginResponse->addHtmlCode('imagemapJavascriptCode',
            $this->drawImagemapJavascriptCode());
    }

    /**
     * @see Ajaxable::ajaxHandleAction()
     */
    public function ajaxHandleAction($actionName, PluginEnabler $pluginEnabler) {
        switch ($actionName) {
            case 'Location.Zoom':
            case 'Location.Pan':
            case 'Layers.LayerShowHide':
            case 'Layers.LayerDropDownChange':
                $pluginEnabler->enablePlugin('toolTips');
            break;
        }
    }
}

?>
