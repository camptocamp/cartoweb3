<?php
/**
 * This plugin let the user select a point on the map and recover the coordinate 
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
 * @package Plugins
 * @version $Id$
 */

class ClientGeoloc extends ClientPlugin
                   implements FilterProvider, Sessionable,
                              GuiProvider, ToolProvider, Ajaxable {
  
    /**
     * @var Logger
     */
    private $log;

    /**
     * @var XY coordinates
     */
    private $Geo_x;
    private $Geo_y;

    /**
     * @var pluginStatus
     * status of the plugin, activated or not
     */
    private $pluginStatus;

    /** 
     * Constructor
     */
    public function __construct() {
        parent::__construct();  
        $this->log =& LoggerManager::getLogger(__CLASS__);

        $this->pluginStatus = true;
    }

    /**
     * @see Sessionable::loadSession()
     */
    public function loadSession($sessionObject) {
        $this->Geo_x = isset($sessionObject['x']) ? $sessionObject['x'] : '';
        $this->Geo_y = isset($sessionObject['y']) ? $sessionObject['y'] : '';
    }

    /**
     * Reinitializes session-saved tool.
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->Geo_x = '';
        $this->Geo_y = '';
    }

    /**
     * @see Sessionable::saveSession()
     */
    public function saveSession() {
        return array('x' => $this->Geo_x,
                     'y' => $this->Geo_y,
        );
    }
    
    /**
     * @see FilterProvider::filterGetRequest()
     */
    public function filterGetRequest(FilterRequestModifier $request) {

        //$this->pluginStatus = true;

    }

    /**
     * @see FilterProvider::filterPostRequest()
     */
    public function filterPostRequest(FilterRequestModifier $request) {}

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {

        if (isset($request['tool']) && $request['tool'] == 'geoloc') {
            if (isset($request['selection_coords'])) {
                $arr_coord = explode(',',$request['selection_coords']);
                if (sizeof($arr_coord) == 2) {
                    $this->Geo_x = round($arr_coord[0]);
                    $this->Geo_y = round($arr_coord[1]);
                    $this->getCartoclient()->addMessage('geo_x='.$this->Geo_x.',geo_y='.$this->Geo_y);
                }
            }
        }
    }

    /**
     * Recover the tool received via url
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {}
  
      /**
     * This method factors the plugin output for both GuiProvider::renderForm()
     * and Ajaxable::ajaxGetPluginResponse().
     * @return array array of variables and html code to be assigned
     */
    protected function renderFormPrepare() {
        return array('geoloc_active' => $this->pluginStatus);
    }

    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {
        $template->assign($this->renderFormPrepare());
    }

    /**
     * @see ToolProvider::handleMainmapTool()
     */
    public function handleMainmapTool(ToolDescription $tool, Shape $mainmapShape) {}

    /**
     * @see ToolProvider::handleKeymapTool()
     */
    public function handleKeymapTool(ToolDescription $tool, Shape $keymapShape) {}

    /**
     * @see ToolProvider::handleApplicationTool()
     */
    public function handleApplicationTool(ToolDescription $tool) {}

    /**
     * @see ToolProvider::getTools()
     */
    public function getTools() {
         return array(new ToolDescription('geoloc', true, 150, 1));
    }

    /**
     * @see Ajaxable::ajaxGetPluginResponse()
     */
    public function ajaxGetPluginResponse(AjaxPluginResponse $ajaxPluginResponse) {
        $output = $this->renderFormPrepare();
    }
    
    /**
     * @see Ajaxable::ajaxHandleAction()
     */
    public function ajaxHandleAction($actionName, PluginEnabler $pluginEnabler) {
        switch ($actionName) {
            case 'Geoloc.DoIt':
                $pluginEnabler->disableCoreplugins();
                $pluginEnabler->enablePlugin('geoloc');
            break;
        }
    }
}

?>