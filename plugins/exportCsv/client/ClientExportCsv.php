<?php
/**
 * CSV Export
 * @package Plugins
 * @version $Id$
 */

/**
 * Export super class
 */
require_once(CARTOCLIENT_HOME . 'client/ExportPlugin.php');

/**
 * CSV Export
 * @package Plugins
 */
class ClientExportCsv extends ExportPlugin {

    /**
     * @var string
     */
    public $tableId;
    
    /**
     * @var string
     */
    public $fileName;

    /**
     * Returns relative Web path to external export script
     * @return string
     */
    public function getExportScriptPath() {
        return 'exportCsv/export.php';
    }

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    /**
     * Returns CSV file name
     *
     * Format is set in configuration file, key fileName.
     * @return string
     */
    private function generateFileName() {
        
        $tableName = I18n::gt($this->tableId);
        
        if (!is_null($this->getConfig()->fileName)) {
            $format = $this->getConfig()->fileName;
        } else {
            $format = '[table]-[date,dMY].csv';
        }
        $fileName = $format;
        $fileName = str_replace('[table]', $tableName, $fileName);
        ereg('(.*)\[date,(.*)\](.*)', $fileName, $match);
        $fileName = $match[1] . date($match[2]) . $match[3];
        
        return $fileName;
    }

    /**
     * Handles HTTP request received by script export.php
     * @param array HTTP request
     */
    function handleHttpPostRequest($request) {
    }

    function handleHttpGetRequest($request) {
        
        if (array_key_exists('exportcsv_tableid', $request)) {
            $this->tableId = $request['exportcsv_tableid'];
            $this->fileName = $this->generateFileName();
        }
    }
    
    function renderForm(Smarty $template) {

    }

    function getConfiguration() {
        
        $config = new ExportConfiguration();
        $config->setRenderMap(false);
        $config->setRenderKeymap(false);
        $config->setRenderScalebar(false);
        
        return $config;
    }

    /**    
     * Returns an exported CSV single line
     * @param array values
     * @param string separator
     * @param string text delimiter
     * @param boolean true if UTF8 decoding is required
     * @return string
     */
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
    
    /**
     * Computes CSV export
     * @return ExportOutput
     * @see ExportPlugin::getExportResult
     */
    function getExport() {

        $mapResult = $this->getExportResult($this->getConfiguration());

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
            foreach ($mapResult->queryResult->tableGroup->tables as $table) {
                if ($table->tableId == $this->tableId
                    && $table->numRows > 0) {
                    
                    $contents .= $this->exportLine($table->columnTitles, $sep, $tD, $utf8);

                    foreach ($table->rows as $row) {
                    
                        $contents .= $this->exportLine($row->cells, $sep, $tD, $utf8);
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
