<?php
/**
 * @package Plugins
 * @version $Id$
 */

define('FPDF_FONTPATH', CARTOCLIENT_HOME . 'include/fpdf/font/');
require_once 'fpdf/fpdf.php';

/**
 * Customized version of FPDF.
 * @package Plugins
 */
class cFPDF extends FPDF {

    /**
     * Builds text labels with 90°-increment orientation.
     * See http://fpdf.org/fr/script/script31.php
     */
    private function textWithDirection($x, $y, $txt, $direction = 'R') {       
        $txt = str_replace(')', '\\)', 
                           str_replace('(', '\\(', 
                                       str_replace('\\', '\\\\', $txt)));
        
        $expr = 'BT %.2f %.2f %.2f %.2f %.2f %.2f Tm (%s) Tj ET';
        
        switch($direction) {
            case 'R':
                $s = sprintf($expr, 1, 0, 0, 1, $x * $this->k, 
                             $this->h->$y * $this->k, $txt);
                break;

            case 'L':
                $s = sprintf($expr, -1, 0, 0, -1, $x * $this->k, 
                             $this->h->$y * $this->k, $txt);
                break;

            case 'U':
                $s = sprintf($expr, 0, 1, -1, 0, $x * $this->k,
                             $this->h->$y * $this->k, $txt);
                break;

            case 'D':
                $s = sprintf($expr, 0, -1, 1, 0, $x * $this->k,
                             $this->h->$y * $this->k, $txt);
                break;

            default:
                $s = sprintf('BT %.2f %.2f Td (%s) Tj ET',
                             $x * $this->k, $this->h->$y * $this->k, $txt);
        }
        
        if ($this->ColorFlag)
            $s = 'q ' . $this->TextColor . ' ' . $s . ' Q';
        $this->_out($s);
    }
}

/**
 * FPDF implementation of PdfWriter.
 * @package Plugins
 */
 class CwFpdf implements PdfWriter {

    private $log;
    protected $p;
    protected $general;
    protected $format;
    
    function __construct(PdfGeneral $general, PdfFormat $format) {
       $this->log =& LoggerManager::getLogger(__CLASS__);
       $this->general = $general;
       $this->format = $format;
       
       $this->p = new cFPDF(ucfirst($this->general->selectedOrientation),
                            $this->general->distUnit,
                            ucfirst($this->general->selectedFormat));
    }

    function initializeDocument() {
        $this->p->SetMargins($this->format->horizontalMargin,
                             $this->format->verticalMargin);
        $this->p->AliasNbPages();
    }

    function addPage() {
        $this->p->AddPage();
    }

    function addTextBlock() {}

    function addGfxBlock() {}

    function addTableCell() {}

    function addTableRow() {}
    
    function addTable() {}

    function finalizeDocument() {
        return $this->p->Output($this->general->filename, 'S');
    }
}
