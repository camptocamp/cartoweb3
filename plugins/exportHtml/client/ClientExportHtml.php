<?php
/**
 * HTML Export
 * @package Plugins
 * @version $Id$
 */

/**
 * Export super class
 */
require_once(CARTOCLIENT_HOME . 'client/ExportPlugin.php');

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

        $smarty = new Smarty_CorePlugin($this->getCartoclient(), $this);

        $mapInfo = $this->cartoclient->getMapInfo();
        // TODO: Add icons
        
        $legends = array();
        foreach ($mapRequest->layersRequest->layerIds as $layerId) {
            $layer = $mapInfo->getLayerById($layerId);
            $legends[] = array('label' => I18n::gt($layer->label)); 
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
