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
     * "TextWithRotation" is available at the same location as well.
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

    /**
     * Translates config text-alignement keyword into FPDF one.
     */
    private function getTextAlign($align) {
        switch (strtolower($align)) {
            case 'center': return 'C';
            case 'right': return 'R';
            case 'left': default: return 'L';
        }
    }

    private function setDrawColor($color) {
        $borderColor = PrintTools::switchColorToRgb($color);
        $this->p->SetDrawColor($borderColor[0], $borderColor[1],
                               $borderColor[2]);
    }

    private function setFillColor($color) {
        $bgColor = PrintTools::switchColorToRgb($color);
        $this->p->SetFillColor($bgColor[0], $bgColor[1], $bgColor[2]);
    }

    private function setLineWidth($width) {
        $borderWidth = PrintTools::switchDistUnit($width,
                                                  $this->general->distUnit,
                                                  'mm');
        $this->p->SetLineWidth($borderWidth);
    }

    function addTextBlock(PdfBlock $block) {
        // text properties
        $fontStyle = false;
        if ($block->fontBold) $fontStyle .= 'B';
        if ($block->fontItalic) $fontStyle .= 'I';
        if ($block->fontUnderline) $fontStyle .= 'U';
        $this->p->SetFont($block->fontFamily, $fontStyle, $block->fontSize);

        $color = PrintTools::switchColorToRgb($block->color);
        $this->p->SetTextColor($color[0], $color[1], $color[2]);
    
        $wt = $this->p->GetStringWidth($block->content);

        $textAlign = $this->getTextAlign($block->textAlign);

        // box properties
        $this->setFillColor($block->backgroundColor);
        
        if ($block->borderWidth) {
            $border = 1;
            $this->setLineWidth($block->borderWidth);
            $this->setDrawColor($block->borderColor);
            // borderStyle property not available with FPDF
        } else {
            $border = 0;
        }

        $this->p->SetXY(0, 0);
        $this->p->Cell($wt, 30, $block->content, $border, 0, $textAlign, 1);
        // TODO: handle transparent background
                       
    }

    function addGfxBlock(PdfBlock $block) {
        $this->setLineWidth($block->borderWidth);        
        $this->setDrawColor($block->borderColor);
        $this->setFillColor($block->backgroundColor);
        // borderStyle property not available with FPDF
        
        $this->p->Rect(0, 0, $block->width, $block->height, 'DF');
        $this->p->Image($block->content, 0, 0, $block->width, $block->height);
        // FIXME: image completely overlap containing box...
    }

    function addTableCell() {}

    function addTableRow() {}
    
    function addTable() {}

    function finalizeDocument() {
        return $this->p->Output($this->general->filename, 'S');
    }
}
