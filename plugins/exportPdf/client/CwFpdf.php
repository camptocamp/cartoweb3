<?php
/**
 * FPDF-based toolbox for PDF map printing.
 * @package Plugins
 * @author Alexandre Saunier
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
     * @param float reference point x-coord
     * @param float reference point y-coord
     * @param string text to print
     * @param enum('R', 'L', 'U', 'D') direction
     */
    public function textWithDirection($x, $y, $txt, $direction = 'R') {       
        $txt = str_replace(')', '\\)', 
                           str_replace('(', '\\(', 
                                       str_replace('\\', '\\\\', $txt)));
        
        $expr = 'BT %.2f %.2f %.2f %.2f %.2f %.2f Tm (%s) Tj ET';
        
        switch($direction) {
            case 'R':
                $s = sprintf($expr, 1, 0, 0, 1, $x * $this->k, 
                             ($this->h - $y) * $this->k, $txt);
                break;

            case 'L':
                $s = sprintf($expr, -1, 0, 0, -1, $x * $this->k, 
                             ($this->h - $y) * $this->k, $txt);
                break;

            case 'U':
                $s = sprintf($expr, 0, 1, -1, 0, $x * $this->k,
                             ($this->h - $y) * $this->k, $txt);
                break;

            case 'D':
                $s = sprintf($expr, 0, -1, 1, 0, $x * $this->k,
                             ($this->h - $y) * $this->k, $txt);
                break;

            default:
                $s = sprintf('BT %.2f %.2f Td (%s) Tj ET',
                             $x * $this->k, ($this->h - $y) * $this->k, $txt);
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

    /**
     * @var Logger
     */
    private $log;
    
    /**
     * PDF engine object (FPDF)
     * @var cFPDF
     */
    protected $p;
    
    /**
     * @var PdfGeneral
     */
    protected $general;
    
    /**
     * @var PdfFormat
     */
    protected $format;
    
    /**
     * @var SpaceManager
     */
    protected $space;
    
    /**
     * Constructor.
     * @param PdfGeneral
     * @param PdfFormat
     */
    function __construct(PdfGeneral $general, PdfFormat $format) {
       $this->log =& LoggerManager::getLogger(__CLASS__);
       $this->general = $general;
       $this->format = $format;
       
       $this->p = new cFPDF(ucfirst($this->general->selectedOrientation),
                            $this->general->distUnit,
                            ucfirst($this->general->selectedFormat));

       $params = array('width' => $this->p->w,
                       'height' => $this->p->h,
                       'horizontalMargin' => $this->format->horizontalMargin,
                       'verticalMargin' => $this->format->verticalMargin,
                       'YoAtTop' => true);
                       
       $this->space = new SpaceManager($params);
    }

    /**
     * @return float
     * @see PdfWriter::getPageWidth()
     */
    function getPageWidth() {
        return $this->p->w;
    }

    /**
     * @return float
     * @see PdfWriter::getPageHeight()
     */
    function getPageHeight() {
        return $this->p->h;
    }

    /**
     * @see PdfWriter::initializeDocument()
     */
    function initializeDocument() {
        $this->p->SetMargins($this->format->horizontalMargin,
                             $this->format->verticalMargin);
        $this->p->AliasNbPages();
        $this->p->SetAutoPageBreak(false);
    }

    /**
     * @see PdfWriter::addPage()
     */
    function addPage() {
        $this->p->AddPage();
    }

    /**
     * Translates config text-alignement keyword into FPDF one.
     * @param string horizontal alignment keyword
     * @return enum('C', 'R', 'L')
     */
    private function getTextAlign($align) {
        switch (strtolower($align)) {
            case 'center': return 'C';
            case 'right': return 'R';
            case 'left': default: return 'L';
        }
    }

    /**
     * Sets lines color.
     * @param mixed color info (keyword, hex code, RGB array)
     */
    private function setDrawColor($color) {
        $borderColor = PrintTools::switchColorToRgb($color);
        $this->p->SetDrawColor($borderColor[0], $borderColor[1],
                               $borderColor[2]);
    }

    /**
     * Sets filling color (background).
     * @param mixed color info (keyword, hex code, RGB array)
     */
    private function setFillColor($color) {
        $bgColor = PrintTools::switchColorToRgb($color);
        $this->p->SetFillColor($bgColor[0], $bgColor[1], $bgColor[2]);
    }

    /**
     * Sets line width in PdfGeneral dist_unit.
     * @param float
     */
    private function setLineWidth($width) {
        $borderWidth = PrintTools::switchDistUnit($width,
                                                  $this->general->distUnit,
                                                  'mm');
        $this->p->SetLineWidth($borderWidth);
    }

    /**
     * @param PdfBlock text block object
     * @see PdfWriter::addTextBlock()
     */
    function addTextBlock(PdfBlock $block) {
        // text properties
        $fontStyle = false;
        if ($block->fontBold) $fontStyle .= 'B';
        if ($block->fontItalic) $fontStyle .= 'I';
        if ($block->fontUnderline) $fontStyle .= 'U';
        $this->p->SetFont($block->fontFamily, $fontStyle, $block->fontSize);

        $color = PrintTools::switchColorToRgb($block->color);
        $this->p->SetTextColor($color[0], $color[1], $color[2]);
   
        if (!isset($block->width)) {
            $block->width = $this->p->GetStringWidth($block->content);
            $block->width += 2 * $block->padding;
        }

        if (!isset($block->height)) {
            $block->height = 20; // FIXME: dynamically set this value
            $block->height += 2 * $block->padding;
        }

        if ($block->orientation == 'vertical') {
            list($block->width, $block->height) = 
                array($block->height, $block->width);
        }
        
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

        list($x0, $y0) = $this->space->checkIn($block);

        if ($block->orientation == 'vertical') {
            $rectOpt = $border ? 'DF' : 'F'; 
            $this->p->Rect($x0, $y0, $block->width, $block->height, $rectOpt);
            $this->p->textWithDirection($x0 + $block->width - $block->padding, 
                                        $y0 + $block->height - $block->padding, 
                                        $block->content, 'U');
        } else {
            $this->p->SetXY($x0, $y0);
            $this->p->Cell($block->width, $block->height, $block->content,
                           $border, 0, $textAlign, 1);
        }

        // TODO: handle transparent background
        // TODO: if block height can only be determined precisely after drawing
        // it (see FIXME above), update allocated space in space manager. 
                       
    }

    /**
     * @param PdfBlock graphical (image, PDF) block object
     * @see PdfWriter::addGfxBlock()
     */
    function addGfxBlock(PdfBlock $block) {
        $this->setLineWidth($block->borderWidth);        
        $this->setDrawColor($block->borderColor);
        $this->setFillColor($block->backgroundColor);
        // borderStyle property not available with FPDF

        $imageWidth = $block->width;
        $imageHeight = $block->height;
        $shift = $block->borderWidth + $block->padding;

        $block->width += 2 * $shift;
        $block->height += 2 * $shift;
        
        list($x0, $y0) = $this->space->checkIn($block);

        if ($block->padding)
            $this->p->Rect($x0, $y0, $block->width, $block->height, 'DF');
        
        $this->p->Image($block->content, $x0 + $shift, $y0 + $shift, 
                        $imageWidth, $imageHeight);

        if (!$block->padding)
            $this->p->Rect($x0, $y0, $block->width, $block->height, 'D');
    }

    function addTableCell() {}

    function addTableRow() {}
    
    function addTable() {}

    /**
     * @see PdfWriter::finalizeDocument()
     */
    function finalizeDocument() {
        return $this->p->Output($this->general->filename, 'S');
    }
}
