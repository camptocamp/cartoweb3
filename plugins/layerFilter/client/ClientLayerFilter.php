<?php
/**
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
 * @copyright 2008 Camptocamp SA
 * @package Plugins
 * @version $Id$
 */

/**
 * @package Plugins
 */
class ClientLayerFilter extends ClientPlugin
                        implements GuiProvider, Ajaxable, ServerCaller {

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var boolean
     */
    protected $i18n = false;

    /**
     * @var stdClass
     */
    protected $formObjects;

    /**
     * @var array
     */
    protected $criteria;

    /**
     * @var boolean
     */
    protected $reset = false;

    /** 
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     * @param array HTTP request
     */
    public function handleHttpPostRequest($request) {
        
        if (!empty($request['layerFilterReset'])) {
            $this->reset = true;
            return;
        }

        $this->criteria = array();
        
        $formObjects = $this->getFormObjects();
        foreach ($formObjects as $critname => &$critprop) {
            
            if ($critprop->type == 'checkbox') {

                $all_checked = !empty($critprop->allOptionsListed);
                $values = array();
                foreach ($critprop->options as $optname => &$optprop) {
                    if (!empty($request[$critname . '_' . $optname])) {
                        $values[] = $optname;
                        $optprop->selected = true;
                    } else {
                        // at least one option is not checked
                        $all_checked = false;
                        $optprop->selected = false;
                    }
                }

                if ($all_checked) {
                    // all options are checked => no need to filter
                    continue;
                }

                if (count($values) > 0) {
                    // criteria are not listed if all options are checked
                    // (which means no filter is needed)
                    $this->criteria[$critname] = implode(',', $values);
                } else {
                    // no option is checked => full filter
                    $this->criteria[$critname] = false;
                }

            } else {
                // first reset all radio/dropdown options
                foreach ($critprop->options as $optname => &$optprop) {
                    $optprop->selected = false;
                }

                if (isset($request[$critname]) &&
                    array_key_exists($request[$critname], $critprop->options)) {
                    $optname = $request[$critname];
                    $critprop->options->$optname->selected = true;
                    
                    if ($optname != 'null') {
                        // radio and dropdown  criteria are recorded only if 
                        // not "null" and with a value listed in config
                        $this->criteria[$critname] = $optname;
                    }
                }
            }
        }
    }

    /**
     * Makes sure filter criteria are set.
     */
    protected function retrieveCriteria() {
        if (isset($this->criteria)) {
            // criteria are already set, nothing to do
            return;
        }

        // else get default criteria from config
        $formObjects = $this->getFormObjects();
        foreach ($formObjects as $critname => &$critprop) {

            if ($critprop->type == 'checkbox') {
                $options = array();
                $all_checked = !empty($critprop->allOptionsListed);
                foreach ($critprop->options as $optname => $optprop) {
                    if (!empty($optprop->selected)) {
                        $options[] = $optname;
                    } else {
                        $all_checked = false;
                    }
                }

                if ($all_checked) {
                    continue;
                }

                if (count($options) > 0) {
                    $this->criteria[$critname] = implode(',', $options);
                } else {
                    // no option is selected => full filter
                    $this->criteria[$critname] = false;
                }

            } else {
                // radio/dropdown: one value per criterion
                foreach ($critprop->options as $optname => $optprop) {
                    if (!empty($optprop->selected) && $optname != 'null') {
                        $this->criteria[$critname] = $optname;
                    }
                }
            }
        }
    }

    /**
     * @see GuiProvider::handleHttpGetRequest()
     * @param array HTTP request
     */
    public function handleHttpGetRequest($request) {}

    /**
     * @see GuiProvider::renderForm()
     * @param Smarty
     */
    public function renderForm(Smarty $template) {
        $template->assign('layerFilter', $this->drawFilterForm());
    }

    /**
     * Draws filter form.
     * @return string
     */
    protected function drawFilterForm() {
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $smarty->assign(array('formObjects' => $this->getFormObjects(),
                              'i18n'        => $this->i18n,
                              'ajaxOn'      => $this->cartoclient->getConfig()->ajaxOn));
        return $smarty->fetch('filter_form.tpl');
    }

    /**
     * Gets criteria config.
     * @return stdClass
     */
    protected function getFormObjects() {
        if (!isset($this->formObjects)) {
            $iniArray = $this->getConfig()->getIniArray();
            $configStruct = StructHandler::loadFromArray($iniArray);
            $this->i18n = !empty($configStruct->i18n);
            if (empty($configStruct->criteria)) {
                throw new CartoclientException('layerFilter config is missing');
            }
            $this->formObjects = $configStruct->criteria;
        }
        return $this->formObjects;
    }

    /** 
     * @see ServerCaller::buildRequest()
     */
    public function buildRequest() {
        $this->retrieveCriteria();

        if (count($this->criteria) > 0) {
            $layerFilterRequest = new LayerFilterRequest;
            // Keys and values are transmitted separately because WSDL does not
            // support associative arrays with "dynamic" key names.
            $layerFilterRequest->criteria_keys   = array_keys($this->criteria);
            $layerFilterRequest->criteria_values = array_values($this->criteria);
            return $layerFilterRequest;
        }
    }

    /** 
     * @see ServerCaller::initializeResult()
     */ 
    public function initializeResult($result) {}

    /**
     * @see ServerCaller::handleResult()
     */
    public function handleResult($result) {}

    /**
     * @see Ajaxable::ajaxGetPluginResponse()
     */
    public function ajaxGetPluginResponse(AjaxPluginResponse $ajaxPluginResponse) {
        if ($this->reset) {
            $ajaxPluginResponse->addHtmlCode('gui', $this->drawFilterForm());
        }
    }

    /**
     * @see Ajaxable::ajaxHandleAction()
     */
    public function ajaxHandleAction($actionName, PluginEnabler $pluginEnabler) {
        if ($actionName == 'LayerFilter.Apply' ||
            $actionName == 'LayerFilter.Reset') {
            $pluginEnabler->enablePlugin('layerFilter');
        }
    }
}
