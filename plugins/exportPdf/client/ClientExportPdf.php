<?php
/**
 * @package Plugins
 * @author Alexandre Saunier
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
     * @param float distance to convert
     * @param string initial unit: in, pt, cm, mm
     * @param string final unit
     * @return float converted distance
     */
    static function switchDistUnit($dist, $from, $to) {
        if ($from == $to) return $dist;
        
        $ratio = 1;
        
        if ($from == 'cm') {
            $ratio = self::switchDistUnit(10, 'mm', $to);
            $from = 'mm';
        }
        elseif ($to == 'cm') {
            $ratio = self::switchDistUnit(0.1, $from, 'mm');
            $to = 'mm';
        }

        if ($from == 'in') {
            $ratio = self::switchDistUnit(72, 'pt', $to);
            $from = 'pt';
        }
        elseif ($to == 'in') {
            $ratio = self::switchDistUnit(1 / 72, $from, 'pt');
            $to = 'pt';
        }

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
     * @param string hexadecimal color code
     * @return array array of RGB codes
     */
    static function switchHexColorToRgb($color) {
        return array(hexdec(substr($color, 1, 2)), 
                     hexdec(substr($color, 3, 2)), 
                     hexdec(substr($color, 5, 2))
                     );
    }

    /**
     * Converts passed color in RGB codes.
     * @param mixed
     * @return array array of RGB codes
     */
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

    /**
     * Returns the PDF writeable directory path (creates it if none).
     * @return string
     */
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
 * General configuration data for PDF generation.
 * @package Plugins
 */
class PdfGeneral {
    
    /**
     * Name of PDF Engine class
     * @var string
     */
    public $pdfEngine          = 'PdfLibLite';
    
    /**
     * @var string
     */
    public $pdfVersion         = '1.3';
    
    /**
     * @var string
     */
    public $distUnit           = 'mm';
    
    /**
     * @var float
     */
    public $horizontalMargin   = 10;
    
    /**
     * @var float
     */
    public $verticalMargin     = 10;
    
    /**
     * @var float
     */
    public $width;
    
    /**
     * @var float
     */
    public $height;
    
    /**
     * @var array
     */
    public $formats;
    
    /**
     * @var string
     */
    public $defaultFormat;
    
    /**
     * @var string
     */
    public $selectedFormat;
    
    /**
     * @var array
     */
    public $resolutions        = array(96);
    
    /**
     * @var string
     */
    public $defaultResolution  = 96;
    
    /**
     * @var string
     */
    public $selectedResolution;
    
    /**
     * @var string
     */
    public $defaultOrientation = 'portrait';
    
    /**
     * @var string
     */
    public $selectedOrientation;
    
    /**
     * @var array
     */
    public $activatedBlocks;
    
    /**
     * @var boolean
     */
    public $allowPdfInput      = false;
    
    /**
     * @var string
     */
    public $filename           = 'map.pdf';
}

/**
 * Format description for PDF generation.
 * @package Plugins
 */
class PdfFormat {

    /**
     * @var string
     */
    public $label;
    
    /**
     * @var float
     */
    public $bigDimension;
    
    /**
     * @var float
     */
    public $smallDimension;
    
    /**
     * @var float
     */
    public $horizontalMargin;
    
    /**
     * @var float
     */
    public $verticalMargin;
    
    /**
     * @var float
     */
    public $maxResolution;
}

/**
 * Block (basic element) description for PDF generation.
 * @package Plugins
 */
class PdfBlock {

    /**
     * @var string
     */
    public $id;
    
    /**
     * @var text
     */
    public $type;
    
    /**
     * @var string
     */
    public $content          = '';

    /**
     * @var string
     */
    public $fontFamily       = 'times';
    
    /**
     * @var float
     */
    public $fontSize         = 12; // pt
    
    /**
     * @var boolean
     */
    public $fontItalic       = false;
    
    /**
     * @var boolean
     */
    public $fontBold         = false;
    
    /**
     * @var boolean
     */
    public $fontUnderline    = false;
    
    /**
     * @var string
     */
    public $color            = 'black';
    
    /**
     * @var string
     */
    public $backgroundColor  = 'white';
    
    /**
     * @var float
     */
    public $borderWidth      = 1;
    
    /**
     * @var string
     */
    public $borderColor      = 'black';
    
    /**
     * @var string
     */
    public $borderStyle      = 'solid';
    
    /**
     * @var float
     */
    public $padding          = 0;
    
    /**
     * @var float
     */
    public $horizontalMargin = 0;
    
    /**
     * @var float
     */
    public $verticalMargin   = 0;
    
    /**
     * @var string
     */
    public $horizontalBasis  = 'left';
    
    /**
     * @var string
     */
    public $verticalBasis    = 'top';
    
    /**
     * @var boolean
     */
    public $hCentered        = false;
    
    /**
     * @var boolean
     */
    public $vCentered        = false;
    
    /**
     * @var string
     */
    public $textAlign        = 'center';
    
    /**
     * @var string
     */
    public $verticalAlign    = 'center';
    
    /**
     * @var string
     */
    public $orientation      = 'horizontal';
    
    /**
     * @var int
     */
    public $zIndex           = 1;
    
    /**
     * @var int
     */
    public $weight           = 50;
    
    /**
     * @var boolean
     */
    public $inNewPage        = false;
    
    /**
     * @var boolean
     */
    public $inLastPages      = false;
    
    /**
     * @var float
     */
    public $width;
    
    /**
     * @var float
     */
    public $height;
    
    /**
     * @var boolean
     */
    public $singleUsage      = true;
    
    /**
     * @var string
     */
    public $parent;
    
    /**
     * @var boolean
     */
    public $inFlow           = true;

    /**
     * Id of caption block (mainly for tables)
     * @var string
     */
     public $caption         = '';

     /**
      * Id of headers block (mainly for tables)
      * @var string
      */
      public $headers        = '';

      /**
       * @var boolean
       */
       public $standalone    = true;
}

/**
 * Description of tabular blocks.
 * @package Plugins
 */
class TableElement {

    public $caption = '';
    public $headers = array();
    public $rows    = array();
    public $totalWidth = 0;
    public $colsWidth = array();
    public $x0;
    public $y0;
}

/**
 * Interface for PDF generators tools.
 * @package Plugins
 */
interface PdfWriter {

    /**
     * Sets general data and opens PDF document.
     */
    function initializeDocument();
   
    /**
     * Returns page width in PdfGeneral dist_unit.
     * @return float
     */
    function getPageWidth();

    /**
     * Returns page height in PdfGeneral dist_unit.
     * @return float
     */
    function getPageHeight();
    
    /**
     * Adds a new page to PDF document.
     */
    function addPage();
    
    /**
     * Adds a block with textual content.
     * @param PdfBlock
     */
    function addTextBlock(PdfBlock $block);
    
    /**
     * Adds a block with graphical (image, PDF...) content.
     * @param PdfBlock
     */
    function addGfxBlock(PdfBlock $block);
    
    function addTableCell($text, $width, $height);
    
    function addTableRow(TableElement $table, $row);
    
    function addTable(PdfBlock $block);
    
    /**
     * Performs final PDF operations and outputs document.
     */
    function finalizeDocument();
}

/**
 * Handles positioning of blocks in the PDF document.
 * @package Plugins
 */
class SpaceManager {
    
    /**
     * @var Logger
     */
    private $log;
    
    /**
     * @var float
     */
    private $minX;
    
    /**
     * @var float
     */
    private $maxX;
    
    /**
     * @var float
     */
    private $minY;
    
    /**
     * @var float
     */
    private $maxY;
    
    /**
     * Indicates if the Y-origin is at top of page.
     * @var boolean
     */
    private $YoAtTop = true;

    /**
     * @var array
     */
    private $allocated = array();
    
    /**
     * @var array
     */
    private $levels = array();

    /**
     * Constructor.
     * @param array contains page max extent + Y-origin location.
     */
    function __construct($params) {
        $this->log =& LoggerManager::getLogger(__CLASS__);

        $this->minX = $params['horizontalMargin'];
        $this->minY = $params['verticalMargin'];
        $this->maxX = $params['width'] - $params['horizontalMargin'];
        $this->maxY = $params['height'] - $params['verticalMargin'];
        $this->YoAtTop = $params['YoAtTop'];

        if ($this->minX > $this->maxX || $this->minY > $this->maxY)
            throw new CartoclientException('Invalid SpaceManager params');
    }

    /**
     * Resets allocated spaces.
     */
    public function reset() {
        $this->allocated = array();
        $this->levels = array();
    }
    
    /**
     * Records newly added areas in allocated space list.
     * @param PdfBlock
     * @param float X-coord of block reference point
     * @param float Y-coord of block reference point
     * @return array (X,Y)
     */
    private function allocateArea(PdfBlock $block, $x, $y) {
        if (!isset($this->allocated[$block->zIndex]))
            $this->allocated[$block->zIndex] = array();

        $this->allocated[$block->zIndex][$block->id] = 
            array('minX' => $x,
                  'minY' => $y,
                  'maxX' => $x + $block->width,
                  'maxY' => $y + $block->height);

        $this->levels[$block->id] = $block->zIndex;

        return array($x, $y);
    }

    /**
     * Computes the block reference point Y-coordinate.
     * @param PdfBlock
     * @param float extent minimal Y-coord
     * @param float extent maximal Y-coord
     * @return float
     */
    private function getY(PdfBlock $block, $minY, $maxY) {
        if ($block->verticalBasis == 'top') {
            // reference is page top border
            
            if ($this->YoAtTop) {
                // y = 0 at top of page and 
                // reference point is box top left corner
                $y = $minY + $block->verticalMargin;
            } else {
                // y = 0 at bottom of page and 
                // reference point is box bottom left corner
                $y = $maxY - $block->verticalMargin -
                      $block->height;
            }
        } else {
            // reference is page bottom border
            if ($this->YoAtTop) {
                $y = $maxY - $block->verticalMargin -
                      $block->height;
            } else {
                $y = $minY + $block->verticalMargin;
            }
        }
       
        return $y;
    }

    /**
     * Computes the block reference point X-coordinate.
     * @param PdfBlock
     * @param float extent minimal X-coord
     * @param float extent maximal X-coord
     * @return float
     */
    private function getX(PdfBlock $block, $minX, $maxX) {
        if ($block->horizontalBasis == 'left') {
            $x = $minX + $block->horizontalMargin;
        } else {
            $x = $maxX - $block->horizontalMargin - $block->width;
        }

        return $x;
    }

    /**
     * Returns the min and max coordinates of given block. If name is invalid,
     * returns the maximal allowed extent.
     * @param string block name
     * @return array
     */
    private function getBlockExtent($name) {
        if (!isset($this->levels[$name]))
            return array('minX' => $this->minX, 'minY' => $this->minY,
                         'maxX' => $this->maxX, 'maxY' => $this->maxY);

        $zIndex = $this->levels[$name];
        return $this->allocated[$zIndex][$name];
    }

    /**
     * Returns the nearest available reference point (min X, min Y)
     * according to the block positioning properties.
     * @param PdfBlock
     * @return array (X,Y) of reference point
     */
    public function checkIn(PdfBlock $block, $isTable = false) {
        // TODO: handle block with no initially known dimensions (legend...)
        // TODO: handle blocks too high to fit below previous block and
        // that must be displayed with a X shift etc.
        // TODO: handle more evoluted inter-block positioning than "inFlow"?
        // TODO: take into account parent-block border-width in block 
        // positioning: must be shifted of a border-width value in X and Y.

        // if block must be displayed right below previous block
        if ($block->inFlow && isset($this->allocated[$block->zIndex])) {
            $elders = array_keys($this->allocated[$block->zIndex]);

            if($elders) {
                $refBlock = array_pop($elders);
                $extent = $this->getBlockExtent($refBlock);
                
                $x0 = $extent['minX'];
                $y0 = ($this->YoAtTop) ? $extent['maxY'] 
                      : $extent['minY'] - $block->height;
                      
                return $this->allocateArea($block, $x0, $y0);
            }
        }
        
        // if parent specified, block is embedded in it.
        if (isset($block->parent)) {
            $extent = $this->getBlockExtent($block->parent);
            $minX = $extent['minX'];
            $minY = $extent['minY'];
            $maxX = $extent['maxX'];
            $maxY = $extent['maxY'];
        } else {
            $minX = $this->minX;
            $minY = $this->minY;
            $maxX = $this->maxX;
            $maxY = $this->maxY;
        }

        // hCentered : block is horizontally centered, no matter if there are
        // already others block at the same zIndex...
        if ($block->hCentered) {
            $x0 = ($maxX + $minX - $block->width) / 2;
        } else {
            $x0 = $this->getX($block, $minX, $maxX);
        }
      
        // vCentered : same than hCentered in Y axis
        if ($block->vCentered) {
            $y0 = ($maxY + $minY - $block->height) / 2;
        } else {
            $y0 = $this->getY($block, $minY, $maxY);
        }
        
        if ($isTable)
            return array($x0, $y0);

        return $this->allocateArea($block, $x0, $y0);
    }

    public function checkTableIn(PdfBlock $block, TableElement $table) {
        $tableBlock = clone $block;
        $tableBlock->width = $table->totalWidth;
        $tableBlock->inFlow = false;
        $tableBlock->verticalBasis = 'top';
        return $this->checkIn($tableBlock, true);
        // FIXME: Pdf Engines with YoAtTop = false will return incorrect Y!
    }

    public function getAvailableSpan(PdfBlock $block) {
        if (isset($block->parent)) {
            $extent = $this->getBlockExtent($block->parent);
            return $extent['maxX'] - $extent['minX'];
        }
        
        return $this->maxX - $this->minX; 
    }
}

/**
 * Overall class for PDF generation management.
 * @package Plugins
 */
class ClientExportPdf extends ExportPlugin {

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
                                    'queryResult');
    //TODO: display queryResult form option only ifavailable in MapResult

    /**
     * Constructor
     */
    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    /**
     * @return PdfGeneral
     */
    public function getGeneral() {
        return $this->general;
    }

    /**
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
     * Returns export script path.
     * @return string
     */
    public function getExportScriptPath() {
        return 'exportPdf/export.php';
    }

    /**
     * Returns PDF file name.
     * @return string
     */
    public function getFilename() {
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

        return $this->general->{'default' . ucfirst($name)};
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

        $this->blocks[$id]->id = $id;

        if ($id == 'title' || $id == 'note') {
            $this->blocks[$id]->content = 
                stripslashes(trim($request[$pdfItem]));
        }

        if ($this->blocks[$id]->caption && 
            !in_array($this->blocks[$id]->caption, $this->blocks)) {
            
            $caption = $this->blocks[$id]->caption;
            $this->createBlock($request, $iniObjects, $caption);
            
            $this->blocks[$caption]->standalone = false;
        }

        if ($this->blocks[$id]->headers &&
            !in_array($this->blocks[$id]->headers, $this->blocks)) {
            
            $headers = $this->blocks[$id]->headers;
            $this->createBlock($request, $iniObjects, $headers);
            
            $this->blocks[$headers]->standalone = false;
            $this->blocks[$headers]->content = 
               $this->getArrayFromList($this->blocks[$headers]->content, true);
        }

        if ($this->blocks[$id]->type == 'table') {
            // TODO: handle multi-row tables. For now we are limited to 
            // one single row.
            $this->blocks[$id]->content = $this->getArrayFromList(
                                            $this->blocks[$id]->content, true);
        }
    }

    /**
     * Sets PDF settings objects based on $_REQUEST and configuration data.
     * @param array $_REQUEST
     * @see GuiProvider::handleHttpPostRequest()
     */
    function handleHttpPostRequest($request) {
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
            $this->createBlock($request, $iniObjects, $id);
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

    /**
     * Not used/implemented yet.
     * @see GuiProvider::handleHttpGetRequest()
     */
    function handleHttpGetRequest($request) {}

    /**
     * @see GuiProvider::renderForm()
     * @param Smarty
     */
    function renderForm($template) {
        if (!$template instanceof Smarty) {
            throw new CartoclientException('unknown template type');
        }

        $template->assign('exportPdf', $this->drawUserForm());
    }

    /**
     * Builds PDF settings user interface.
     * @return string Smarty fetch result
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

    /**
     * Returns given distance at selected printing resolution.
     * @param float distance in PdfGeneral dist_unit
     * @return int distance in pixels
     */
    private function getDistWithRes($dist) {
        $dist = PrintTools::switchDistUnit($dist,
                                           $this->general->distUnit, 
                                           'in');
        return round($dist * $this->general->selectedResolution);
    }

    /**
     * Builds export configuration.
     * @param boolean true if configuring to get overview map
     * @return ExportConfiguration
     */
    function getConfiguration($isOverview = false) {
        
        $config = new ExportConfiguration();

        if ($isOverview) {
            $renderMap = true;
            $renderScalebar = false;
        } else {
            $renderMap = isset($this->blocks['mainmap']);
            $renderScalebar = isset($this->blocks['scalebar']);

            if ($renderMap) {
                $mainmap = $this->blocks['mainmap'];
                
                $mapWidth = $this->getDistWithRes($mainmap->width);
                
                $config->setMapWidth($mapWidth);
                
                $mapHeight = $this->getDistWithRes($mainmap->height);
                $config->setMapHeight($mapHeight);
            }
        }
        
        $config->setRenderMap($renderMap);
        $config->setRenderKeymap(false);
        $config->setRenderScalebar($renderScalebar);

        $this->log->debug('Selected resolution: ' .
                          $this->general->selectedResolution);
        $this->log->debug('Print config:');
        $this->log->debug($config);

        //TODO: set maps dimensions + resolutions for scalebar and overview
        
        return $config;
    }

    /**
     * Returns the absolute URL of $gfx by prepending CartoServer base URL.
     * @param string
     * @return string
     */
    private function getGfxPath($gfx) {
        //TODO: use local path if direct-access mode is used?
        return $this->cartoclient->getConfig()->cartoserverBaseUrl . $gfx;
    }

    /**
     * Updates Mapserver-generated maps PdfBlocks with data returned by 
     * CartoServer.
     * @param MapResult
     * @param string name of PDF block to update
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
    }

    /**
     * Sets mainmap dimensions according to selected format and orientation.
     * @param PdfWriter
     */
    private function setMainMapDim(PdfWriter $pdf) {
        $mainmap = $this->blocks['mainmap'];
        
        if (!isset($mainmap->width)) {
            $hmargin = $this->format->horizontalMargin 
                       + $mainmap->horizontalMargin;
            $mainmap->width = $pdf->getPageWidth() - 2 * $hmargin; 
        }
        
        if (!isset($mainmap->height)) {
            $vmargin = $this->format->verticalMargin
                       + $mainmap->verticalMargin;
            $mainmap->height = $pdf->getPageHeight() - 2 * $vmargin;
        }
    }

    /**
     * Transforms query results from MapResult into TableElements
     * @param MapResult
     * @return array array of TableElement
     */
    private function getQueryResult($mapResult) {
        if (!$mapResult instanceof MapResult || 
            !isset($mapResult->queryResult))
            return array();

        $results = array();
        foreach ($mapResult->queryResult->layerResults as $layer) {
            if (!$layer->numResults)
                continue;

            $table = new TableElement;
            
            $table->caption = I18n::gt($layer->layerId);
            
            $table->headers = array('Id');
            foreach ($layer->fields as $field)
                $table->headers[] = I18n::gt($field);

            foreach ($layer->resultElements as $res) {
                $row = array($res->id);
                foreach ($res->values as $val)
                    $row[] = $val;
                $table->rows[] = $row;
            }

            $results[] = $table;
        }
    
        return $results;
    }
    
    /**
     * @see ExportPlugin::getExport()
     * @return ExportOutput export result
     */
    function getExport() {

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
            $overviewResult = $this->getExportResult(
                                  $this->getConfiguration(true));
        } else {
            $overviewResult = false;
        }

        $this->updateMapBlock($mapResult, 'mainmap');
        $this->updateMapBlock($mapResult, 'scalebar');
        $this->updateMapBlock($overviewResult, 'overview', 'mainmap');
        
        $pdf->initializeDocument();
 
        $pdf->addPage();
 
        foreach ($this->blocks as $block) {
            if ($block->inNewPage || !$block->standalone)
                continue;

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

        // query results displaying
        if (isset($this->blocks['queryResult'])) {
            $queryResult = $this->getQueryResult($mapResult);        
            if ($queryResult) {
                $this->blocks['queryResult']->content = $queryResult;
                
                $pdf->addPage();
                $pdf->addTable($this->blocks['queryResult']);
            }
        }
        
        if (isset($this->blocks['table'])) {
            require_once(dirname(__FILE__) . '/table.php');
            $tableObj = new TableElement;
            $tableObj->rows = $table;
            $this->blocks['table']->content = $tableObj;

            $pdf->addPage();
            $pdf->addTable($this->blocks['table']);
        }
 
        $contents = $pdf->finalizeDocument();
 
        $output = new ExportOutput();
        $output->setContents($contents);
        return $output;
    }
}
?>
