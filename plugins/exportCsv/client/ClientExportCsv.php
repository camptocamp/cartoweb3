<?php
/**
 * @package Plugins
 * @version $Id$
 */

require_once(CARTOCLIENT_HOME . 'client/ExportPlugin.php');

/**
 * @package CorePlugins
 */
class ClientExportCsv extends ExportPlugin {

    const EXPORT_SCRIPT_PATH = 'exportCsv/export.php';

    public $layerId;
    public $layerName;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    function handleHttpRequest($request) {
        
        if (array_key_exists('exportcsv_layerid', $request)) {
            $this->layerId = $request['exportcsv_layerid'];
            $this->layerName = I18n::gt($this->layerId);
        }
    }

    function renderForm($template) {
        if (!$template instanceof Smarty) {
            throw new CartoclientException('unknown template type');
        }

    }

    function getConfiguration() {
        
        $config = new ExportConfiguration();
        $config->setRenderMap(false);
        $config->setRenderKeymap(false);
        $config->setRenderScalebar(false);
        
        return $config;
    }
    
    function export($mapResult) {

        if (!is_null($this->getConfig()->separator)) {
            $sep = $this->getConfig()->separator;
        } else {
            $sep = ';';
        }
        if (!is_null($this->getConfig()->textDelimiter)) {            
            $tD = $this->getConfig()->textDelimiter;
            
            // special characters
            switch ($tD) {
            case 'double-quote':
                $tD = '"';
                break;
            }
        } else {
            $tD = '"';
        }
        
        $contents = '';
        if (isset($mapResult->queryResult)) {
            foreach ($mapResult->queryResult->layerResults as $layer) {
                if ($layer->layerId == $this->layerId
                    && $layer->numResults > 0) {
                    $contents .= $tD . implode("$tD$sep$tD", $layer->fields) . "$tD\n";
                    foreach ($layer->resultElements as $element) {
                        $contents .= $tD . implode("$tD$sep$tD", $element->values) . "$tD\n";
                    }
                }
            }
        }

        $output = new ExportOutput();
        $output->setContents($contents);
        return $output;
    }
}
?>
