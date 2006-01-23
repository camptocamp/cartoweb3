<?php
/**
 * Client part of PDF export plugin.
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
 * @author Alexandre Saunier
 * @version $Id$
 */

require_once CARTOWEB_HOME . 'client/ExportPlugin.php';
require_once dirname(__FILE__) . '/ExportPdfObjects.php';

/**
 * Session container.
 * @package Plugins
 */
class ExportPdfState {

    /**
     * @var array
     */
    public $formFields = array();
}

/**
 * Overall class for PDF generation management.
 * @package Plugins
 */
class ClientExportPdf extends ExportPlugin
                      implements Sessionable, InitUser, ToolProvider {

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var Smarty_Plugin
     */
    protected $smarty;

    /**
     * @var PdfGeneral
     */
    protected $general;

    /**
     * @var PdfFormat
     */
    protected $format;
    
    /**
     * @var PdfBlock
     */
    protected $blockTemplate;
    
    /**
     * @var array
     */
    protected $blocks = array();

    /**
     * @var array
     */
    protected $optionalInputs = array('title', 'note', 'scalebar', 'overview',
                                      'queryResult', 'legend');
    // TODO: display queryResult form option only if available in MapResult

    /**
     * @var float
     */
    protected $mapScale;

    /**
     * @var int
     */
    protected $mapServerResolution;

    /**
     * @var string
     */
    protected $charset;

    /**
     * @var boolean
     */
    protected $isPrintingPdf;

    /**
     * @var ExportPdfState
     */
    protected $exportPdfState;

    /**
     * GUI mode constants
     */
    const GUIMODE_CLASSIC = 'classic';
    const GUIMODE_ROTATE  = 'rotate';
   
    /**
     * Tool constant
     */
    const TOOL_ROTATE = 'pdfrotate';

    /**
     * Output constants
     */
    const OUTPUT_INLINE      = 'inline';
    const OUTPUT_ATTACHMENT  = 'attachment';
    const OUTPUT_LINK        = 'link';
    const OUTPUT_REDIRECTION = 'redirection';

    /**
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);        
        parent::__construct();
    }

    /**
     * Returns general data object.
     * @return PdfGeneral
     */
    public function getGeneral() {
        return $this->general;
    }

    /**
     * Returns formats object.
     * @return PdfFormat
     */
    public function getFormat() {
        return $this->format;
    }

    /**
     * @return array array of activated PdfBlocks
     */
    public function getBlocks() {
        return $this->blocks;
    }

    /**
     * @see InitUser::handleInit()
     */
    public function handleInit($exportPdfInit) {

        if (empty($exportPdfInit->mapServerResolution)) {
            throw new CartoclientException('MapServer resolution is missing.');
        }
        $this->mapServerResolution = $exportPdfInit->mapServerResolution;
    }

    /**
     * @see ToolProvider::handleMainmapTool()
     */
    public function handleMainmapTool(ToolDescription $tool,
                                      Shape $mainmapShape) {
        /* nothing to do */
    }

    /**
     * @see ToolProvider::handleKeymapTool()
     */
    public function handleKeymapTool(ToolDescription $tool,
                                     Shape $keymapShape) {
        /* nothing to do */
    }

    /**
     * @see ToolProvider::handleApplicationTool()
     */
    public function handleApplicationTool(ToolDescription $tool) {
        /* nothing to do */
    }

    /**
     * Returns PDF rotate tool.
     * @return array array of ToolDescription
     */
    public function getTools() {
        
        if ($this->getConfig()->{'general.guiMode'} == self::GUIMODE_ROTATE) {
            return array(new ToolDescription(self::TOOL_ROTATE, true, 101, 1));
        } else {
            return array();
        }
    }

    /**
     * Returns PDF file name.
     * @return string
     */
    protected function getFilename() {
        if (preg_match("/^(.*)\[date,(.*)\](.*)$/", 
                       $this->general->filename, $regs)) {
            $this->general->filename = $regs[1] . date($regs[2]) . $regs[3];
        }
        return $this->general->filename;
    }

    /**
     * Returns an array from a comma-separated list string.
     * @param array
     * @param boolean (default: false) true: returns a simplified array
     * @return array
     */
    protected function getArrayFromList($list, $simple = false) {
        $list = explode(',', $list);
        $res = array();
        foreach ($list as $d) {
            $d = trim($d);
            if ($simple) $res[] = $d;
            else $res[$d] = I18n::gt($d);
        }
        return $res;
    }

    /**
     * Returns an array from a comma-separated list of a ini parameter.
     * @param string name of ini parameter
     * @param boolean (default: false) true: returns a simplified array
     * @return array
     */
    protected function getArrayFromIni($name, $simple = false) {
        $data = $this->getConfig()->$name;
        if (!$data) return array();

        return $this->getArrayFromList($data, $simple);
    }

    /**
     * Updates $target properties with values from $from ones.
     * @param object object to override
     * @param object object to copy
     */
    protected function overrideProperties($target, $from) {
        foreach (get_object_vars($from) as $key => $val) {
            $target->$key = $val;
        }
    }

    /**
     * Returns value from $_REQUEST or else from default configuration.
     * @param string name of parameter
     * @param array available values
     * @param array $_REQUEST
     * @return string
     */
    protected function getSelectedValue($name, $choices, $request) {
        $name = strtolower($name);
        $reqname = 'pdf' . ucfirst($name);

        if (isset($request[$reqname]) && 
            in_array($request[$reqname], $choices))
            return $request[$reqname];

        if (isset($this->general->{'default' . ucfirst($name)}))
            return $this->general->{'default' . ucfirst($name)};

        return false;
    }

    /**
     * Sorts blocks using $property criterium (in ASC order).
     * @param string name of property used to sort blocks
     */
    protected function sortBlocksBy($property) {
        $blocksVars = array_keys(get_object_vars($this->blockTemplate));
        if (!in_array($property, $blocksVars))
            return $this->blocks;

        $sorter = array();
        foreach ($this->blocks as $id => $block) {
            $val = $block->$property;
            if (isset($sorter[$val]))
                array_push($sorter[$val], $id);
            else
                $sorter[$val] = array($id);
        }
        
        ksort($sorter);

        $blocks = array();
        foreach ($sorter as $val) {
            foreach ($val as $id)
                $blocks[$id] = $this->blocks[$id];
        }

        $this->blocks = $blocks;
    }

    /**
     * Sets a table block.
     * @param array $request
     * @param stdClass INI object
     * @param string object id
     */                   
    protected function setTableBlock($request, $iniObjects, $id) {
        $this->log->debug(__METHOD__);
        
        if ($this->blocks[$id]->caption && 
            !in_array($this->blocks[$id]->caption, $this->blocks)) {
            
            $caption = $this->blocks[$id]->caption;
            $this->createBlock($request, $iniObjects, $caption);
            
            $this->blocks[$caption]->standalone = false;
 
            $content = $this->blocks[$caption]->content;
            $content = Encoder::encode($content);
            $content = Encoder::decode($content);
            $this->blocks[$caption]->content = $content;
            
            if (!isset($this->blocks[$caption]->height) && 
                isset($this->blocks[$id]->height)) {
                $this->blocks[$caption]->height =
                    $this->blocks[$id]->height;
            }
        }
     
        if ($this->blocks[$id]->headers &&
            !in_array($this->blocks[$id]->headers, $this->blocks)) {
            
            $headers = $this->blocks[$id]->headers;
            $this->createBlock($request, $iniObjects, $headers);
            
            $this->blocks[$headers]->standalone = false;
 
            $content = $this->blocks[$headers]->content;
            $content = Encoder::encode($content);
            $content = Encoder::decode($content);
            $this->blocks[$headers]->content = $content;
            
            $this->blocks[$headers]->content = 
               $this->getArrayFromList($this->blocks[$headers]->content,
                                       true);
        
            if (!isset($this->blocks[$headers]->height) &&
                isset($this->blocks[$id]->height)) {
                $this->blocks[$headers]->height =
                    $this->blocks[$id]->height;
            }
        }
     
        // TODO: handle multi-row tables when getting content from INI 
        // For now we are limited to one single row.
        if ($this->blocks[$id]->content) {
            $content = $this->blocks[$id]->content;
            $content = Encoder::encode($content);
            $content = Encoder::decode($content);
            $this->blocks[$id]->content = $this->getArrayFromList($content,
                                                                  true);
        }
    }

    /**
     * Sets a legend block.
     * @param array $request
     * @param string object id
     */                   
    protected function setLegendBlock($request, $id) {
        $this->log->debug(__METHOD__);
        
        $lastMapRequest = $this->getLastMapRequest();
        $layersCorePlugin = $this->cartoclient->getPluginManager()->
                            getPlugin('layers');
        
        if (is_null($lastMapRequest) || is_null($layersCorePlugin)) {
            unset($this->blocks[$id], $lastMapRequest,
                  $layersCorePlugin);
            return;
        }
        
        $selectedLayers = $lastMapRequest->layersRequest->layerIds;
        if ($this->blocks[$id]->content) {
            $content =
                $this->getArrayFromList($this->blocks[$id]->content,
                                        true);
            // layers whose ids begin with "!" are not displayed
            // in legend: 
            foreach ($content as $layerId) {
                if ($layerId{0} == '!') {
                    $layerId = substr($layerId, 1);
                    $key = array_search($layerId, $selectedLayers);
                    if (is_numeric($key))
                        unset($selectedLayers[$key]);
                    else {
                        // case of LayerGroup
                        foreach ($layersCorePlugin->
                                     fetchChildrenFromLayerGroup(
                                        array($layerId)) as $childId) {
                            $key = array_search($childId, 
                                                $selectedLayers);
                            if (is_numeric($key))
                                unset($selectedLayers[$key]);
                        }
                    }
                }
            }
            $this->blocks[$id]->content = '';
        }
        
        $this->blocks[$id]->content =
            $layersCorePlugin->getPrintedLayers($selectedLayers,
                                                $this->getLastScale());
        
        if ($request['pdfLegend'] == 'out')
            $this->blocks[$id]->inNewPage = true;
    }

    /**
     * Instanciates a PdfBlock object.
     * @param array $request
     * @param stdClass INI object
     * @param string object id
     */
    protected function createBlock($request, $iniObjects, $id) {
        $this->log->debug(__METHOD__);
    
        $pdfItem = 'pdf' . ucfirst($id);
        if ($this->isPrintingPdf($request) &&
            !(isset($request[$pdfItem]) && trim($request[$pdfItem])) &&
            in_array($id, $this->optionalInputs)) {
            // removes user blocks if printing and no input
            return;
        }
       
        if (isset($iniObjects->blocks->$id))
            $block = $iniObjects->blocks->$id;
        else
            $block = new stdclass();
            
        $this->blocks[$id] = StructHandler::mergeOverride(
                                 $this->blockTemplate,
                                 $block, true);

        // checks block permissions
        $blockRoles =& $this->blocks[$id]->allowedRoles;
        $blockRoles = $this->getArrayFromList($blockRoles, true);
        if (!SecurityManager::getInstance()->hasRole($blockRoles)) {
            unset($this->blocks[$id]);
            return;
        }

        $this->blocks[$id]->id = $id;

        if ($this->isPrintingPdf($request) && 
            ($id == 'title' || $id == 'note')) {
            $this->blocks[$id]->content = trim($request[$pdfItem]);
        }

        // translation for language dependent block content (text, URL, etc.)
        if ($this->blocks[$id]->i18n && $this->blocks[$id]->content) {
            $this->blocks[$id]->content = I18n::gt($this->blocks[$id]->content);
        }

        if ($this->blocks[$id]->type == 'text' &&
            (stristr($this->blocks[$id]->content, 'file~') ||
             stristr($this->blocks[$id]->content, 'db~'))) {
            $this->blocks[$id]->content = 
                PrintTools::getContent($this->blocks[$id]->content);
        }

        elseif (($this->blocks[$id]->type == 'image' ||
                 $this->blocks[$id]->type == 'north') && 
                !in_array($id, array('mainmap', 'overview', 'scalebar')) &&
                $this->blocks[$id]->content &&
                Utils::isRelativePath($this->blocks[$id]->content)) {
            // detects if image path is relative and than completes it
            $this->blocks[$id]->content = CARTOWEB_HOME . 
                                          $this->blocks[$id]->content;
        }

        elseif ($this->isPrintingPdf($request) && 
                $this->blocks[$id]->type == 'table') {
            $this->setTableBlock($request, $iniObjects, $id);
        }

        elseif ($this->isPrintingPdf($request) && 
                $this->blocks[$id]->type == 'legend') {
            $this->setLegendBlock($request, $id);
        }
    }

    /**
     * Updates available formats list considering allowed roles info.
     * @param boolean if false, use general::formats keys as format ids
     */
    protected function setAllowedFormats($simple) {
        $allowedFormats = array();
        foreach ($this->general->formats as $id => $format) {
            if (!$simple)
                $format = $id;
                
            $formatRoles = $this->getArrayFromIni(
                                               "formats.$format.allowedRoles");
            if (!$formatRoles)
                $formatRoles = SecurityManager::ALL_ROLE;
                
            if (SecurityManager::getInstance()->hasRole($formatRoles))
                $allowedFormats[$id] = $format;
        }
        $this->general->formats = $allowedFormats;
    }

    /**
     * Returns for each allowed format, the list of its allowed resolutions.
     * Warning: perform allowed formats filtering first!
     * @return array array(<format> => array(<list of resolutions>))
     */
    protected function getAllowedResolutions() {
        $allowedResolutions = array();
        foreach ($this->general->formats as $id => $format) {
            $maxResolution = $this->getConfig()->{"formats.$id.maxResolution"};

            if (!$maxResolution) {
                $allowedResolutions[$id] = $this->general->resolutions;
                continue;
            }
            
            $resolutions = array();
            foreach ($this->general->resolutions as $rid => $resolution) {
                if ($rid <= $maxResolution)
                    $resolutions[$rid] = $resolution;
            }
            $allowedResolutions[$id] = $resolutions;
        }
        return $allowedResolutions;
    }

    /**
     * Populates PdfGeneral object.
     * @param stdclass objects from INI file
     * @param array user configs (usually $_REQUEST)
     */
    protected function setGeneral($iniObjects, $request = array()) {
        $this->log->debug(__METHOD__);
    
        $this->general = new PdfGeneral;
        $this->overrideProperties($this->general, $iniObjects->general);
        
        if (empty($this->mapServerResolution)) {
            throw new CartoclientException(
                'Plugin exportPdf is not activated on your CartoServer.');
        }
        $this->general->mapServerResolution = $this->mapServerResolution;
        
        $this->general->formats = $this->getArrayFromList($this->general->formats);
        
        $this->setAllowedFormats(false);
        
        $this->general->resolutions = $this->getArrayFromList(
                                                   $this->general->resolutions);
        
        $this->general->scales = $this->getArrayFromList(
                                                   $this->general->scales);

        $this->general->activatedBlocks = $this->getArrayFromList(
                                               $this->general->activatedBlocks, 
                                               true);
        
        $this->general->selectedFormat = $this->getSelectedValue('format',
                                                       $this->general->formats,
                                                       $request);

        $this->general->selectedResolution = $this->getSelectedValue(
                                                   'resolution',
                                                   $this->general->resolutions,
                                                   $request);

        $this->general->selectedScale = $this->getSelectedValue(
                                                   'scale',
                                                   $this->general->scales,
                                                   $request);

        $this->general->selectedOrientation = $this->getSelectedValue(
                                                'orientation',
                                                array('portrait', 'landscape'),
                                                $request);
    }

    /**
     * Populates PdfFormat object with selected format info.
     * @param stdclass objects from INI file
     */
    protected function setFormat($iniObjects) {
 
        $sf = $this->general->selectedFormat;
        
        if (!isset($iniObjects->formats->$sf))
            throw new CartoclientException("invalid exportPdf format: $sf");
        
        $this->format = new PdfFormat;
        $this->overrideProperties($this->format, $iniObjects->formats->$sf);
            
        if (!isset($this->format->horizontalMargin))
            $this->format->horizontalMargin = $this->general->horizontalMargin;
        if (!isset($this->format->verticalMargin))
            $this->format->verticalMargin = $this->general->verticalMargin;

        // adapts general settings depending on selected format
        if (isset($this->format->maxResolution) &&
            $this->general->selectedResolution > $this->format->maxResolution)
            $this->general->selectedResolution = $this->format->maxResolution;

        if ($this->general->selectedOrientation == 'portrait') {
            $this->general->width = $this->format->smallDimension;
            $this->general->height = $this->format->bigDimension;
        } else {
            $this->general->width = $this->format->bigDimension;
            $this->general->height = $this->format->smallDimension;
        }

        if (!$this->general->width || !$this->general->height)
            throw new CartoclientException('invalid exportPdf dimensions');
    }

    /**
     * Determines what export mode matches current plugin.
     * @return string
     */
    protected function getExportMode() {
        // WARNING: export plugins names must begin with "export"
        // (see ExportPlugin::getExportUrl() code comments)
        $mode = substr(get_class(), 12); // 12 = strlen('ClientExport')
        return strtolower($mode{0}) . substr($mode, 1);
    }

    /**
     * Indicates if PDF printing is currently in progress.
     * @param array
     * @return boolean
     */
    protected function isPrintingPdf($request) {
        if (!isset($this->isPrintingPdf)) {
            $this->isPrintingPdf = isset($request['mode']) &&
                                   $request['mode'] == $this->getExportMode();
        }
        return $this->isPrintingPdf;
    }

    /**
     * Sets PdfGeneral, PdfFormat and PdfBlock objects from config and
     * from request data if any.
     * @param array request data
     */
    protected function setPdfObjects($request = array()) {
        $ini_array = $this->getConfig()->getIniArray();
        $iniObjects = StructHandler::loadFromArray($ini_array);

        // general settings retrieving
        $this->setGeneral($iniObjects, $request);
        
        // formats settings retrieving
        $this->setFormat($iniObjects);

        // blocks settings retrieving
        $this->blockTemplate = new PdfBlock;
        $this->overrideProperties($this->blockTemplate, $iniObjects->template);

        foreach ($this->general->activatedBlocks as $id) {
            $this->createBlock($request, $iniObjects, $id);
        }

        unset($iniObjects);

        $this->log->debug('REQUEST:');
        $this->log->debug($request);
        $this->log->debug('general settings:');
        $this->log->debug($this->general);
        $this->log->debug('format settings:');
        $this->log->debug($this->format);
        $this->log->debug('blocks settings:');
        $this->log->debug($this->blocks);
    }

    /**
     * Sets PDF settings objects based on $_REQUEST and configuration data.
     * @param array $_REQUEST
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {
        $this->log->debug(__METHOD__);

        $pdfRoles = $this->getArrayFromIni('general.allowedRoles');
        if (!SecurityManager::getInstance()->hasRole($pdfRoles)) {
            return;
        }

        $this->exportPdfState->formFields = array();
        if (isset($request['pdfReset'])) {
            return;
        }
        
        $this->log->debug('processing exportPdf request');
        
        // Saves user inputs in session:
        foreach ($request as $inputName => $inputVal) {
            if (substr($inputName, 0, 3) == 'pdf') {
                $this->exportPdfState->formFields[$inputName] = $inputVal;
            }
        }

        $this->setPdfObjects($request);

        if ($this->isPrintingPdf($request)) {
            // sorting blocks (order of processing)
            $this->sortBlocksBy('weight');
            $this->sortBlocksBy('zIndex');
        }
    }

    /**
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {
        if ($this->isPrintingPdf($request)) {
            $this->log->debug(__METHOD__);

            $pdfRoles = $this->getArrayFromIni('general.allowedRoles');
            if (!SecurityManager::getInstance()->hasRole($pdfRoles)) {
                return;
            }

            $this->setPdfObjects();

            // sorting blocks (order of processing)
            $this->sortBlocksBy('weight');
            $this->sortBlocksBy('zIndex');
        }
    }

    /**
     * @see GuiProvider::renderForm()
     * @param Smarty
     */
    public function renderForm(Smarty $template) {
        $this->log->debug(__METHOD__);
        $template->assign('exportPdf', $this->drawUserForm());
    }

    /**
     * Builds PDF settings user interface.
     * @return string Smarty fetch result
     */
    protected function drawUserForm() {

        $pdfRoles = $this->getArrayFromIni('general.allowedRoles');
        if (!SecurityManager::getInstance()->hasRole($pdfRoles))
            return '';

        if (!isset($this->general)) {
            $this->setPdfObjects();
        }

        $allowedResolutions = $this->getAllowedResolutions();
        if (isset($allowedResolutions[$this->general->selectedFormat])) {
            $pdfResolution_options = 
                           $allowedResolutions[$this->general->selectedFormat];
        } else {
            $pdfResolution_options =
                           $allowedResolutions[$this->general->defaultFormat];
        }

        $marginX = $marginY = 0;
        $formatDimensions = array();

        $isModeRotate = ($this->general->guiMode == self::GUIMODE_ROTATE);
        if ($isModeRotate) {
            // Passes map margins and format dimensions to Javascript
            $marginX += $this->general->horizontalMargin;
            $marginX += $this->blocks['mainmap']->horizontalMargin;
            $marginY += $this->general->verticalMargin;
            $marginY += $this->blocks['mainmap']->verticalMargin;       
            foreach ($this->general->formats as $format) {
                $dimension = new stdClass();
                $dimension->format = $format;
                $smallDim = "formats.$format.smallDimension";
                $bigDim   = "formats.$format.bigDimension";
                $dimension->xsize = $this->getConfig()->$smallDim;
                $dimension->ysize = $this->getConfig()->$bigDim;
                $formatDimensions[] = $dimension;
            }
        }
       
        $this->smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $this->smarty->assign(array(
                   'exportScriptPath'       => $this->getExportUrl(),
                   'isModeRotate'           => $isModeRotate,
                   'pdfFormat_options'      => $this->general->formats,
                   'pdfResolution_options'  => $pdfResolution_options,
                   'pdfAllowedResolutions'  => $allowedResolutions,
                   'pdfScale_options'       => $this->general->scales,
                   'pdfOrientation'         => $this->general->defaultOrientation,
                   'pdfMarginX'             => $marginX,
                   'pdfMarginY'             => $marginY,
                   'pdfFormatDimensions'    => $formatDimensions
                       ));
        
        $this->smarty->assign(array(
                   'pdfFormat_selected'     => $this->getFormField('pdfFormat',
                                                             'selectedFormat'),
                   'pdfResolution_selected' => $this->getFormField('pdfResolution',
                                                             'selectedResolution'),
                   'pdfScale_selected'      => $this->getFormField('pdfScale',
                                                             'selectedScale'),
                   'pdfOrientation'         => $this->getFormField('pdfOrientation',
                                                             'defaultOrientation'),
                   'pdfMapAngle'            => $this->getFormField('pdfMapAngle'),
                   'pdfMapCenterX'          => $this->getFormField('pdfMapCenterX'),
                   'pdfMapCenterY'          => $this->getFormField('pdfMapCenterY'),
                       ));
        
        foreach ($this->optionalInputs as $input) {
            $inputName = 'pdf' . ucfirst($input);
            $inputValName = $inputName . '_value';
            if (in_array($input, $this->general->activatedBlocks)) {
                $this->smarty->assign(
                    array($inputName    => true,
                          $inputValName => $this->getFormField($inputName,
                                                               $input, true),
                          ));
            } else {
                $this->smarty->assign(array($inputName    => false,
                                            $inputValName => '',
                                            ));
            }
        }

        return $this->smarty->fetch('form.tpl');
    } 

    /**
     * Gets value of given field from session or else from config.
     * @param string fieldname
     * @param string name of default container
     * @param boolean if true, field is a block
     * @return string
     */
    protected function getFormField($fieldName, $default = false,
                                                $isblock = false) {
        if (count($this->exportPdfState->formFields)) {
            if (isset($this->exportPdfState->formFields[$fieldName])) {
                return $this->exportPdfState->formFields[$fieldName];
            } else {
                return '';
            }
        }

        if ($isblock) {
            if (!isset($this->blocks[$default])) {
                throw new CartoclientException("invalid block id: $default");
            }
            return $this->blocks[$default]->content;    
        }
        
        if ($default && isset($this->general->$default)) {
            return $this->general->$default;
        }

        return '';
    }

    /**
     * Returns given distance at selected printing resolution.
     * @param float distance in PdfGeneral dist_unit
     * @return int distance in pixels
     */
    protected function getNewMapDim($dist) {
        $dist = PrintTools::switchDistUnit($dist,
                                           $this->general->distUnit,
                                           'in');
        $dist *= $this->general->selectedResolution;
        return round($dist);
    }

    /**
     * @return Bbox bbox from last session-saved MapResult.
     */
    protected function getLastBbox() {
        $mapResult = $this->getLastMapResult();
        
        if (is_null($mapResult))
            return new Bbox;

        return $mapResult->locationResult->bbox;
    }

    /**
     * @return float scale from last session-saved MapResult.
     */
    protected function getLastScale() {
        if (!isset($this->mapScale)) {
            $mapResult = $this->getLastMapResult();

            if (is_null($mapResult))
                return 0;
    
            $this->mapScale = $mapResult->locationResult->scale;
        }
        return $this->mapScale;
    }

    /**
     * Returns shape (a rectangle or a rotated rectangle) that will be
     * drawn on overview.
     * @param Bbox
     * @return StyledShape
     */
    protected function getOverviewShape(Bbox $mapBbox) {

        $angle = 0;
        $mapAngle = $this->getFormField('pdfMapAngle');
        if (!is_null($mapAngle) && is_numeric($mapAngle)) {
            $angle = $mapAngle;            
        }
        if (is_null($angle) || $angle == 0) {
            
            // No rotation, returns a rectangle
            $outline = new Rectangle($mapBbox->minx, $mapBbox->miny,
                                     $mapBbox->maxx, $mapBbox->maxy);
        } else {
            
            // Rotation, returns a rotated rectangle (polygon)
            $points = array();
            
            // Centers
            $cx = ($mapBbox->maxx + $mapBbox->minx) / 2;
            $cy = ($mapBbox->maxy + $mapBbox->miny) / 2;
            
            // Moves to origin
            $x1 = $mapBbox->maxx - $cx;
            $y1 = $mapBbox->maxy - $cy;
            
            // Rotates
            $x1p = $x1 * cos($angle) - $y1 * sin($angle);
            $y1p = $x1 * sin($angle) + $y1 * cos($angle);
            
            // Moves to origin                        
            $x2 = $mapBbox->minx - $cx;
            $y2 = $mapBbox->maxy - $cy;
            
            // Rotates
            $x2p = $x2 * cos($angle) - $y2 * sin($angle);
            $y2p = $x2 * sin($angle) + $y2 * cos($angle);

            // Creates polygon
            $points[] = new Point($mapBbox->maxx - $x1 + $x1p,
                                  $mapBbox->maxy - $y1 + $y1p);                                   
            $points[] = new Point($mapBbox->minx - $x2 + $x2p,
                                  $mapBbox->maxy - $y2 + $y2p);
            $points[] = new Point($mapBbox->minx + $x1 - $x1p,
                                  $mapBbox->miny + $y1 - $y1p);
            $points[] = new Point($mapBbox->maxx + $x2 - $x2p,
                                  $mapBbox->miny + $y2 - $y2p);                                                                                            
            $points[] = new Point($mapBbox->maxx - $x1 + $x1p,
                                  $mapBbox->maxy - $y1 + $y1p);                                   
            $outline = new Polygon();            
            $outline->points = $points; 
        }
        $styledOutline = new StyledShape();
        $styledOutline->shape = $outline;
                
        $shapeStyle = new StyleOverlay();
      
        if (isset($this->general->overviewColor) && 
            $this->general->overviewColor &&
            $this->general->overviewColor != 'none') {
  
            list($r, $g, $b) = Utils::switchColorToRgb($this->general
                                                            ->overviewColor);
            $shapeStyle->color->setFromRGB($r, $g, $b);
        } else {
            $shapeStyle->color->setFromRGB(-1, -1, -1);
        }
                
        if (isset($this->general->overviewOutlineColor) &&
            $this->general->overviewOutlineColor &&
            $this->general->overviewOutlineColor != 'none') {

            list($r, $g, $b) = Utils::switchColorToRgb($this->general
                                                            ->overviewOutlineColor);
            $shapeStyle->outlineColor->setFromRGB($r, $g, $b);
        } else {
            $shapeStyle->outlineColor->setFromRGB(-1, -1, -1);
        }
                
        $styledOutline->shapeStyle = $shapeStyle;
        return $styledOutline;
    }

    /**
     * Builds export configuration.
     * @param string keymap/overview type
     * @param Bbox if set, indicates mainmap extent to outline in overview map
     * @return ExportConfiguration
     */
    protected function getConfiguration($keymap = 'none', $mapBbox = NULL) {
        $this->log->debug(__METHOD__);
        
        $config = new ExportConfiguration();

        if ($this->general->guiMode == self::GUIMODE_ROTATE &&
            !empty($this->general->selectedScale)) {
            $scale = $this->general->selectedScale;
        } else {
            $scale = $this->getLastScale();
        }
        $mapWidth = $mapHeight = 0;
        $mapAngle = NULL;

        $showRefMarks = false;
        if ($keymap == 'overview') {
            // getting overview map
            $renderMap = true;
            $renderScalebar = false;
            $renderKeymap = false;

            $overview = $this->blocks['overview'];

            // overview dimensions
            $mapWidth = $this->getNewMapDim($overview->width);
            $mapHeight = $this->getNewMapDim($overview->height);

            // mainmap outline:
            if (isset($mapBbox) && 
                $this->cartoclient->getPluginManager()
                                  ->getPlugin('outline') != NULL) {
                
                $styledOutline = $this->getOverviewShape($mapBbox);
                $config->setPrintOutline($styledOutline);
            }
            
            // scale:
            if ($this->general->overviewScaleFactor <= 0)
                $this->general->overviewScaleFactor = 1;

            $scale *= $this->general->overviewScaleFactor;
       } else {
            $renderMap = isset($this->blocks['mainmap']);
            $renderScalebar = isset($this->blocks['scalebar']);
            $renderKeymap = ($keymap == 'static');
            $showRefMarks = $this->general->showRefMarks;

            if ($renderMap) {
                $mainmap = $this->blocks['mainmap'];

                // new map dimensions:
                $mapWidth = $this->getNewMapDim($mainmap->width);
                $mapHeight = $this->getNewMapDim($mainmap->height);
                $mapAngle = Utils::negativeRad2Deg($this->getFormField('pdfMapAngle'));
            }
        }
       
        $config->setRenderMap($renderMap);
        $config->setRenderKeymap($renderKeymap);
        $config->setRenderScalebar($renderScalebar);

        // map dimensions:
        $config->setMapWidth($mapWidth);
        $config->setMapHeight($mapHeight);
        $config->setMapAngle($mapAngle);
      
        // scale:
        $scale *= $this->general->mapServerResolution;
        $scale /= $this->general->selectedResolution;
        $config->setScale($scale);
        
        // resolution
        $config->setResolution($this->general->selectedResolution);
        
        // map center coordinates:
        $savedBbox = $this->getLastBbox();
        $center = NULL;
        $cx = $this->getFormField('pdfMapCenterX');
        $cy = $this->getFormField('pdfMapCenterY');
        if (!is_null($cx) && is_numeric($cx)
            && !is_null($cy) && is_numeric($cy)) {
            $center = new Point($cx, $cy);
        } else {
            $center = $savedBbox->getCenter();
        }
        $config->setPoint($center);
        
        $config->setBbox($savedBbox);      
        $config->setZoomType('ZOOM_SCALE');
        $config->setLocationType('zoomPointLocationRequest');

        $config->setShowRefMarks($showRefMarks);

        $this->log->debug('Selected resolution: ' .
                          $this->general->selectedResolution);
        $this->log->debug('Print config:');
        $this->log->debug($config);

        return $config;
    }

    /**
     * Returns the absolute URL of $gfx, using the ResourceHandler
     * @param string
     * @return string
     */
    protected function getGfxPath($gfx) {

        $resourceHandler = $this->cartoclient->getResourceHandler();
        $url = $resourceHandler->getPathOrAbsoluteUrl($gfx, false);
        return ResourceHandler::convertXhtml($url, true);
    }

    /**
     * Updates Mapserver-generated maps PdfBlocks with data returned by 
     * CartoServer.
     * @param MapResult
     * @param string name of PdfBlock to update
     * @param string name of MapResult property
     */
    protected function updateMapBlock($mapObj, $name, $msName = '') {
        if (!$msName) $msName = $name;

        if (!$mapObj instanceof MapResult ||
            !$mapObj->imagesResult->$msName->isDrawn ||
            !isset($this->blocks[$name]))
            return;

        $map = $mapObj->imagesResult->$msName;
        $block = $this->blocks[$name];

        $block->content = $this->getGfxPath($map->path);
        $block->type = 'image';

        $resolution = ($msName == 'keymap') ?
                      $this->general->mapServerResolution :
                      $this->general->selectedResolution;
        
        if (!isset($block->width)) {
            $width = $map->width / $resolution;
            $block->width = PrintTools::switchDistUnit($width,
                                       'in', $this->general->distUnit);
        }
        
        if (!isset($block->height)) {
            $height = $map->height / $resolution;
            $block->height = PrintTools::switchDistUnit($height,
                                       'in', $this->general->distUnit);
        }
    }

    /**
     * Sets mainmap dimensions according to selected format and orientation.
     * @param PdfWriter
     */
    protected function setMainMapDim(PdfWriter $pdf) {
        $mapBlock = $this->blocks['mainmap'];
        
        if (!isset($mapBlock->width)) {
            $hmargin = $this->format->horizontalMargin 
                       + $mapBlock->horizontalMargin;
            $mapBlock->width = $pdf->getPageWidth() - 2 * $hmargin; 
        }
        
        if (!isset($mapBlock->height)) {
            $vmargin = $this->format->verticalMargin
                       + $mapBlock->verticalMargin;
            $mapBlock->height = $pdf->getPageHeight() - 2 * $vmargin;
        }
    }

    /**
     * Transforms results from TableGroups into TableElements
     * @return array array of TableElement
     */
    protected function getQueryResult() {
        $this->log->debug(__METHOD__);
        
        // Gets optional table groups (default = all)
        $groups = $this->blocks['queryResult']->content;
   
        $tables = $this->cartoclient->getPluginManager()->tables;
        $tableGroups = $tables->getTableGroups();

        if (empty($tableGroups))
            return array();

        $results = array();
        foreach ($tableGroups as $group) {

            if ($groups && !in_array($group->groupId, $groups)) {
                continue;
            }

            foreach ($group->tables as $table) {

                $tableElt = new TableElement;
                $tableElt->caption = $table->tableTitle;
                $tableElt->headers = $table->noRowId ? array() 
                                                     : array(I18n::gt('Id'));
                
                foreach ($table->columnTitles as $field) {
                    $tableElt->headers[] = $field;
                }

                foreach ($table->rows as $res) {
                    $row = $table->noRowId ? array() : array($res->rowId);
                    foreach ($res->cells as $val) {
                        $row[] = $val;
                    }
                    $tableElt->rows[] = $row;
                }
                $results[] = $tableElt;
            }
        }
        return $results;
    }
    
    /**
     * Returns (x,y) coords of given map corner using given format.
     * @param PdfBlock
     * @return string
     */
    protected function getCornerCoords(PdfBlock $block, MapResult $mapResult) {
        switch ($block->id) {
            case 'tlcoords':
                $x = $mapResult->locationResult->bbox->minx;
                $y = $mapResult->locationResult->bbox->maxy;
                break;
            
            case 'brcoords':
                $x = $mapResult->locationResult->bbox->maxx;
                $y = $mapResult->locationResult->bbox->miny;
                break;

            default: return;
        }

        switch ($block->content) {
            case 'YX':
                $block->content = sprintf('Y = %d, X = %d', $x, $y);
                break;

            case 'xy':
            default:
                $block->content = sprintf('x = %d, y = %d', $x, $y);
        }
    }

    /**
     * Draws given block.
     * @param PdfWriter
     * @param PdfBlock
     */
    protected function addBlock(PdfWriter $pdf, PdfBlock $block) {
        switch ($block->type) {
            case 'image':
                $pdf->addGfxBlock($block);
                break;
            case 'text':
                $pdf->addTextBlock($block);
                break;
            case 'legend':
                $pdf->addLegend($block);
                break;
            case 'table':
                $pdf->addTable($block);
                break;
            case 'north':
                $pdf->addNorthArrow($block,
                    Utils::negativeRad2Deg($this->getFormField('pdfMapAngle')));
                break;
            default:
                // ignores block
            
            // TODO: handle type = pdf
        }
    }

    /**
     * @see ExportPlugin::getExport()
     * @return ExportOutput export result
     */
    protected function getExport() {
        $this->log->debug(__METHOD__);

        $pdfClass =& $this->general->pdfEngine;
        
        $pdfClassFile = dirname(__FILE__) . '/' . $pdfClass . '.php';
        if (!is_file($pdfClassFile)) {
            throw new CartoclientException("invalid PDF engine: $pdfClassFile");
        }
        
        require_once $pdfClassFile;
        $pdf = new $pdfClass($this);
 
        if (isset($this->blocks['mainmap']))
            $this->setMainMapDim($pdf);

        if (isset($this->blocks['overview'])) {
            $keymap = ($this->blocks['overview']->content == 'static') ?
                      'static' : 'overview';
        } else {
            $keymap = 'none';
        }
 
        // Retrieving of data from CartoServer:
        $mapResult = $this->getExportResult(
                         $this->getConfiguration($keymap == 'static' ? 
                                                 'static' : 'none'));
 
        if ($keymap == 'overview') {
            $mapBbox = $mapResult->locationResult->bbox;
            $overviewResult = $this->getExportResult(
                                  $this->getConfiguration($keymap, $mapBbox));
        } else {
            $overviewResult = false;
        }

        if (isset($this->blocks['mainmap'])) {
            $this->updateMapBlock($mapResult, 'mainmap');
            $this->updateMapBlock($mapResult, 'scalebar');
            if ($keymap == 'static') {
                $this->updateMapBlock($mapResult, 'overview', 'keymap');
            } else {
                $this->updateMapBlock($overviewResult, 'overview', 'mainmap');
            }
        }

        if (isset($this->blocks['tlcoords']))
            $this->getCornerCoords($this->blocks['tlcoords'], $mapResult);
        if (isset($this->blocks['brcoords']))
            $this->getCornerCoords($this->blocks['brcoords'], $mapResult);
        
        if (isset($this->blocks['scaleval'])) {
            $scale = $mapResult->locationResult->scale;
            $scale *= $this->general->selectedResolution;
            $scale /= $this->general->mapServerResolution;
            $scale = number_format($scale, 0, ',',"'");
            $this->blocks['scaleval']->content = sprintf('%s 1:%s',
                                                         I18n::gt('Scale'),
                                                         $scale);
        }
        
        if (isset($this->blocks['date'])) {
            if (preg_match('/^(.*)\[(.*)\](.*)$/Us', 
                           $this->blocks['date']->content, $regs)) {
                $this->blocks['date']->content = 
                                          $regs[1] . date($regs[2]) . $regs[3];
            } else {
                $this->blocks['date']->content = 
                                          date($this->blocks['date']->content);
            }
        }

        $pdf->initializeDocument();
 
        if(isset($this->blocks['mainmap'])) {
            $pdf->addPage();
        }
 
        $lastPagesBlocks = array();
        foreach ($this->blocks as $block) {
            if ($block->inLastPages) {
                $lastPagesBlocks[] = $block->id;
                continue;
            }
        
            if ($block->multiPage || $block->inNewPage || !$block->standalone)
                continue;

            if (isset($this->blocks['mainmap'])) {
                $this->addBlock($pdf, $block);
            }
        }

        if (isset($this->blocks['legend']) && 
            $this->blocks['legend']->inNewPage &&
            !$this->blocks['legend']->inLastPages) {
            $pdf->addPage();
            $pdf->addLegend($this->blocks['legend']);
        }

        // query results displaying
        if (isset($this->blocks['queryResult'])) {
            $queryResult = $this->getQueryResult();        
            if ($queryResult) {
                $this->blocks['queryResult']->content = $queryResult;
                
                $pdf->addPage();
                $pdf->addTable($this->blocks['queryResult']);
            }
        }
        
        // handling inLastPages blocks:
        foreach ($lastPagesBlocks as $id) {
            if (!$this->blocks[$id]->content)
                continue;

            $pdf->addPage();
            $this->addBlock($pdf, $block);
        }
 
        $contents = $pdf->finalizeDocument();
        $this->charset = $pdf->getCharset();
 
        $output = new ExportOutput();
        $output->setContents($contents);
        return $output;
    }

    /**
     * Writes PDF file on disk.
     * @param string PDF content
     * @return string filename
     */
    protected function generatePdfFile($pdfBuffer) {
        $filename = $this->getFilename();
    
        $filepath = $this->getCartoclient()->getConfig()->webWritablePath . 
                        'pdf/' . $filename;

        $fp = fopen($filepath, 'w');
        fwrite($fp, $pdfBuffer);
        fclose($fp);
        return $filename;
    }

    /**
     * Returns generated PDF file URL.
     * @param string filename
     * @param boolean if true, remove special chars from URL
     * @return string URL
     */
    protected function getPdfFileUrl($filename, $filter = false) {
        $resourceHandler = $this->cartoclient->getResourceHandler();
        $pdfUrl = $resourceHandler->getGeneratedUrl('pdf/' . $filename);
        $pdfUrl = $resourceHandler->getFinalUrl($pdfUrl, true, true);

        if ($filter) {
            $pdfUrl = ResourceHandler::convertXhtml($pdfUrl, true);
        }

        return $pdfUrl;
    }

    /**
     * Set type (PDF) and charset header.
     */
    protected function setTypeHeader() {
        header('Content-type: application/pdf; charset=' . $this->charset);
    }

    /**
     * @see ExportPlugin::output()
     */
    public function output() {
        $this->log->debug(__METHOD__);
        
        $pdfBuffer = $this->getExport()->getContents();
    
        switch ($this->general->output) {
            case self::OUTPUT_INLINE:
                $this->setTypeHeader();
                header('Content-Length: ' . strlen($pdfBuffer));
                header('Content-Disposition: inline; filename=' . 
                       $this->getFilename());
                print $pdfBuffer;
                break;

            case self::OUTPUT_ATTACHMENT:
                $this->setTypeHeader();
                header('Content-Length: ' . strlen($pdfBuffer));
                header('Content-Disposition: attachment; filename=' .
                       $this->getFilename());
                print $pdfBuffer;
                break;

            case self::OUTPUT_LINK:
                $filename = $this->generatePdfFile($pdfBuffer);
                $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
                $smarty->assign('pdfFileUrl', $this->getPdfFileUrl($filename));
                $smarty->display('outputLink.tpl');
                break;

            case self::OUTPUT_REDIRECTION:
            default:
                $filename = $this->generatePdfFile($pdfBuffer);
                $this->setTypeHeader();
                header('Location: ' . $this->getPdfFileUrl($filename, true));
                break;
        }
        
        return '';
    }

    /**
     * @see Sessionable::loadSession()
     */
    public function loadSession($sessionObject) {
        $this->exportPdfState = $sessionObject;
    }

    /**
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->exportPdfState = new ExportPdfState;
    }

    /**
     * @see Sessionable::saveSession()
     */
    public function saveSession() {
        return $this->exportPdfState;
    }
}
?>
