<?php
/**
 * StatsReports plugin client part
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
 * @copyright 2007 Camptocamp SA
 * @package Plugins
 * @version $Id$
 */
 
require_once CARTOWEB_HOME . 'plugins/statsReports/common/artichow/LinePlot.class.php';
require_once CARTOWEB_HOME . 'plugins/statsReports/common/artichow/BarPlot.class.php';


abstract class StatsField {
    
    protected $plugin;
    protected $id;
    protected $onchange = false;

    public function __construct($plugin) {
        $this->plugin = $plugin;
    } 
    
    abstract public function getDbField();
    
    protected function getDbTable() {
        return $this->plugin->getCurrentPrefix() .
               '_' . $this->getDbField();
    }
    
    protected function getOptionsSql($project = NULL) {
        
        return 'SELECT t.id, t.descr FROM ' . $this->getDbTable() .
               ' AS t , ' . $this->plugin->getCurrentPrefix() .
               '_dimensions AS d WHERE d.report_name = \'' .
               $this->plugin->getCurrentReport()->name .
               '\' AND d.field_name = \'' .
               $this->getDbField() . '\' AND d.id = t.id ORDER BY t.descr';        
    }
    
    public function getOptions($project = NULL) {

        $db = $this->plugin->getCurrentDb();
        $dbResult = $db->query($this->getOptionsSql($project));
        
        Utils::checkDbError($dbResult, 'Failed querying ' . $this->getDbTable());

        $options = array();
        while ($dbResult->fetchInto($row, DB_FETCHMODE_ASSOC)) {
            $options[$row['id']] = $row['descr'];
        }
        return $options;
    }
    
    public function drawForm($project = NULL) {
        
        $smarty = new Smarty_Plugin($this->plugin->getCartoclient(), $this->plugin);
        $smarty->assign(array('stats_options'  => $this->getOptions($project),
                              'stats_id'       => $this->id,
                              'stats_label'    => I18n::gt(ucfirst($this->id)),
                              'stats_onchange' => $this->onchange));

        $field = $this->plugin->getField($this->id);            

        $smarty->assign(array('stats_selected' => $field));
        return $smarty->fetch('stats_dimension_form.tpl');
    }
    
    public function getWhere($values) {
        
        if (!is_null($values) && count($values) > 0) { 
            
            $wheres = array();
            foreach ($values as $value) {
                $wheres[] = $this->getDbField() . " = '$value'";
            }    
            return '(' . implode(' OR ', $wheres) . ')';
        }
    }
    
    public function getSelectedOptions($values, $project = NULL) {
        
        $options = $this->getOptions($project);
        unset($options['_empty']);
        
        if (!is_null($values) && count($values) > 0) {        
            foreach ($options as $optionKey => $optionValue) {
                if (!in_array($optionKey, $values)) {
                    unset($options[$optionKey]);
                }
            }
        }                     
        return $options;     
    }
}

abstract class StatsProjectField extends StatsField {

    protected function getOptionsSql($project = NULL) {

        $projects = '1=1';
        if (!is_null($project)) {
            
            $projects = '1=0';
            if (is_array($project) && count($project) > 0) {
                
                $projects = 'p.id IN (\'' . implode('\',\'', $project) . '\')';
            }
        }

        return 'SELECT t.id AS id, t.descr AS dim, p.descr AS project FROM ' . $this->getDbTable() .
               ' AS t , ' . $this->plugin->getCurrentPrefix() .
               '_dimensions AS d, ' . $this->plugin->getCurrentPrefix() .
               '_general_mapid AS p WHERE d.report_name = \'' .
               $this->plugin->getCurrentReport()->name .
               '\' AND d.field_name = \'' .
               $this->getDbField() . '\' AND d.id = t.id AND t.general_mapid = p.id AND ' .
               $projects . ' ORDER BY t.descr';                
    }

    public function getOptions($project = NULL) {

        $db = $this->plugin->getCurrentDb();
        $dbResult = $db->query($this->getOptionsSql($project));
        
        Utils::checkDbError($dbResult, 'Failed querying ' . $this->getDbTable());

        $options = array();
        while ($dbResult->fetchInto($row, DB_FETCHMODE_ASSOC)) {
            $options[$row['id']] = $row['dim'] . ' (' . $row['project']. ')';
        }
        return $options;
    }
}

class ValueStatsField extends StatsField {
    
    protected $id = 'value';
    
    public function getDbField() {
        return '';
    }
    
    public function getOptions($project = NULL) {
        
        $report = $this->plugin->getCurrentReport();
        $values = $this->plugin->explodeList($report->options['values']);
        $options = array();
        foreach ($values as $value) {
            $options[$value] = I18n::gt(ucfirst($value));
        }
        return $options;
    }

    public function getWhere($values) {
        return '';
    }    
}

class TimeStatsField extends StatsField {
    
    protected $id = 'time';
    
    public function getDbField() {
        return 'general_time';
    }

    protected function convertTime($dbTime) {
        
        $period = $this->plugin->getCurrentPeriodType();
        switch ($period) {
        case 'hour':
            return date('d.m.Y-H', strtotime($dbTime));
            break;
        case 'day':
            return date('d.m.Y', strtotime($dbTime));
            break;
        case 'week':
            return date('W/Y', strtotime($dbTime));
            break;
        case 'month':
            return date('m.Y', strtotime($dbTime));
            break;
        case 'year':
            return date('Y', strtotime($dbTime));
            break;
        }
    }

    public function getOptions($project = NULL) {

        $db = $this->plugin->getCurrentDb();
        $sql = 'SELECT DISTINCT(general_time) as time FROM ' .
               $this->plugin->getCurrentTableName() .
               ' ORDER BY general_time';                
        $dbResult = $db->query($sql);
        
        Utils::checkDbError($dbResult, 'Failed querying ' . $this->getDbTable());

        $options = array();
        while ($dbResult->fetchInto($row, DB_FETCHMODE_ASSOC)) {
            $time = $this->convertTime($row['time']);
            $options[$row['time']] = $time;
        }
        return $options;
    }
    
    public function getWhere($values) {

        if (!is_null($values) && count($values) > 0) { 
            
            $wheres = array();
            foreach ($values as $value) {
                $wheres[] = $this->getDbField() . " = TO_TIMESTAMP('$value', 'YYYY-MM-DD HH:MI:SS')";
            }    
            return '(' . implode(' OR ', $wheres) . ')';
        }
    }
    
}

class ProjectStatsField extends StatsField {
    
    protected $id = 'project';
    protected $onchange = true;
    
    public function getDbField() {
        return 'general_mapid';
    }
}

class WidthStatsField extends StatsProjectField {
    
    protected $id = 'width';
    
    public function getDbField() {
        return 'images_mainmap_width';
    }
}

class HeightStatsField extends StatsProjectField {
    
    protected $id = 'height';
    
    public function getDbField() {
        return 'images_mainmap_height';
    }
}

class ThemeStatsField extends StatsProjectField {
    
    protected $id = 'theme';

    public function getDbField() {
        return 'layers_switch_id';
    }
}

class LayerStatsField extends StatsProjectField { 

    protected $id = 'layer';

    public function getDbField() {
        return 'layer';
    }
}

class ScaleStatsField extends StatsProjectField { 

    protected $id = 'scale';

    public function getDbField() {
        return 'location_scale';
    }
    
    public function getOptions($project = NULL) {
        
        $report = $this->plugin->getCurrentReport();
        $scales = $this->plugin->explodeList($report->options['scales']);
        $previous = 'min';
        $previousKey = 0;
        $options = array();
        foreach ($scales as $value) {
            
            $current = '1:' . intval($value);
            $options[$previousKey] = $previous . '-' . $current;
            $previousKey++;
            $previous = $current;
        }
        $options[$previousKey] = $previous . '-max';
        return $options;
        
    }
}

class PdfFormatStatsField extends StatsProjectField { 

    protected $id = 'pdfFormat';

    public function getDbField() {
        return 'exportpdf_format';
    }
}

class PdfResStatsField extends StatsProjectField { 

    protected $id = 'pdfRes';

    public function getDbField() {
        return 'exportpdf_resolution';
    }
}

class UserStatsField extends StatsProjectField {
    
    protected $id = 'user';

    public function getDbField() {
        return 'general_security_user';
    }
}

class XStatsField extends StatsField {
    
    protected $id = 'x';

    public function getDbField() {
        return 'x';
    }

    public function getOptions($project = NULL) {
        return array();
    }    
}

class YStatsField extends StatsField {
    
    protected $id = 'y';

    public function getDbField() {
        return 'y';
    }

    public function getOptions($project = NULL) {
        return array();
    }    
}



/**
 * Plugin state
 */
class StatsReportsState {

    public $gridCache = array();

    // Class limits for colors
    public $colors = NULL;    

    // Image file name for grids
    public $imageFile;
}

class ClientStatsReports extends ClientPlugin
                      implements Sessionable, GuiProvider, Ajaxable, ServerCaller {

    protected $statsReportsState;

    // Form values
    protected $dimensions = array('time', 'project',
                                  'width', 'height',
                                  'theme', 'layer', 'scale',
                                  'user', 'pdfFormat',
                                  'pdfRes', 'value');
    
    // Form selections
    protected $data;
    protected $report;
    protected $periodtype;
    protected $display;

    protected $column;
    protected $line;

    protected $time;
    
    protected $project;
    protected $width;
    protected $height;
    protected $theme;
    protected $layer;
    protected $scale;
    protected $user;
    protected $pdfFormat;
    protected $pdfRes;
    
    protected $value;

    // Ajax action name
    protected $actionName;

    // Configurations
    protected $datas;
    
    // Database for temporary tables
    protected $tempDb;
    
    // Database connection(s)
    protected $dbs = array();

    // Reports for current configuration
    protected $reports = array();

    // Temporary report results
    protected $lines = array();
    
    /**
     * Initialization 
     */
    public function initialize() {
        
        $this->datas = array();
        foreach($this->getConfig()->getIniArray() as $key => $value) {
            
            $keys = explode('.', $key);
            if (count($keys) == 3 && $keys[0] == 'datas') {
                
                if (!array_key_exists($keys[1], $this->datas)) {
                    $this->datas[$keys[1]] = new stdClass();
                    $this->datas[$keys[1]]->label = NULL;
                    $this->datas[$keys[1]]->prefix = NULL;
                    $this->datas[$keys[1]]->dsn = NULL;
                }
                if (in_array($keys[2], array('label', 'prefix', 'dsn'))) {
                    $this->datas[$keys[1]]->{$keys[2]} = $value;
                }
            }
        }
        
        Utils::getDb($this->tempDb, $this->getConfig()->tempDsn);
    }
    
    /**
     * @see Sessionable::loadSession()
     */
    public function loadSession($sessionObject) {

        $this->statsReportsState = $sessionObject;                
    }

    /**
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo,
                                  InitialMapState $initialMapState) {

        $this->statsReportsState = new StatsReportsState();
    }

    /**
     * @see Sessionable::saveSession()
     */
    public function saveSession() {

        return $this->statsReportsState;
    }

    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {

        $template->assign('stats_reports_form', $this->drawStatsReportsForm());
    }
     
    protected function convertFormFields($request, $fields) {
        
        foreach ($fields as $field) {
            if (isset($request["stats_$field"]) &&
                $request["stats_$field"] != '_empty') {
                $this->{$field} = $request["stats_$field"];
            }
        }
    }
    
    protected function convertDimensionFields($request, $dimensions) {
  
        foreach ($dimensions as $dimension) {
            $class = ucfirst($dimension) . 'StatsField';
            $field = new $class($this);
            if (isset($request["stats_$dimension"])) {
                 $this->{$dimension} = $request["stats_$dimension"];
            }
        }
    }
     
    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {
               
        $this->convertFormFields($request, array('data', 'report',
                                                 'periodtype', 'display',
                                                 'column', 'line'));
        $this->convertDimensionFields($request, $this->dimensions);

        // Compute report results
        if ($this->actionName == 'StatsReports.ComputeReport') {
            
            if ($this->display == 'map') {
                
                $this->getGridResults();             
                $this->getMap();
            } else {
                $this->getSimpleResults();                            
            }
        }        
    }

    /**
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {}
  
    /**
     * @see Ajaxable::ajaxGetPluginResponse()
     */
    public function ajaxGetPluginResponse(AjaxPluginResponse $ajaxPluginResponse) {
        
        switch ($this->actionName) {
        case 'StatsReports.RefreshData':
            
            $ajaxPluginResponse->addHtmlCode('report', $this->drawReportForm());            
            break;
        case 'StatsReports.RefreshReport':
        
            $ajaxPluginResponse->addHtmlCode('periodtype', $this->drawPeriodTypeForm());
            $ajaxPluginResponse->addVariable('periodtypecount', $this->getPeriodTypeCount());
            break;
        case 'StatsReports.RefreshPeriodType':
        
            $ajaxPluginResponse->addHtmlCode('display', $this->drawDisplayForm());
            $ajaxPluginResponse->addVariable('displaycount', $this->getDisplayCount());
            break;
        case 'StatsReports.RefreshOptions':
        
            $ajaxPluginResponse->addHtmlCode('options', $this->drawOptionsForm());
            break;
        case 'StatsReports.ComputeReport':
        
            if ($this->display != 'map') {
                $ajaxPluginResponse->addHtmlCode('result', $this->drawResult());
                $ajaxPluginResponse->addVariable('resulttype', 'not_map');
            } else {
                $ajaxPluginResponse->addHtmlCode('legend', $this->drawLegend());
                $ajaxPluginResponse->addVariable('resulttype', 'map');
            }
            break;
        }                
    }
 
    /**
     * @see Ajaxable::ajaxHandleAction()
     */
    public function ajaxHandleAction($actionName, PluginEnabler $pluginEnabler) {
        
        $this->actionName = $actionName;
        switch ($this->actionName) {
        case 'StatsReports.RefreshData':
        case 'StatsReports.RefreshReport':
        case 'StatsReports.RefreshPeriodType':
        case 'StatsReports.RefreshOptions':
        
            $pluginEnabler->disableCoreplugins();
            $pluginEnabler->enablePlugin('statsReports');
            break;
        case 'StatsReports.ComputeReport':

            $pluginEnabler->disableCoreplugins();
            $pluginEnabler->enablePlugin('images');
            $pluginEnabler->enablePlugin('statsReports');
            break;
        }        
    }  

    /**
     * @see ServerCaller::buildRequest()
     */
    public function buildRequest() {
        
        $request = new StatsReportsRequest();
        $request->imageFile = $this->statsReportsState->imageFile;
        
        return $request;    
    }

    /**
     * @see ServerCaller::initializeResult()
     */
    public function initializeResult($outlineResult) {}

    /**
     * @see ServerCaller::handleResult()
     */
    public function handleResult($outlineResult) {}
    
    protected function getDb($dsn) {
        
        if (!array_key_exists($dsn, $this->dbs)) {
            
            $db = NULL;
            Utils::getDb($db, $dsn);
            $this->dbs[$dsn] = $db;
        }
        return $this->dbs[$dsn];
    }
        
    public function explodeList($list) {
        
        $items = explode(',', $list);
        $result = array();
        foreach ($items as $item) {
            $trimed = trim($item);
            if ($trimed != '') {
                $result[] = $trimed;
            }
        }
        return $result;
    }    
        
    protected function getDataOptions() {
        
        $options = array('_empty' => '');
        foreach ($this->datas as $id => $data) {
            $options[$id] = I18n::gt($data->label);
        }        
        return $options;
    }
    
    protected function drawStatsReportsForm() {
        
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $smarty->assign(array('stats_data_options' => $this->getDataOptions(),
                              'stats_data' => $this->data));
        return $smarty->fetch('stats_reports_form.tpl');        
    }      
    
    protected function getReports() {
        
        if (count($this->reports) > 0) {
            return $this->reports;
        }
        
        $db = $this->getDb($this->datas[$this->data]->dsn);
        $prefix = $this->datas[$this->data]->prefix;

        // TODO: Look for labels
        $sql = 'SELECT name, config, label FROM ' . $prefix . '_reports ORDER BY name';        
        $dbResult = $db->query($sql);
        
        Utils::checkDbError($dbResult, 'Failed querying ' . $prefix . '_reports');

        $this->reports = array();
        while ($dbResult->fetchInto($row, DB_FETCHMODE_ASSOC)) {
            
            $report = new stdClass();
            $report->name = $row['name'];
            $report->label = $row['label'];
            
            $lines = explode("\n", $row['config']);
            $options = array();
            foreach ($lines as $line) {
                
                if (strpos($line, '=')) {
                    $items = explode('=', $line);
                    
                    if (!strpos($items[0], '.')) {
                        $options[$items[0]] = $items[1];
                    } else {
                        
                        $items2 = explode('.', $items[0]);
                        if (!array_key_exists($items2[0], $options)) {
                            $options[$items2[0]] = array();
                        }
                        $options[$items2[0]][$items2[1]] = $items[1];
                    }
                }
            }
            $report->options = $options;
            $this->reports[$report->name] = $report;
        }   
        return $this->reports;
    }
        
    protected function getReportOptions() {
    
        $options = array('_empty' => '');
        $reports = $this->getReports();
        foreach ($reports as $report) {
            $options[$report->name] = $report->label;
        }
        
        return $options;
    }
    
    protected function drawReportForm() {

        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $smarty->assign(array('stats_report_options' => $this->getReportOptions(),
                              'stats_report' => $this->report));
        return $smarty->fetch('stats_report_form.tpl');            
    }           

    protected function getPeriodTypeOptions() {
    
        $options = array();
        $reports = $this->getReports();
        $periods = $reports[$this->report]->options['periods'];
        if (count($periods) > 1) {
            $options['_empty'] = '';
        }
        foreach ($periods as $period => $count) {
            $options[$period] = I18n::gt(ucfirst($period)) . " (max. $count)";
        }       
             
        return $options;
    }
    
    protected function drawPeriodTypeForm() {

        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $smarty->assign(array('stats_periodtype_options' => $this->getPeriodTypeOptions(),
                              'stats_periodtype' => $this->periodtype));
        return $smarty->fetch('stats_periodtype_form.tpl');            
    }           

    protected function getPeriodTypeCount() {
    
        $reports = $this->getReports();
        return count($reports[$this->report]->options['periods']);
    }
    
    protected function getDisplayOptions() {
    
        $reports = $this->getReports();
        $type = $reports[$this->report]->options['type'];
        switch ($type) {
        case 'simple':
            return array('_empty' => '',
                         'table' => I18n::gt('Tableau'),
                         'graph1' => I18n::gt('Graphique (combine)'),
                         'graph2' => I18n::gt('Graphiques'));
            break;
        case 'gridbbox':
        case 'gridcenter':
            return array('map' => I18n::gt('Carte'));
            break;
        }
    }
    
    protected function drawDisplayForm() {

        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $smarty->assign(array('stats_display_options' => $this->getDisplayOptions(),
                              'stats_display' => $this->display));
        return $smarty->fetch('stats_display_form.tpl');            
    }           

    protected function getDisplayCount() {
    
        $reports = $this->getReports();
        $type = $reports[$this->report]->options['type'];
        return $type == 'simple' ? 2 : 1;
    }
    
    protected function getDimensionOptions() {
        
        $reports = $this->getReports();
        $report = $reports[$this->report];
        
        $options = array('_empty' => '',
                         'time' => I18n::gt('Temps') . ' (' .
                                   I18n::gt(ucfirst($this->periodtype)) . ')',
                         'value' => I18n::gt('Type de valeurs'));
        $dimensions = $this->explodeList($report->options['dimensions']);
        foreach ($dimensions as $dimension) {
            $options[$dimension] = I18n::gt(ucfirst($dimension));
        }
        return $options;
    }
    
    protected function drawOptionsForm() {
        
        $report = $this->getCurrentReport();

        $options = '';
        
        $dimensions = $this->explodeList($report->options['dimensions']);
        $dimensions = array_merge(array('time', 'value'), $dimensions);

        // TODO manage projects
        foreach ($dimensions as $dimension) {
            
            $class = ucfirst($dimension) . 'StatsField';
            $field = new $class($this);
            $options .= $field->drawForm($this->project);
        }
        
        if ($this->display == 'table' ||
            $this->display == 'graph1' ||
            $this->display == 'graph2') {        

            $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
            
            $columnOptions = $this->getDimensionOptions();
            $lineOptions = $this->getDimensionOptions();
             
            switch ($this->display) {
            case 'table':
                $columnLabel = I18n::gt('Colonne');
                $lineLabel = I18n::gt('Ligne');
                break;
            case 'graph1':
            case 'graph2':
                $columnLabel = I18n::gt('Axe des X');
                $lineLabel = I18n::gt('Comparaison');
                break;
            }
            $smarty->assign(array('stats_label_column' => $columnLabel,
                                  'stats_label_line' => $lineLabel,
                                  'stats_column_options' => $columnOptions,
                                  'stats_line_options' => $lineOptions,
                                  'stats_column' => $this->column,
                                  'stats_line' => $this->line));
            $options .= $smarty->fetch('stats_options_form.tpl'); 
        }
                 
        return $options;
    }
    
    public function getField($id) {
        return $this->{$id};
    }
        
    protected function getDbField($dimension) {
        
        $class = $dimension . 'StatsField';        
        $field = new $class($this);
        return $field->getDbField();
    }
    
    protected function getSelectedOptions($dimension) {

        $class = ucfirst($dimension) . 'StatsField';
        $field = new $class($this);
        return $field->getSelectedOptions($this->{$dimension});
    }
    
    protected function getSimpleResults() {
        
        $report = $this->getCurrentReport();
        $db = $this->getDb($this->datas[$this->data]->dsn);
        
        $tableName = $this->getCurrentTableName();
                     
        $valueSql = '';
        if ($this->column == 'value' ||
            $this->line == 'value') {
            foreach ($this->value as $value) {
                if ($valueSql != '') {              
                   $valueSql .= ',';
                }
                $valueSql .= "SUM($value) AS $value";
            }
        } else {
            foreach ($this->value as $value) {
                if ($valueSql != '') {              
                   $valueSql .= ' + ';
                }
                $valueSql .= "SUM($value)";
            }
            $valueSql .= " AS value";           
        }
        
        $wheres = array('1=1');
        foreach ($this->dimensions as $dimension) {
            $class = $dimension . 'StatsField';
            $field = new $class($this);
            $where = $field->getWhere($this->{$dimension});
            if ($where != '') {
                $wheres[] = $where;
            }
        }
        $where = implode(' AND ', $wheres);
   
        $groupBy = '';
        if ($this->line != 'value') {
            $groupBy .= $this->getDbField($this->line);
        }
        if ($this->column != 'value') {
            if ($groupBy != '') {
                $groupBy .= ',';        
            }
            $groupBy .= $this->getDbField($this->column);
        }
        
        $sql = "SELECT $valueSql";
        if ($groupBy != '') {
            $sql .= ", $groupBy FROM $tableName WHERE $where GROUP BY $groupBy ORDER BY $groupBy";
        } else {
            $sql .= " FROM $tableName WHERE $where";
        }
        $dbResult = $db->query($sql);       
        Utils::checkDbError($dbResult, 'Failed querying ' . $tableName);
             
        $lines = array();
        
        while ($dbResult->fetchInto($row, DB_FETCHMODE_ASSOC)) {
            
            if ($this->column == 'value') {
                foreach ($this->value as $value) {
                    $lines[$row[$this->getDbField($this->line)]][$value] = $row[$value];
                }
            } else if ($this->line == 'value') {                
                foreach ($this->value as $value) {
                    $lines[$value][$row[$this->getDbField($this->column)]] = $row[$value];
                }
            } else {
                $lines[$row[$this->getDbField($this->line)]][$row[$this->getDbField($this->column)]] = $row['value'];
            }            
        }
        $this->lines = $lines;
    }
    
    protected function getGridResults() {
        
        $report = $this->getCurrentReport();
        $nx = $report->options['nx'];
        $ny = $report->options['ny'];

        $db = $this->getDb($this->datas[$this->data]->dsn);
        
        $tableName = $this->getCurrentTableName();
                             
        $valueSql = '';
        foreach ($this->value as $value) {
            if ($valueSql != '') {              
                $valueSql .= ',';
            }
            $valueSql .= $value;
        }
        
        $wheres = array('1=1');
        foreach ($this->dimensions as $dimension) {
            $class = $dimension . 'StatsField';
            $field = new $class($this);
            $where = $field->getWhere($this->{$dimension});
            if ($where != '') {
                $wheres[] = $where;
            }
        }
        $where = implode(' AND ', $wheres);
               
        $sql = "SELECT $valueSql FROM $tableName WHERE $where";
        $dbResult = $db->query($sql);       
        Utils::checkDbError($dbResult, 'Failed querying ' . $tableName);
                
        $lines = array();
        for ($i = 0; $i < $nx; $i++) {
            for ($j = 0; $j < $ny; $j++) {
                $lines[$i][$j] = 0;
            }
        }
        
        while ($dbResult->fetchInto($row, DB_FETCHMODE_ASSOC)) {
            
            foreach ($this->value as $value) {
                
                $list = explode(',', trim($row[$value], '{}'));                
                foreach ($list as $key => $value) {
                    
                    $x = floor($key / $ny);
                    $y = $ny - ($key - ($ny * $x)) - 1; 
                    $lines[$x][$y] += $value; 
                }
            }
        }
        $this->lines = $lines;
    }
    
    protected function drawResult() {                
            
        $columnOptions = $this->getSelectedOptions($this->column);
        $lineOptions = $this->getSelectedOptions($this->line);

        switch ($this->display) {
        case 'table':

            $linesTemplate = array();
            foreach ($lineOptions as $lineKey => $lineTitle) {
                
                $lineTemplate = new stdClass();
                $lineTemplate->lineTitle = $lineOptions[$lineKey];
                $values = array();
                if (isset($this->lines[$lineKey])) {
                    
                    $line = $this->lines[$lineKey];
                    foreach ($columnOptions as $columnKey => $columnTitle) {
                        if (isset($line[$columnKey])) {
                            $values[] = $line[$columnKey];
                        } else {
                            $values[] = 0;
                        }
                    }
                } else {
                    
                    foreach ($columnOptions as $columnKey => $columnTitle) {
                        $values[] = 0;
                    }
                }
                $lineTemplate->values = $values;
                $linesTemplate[] = $lineTemplate;
            }            
            
            $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
            $smarty->assign(array('stats_columnTitles' => $columnOptions,
                                  'stats_lines' => $linesTemplate));
            return $smarty->fetch('stats_results_table.tpl'); 

            break;
        case 'graph1':
        case 'graph2':
            
            $graphs = array();
            $graphValues = array();
            $md5 = array();
            foreach ($lineOptions as $lineKey => $lineTitle) {
                
                if (isset($this->lines[$lineKey])) {
                    
                    $line = $this->lines[$lineKey];
                    $graphValues[$lineTitle] = array();
                    $md5[$lineTitle] = 'foo';
                    foreach ($columnOptions as $columnKey => $columnTitle) {
                        if (isset($line[$columnKey])) {
                            $graphValues[$lineTitle][$columnTitle] = $line[$columnKey];
                        } else {
                            $graphValues[$lineTitle][$columnTitle] = 0;
                        }
                        $md5[$lineTitle] = md5($md5[$lineTitle] . '-' . $lineTitle .
                                               '-' . $columnTitle . '-' .
                                               $graphValues[$lineTitle][$columnTitle]);
                    }
                    
                }
            }
            $graphType = 'bar';
            $xUnit = $this->column;
            $yUnit = $this->line;
            if ($this->column == 'time') {
                $graphType = 'line';
                $xUnit = $this->periodtype;
            }
            if ($this->line == 'time') {
                $yUnit = $this->periodtype;                
            }
            $graphTitle = '';
            if ($this->line != 'value') {
                foreach ($this->value as $value) {
                    if ($graphTitle != '') {                        
                        $graphTitle .= ' + ';
                    }
                    $graphTitle .= I18n::gt(ucfirst($value));
                }
            } 
            if ($this->display == 'graph1') {
                $md5Final = 'foo';
                foreach ($md5 as $m) {
                    $md5Final = md5($md5Final . '-' . $m);
                }                             
                $graphs[] = $this->getGraph($graphTitle, $graphType, I18n::gt(ucfirst($xUnit)),
                                            $graphValues, $md5Final);
            } else {
                foreach ($graphValues as $title => $values) {
                    
                    $finalTitle = I18n::gt(ucfirst($yUnit)) . ' ' . $title;
                    if ($graphTitle != '') {
                        $finalTitle = $graphTitle . " ($finalTitle)";
                    }
                    $graphs[] = $this->getGraph($finalTitle, $graphType,
                                                I18n::gt(ucfirst($xUnit)),
                                                array($title => $values),
                                                $md5[$title]);
                }
            }

            $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
            $smarty->assign(array('stats_graphs' => $graphs));
            return $smarty->fetch('stats_results_graphs.tpl'); 
            
            break;
        }
    }
    
    protected function getGraph($title, $type, $xUnit, $data, $md5) {
        
        if (!$data) {
            return null;
        }
    
        $colors = array(new awBlue, new awRed, new awGreen, new awMidGray);
    
        $fileName = "graph_$md5.png";
        
        $basePath = CARTOWEB_HOME . 'htdocs/generated/';
        $path = $basePath . 'stats/';
        if (!is_dir($path)) {
            Utils::makeDirectoryWithPerms($path, $basePath);
        }      
        $path .= 'graphs/';
        if (!is_dir($path)) {
            Utils::makeDirectoryWithPerms($path, $basePath);
        }              
    
        if (file_exists($path . $fileName)) {
            return "generated/stats/graphs/$fileName";
        }
        
        $graph = new awGraph(650, 500);
        $graph->border->hide();
        
        $i = 0;
        $plots = array();
        $group = new awPlotGroup();
        $group->setSize(0.92, 0.88);
        $group->setCenter(0.54, 0.46);           
        $group->setSpace(2, 2, 0, 0);     
        $group->axis->bottom->label->setAngle(90); 
        $group->axis->bottom->title->set($xUnit);
        $group->axis->bottom->setTitleAlignment(awLabel::LEFT);
        $group->axis->bottom->setTitlePosition(-0.1);
        $group->legend->setPosition(0.92, 0.1);
        $group->legend->setAlign(awLegend::RIGHT, awLegend::TOP);
        $group->legend->shadow->smooth(TRUE);
        $group->title->set($title);

        foreach ($data as $graphLabel => $graphData) {
            
            $plotData = array_values($graphData);
            $plotLabel = array_keys($graphData);

            $legendType = NULL;
            switch ($type) {
            case 'line':
            
                $plot = new awLinePlot(array_values($plotData));

                $plot->mark->setType(awMark::SQUARE, 6);
                $plot->mark->setFill($colors[$i]);
                $plot->setColor($colors[$i]);
                $plot->setThickness(3);                
                
                $legendType = awLegend::LINE;
                break;
            case 'bar':
            
                $plot = new awBarPlot(array_values($plotData), $i+1, count($data));               
                $plot->SetBarColor($colors[$i]);

                $legendType = awLegend::BACKGROUND;
                break;
            }        
            
            if (count($data) > 1) {
                $group->legend->add($plot, $graphLabel, $legendType);
            }

            if (count($data) == 2) {
                
                if ($i == 0) {
                    $plot->setYAxis(awPlot::LEFT);
                    $group->axis->left->setColor($colors[$i]);
                } else {
                    $plot->setYAxis(awPlot::RIGHT);
                    $group->axis->right->setColor($colors[$i]);
                    $group->setSize(0.86, 0.88);
                    $group->setCenter(0.50, 0.46);                
                }
            }
            $group->axis->bottom->setLabelText($plotLabel); 
            $group->add($plot);

            $i++;
            if ($i == count($colors)) {
                $i = 0;
            }
        }

        $graph->add($group);

        // save file
        ob_start();
        $graph->draw();
        file_put_contents($path . $fileName, ob_get_contents());
        ob_end_clean();
        
        return "generated/stats/graphs/$fileName";
    }    
    
    protected function getTableName() {

        return SecurityManager::getInstance()->getUser() . '_stats';                
    }    
    
    protected function getMap() {
        
        $report = $this->getCurrentReport();
        $nx = $report->options['nx'];
        $ny = $report->options['ny'];
        $minx = $report->options['minx'];
        $miny = $report->options['miny'];
        $size = $report->options['size'];
                
        // Finds min and max
        $min = NULL;
        $max = NULL;
        for ($i = 0; $i < $nx; $i++) {
            if (array_key_exists($i, $this->lines)) {
                for ($j = 0; $j < $ny; $j++) {
                    if (array_key_exists($j, $this->lines[$i])) {
                        if (is_null($min) || $this->lines[$i][$j] < $min) {
                            $min = $this->lines[$i][$j];
                        }     
                        if (is_null($max) || $this->lines[$i][$j] > $max) {
                            $max = $this->lines[$i][$j];
                        }     
                    }        
                }
            }
        }
        
        // Completes table with min values
        for ($i = 0; $i < $nx; $i++) {
            if (!array_key_exists($i, $this->lines)) {
                $this->lines[$i] = array();
            }
            for ($j = 0; $j < $ny; $j++) {
                if (!array_key_exists($j, $this->lines[$i])) {
                    $this->lines[$i][$j] = $min;
                }
            }
        }
        if ($report->options['type'] == 'gridcenter') {

            // Removes extremes values            
            $min2 = NULL;
            $max2 = NULL;
            for ($i = 0; $i < $nx; $i++) {
                for ($j = 0; $j < $ny; $j++) {
                    if ((is_null($min2) || $this->lines[$i][$j] < $min2) &&
                        $this->lines[$i][$j] > $min) {
                        $min2 = $this->lines[$i][$j];
                    }     
                    if ((is_null($max2) || $this->lines[$i][$j] > $max2) &&
                        $this->lines[$i][$j] < $max) {
                        $max2 = $this->lines[$i][$j];
                    }     
                }
            }         
            
            // Replace value with mean value of neighbors   
            for ($i = 0; $i < $nx; $i++) {
                for ($j = 0; $j < $ny; $j++) {
                    if ($this->lines[$i][$j] == $min || $this->lines[$i][$j] == $max) {
                        $n = 0;
                        $value = 0;
                        if ($i - 1 >= 0) {
                            $value += $this->lines[$i - 1][$j];
                            $n++;
                        }
                        if ($i + 1 < $nx) {
                            $value += $this->lines[$i + 1][$j];
                            $n++;
                        }
                        if ($j - 1 >= 0) {
                            $value += $this->lines[$i][$j - 1];
                            $n++;
                        }
                        if ($j + 1 < $ny) {
                            $value += $this->lines[$i][$j + 1];
                            $n++;
                        }
                        $this->lines[$i][$j] = $value / $n;
                    }
                }
            }
            $min = $min2;
            $max = $max2;
        }
                  
        $nPixelsX = 4000;
        $nPixelsY = 3000;
        $npx = floor($nPixelsX / $nx);
        $npy = floor($nPixelsY / $ny);

        // FIXME: Find best location for image
        $basePath = CARTOWEB_HOME . 'www-data/';
        $path = $basePath . 'stats/';
        if (!is_dir($path)) {
            Utils::makeDirectoryWithPerms($path, $basePath);
        }      
        
        $image = imagecreate($npx * $nx, $npy * $ny);

        $nColors = $this->getConfig()->nColors;                

        $this->statsReportsState->colors = array();
        for ($i = 1; $i <= $nColors; $i++) {
            $limit = $min + floor((($max - $min + 1) / $nColors) * $i) - 1;
            $color =  255 - floor(($i - 1) * (255 / ($nColors - 1)));
            $this->statsReportsState->colors[$color] = $limit; 
        }
        $imgColors = array();
        foreach ($this->statsReportsState->colors as $color => $limit) {
            $imgColors[$color] = imagecolorallocate($image, 255, $color, $color);
        }

        for ($i = 0; $i < $nx; $i++) {
            for ($j = 0; $j < $ny; $j++) {
                
                $c = NULL;
                foreach ($this->statsReportsState->colors as $color => $limit) {
                    if ($limit >= $this->lines[$i][$j]) {
                        $c = $imgColors[$color];
                        break;
                    }
                }
                imagefilledrectangle($image,
                                     $i * $npx,
                                     $j * $npy,
                                     ($i + 1) * $npx - 1,
                                     ($j + 1) * $npy - 1,
                                     $c);
            }
        }
        $imageFile = $basePath . SecurityManager::getInstance()->getUser() . '_image'; 
        $this->statsReportsState->imageFile = $imageFile . '.png';
        imagepng($image, $this->statsReportsState->imageFile, 9);   
        
        // Creates world file
        $world = ($size / $npx) . "\n0.0\n0.0\n-" . ($size / $npy) . "\n";
        $world .= $minx . "\n" . ($miny + $ny * $size) . "\n";
        file_put_contents($imageFile . '.wld', $world);
        
        // Creates legends
        $basePath = CARTOWEB_HOME . 'htdocs/generated/';
        $path = $basePath . 'stats/';
        if (!is_dir($path)) {
            Utils::makeDirectoryWithPerms($path, $basePath);
        }      
        $path .= 'legends/';
        if (!is_dir($path)) {
            Utils::makeDirectoryWithPerms($path, $basePath);
        }      
        
        $i = 0;    
        foreach ($this->statsReportsState->colors as $color => $limit) {            
            
            $img = imagecreate(20, 15);
            $imgColor = imagecolorallocate($img, 255, $color, $color);
        
            imagefill($img, 0, 0, $imgColor);
            imagepng($img, $path . SecurityManager::getInstance()->getUser() . '_' . $i . '.png');
                    
            $i++;
        }        
    }

    /**
     * Draws legend (images generated by server)
     * 
     * @return string generated HTML
     */
    protected function drawLegend() {

        if (is_null($this->statsReportsState->colors)) {
            return ' ';
        }

        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);

        $legend = array();
        $i = 0;
        $last = -1;
        foreach ($this->statsReportsState->colors as $value) {
            $l = '';
            if ($i != 0) {
                $l .= $last;
            }
            $l .= "-";
            if ($i < count($this->statsReportsState->colors) - 1) {
                $l .= $value;                
            }
            $legend[] = $l;
            $last = $value;
            $i++;
        }
        $smarty->assign(array('stats_legend' => $legend,
                              'stats_user' => SecurityManager::getInstance()->getUser()));

        return $smarty->fetch('stats_legend.tpl');
    }
    
    public function getCurrentDb() {
        return $this->getDb($this->datas[$this->data]->dsn);        
    }
    
    public function getCurrentPrefix() {
        return $this->datas[$this->data]->prefix;
    }
    
    public function getCurrentReport() {
        
        $reports = $this->getReports();
        return $reports[$this->report];
    }
   
    public function getCurrentPeriodType() {
        return $this->periodtype;
    }
   
    public function getCurrentTableName() {
        
        return $this->datas[$this->data]->prefix . '_' . 
               str_replace(' ', '_', $this->report) . '_' .
               $this->periodtype;
    }
}

?>