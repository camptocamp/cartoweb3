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

require_once CARTOWEB_HOME . 'client/ExportPlugin.php';

/**
 * @package Plugins
 */
class ClientExportLinkIt extends ExportPlugin
                         implements FilterProvider, ToolProvider {

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
     * @var boolean
     */
    protected $isUrlTooLong = false;

    /**
     * @var array
     */
    protected $params = array();

    /**
     * @var MapRequest
     */
    protected $lastMapRequest;

    /**
     * @var MapResult
     */
    protected $lastMapResult;

    protected $session;

    /**
     * Tool constant
     */
    const TOOL_LINKIT = 'linkit';

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

    /**
     * @see FilterProvider::filterPostRequest()
     */
    public function filterPostRequest(FilterRequestModifier $request) {}

    /**
     * @see FilterProvider::filterGetRequest()
     */
    public function filterGetRequest(FilterRequestModifier $request) {
        if ($this->isUrlCompressed && $request->getValue('q')) {
            
            // char "+" may be used by base64 encoding but might be interpreted
            // by PHP as a white space in $_REQUEST. To avoid that unwished 
            // decoding, replace ' ' by '+'. TODO: find a better fix?
            $q = str_replace(' ', '+', $request->getValue('q'));
            $query_string = urldecode(gzinflate(base64_decode($q)));
            
            foreach (explode('&', $query_string) as $param) {
                if (empty($param)) continue;

                $param_parts = explode('=', $param);
                if (count($param_parts) == 1) {
                    $param_parts[1] = '';
                }
                list($key, $value) = $param_parts;
                
                // case of "array" parameters (eg. "outline_point[]")
                if (preg_match('/(.*)\[(.*)\]/', $key, $regs)) {
                    $key = $regs[1];
                    $sub_key = $regs[2];
                    if (is_null($request->getValue($key))) {
                        if (!empty($sub_key)) {
                            $request->setValue($key, array($sub_key => $value));
                        } else {
                            $request->setValue($key, array($value));
                        }
                    } else {
                        $array = $request->getValue($key);
                        if (!empty($sub_key)) {
                            $array[$sub_key] = $value;
                        } else {
                            $array[] = $value;
                        }
                        $request->setValue($key, $array);
                    }
                } else {
                    $request->setValue($key, $value);
                }
            }

            $request->setValue('q', NULL);
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
        $template->assign(array('linkIt' => $this->drawLinkBoxContainer()));
    }

    /**
     * Returns linkIt box container HTML
     * @return string
     */
    protected function drawLinkBoxContainer() {
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $smarty->assign(array('linkItRequestUrl' => $this->getExportUrl()));
        return $smarty->fetch('link_container.tpl');
    }

    /**
     * Returns "link it" box HTML
     * @return string
     */
    protected function drawLinkBox() {
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $smarty->assign(array('linkItUrl' => $this->getLinkUrl(),
                              'isUrlTooLong' => $this->isUrlTooLong));
        return $smarty->fetch('link_box.tpl');
    }

    /**
     * Builds URL that describes current map context.
     * @param boolean (default true) tells if URL special chars must be escaped
     * @return string
     */
    protected function getLinkUrl($useXhtml = true) {
        $this->setContextQueryString();
        
        if ($this->isUrlCompressed) {
            $this->queryString = 'q=' . base64_encode(gzdeflate($this->queryString, 9));
        }
        $this->queryString = basename($_SERVER['PHP_SELF']) . '?reset_session&' . $this->queryString;

        $resourceHandler = $this->cartoclient->getResourceHandler();
        $url = $resourceHandler->getFinalUrl($this->queryString, true, true, $useXhtml);
    
        $urlMaxLength = $this->getConfig()->urlMaxLength;
        if (!empty($urlMaxLength) && strlen($url) > $urlMaxLength) {
            $this->isUrlTooLong = true;
        }

        return $url;
    }

    /**
     * Sets query string var with map context info.
     */
    protected function setContextQueryString() {
        $this->session = $this->cartoclient->getClientSession();
        $this->lastMapRequest = $this->session->lastMapRequest;
        $this->lastMapResult = $this->session->lastMapResult;

        // layers data
        $this->addLayersParams();

        // location data
        $this->addLocationParams();

        // image data
        $this->addImageParams();

        // outline data
        $this->addOutlineParams();

        // query data
        $this->addQueryParams();

        // customized params
        $this->addCustomizedParams();

        $this->queryString = implode('&', $this->params);
    }

    /**
     * Sets GET parameters for layer data.
     */
    protected function addLayersParams() {
        if (!empty($this->lastMapRequest->layersRequest->switchId)) {
            $this->params[] = 'switch_id=' . $this->lastMapRequest
                                                  ->layersRequest->switchId;
        }

        $clientLayers = unserialize($this->session->pluginStorage->ClientLayers);
        if (!empty($clientLayers->layersData)) {
            $selected_layers = array();
            foreach ($clientLayers->layersData as $layerId => $layerState) {
                if ($layerState->selected) {
                    $selected_layers[] = $layerId;
                }
            }
            $this->params[] = 'layer_select=' . implode(',', $selected_layers);
        }
    }

    /**
     * Sets GET parameters for location data.
     */
    protected function addLocationParams() {
        $this->params[] = 'recenter_bbox=' . $this->lastMapResult->locationResult
                                                  ->bbox->toRemoteString(',');
    }

    /**
     * Sets GET parameters for image data.
     */
    protected function addImageParams() {
        $mainmap = $this->lastMapResult->imagesResult->mainmap;
        
        if (!$mainmap->isDrawn) return;

        $mapsizes = $this->cartoclient->getPluginManager()->getPlugin('images')
                         ->getMapSizes();
        foreach ($mapsizes as $mapid => $mapsize) {
            if ($mapsize['width'] != $mainmap->width ||
                $mapsize['height'] != $mainmap->height) {
                continue;
            }

            $this->params[] = 'mapsize=' . $mapid;
            return;
        }

        $this->params[] = sprintf('customMapsize=%sx%s', $mainmap->width,
                                                         $mainmap->height);
    }

    /**
     * Sets GET parameters for outline data.
     */
    protected function addOutlineParams() {
        if (empty($this->lastMapRequest->outlineRequest)) return;

        foreach($this->lastMapRequest->outlineRequest->shapes as $shape) {
            
            $points = array();
            switch($shape->shape->className) {
                case 'Polygon': 
                case 'Line':
                    $points = $shape->shape->points;
                    
                    if ($shape->shape->className == 'Polygon') {
                        $param_name = 'outline_poly[]';
                        array_pop($points);
                    } else {
                        $param_name = 'outline_line[]';
                    }

                    $points_coords = array();
                    foreach ($points as $point) {
                        $points_coords[] = $point->x . ',' . $point->y;
                    }
                    $param_value = $param_name . '=' . implode(';', $points_coords);
                    break;
                
                case 'Point':
                    $param_value = 'outline_point[]=' . 
                                   $shape->shape->x . ',' . $shape->shape->y;
                    break;

                case 'Circle':
                    $param_value = 'outline_circle[]=' .
                                   $shape->shape->x . ',' . $shape->shape->y .
                                   ';' . $shape->shape->radius;
                    break;
            }

            if (empty($param_value)) continue;
            if (!empty($shape->label)) {
                $param_value .= '|' . urlencode($shape->label);
            }
            $this->params[] = $param_value;
        }
    }

    /**
     * Sets GET parameters for query data.
     */
    protected function addQueryParams() {
        if (empty($this->lastMapResult->queryResult) ||
            empty($this->lastMapResult->queryResult->tableGroup)) return;

        $group = $this->lastMapResult->queryResult->tableGroup;
        if ($group->groupId != 'query') return;

        $hasQueryParams = false;
        foreach($group->tables as $table) {

            $rowHasId = false;

            if ($table->numRows == 0) continue;

            $selectedIds = array();
            foreach($table->rows as $row) {
                if (!empty($row->rowId)) {
                    $hasQueryParams = $rowHasId = true;
                    $selectedIds[] = urlencode($row->rowId);
                }
            }
            if ($rowHasId) {
                $this->params[] = sprintf('query_blocks[%s]=%s',
                                          $table->tableId,
                                          implode(',', $selectedIds));
            }
        }

        if ($hasQueryParams) {
            $this->params[] = 'query_hilight=1';
            $this->params[] = 'query_return_attributes=1';
        }
    }

    /**
     * Use this method to add your own parameters from customized plugins.
     */
    protected function addCustomizedParams() {}

    /** 
     * @see ToolProvider::handleMainmapTool()
     */
    public function handleMainmapTool(ToolDescription $tool,
                               Shape $mainmapShape) {}
    
    /** 
     * @see ToolProvider::handleKeymapTool()
     */
    public function handleKeymapTool(ToolDescription $tool,
                              Shape $keymapShape) {}

    /** 
     * @see ToolProvider::handleApplicationTool()
     */
    public function handleApplicationTool(ToolDescription $tool) {}

    /** 
     * @see ToolProvider::getTools()
     */
    public function getTools() {
        return array(new ToolDescription(self::TOOL_LINKIT, true, 120,
                                         ToolDescription::MAINMAP, false, 
                                         1, false, true));
    }

    /**
     * @see ExportPlugin::getExport()
     */
    protected function getExport() {}

    /**
     * @see ExportPlugin::output()
     */
    public function output() {
        return $this->drawLinkBox();
    }
}
