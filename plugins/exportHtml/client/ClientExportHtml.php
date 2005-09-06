<?php
/**
 * HTML Export
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

/**
 * Export super class
 */
require_once(CARTOWEB_HOME . 'client/ExportPlugin.php');

/**
 * HTML export
 * @package Plugins
 */
class ClientExportHtml extends ExportPlugin {

    /**
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    /** 
     * Returns path to Web base URL
     *
     * Being in a plugin, the path to images is not the same.
     * @return string
     */ 
    public function getBaseUrl() {
        return '../';
    }

    /**
     * Not used.
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {
    }

    /**
     * Not used.
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpGetRequest($request) {
    }

    /**
     * Draws "print" link or button used to launch HTML export.
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {
        $template->assign(array('exporthtml_active' => true,
                                'exporthtml_url' => $this->getExportScriptPath()));
    }

    /**
     * Builds export configuration.
     * @return ExportConfiguration
     */
    public function getConfiguration() {
        
        $config = new ExportConfiguration();
        $config->setRenderMap(true);
        $config->setRenderKeymap(true);
        $config->setRenderScalebar(true);
        
        return $config;
    }
    
    /**
     * Computes HTML export
     *
     * Looks for displayed layers in latest MapRequest.
     * @return ExportOutput
     * @see ExportPlugin::getExportResult
     */
    public function getExport() {
    
        $mapRequest = $this->cartoclient->getClientSession()->lastMapRequest;
        $mapResult = $this->getExportResult($this->getConfiguration());

        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);

        $mapInfo = $this->cartoclient->getMapInfo();
        
        $legends = array();
        foreach ($mapRequest->layersRequest->layerIds as $layerId) {
            $layer = $mapInfo->layersInit->getLayerById($layerId);
        
            // TODO: Improve icons retrieving (currently very simplified)
            
            if (empty($layer->icon)) {
                $iconUrl = '';
            } else {
                $resourceHandler = $this->getCartoclient()->getResourceHandler();
                $iconUrl = $this->getBaseUrl();
                $iconUrl .= $resourceHandler->getFinalUrl($layer->icon, false);
            }
            
            $legends[] = array('label' => I18n::gt($layer->label),
                               'icon'  => $iconUrl,
                               ); 
        }
        
        $smarty->assign(array('exporthtml_mainmap'  => $this->getBaseUrl()
                                    . $mapResult->imagesResult->mainmap->path,
                              'exporthtml_keymap'   => $this->getBaseUrl()
                                    . $mapResult->imagesResult->keymap->path,
                              'exporthtml_scalebar' => $this->getBaseUrl()
                                    . $mapResult->imagesResult->scalebar->path,
                              'exporthtml_legends' => $legends));
                              
        $output = new ExportOutput();
        $output->setContents($smarty->fetch('export.tpl'));
        return $output;
    }
}

?>
