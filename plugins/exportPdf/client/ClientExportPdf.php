<?php
/**
 * @package Plugins
 * @version $Id$
 */

require_once(CARTOCLIENT_HOME . 'client/ExportPlugin.php');

/**
 * Provides static conversion tools.
 * @package Plugins
 */
class PrintTools {

    /**
     * Converts the distance $dist from $from unit to $to unit.
     * 1 in = 72 pt = 2.54 cm = 25.4 mm
     */
    static function switchDistUnit($dist, $from, $to) {
        if ($from == $to) return $dist;
        
        $ratio = 1;
        
        if ($from == 'cm')
            $ratio = self::switchDistUnit(10, 'mm', $to);
        elseif ($to == 'cm')
            $ratio = self::switchDistUnit(0.1, $from, 'mm');

        if ($from == 'in')
            $ratio = self::switchDistUnit(25.4, 'mm', $to);
        elseif ($to == 'in')
            $ratio = self::switchDistUnit(1 / 25.4, $from, 'mm');

        if ($from == 'mm' && $to == 'pt')
            $ratio *= 72 / 25.4;
        elseif ($from == 'pt' && $to == 'mm')
            $ratio *= 25.4 / 72;
        else
            throw new CartoclientException("unknown dist unit: $from or $to");
        
        return $dist * $ratio;
    }

    /**
     * Converts #xxyyzz hexadecimal color codes into RGB.
     */
    static function switchHexColorToRgb($color) {
        return array(hexdec(substr($color, 1, 2)), 
                     hexdec(substr($color, 3, 2)), 
                     hexdec(substr($color, 5, 2))
                     );
    }

    static function switchColorToRgb($color) {
        if ($color{0} == '#')
            return self::switchHexColorToRgb($color);

        if (is_array($color))
            return $color;

        switch($color) {
            case 'black': return array(0, 0, 0);
            case 'white': default: return array(255, 255, 255);
        }
    }

    static function getPdfDir() {
        $dir = CARTOCLIENT_HOME . 'www-data/pdf';
        if (!is_dir($dir)) {
            //FIXME: security issue?
            mkdir($dir, 0777);
        }
        return $dir;
    }
}

/**
 * @package Plugins
 */
class PdfGeneral {
    public $pdfEngine          = 'PdfLibLite';
    public $pdfVersion         = '1.3';
    public $distUnit           = 'mm';
    public $horizontalMargin   = 10;
    public $verticalMargin     = 10;
    public $width;
    public $height;
    public $formats;
    public $defaultFormat;
    public $selectedFormat;
    public $resolutions        = array(96);
    public $defaultResolution  = 96;
    public $selectedResolution;
    public $defaultOrientation = 'portrait';
    public $selectedOrientation;
    public $activatedBlocks;
    public $allowPdfInput      = false;
    public $filename           = 'map.pdf';
}

/**
 * @package Plugins
 */
class PdfFormat {
    public $label;
    public $bigDimension;
    public $smallDimension;
    public $horizontalMargin;
    public $verticalMargin;
    public $maxResolution;
}

/**
 * @package Plugins
 */
class PdfBlock {
    public $type;
    public $content          = false;
    public $fontFamily       = 'times';
    public $fontSize         = 12; // pt
    public $fontItalic       = false;
    public $fontBold         = false;
    public $fontUnderline    = false;
    public $color            = 'black';
    public $backgroundColor  = 'white';
    public $borderWidth      = 1;
    public $borderColor      = 'black';
    public $borderStyle      = 'solid';
    public $padding          = 0;
    public $horizontalMargin = 0;
    public $verticalMargin   = 0;
    public $horizontalBasis  = 'left';
    public $verticalBasis    = 'top';
    public $hCentered        = false;
    public $textAlign        = 'center';
    public $verticalAlign    = 'center';
    public $orientation      = 'horizontal';
    public $zIndex           = 1;
    public $weight           = 50;
    public $inNewPage        = false;
    public $inLastPages      = false;
    public $width;
    public $height;
    public $singleUsage      = true;
}

/**
 * Interface for PDF generators tools.
 * @package Plugins
 */
interface PdfWriter {

    function initializeDocument();
    function addPage();
    function addTextBlock(PdfBlock $block);
    function addGfxBlock(PdfBlock $block);
    function addTableCell();
    function addTableRow();
    function addTable();
    function finalizeDocument();
}

/**
 * @package Plugins
 */
class SpaceManager {
    
    private $log;
    private $allocatedAreas = array();

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * Returns the nearest available reference point according to the block
     * positioning properties.
     */
    private function checkIn(PdfBlock $block) {
        
    }
}

/**
 * @package Plugins
 */
class ClientExportPdf extends ExportPlugin {

    private $log;
    private $smarty;

    private $general;
    private $format;
    private $blockTemplate;
    private $blocks = array();

    private $optionalInputs = array('title', 'note', 'scalebar', 'overview');

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    /**
     * Returns export script path.
     */
    public function getExportScriptPath() {
        return 'exportPdf/export.php';
    }

    /**
     * Returns PDF file name.
     */
    public function getFilename() {
        return $this->general->filename;
    }

    /**
     * Returns an array from a comma-separated list string.
     */
    private function getArrayFromList($list, $simple = false) {
        $list = explode(',', $list);
        $res = array();
        foreach ($list as $d) {
            $d = trim($d);
            if ($simple) $res[] = strtolower($d);
            else $res[strtolower($d)] = I18n::gt($d);
        }
        return $res;
    }

    /**
     * Returns an array from a comma-separated list of a ini parameter.
     */
    private function getArrayFromIni($name, $simple = false) {
        $data = $this->getConfig()->$name;
        if (!$data) return array();

        return $this->getArrayFromList($data, $simple);
    }

    /**
     * Updates $target properties with values from $from ones.
     */
    private function overrideProperties($target, $from) {
        foreach (get_object_vars($from) as $key => $val) {
            $target->$key = $val;
        }
    }

    /**
     * Returns value from $_REQUEST or else from default configuration.
     */
    private function getSelectedValue($name, $choices, $request) {
        $name = strtolower($name);
        $reqname = 'pdf' . ucfirst($name);

        if (isset($request[$reqname]) && 
            in_array(strtolower($request[$reqname]), $choices))
            return strtolower($request[$reqname]);

        return strtolower($this->general->{'default' . ucfirst($name)});
    }

    /**
     * Sorts blocks using $property criterium (in ASC order).
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
     * Sets PDF settings objects based on $_REQUEST and configuration data.
     */
    function handleHttpRequest($request) {
        $this->log->debug('processing exportPdf request');

        $ini_array = $this->getConfig()->getIniArray();
        $iniObjects = StructHandler::loadFromArray($ini_array);

        // TODO: check validity of each exportPdf config object???
        if (!isset($iniObjects->general) || !is_object($iniObjects->general))
            throw new CartoclientException('invalid exportPdf configuration');

        // general settings retrieving
        $this->general = new PdfGeneral;
        $this->overrideProperties($this->general, $iniObjects->general);
        
        $this->general->formats = $this->getArrayFromList(
                                      $this->general->formats, true);
        
        $this->general->resolutions = $this->getArrayFromList(
                                          $this->general->resolutions, true);
        
        $this->general->activatedBlocks = $this->getArrayFromList(
                                              $this->general->activatedBlocks, 
                                              true);
        
        $this->general->selectedFormat = $this->getSelectedValue(
                                             'format',
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
        
        // formats settings retrieving
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

        // blocks settings retrieving
        $this->blockTemplate = new PdfBlock;
        $this->overrideProperties($this->blockTemplate, $iniObjects->template);

        foreach ($this->general->activatedBlocks as $id) {
            $pdfItem = 'pdf' . ucfirst($id);
            if (!(isset($request[$pdfItem]) && trim($request[$pdfItem])) &&
                in_array($id, $this->optionalInputs))
                continue;
            
            if (isset($iniObjects->blocks->$id))
                $block = $iniObjects->blocks->$id;
            else
                $block = new stdclass();
                
            $this->blocks[$id] = StructHandler::mergeOverride(
                                     $this->blockTemplate,
                                     $block, true);

            if ($id == 'title' || $id == 'note') {
                $this->blocks[$id]->content = 
                    stripslashes(trim($request[$pdfItem]));
            }
        }

        unset($iniObjects);

        // sorting blocks (order of processing)
        $this->sortBlocksBy('weight');
        $this->sortBlocksBy('zIndex');
        // TODO: handle inNewPage + inLastPages parameters

        $this->log->debug('REQUEST:');
        $this->log->debug($request);
        $this->log->debug('general settings:');
        $this->log->debug($this->general);
        $this->log->debug('format settings:');
        $this->log->debug($this->format);
        $this->log->debug('blocks settings:');
        $this->log->debug($this->blocks);
    }

    function renderForm($template) {
        if (!$template instanceof Smarty) {
            throw new CartoclientException('unknown template type');
        }

        $template->assign('exportPdf', $this->drawUserForm());
    }

    /**
     * Builds PDF settings user interface.
     */
    private function drawUserForm() {
        $this->smarty = new Smarty_CorePlugin($this->getCartoclient()
                                              ->getConfig(), $this);

        $pdfFormat_options = $this->getArrayFromIni('general.formats');
        $pdfFormat_selected = strtolower($this->getConfig()->
                                         {'general.defaultFormat'});
        
        $pdfResolution_options = $this->getArrayFromIni('general.resolutions');
        $pdfResolution_selected = $this->getConfig()->
                                         {'general.defaultResolution'};

        $pdfOrientation = $this->getConfig()->
                                         {'general.defaultOrientation'};

        $blocks = $this->getArrayFromIni('general.activatedBlocks', 
                                                  true);
        
        $this->smarty->assign(array(
                   'exportScriptPath'       => $this->getExportScriptPath(),
                   'pdfFormat_options'      => $pdfFormat_options,
                   'pdfFormat_selected'     => $pdfFormat_selected,
                   'pdfResolution_options'  => $pdfResolution_options,
                   'pdfResolution_selected' => $pdfResolution_selected,
                   'pdfOrientation'         => $pdfOrientation,
                       ));

        foreach ($this->optionalInputs as $input) {
            $this->smarty->assign('pdf' . ucfirst($input),
                                  in_array($input, $blocks));
        }
        
        return $this->smarty->fetch('form.tpl');
    }

    function getConfiguration($isOverview = false) {
        
        $config = new ExportConfiguration();

        if ($isOverview) {
            $renderMap = true;
            $renderScalebar = false;
        } else {
            $renderMap = isset($this->blocks['mainmap']);
            $renderScalebar = isset($this->blocks['scalebar']);
        }
        
        $config->setRenderMap($renderMap);
        $config->setRenderKeymap(false);
        $config->setRenderScalebar($renderScalebar);

        //TODO: set maps dimensions + resolutions
        
        return $config;
    }

    /**
     * Returns the absolute URL of $gfx by prepending CartoServer base URL.
     */
    private function getGfxPath($gfx) {
        //TODO: use local path if direct-access mode is used?
        return $this->cartoclient->getConfig()->cartoserverBaseUrl . $gfx;
    }

    /**
     * Updates Mapserver-generated maps PdfBlocks with data returned by 
     * CartoServer.
     */
    private function updateMapBlock($mapObj, $name, $msName = false) {
        if (!$msName) $msName = $name;

        if (!$mapObj instanceof MapResult ||
            !$mapObj->imagesResult->$msName->isDrawn ||
            !isset($this->blocks[$name]))
            return;

        $map = $mapObj->imagesResult->$msName;
        $block = $this->blocks[$name];

        $block->content = $this->getGfxPath($map->path);
        // TODO: convert pixel sizes into absolute dist units depending on resolution
        $block->width = 100;//$map->width;
        $block->height = 200;//$map->height;
        $block->type = 'image';
    }
    
    function getExport() {

       // Retrieving of data from CartoServer:
       $mapResult = $this->getExportResult($this->getConfiguration());
       
       if (isset($this->blocks['overview'])) {
           $overviewResult = $this->getExportResult(
                                 $this->getConfiguration(true));
       } else {
           $overviewResult = false;
       }

       $this->updateMapBlock($mapResult, 'mainmap');
       $this->updateMapBlock($mapResult, 'scalebar');
       $this->updateMapBlock($overviewResult, 'overview', 'mainmap');
       
       $pdfClass =& $this->general->pdfEngine;
       
       $pdfClassFile = dirname(__FILE__) . '/' . $pdfClass . '.php';
       if (!is_file($pdfClassFile))
           throw new CartoclientException("invalid PDF engine: $pdfClassFile");
       require_once $pdfClassFile;

       $pdf = new $pdfClass($this->general, $this->format);

       $pdf->initializeDocument();

       $pdf->addPage();

       foreach ($this->blocks as $block) {
           switch ($block->type) {
               case 'image':
                   $pdf->addGfxBlock($block);
                   break;
               case 'text':
                   $pdf->addTextBlock($block);
                   break;
               default:
                   // ignores block
               // TODO: handle type = pdf
           }
           
       }

       // TODO: handle blocks to display on other pages

       $contents = $pdf->finalizeDocument();

       $output = new ExportOutput();
       $output->setContents($contents);
       return $output;
    }
}
?>
