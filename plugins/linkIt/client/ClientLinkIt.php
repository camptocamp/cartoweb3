<?php
/**
 * LinkIt plugin saves current map context (layers, location, mapsize, query,
 * outline) in a URL with GET parameters.
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
class ClientLinkIt extends ClientPlugin
                   implements GuiProvider, FilterProvider /*, Ajaxable */ {

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var boolean
     */
    protected $isUrlCompressed;

    /**
     * @var string
     */
    protected $queryString = '';

    /** 
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    public function Initialize() {
        $this->isUrlCompressed = $this->getConfig()->compressUrl;
    }

    public function filterPostRequest(FilterRequestModifier $request) {}

    public function filterGetRequest(FilterRequestModifier $request) {
        if ($this->isUrlCompressed && $request->getValue('q')) {
            $query_string = base64_decode($request->getValue('q'));
            foreach (explode('&', $query_string) as $param) {
                if (empty($param)) continue;

                list($key, $value) = explode('=', $param);
                if (is_null($value)) {
                    $value = '';
                }
                $request->setValue($key, $value);
            }
        }
    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     * @param array HTTP request
     */
    public function handleHttpPostRequest($request) {}

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
        $template->assign(array('linkIt' => $this->drawLinkBox()));
    }

    protected function drawLinkBox() {
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $smarty->assign(array('linkItUrl' => $this->getLinkUrl()));
        return $smarty->fetch('link_box.tpl');
    }

    protected function getLinkUrl() {
        $this->setContextQueryString();
        
        if ($this->isUrlCompressed) {
            //$this->queryString = 'q=' . convert_uuencode($this->queryString);
            $this->queryString = 'q=' . base64_encode($this->queryString);
        }
        // FIXME: detect what "file" to use (eg. client.php)
        $this->queryString = 'client.php?' . $this->queryString;

        $resourceHandler = $this->cartoclient->getResourceHandler();
        return $resourceHandler->getFinalUrl($this->queryString, true, true);
    }

    protected function setContextQueryString() {
        $session = $this->cartoclient->getClientSession();
        $lastMapRequest = $session->lastMapRequest;
        $lastMapResult = $session->lastMapResult;

        $params = array();
        $params['switch_id'] = $lastMapRequest->layersRequest->switchId; 
        $params['layer_select'] = implode(',', $lastMapRequest->layersRequest->layerIds);

        $query_array = array();
        foreach ($params as $key => $value) {
            $query_array[] = "$key=$value"; 
        }
        $this->queryString = implode('&', $query_array);
    }
}
