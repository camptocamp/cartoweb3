<?php
/**
 * @package Plugins
 * @version $Id$
 */

require_once(CARTOCLIENT_HOME . 'client/ExportPlugin.php');

/**
 * @package Plugins
 */
class ClientExportHtml extends ExportPlugin {

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    public function getExportScriptPath() {
        return 'exportHtml/export.php';
    }
    
    public function getBaseUrl() {
        return '../';
    }

    function handleHttpRequest($request) {
        
    }

    function renderForm($template) {
        if (!$template instanceof Smarty) {
            throw new CartoclientException('unknown template type');
        }

        $template->assign(array('exporthtml_active' => true,
                                'exporthtml_url' => $this->getExportScriptPath()));

    }

    function getConfiguration() {
        
        $config = new ExportConfiguration();
        $config->setRenderMap(true);
        $config->setRenderKeymap(true);
        $config->setRenderScalebar(true);
        
        return $config;
    }
    
    function export($mapRequest, $mapResult) {

        $smarty = new Smarty_CorePlugin($this->getCartoclient()->getConfig(), $this);

        $mapInfo = $this->cartoclient->getMapInfo();
        
        // TODO: Add icons
        
        $legends = array();
        foreach ($mapRequest->layersRequest->layerIds as $layerId) {
            if (array_key_exists($layerId, $mapInfo->layers)) {
                $layer = $mapInfo->layers[$layerId];
                $legends[] = array('label' => I18n::gt($layer->label)); 
            }         
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
