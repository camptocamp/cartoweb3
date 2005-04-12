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

require_once CARTOCLIENT_HOME . 'client/ExportPlugin.php';
require_once dirname(__FILE__) . '/ExportPdfObjects.php';

/**
 * Overall class for PDF generation management.
 * @package Plugins
 */
class ClientExportPdf extends ExportPlugin
                      implements InitUser {

    /**
     * @var Logger
     */
    private $log;
    
    /**
     * @var Smarty_CorePlugin
     */
    private $smarty;

    /**
     * @var PdfGeneral
     */
    private $general;

    /**
     * @var PdfFormat
     */
    private $format;
    
    /**
     * @var PdfBlock
     */
    private $blockTemplate;
    
    /**
     * @var array
     */
    private $blocks = array();

    /**
     * @var array
     */
    private $optionalInputs = array('title', 'note', 'scalebar', 'overview',
                                    'queryResult', 'legend');
    //TODO: display queryResult form option only if available in MapResult

    /**
     * @var float
     */
    private $mapScale;

    /**
     * @var int
     */
    private $mapServerResolution;

    /**
     * @var string
     */
    private $charset;

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
        $this->mapServerResolution = $exportPdfInit->mapServerResolution;
    }

    /**
     * Returns PDF file name.
     * @return string
     */
    public function getFilename() {
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
    private function getArrayFromList($list, $simple = false) {
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
    private function getArrayFromIni($name, $simple = false) {
        $data = $this->getConfig()->$name;
        if (!$data) return array();

        return $this->getArrayFromList($data, $simple);
    }

    /**
     * Updates $target properties with values from $from ones.
     * @param object object to override
     * @param object object to copy
     */
    private function overrideProperties($target, $from) {
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
    private function getSelectedValue($name, $choices, $request) {
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
    private function sortBlocksBy($property) {
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
     * Instanciates a PdfBlock object.
     * @param array $request
     * @param stdClass INI object
     * @param string object id
     */
    private function createBlock($request, $iniObjects, $id) {
        // removes blocks with no user input if required:
        $pdfItem = 'pdf' . ucfirst($id);
        if (!(isset($request[$pdfItem]) && trim($request[$pdfItem])) &&
            in_array($id, $this->optionalInputs))
            return;
        
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

        if ($id == 'title' || $id == 'note') {
            $content = trim($request[$pdfItem]);
            $this->blocks[$id]->content = stripslashes($content);
        }

        elseif ($id == 'scaleval') {
            $scale = number_format($this->getLastScale(), 0, ',',"'");
            $this->blocks[$id]->content = sprintf('%s 1:%s',
                                                  I18n::gt('Scale'),
                                                  $scale);
        }

        elseif ($this->blocks[$id]->type == 'image' && 
                !in_array($id, array('mainmap', 'overview', 'scalebar')) &&
                $this->blocks[$id]->content &&
                substr($this->blocks[$id]->content, 0, 4) != 'http' &&
                substr($this->blocks[$id]->content, 0, 1) != '/' &&
                substr($this->blocks[$id]->content, 1, 1) != ':') {
            // detects if image path is relative and than completes it
            $this->blocks[$id]->content = CARTOCLIENT_HOME . 
                                          $this->blocks[$id]->content;
        }

        elseif($this->blocks[$id]->type == 'table') {
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

        elseif ($this->blocks[$id]->type == 'legend') {
            do {
                $lastMapRequest = $this->getLastMapRequest();
                $layersCorePlugin = $this->cartoclient->getPluginManager()->
                                    getPlugin('layers');
                
                if (is_null($lastMapRequest) || is_null($layersCorePlugin)) {
                    unset($this->blocks[$id], $lastMapRequest,
                          $layersCorePlugin);
                    break;
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
            while (false);
        }
    }

    /**
     * Updates available formats list considering allowed roles info.
     * @param boolean if false, use general::formats keys as format ids
     */
    private function setAllowedFormats($simple) {
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
    private function getAllowedResolutions() {
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
    private function setGeneral($iniObjects, $request = array()) {
    
        $this->general = new PdfGeneral;
        $this->general->mapServerResolution = $this->mapServerResolution;
        
        $this->overrideProperties($this->general, $iniObjects->general);
        
        $simple = (count($request) != 0);
        $this->general->formats = $this->getArrayFromList(
                                                       $this->general->formats,
                                                       $simple);
        $this->setAllowedFormats($simple);
        
        $this->general->resolutions = $this->getArrayFromList(
                                                   $this->general->resolutions,
                                                   $simple);
        
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

        $this->general->selectedOrientation = $this->getSelectedValue(
                                                'orientation',
                                                array('portrait', 'landscape'),
                                                $request);
    }

    /**
     * Populates PdfFormat object with selected format info.
     * @param stdclass objects from INI file
     */
    private function setFormat($iniObjects) {
 
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
     * Sets PDF settings objects based on $_REQUEST and configuration data.
     * @param array $_REQUEST
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {
        
        if (!isset($request['pdfExport']))
            return;

        $pdfRoles = $this->getArrayFromIni('general.allowedRoles');
        if (!SecurityManager::getInstance()->hasRole($pdfRoles))
            return;

        $this->log->debug('processing exportPdf request');

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

        // sorting blocks (order of processing)
        $this->sortBlocksBy('weight');
        $this->sortBlocksBy('zIndex');

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
     * Not used/implemented yet.
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {}

    /**
     * @see GuiProvider::renderForm()
     * @param Smarty
     */
    public function renderForm(Smarty $template) {

        $template->assign('exportPdf', $this->drawUserForm());
    }

    /**
     * Builds PDF settings user interface.
     * @return string Smarty fetch result
     */
    private function drawUserForm() {

        $pdfRoles = $this->getArrayFromIni('general.allowedRoles');
        if (!SecurityManager::getInstance()->hasRole($pdfRoles))
            return '';

        $ini_array = $this->getConfig()->getIniArray();
        $iniObjects = StructHandler::loadFromArray($ini_array);
        $this->setGeneral($iniObjects);

        $allowedResolutions = $this->getAllowedResolutions();
        if (isset($allowedResolutions[$this->general->selectedFormat])) {
            $pdfResolution_options = 
                           $allowedResolutions[$this->general->selectedFormat];
        } else {
            $pdfResolution_options =
                           $allowedResolutions[$this->general->defaultFormat];
        }
        
        $this->smarty = new Smarty_CorePlugin($this->getCartoclient(), $this);
        $this->smarty->assign(array(
                   'exportScriptPath'       => $this->getExportScriptPath(),
                   'pdfFormat_options'      => $this->general->formats,
                   'pdfFormat_selected'     => $this->general->selectedFormat,
                   'pdfResolution_options'  => $pdfResolution_options,
                   'pdfResolution_selected' => $this->general->
                                                            selectedResolution,
                   'pdfAllowedResolutions'  => $allowedResolutions,
                   'pdfOrientation'         => $this->general->
                                                            defaultOrientation,
                       ));

        foreach ($this->optionalInputs as $input) {
            $this->smarty->assign('pdf' . ucfirst($input),
                                  in_array($input, 
                                           $this->general->activatedBlocks));
        }
        
        return $this->smarty->fetch('form.tpl');
    }

    /**
     * Returns given distance at selected printing resolution.
     * @param float distance in PdfGeneral dist_unit
     * @return int distance in pixels
     */
    private function getNewMapDim($dist) {
        $dist = PrintTools::switchDistUnit($dist,
                                           $this->general->distUnit,
                                           'in');
        $dist *= $this->general->selectedResolution;
        return round($dist);
    }

    /**
     * @return Bbox bbox from last session-saved MapResult.
     */
    private function getLastBbox() {
        $mapResult = $this->getLastMapResult();
        
        if (is_null($mapResult))
            return new Bbox;

        return $mapResult->locationResult->bbox;
    }

    /**
     * @return float scale from last session-saved MapResult.
     */
    private function getLastScale() {
        if (!isset($this->mapScale)) {
            $mapResult = $this->getLastMapResult();

            if (is_null($mapResult))
                return 0;
    
            $this->mapScale = $mapResult->locationResult->scale;
        }
        return $this->mapScale;
    }

    /**
     * Builds export configuration.
     * @param boolean true if configuring to get overview map
     * @param Bbox if set, indicates mainmap extent to outline in overview map
     * @return ExportConfiguration
     */
    private function getConfiguration($isOverview = false, $mapBbox = NULL) {
        
        $config = new ExportConfiguration();

        $scale = $this->getLastScale();
        $mapWidth = $mapHeight = 0;

        if ($isOverview) {
            // getting overview map
            $renderMap = true;
            $renderScalebar = false;

            $overview = $this->blocks['overview'];

            // overview dimensions
            $mapWidth = $this->getNewMapDim($overview->width);
            $mapHeight = $this->getNewMapDim($overview->height);

            // mainmap outline:
            if (isset($mapBbox)) {
                $outline = new Rectangle($mapBbox->minx, $mapBbox->miny,
                                         $mapBbox->maxx, $mapBbox->maxy);
                $styledOutline = new StyledShape();
                $styledOutline->shape = $outline;
                $config->setPrintOutline($styledOutline);
            }
            
            // scale:
            if ($this->general->overviewScaleFactor <= 0)
                $this->general->overviewScaleFactor = 1;

            $scale *= $this->general->overviewScaleFactor;
       } else {
            $renderMap = isset($this->blocks['mainmap']);
            $renderScalebar = isset($this->blocks['scalebar']);

            if ($renderMap) {
                $mainmap = $this->blocks['mainmap'];

                // new map dimensions:
                $mapWidth = $this->getNewMapDim($mainmap->width);
                $mapHeight = $this->getNewMapDim($mainmap->height);

                // scale: no change ("paper" scale = "screen" scale)
            }
        }
       
        $config->setRenderMap($renderMap);
        $config->setRenderKeymap(false);
        $config->setRenderScalebar($renderScalebar);

        // map dimensions:
        $config->setMapWidth($mapWidth);
        $config->setMapHeight($mapHeight);
      
        // scale:
        $scale *= $this->general->mapServerResolution;
        $scale /= $this->general->selectedResolution;
        $config->setScale($scale);
        
        // resolution
        $config->setResolution($this->general->selectedResolution);
        
        // map center coordinates:
        $savedBbox = $this->getLastBbox();
        $xCenter = ($savedBbox->minx + $savedBbox->maxx) / 2;
        $yCenter = ($savedBbox->miny + $savedBbox->maxy) / 2;
        $point = new Point($xCenter, $yCenter);
        $config->setPoint($point);

        $config->setBbox($savedBbox);      
        $config->setZoomType('ZOOM_SCALE');
        $config->setLocationType('zoomPointLocationRequest');

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
    private function getGfxPath($gfx) {

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
    private function updateMapBlock($mapObj, $name, $msName = '') {
        if (!$msName) $msName = $name;

        if (!$mapObj instanceof MapResult ||
            !$mapObj->imagesResult->$msName->isDrawn ||
            !isset($this->blocks[$name]))
            return;

        $map = $mapObj->imagesResult->$msName;
        $block = $this->blocks[$name];

        $block->content = $this->getGfxPath($map->path);
        $block->type = 'image';
        
        if (!isset($block->width)) {
            $width = $map->width / $this->general->selectedResolution;
            $block->width = PrintTools::switchDistUnit($width,
                                       'in', $this->general->distUnit);
        }
        
        if (!isset($block->height)) {
            $height = $map->height / $this->general->selectedResolution;
            $block->height = PrintTools::switchDistUnit($height,
                                       'in', $this->general->distUnit);
        }
    }

    /**
     * Sets mainmap dimensions according to selected format and orientation.
     * @param PdfWriter
     */
    private function setMainMapDim(PdfWriter $pdf) {
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
     * Transforms query results from MapResult into TableElements
     * @param MapResult
     * @return array array of TableElement
     */
    private function getQueryResult($mapResult) {
        if (!$mapResult instanceof MapResult || 
            !isset($mapResult->queryResult) ||
            !isset($mapResult->queryResult->tableGroup))
            return array();

        $results = array();
        foreach ($mapResult->queryResult->tableGroup->tables as $table) {
            if (!$table->numRows)
                continue;

            $tableElt = new TableElement;
            
            $tableElt->caption = I18n::gt($table->tableTitle);
            
            $tableElt->headers = array(I18n::gt('Id'));
            foreach ($table->columnTitles as $field)
                $tableElt->headers[] = I18n::gt($field);

            foreach ($table->rows as $res) {
                $row = array($res->rowId);
                foreach ($res->cells as $val)
                    $row[] = Encoder::decode($val);
                $tableElt->rows[] = $row;
            }

            $results[] = $tableElt;
        }
    
        return $results;
    }
    
    /**
     * Returns (x,y) coords of given map corner using given format.
     * @param PdfBlock
     * @return string
     */
    private function getCornerCoords(PdfBlock $block, MapResult $mapResult) {
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
    private function addBlock(PdfWriter $pdf, PdfBlock $block) {
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
            default:
                // ignores block
            
            // TODO: handle type = pdf
        }
    }

    /**
     * @see ExportPlugin::getExport()
     * @return ExportOutput export result
     */
    public function getExport() {

        $pdfClass =& $this->general->pdfEngine;
        
        $pdfClassFile = dirname(__FILE__) . '/' . $pdfClass . '.php';
        if (!is_file($pdfClassFile))
            throw new CartoclientException("invalid PDF engine: $pdfClassFile");
        require_once $pdfClassFile;
 
        $pdf = new $pdfClass($this);
 
        if (isset($this->blocks['mainmap']))
            $this->setMainMapDim($pdf);
 
        // Retrieving of data from CartoServer:
        $mapResult = $this->getExportResult($this->getConfiguration());
 
        if (isset($this->blocks['overview'])) {
            $mapBbox = $mapResult->locationResult->bbox;
            $overviewResult = $this->getExportResult(
                                  $this->getConfiguration(true, $mapBbox));
        } else {
            $overviewResult = false;
        }

        $this->updateMapBlock($mapResult, 'mainmap');
        $this->updateMapBlock($mapResult, 'scalebar');
        $this->updateMapBlock($overviewResult, 'overview', 'mainmap');

        if (isset($this->blocks['tlcoords']))
            $this->getCornerCoords($this->blocks['tlcoords'], $mapResult);
        if (isset($this->blocks['brcoords']))
            $this->getCornerCoords($this->blocks['brcoords'], $mapResult);
        
        $pdf->initializeDocument();
 
        $pdf->addPage();
 
        $lastPagesBlocks = array();
        foreach ($this->blocks as $block) {
            if ($block->inLastPages) {
                $lastPagesBlocks[] = $block->id;
                continue;
            }
        
            if ($block->multiPage || $block->inNewPage || !$block->standalone)
                continue;

            $this->addBlock($pdf, $block);
        }

        if (isset($this->blocks['legend']) && 
            $this->blocks['legend']->inNewPage &&
            !$this->blocks['legend']->inLastPages) {
            $pdf->addPage();
            $pdf->addLegend($this->blocks['legend']);
        }

        // query results displaying
        if (isset($this->blocks['queryResult'])) {
            $queryResult = $this->getQueryResult($mapResult);        
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
    private function generatePdfFile($pdfBuffer) {
        $filename = $this->getFilename();
        $filepath = CARTOCLIENT_HOME . 'www-data/pdf/' . $filename;
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
    private function getPdfFileUrl($filename, $filter = false) {
        $resourceHandler = $this->cartoclient->getResourceHandler();
        $pdfUrl = $resourceHandler->getUrlProvider()->getGeneratedUrl('pdf/' . 
                                                                    $filename);
        $pdfUrl = $resourceHandler->getFinalUrl($pdfUrl, true, true);

        if ($filter) {
            $pdfUrl = ResourceHandler::convertXhtml($pdfUrl, true);
        }

        return $pdfUrl;
    }

    /**
     * Set type (PDF) and charset header.
     */
    private function setTypeHeader() {
        header('Content-type: application/pdf; charset=' . $this->charset);
    }

    /**
     * Outputs generated PDF file using config "output" medium.
     * @param string PDF content
     */
    public function outputPdf($pdfBuffer) {
        switch ($this->general->output) {
            case 'inline':
                $this->setTypeHeader();
                header('Content-Length: ' . strlen($pdfBuffer));
                header('Content-Disposition: inline; filename=' . 
                       $this->getFilename());
                print $pdfBuffer;
                break;

            case 'attachment':
                $this->setTypeHeader();
                header('Content-Length: ' . strlen($pdfBuffer));
                header('Content-Disposition: attachment; filename=' .
                       $this->getFilename());
                print $pdfBuffer;
                break;

            case 'link':
                $filename = $this->generatePdfFile($pdfBuffer);
                // TODO: use template
                printf('<a href="%s">%s</a>',
                       $this->getPdfFileUrl($filename),
                       I18n::gt('Click here to display PDF file'));
                break;

            case 'redirection':
            default:
                $filename = $this->generatePdfFile($pdfBuffer);
                $this->setTypeHeader();
                header('Location: ' . $this->getPdfFileUrl($filename, true));
                break;
        }
        exit;
    }
}
?>
