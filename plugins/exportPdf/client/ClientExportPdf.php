<?php
/**
 * @package Plugins
 * @version $Id$
 */

require_once(CARTOCLIENT_HOME . 'client/ExportPlugin.php');

/**
 * @package Plugins
 */
class PdfGeneral {
    public $pdfEngine          = 'pdflib_lite';
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
}

/**
 * @package Plugins
 */
//FIXME: use interface ?
abstract class PdfWriter {
    protected $currentPage;
    protected $isPageOpen;

    function __construct() {}

    abstract function initializeDocument() {}
    abstract function addPage() {}
    abstract function addTextBlock() {}
    abstract function addImageBlock() {}
    abstract function addTableCell() {}
    abstract function addTableRow() {}
    abstract function addTable() {}
    abstract function finalizeDocument() {}
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

    public function getExportScriptPath() {
        return 'exportPdf/export.php';
    }
    
    public function getBaseUrl() {
        return '../';
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

    private function getSelectedValue($name, $choices, $request) {
        $name = strtolower($name);
        $req = strtolower($request['pdf' . ucfirst($name)]);

        if (isset($req) && in_array($req, $choices))
            return $req;

        return strtolower($this->general->{'default' . ucfirst($name)});
    }

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

    function getConfiguration() {
        
        $config = new ExportConfiguration();

        $renderMainmap = isset($this->blocks['mainmap']);
        $renderOverview = isset($this->blocks['overview']);
        $renderScalebar = isset($this->blocks['scalebar']);
        
        $config->setRenderMap($renderMainmap);
        $config->setRenderKeymap($renderOverview); // FIXME: overview = keymap?
        $config->setRenderScalebar($renderScalebar);

        //TODO: set maps dimensions + resolutions
        
        return $config;
    }
    
    function export($mapRequest, $mapResult) {
       $pdfClass = $this->general->pdfEngine;
       
       //TODO: fix path
       $pdfClassFile = CARTOCLIENT_HOME . 'plugins/exportPdf/client/' . 
                       $pdfClass . '.php';
       
       if (!is_file($pdfClassFile)) {
           throw new CartoclientException("invalid PDF engine: $pdfClas");
       }
           
       require_once $pdfClassFile;

       $pdf = new $pdfClass;

       $pdf->initializeDocument();

       // PDF processing comes here

       $contents = $pdf->finalizeDoucment();

       $output = new ExportOutput();
       $output->setContents($contents);
       return $output;
    }
}
?>
