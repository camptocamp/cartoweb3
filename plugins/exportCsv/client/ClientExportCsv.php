<?php
/**
 * CSV Export
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
require_once(CARTOCLIENT_HOME . 'client/ExportPlugin.php');

/**
 * CSV Export
 * @package Plugins
 */
class ClientExportCsv extends ExportPlugin {

    /**
     * @var string
     */
    public $groupId;
    
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
     * @see ExportPlugin::getExportScriptPath()
     */
    public function getExportScriptPath() {
        // FIXME: is this still needed ??
        $scriptPath = parent::getExportScriptPath();
        if (strstr($scriptPath, '?'))
            $scriptPath .= '&amp;';
        else
            $scriptPath .= '?';
        return $scriptPath;
    }

    /**
     * Constructor
     */
    public function __construct() {
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
	    $tableName = str_replace(' ', '_', $tableName);

        $fileName = str_replace('[table]', $tableName, $fileName);
        ereg('(.*)\[date,(.*)\](.*)', $fileName, $match);
        $fileName = $match[1] . date($match[2]) . $match[3];

        return $fileName;
    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {
    }

    /**
     * Handles HTTP request received by script export.php
     * @param array HTTP request
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {
        if (array_key_exists('exportcsv_groupid', $request)
            && array_key_exists('exportcsv_tableid', $request)) {
            $this->groupId = $request['exportcsv_groupid'];
            $this->tableId = $request['exportcsv_tableid'];
            $this->fileName = $this->generateFileName();
        }
    }
    
    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {
    }

    /**
     * Builds export configuration.
     * @return ExportConfiguration
     */
    public function getConfiguration() {
        
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
    private function exportLine($array, $sep, $tD) {
    
        $contents = '';
        $first = true;
        foreach($array as $value) {
            if ($first) {
                $first = false;
            } else {
                $contents .= $sep;
            }
            $contents .= $tD . $value . $tD;
        }
        $contents .= "\n";
        return $contents;
    }
    
    /**
     * Computes CSV export
     * @return ExportOutput
     * @see ExportPlugin::getExportResult
     */
    public function getExport() {

        $this->getExportResult($this->getConfiguration());

        $tablesPlugin = $this->cartoclient->getPluginManager()->tables;
        $table = $tablesPlugin->getTable($this->groupId, $this->tableId);        

        $contents = '';
        
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
            
        $lineContent = $table->columnTitles;
        if (is_null($lineContent)) {
            $lineContent = array();
        }            
        if (!$table->noRowId) {                        
            $lineContent = array_merge(array(I18n::gt('Id')), $lineContent);
        }
        $contents .= $this->exportLine($lineContent, $sep, $tD);

        foreach ($table->rows as $row) {
            $lineContent = $row->cells;
            if (is_null($lineContent)) {
                $lineContent = array();
            }                    
            if (!$table->noRowId) {
                $lineContent = array_merge(array($row->rowId), $lineContent);
            }
            $contents .= $this->exportLine($lineContent, $sep, $tD);
        }

        $output = new ExportOutput();
        $output->setContents($contents);
        return $output;
    }
}

?>
