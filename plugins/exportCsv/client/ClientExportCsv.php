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

    public function getExportScriptPath() {
        return 'exportCsv/export.php';
    }

    public $layerId;
    public $fileName;

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    private function generateFileName() {
        
        $layerName = I18n::gt($this->layerId);
        
        if (!is_null($this->getConfig()->fileName)) {
            $format = $this->getConfig()->fileName;
        } else {
            $format = '[layer]-[date,dMY].csv';
        }
        $fileName = $format;
        $fileName = str_replace('[layer]', $layerName, $fileName);
        ereg('(.*)\[date,(.*)\](.*)', $fileName, $match);
        $fileName = $match[1] . date($match[2]) . $match[3];
        
        return $fileName;
    }

    function handleHttpRequest($request) {
        
        if (array_key_exists('exportcsv_layerid', $request)) {
            $this->layerId = $request['exportcsv_layerid'];
            $this->fileName = $this->generateFileName();
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
    
    private function exportLine($array, $sep, $tD, $utf8) {
    
        $contents = '';
        $first = true;
        foreach($array as $value) {
            if ($first) {
                $first = false;
            } else {
                $contents .= $sep;
            }
            if ($utf8) {
                $contents .= $tD . $value . $tD;
            } else {
                $contents .= $tD . utf8_decode($value) . $tD;
            }    
        }
        $contents .= "\n";
        return $contents;
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
        $utf8 = (!is_null($this->getConfig()->charsetUtf8) && $this->getConfig()->charsetUtf8);
        
        $contents = '';
        if (isset($mapResult->queryResult)) {
            foreach ($mapResult->queryResult->layerResults as $layer) {
                if ($layer->layerId == $this->layerId
                    && $layer->numResults > 0) {
                    
                    $contents .= $this->exportLine($layer->fields, $sep, $tD, $utf8);

                    foreach ($layer->resultElements as $element) {
                    
                        $contents .= $this->exportLine($element->values, $sep, $tD, $utf8);
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
