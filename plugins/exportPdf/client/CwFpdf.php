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
     * Builds text labels with 90�-increment orientation.
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
     * @var array
     */
    protected $blocks;

    /**
     * @var array
     */
    protected $maxExtent;
    
    /**
     * @var int
     */
    protected $legendLevel;

    /**
     * @var float
     */
    protected $legendShift;
    
    /**
     * Constructor.
     * @param ClientExportPdf
     */
    function __construct(ClientExportPdf $export) {
       $this->log =& LoggerManager::getLogger(__CLASS__);
       $this->general =& $export->getGeneral();
       $this->format =& $export->getFormat();
       $this->blocks =& $export->getBlocks();
       
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
        $this->closePage();
        $this->p->AddPage();
        $this->space->reset();
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
     * Sets text styles (font, underline, bold, italic, color).
     * @param PdfBlock
     */
    private function setTextLayout(PdfBlock $block) {
        $fontStyle = false;
        if ($block->fontBold) $fontStyle .= 'B';
        if ($block->fontItalic) $fontStyle .= 'I';
        if ($block->fontUnderline) $fontStyle .= 'U';
        $this->p->SetFont($block->fontFamily, $fontStyle, $block->fontSize);

        $color = PrintTools::switchColorToRgb($block->color);
        $this->p->SetTextColor($color[0], $color[1], $color[2]);
    }

    /**
     * Sets block container styles (border width and color, background color).
     * @param PdfBlock
     */
    private function setBoxLayout(PdfBlock $block) {
        $this->setLineWidth($block->borderWidth);        
        $this->setDrawColor($block->borderColor);
        $this->setFillColor($block->backgroundColor);
        // borderStyle property not available with FPDF
    }
    
    /**
     * @param PdfBlock text block object
     * @see PdfWriter::addTextBlock()
     */
    function addTextBlock(PdfBlock $block) {
        // text properties
        $this->setTextLayout($block);

        if (!isset($block->width)) {
            $block->width = $this->p->GetStringWidth($block->content);
            $block->width += 2 * $block->padding;
        }

        if (!isset($block->height)) {
            // TODO: dynamically set this value. Possible?
            $block->height = PrintTools::switchDistUnit(20, 'mm',
                                                      $this->general->distUnit); 
            $block->height += 2 * $block->padding;
        }

        if ($block->orientation == 'vertical') {
            list($block->width, $block->height) = 
                array($block->height, $block->width);
        }
        
        $textAlign = $this->getTextAlign($block->textAlign);

        // box properties
        if ($block->borderWidth) {
            $border = 1;
            $this->setBoxLayout($block);
        } else {
            $border = 0;
            $this->setFillColor($block->backgroundColor);
        }

        list($x0, $y0) = $this->space->checkIn($block);

        if ($block->orientation == 'vertical') {
            $rectOpt = $border ? 'DF' : 'F'; 
            $this->p->Rect($x0, $y0, $block->width, $block->height, $rectOpt);
            $this->p->textWithDirection($x0 + $block->width - $block->padding, 
                                        $y0 + $block->height - $block->padding, 
                                        $block->content, 'U');
            list($block->width, $block->height) =
                array($block->height, $block->width);
        } else {
            $this->p->SetXY($x0, $y0);
            $this->p->Cell($block->width, $block->height, $block->content,
                           $border, 0, $textAlign, 1);
        }

        // TODO: handle transparent background
        // TODO: if block height can only be determined precisely after drawing
        // it (see height TODO above), update allocated space in space manager. 
                       
    }

    /**
     * @param PdfBlock graphical (image, PDF) block object
     * @see PdfWriter::addGfxBlock()
     */
    function addGfxBlock(PdfBlock $block) {
        $this->setBoxLayout($block);

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

    /**
     * @param string textual content
     * @param float width
     * @param float height
     * @see PdfWriter::addTableCell()
     */
    private function addTableCell($text, $width, $height) {
        // TODO: handle text alignment
        $x = $this->p->GetX();
        $y = $this->p->GetY();
        $this->p->MultiCell($width, $height, $text, 1, 'C', 1);
        $this->p->SetXY($x + $width, $y);
    }

    /**
     * If there is not enough space to add a table row, adds a new page.
     * @param PdfBlock
     * @param float height of next row
     * @return boolean true if new page added, else false
     */
    private function splitMultiPageTable(PdfBlock $block, $height) {
        // TODO: find a better way to avoid overlapping of potential footer
        // block (what about headers?) than:
        $footerHeight = PrintTools::switchDistUnit(15, 'mm',
                                                   $this->general->distUnit);
        if ($this->p->GetY() + $height + 
            $footerHeight > $this->space->maxY()) {
            $this->addPage();
            $this->setTextLayout($block);
            $this->setBoxLayout($block);
            return true;
        }

        return false;
    }

    /**
     * @param PdfBlock
     * @param TableElement
     * @param array row data
     * @see PdfWriter::addTableRow()
     */
    private function addTableRow(PdfBlock $block, TableElement $table, $row) {
        if (!is_array($row) && !is_object($row))
            $row = array($row);

        $nbLines = 1;
        foreach ($row as $id => $text) {
            $textWidth = $this->p->GetStringWidth($text);
            $cellWidth = $table->colsWidth[$id]; // FIXME: remove margins!
            $nbLines = max($nbLines, ceil($textWidth / $cellWidth));
        }
        $height = $nbLines * $table->rowBaseHeight;

        $this->splitMultiPageTable($block, $height);

        $this->p->SetX($table->x0);
       
        foreach ($row as $id => $text) {
            $this->addTableCell($text, $table->colsWidth[$id], $height);
        }

        $this->p->Ln();
    }

    /**
     * Draws table title row (cf. HTML caption element).
     * @param TableElement
     */
    private function addTableCaption(TableElement $table) {
        $block = $table->caption;
        $this->setTextLayout($block);
        $this->setBoxLayout($block);
        
        if (!isset($block->height))
            $block->height = $table->rowBaseHeight;

        if ($this->splitMultiPageTable($block, $block->height))
            $this->p->SetX($table->x0);

        $textAlign = $this->getTextAlign($block->textAlign);

        $this->p->Cell($block->width, $block->height, $block->content, 1, 0, 
                       $textAlign, 1);
        $this->p->Ln();
    }

    /**
     * Draws table columns headers (columns titles) row.
     * @param TableElement
     */
    private function addTableHeaders(TableElement $table) {
        $block = $table->headers;
        $this->setTextLayout($block);
        $this->setBoxLayout($block);
        $this->addTableRow($block, $table, $block->content);
    }

    /**
     * Sets table caption or headers blocks.
     * @param PdfBlock main table block
     * @param TableElement
     * @param string type of table block (headers|caption) 
     */
    private function setTableMeta(PdfBlock $block, TableElement $table,
                                  $meta) {
        if (isset($this->blocks[$block->$meta])) {
            $subBlock = clone $this->blocks[$block->$meta];
        } else {
            $subBlock = new PdfBlock;
        }

        if ($table->$meta)
            $subBlock->content = $table->$meta;

        return $subBlock;
    }

    /**
     * Computes table columns widths using cells contents widths.
     * @param PdfBlock
     * @param TableElement
     */
    private function setTableWidth(PdfBlock $block, TableElement $table) {
        if ($table->headers->content) {
            $this->setTextLayout($table->headers);
            foreach ($table->headers->content as $id => $header) {
                $table->colsWidth[$id] = $this->p->GetStringWidth($header)
                                         + 2 * $table->headers->padding;
            }
        }

        $this->setTextLayout($block);
        $nbCols = 0;
        foreach ($table->rows as $row) {
            foreach ($row as $id => $cell) {
                $cellWidth = $this->p->GetStringWidth($cell)
                             + 2 * $block->padding;
                if (!isset($table->colsWidth[$id]) || 
                    $cellWidth > $table->colsWidth[$id])
                    $table->colsWidth[$id] = $cellWidth;
            }
            $nbCols = max($nbCols, count($row));
        }

        foreach ($table->colsWidth as $width)
            $table->totalWidth += $width;

        if ($table->caption->content) {
            $this->setTextLayout($table->caption);
            $captionWidth = $this->p->GetStringWidth($table->caption->content)
                            + 2 * $table->caption->padding;
                            
            if (!isset($table->caption->width))
                $table->caption->width = 0;
                
            $table->caption->width = max($table->caption->width, $captionWidth,
                                         $table->totalWidth);
            
            if ($table->caption->width > $table->totalWidth) {
                $delta = $table->caption->width - $table->totalWidth;
                $delta /= $nbCols; // $nbCols cannot be 0
                foreach ($table->colsWidth as $width)
                    $width += $delta;
                $table->totalWidth = $table->caption->width;
            }
        }

        $maxWidth = $this->space->getAvailableSpan($block);

        // if total width is too big
        if ($table->totalWidth > $maxWidth) {       
            $diff = $table->totalWidth - $maxWidth; 
            $colsWidth = $table->colsWidth;
            arsort($colsWidth);

            $cw = array();
            foreach ($colsWidth as $id => $wi) 
                $cw[] = array('id' => $id, 'wi' => $wi);

            if ($cw[0]['wi'] - $cw[1]['wi'] > $diff) {
                $table->colsWidth[$cw[0]['id']] = $cw[0]['wi'] - $diff;
            } else {       
                $mwi = $diff / $nbCols;
                $n = 5; 
                do {       
                    $redfld = array();
                    foreach ($cw as $cinfo) {
                        if ($cinfo['wi'] > $n * $mwi)
                            $redfld[] = $cinfo['id'];
                    }
                    $n--;   
                }       
                while (!count($redfld));
                $mwi = $diff / count($redfld);

                foreach($redfld as $id) 
                    $table->colsWidth[$id] -= $mwi;

                $table->totalWidth = 0;
                foreach($table->colsWidth as $wi)
                    $table->totalWidth += $wi; 
            }
        }
    }
    
    /**
     * @param PdfBlock
     * @see PdfWriter::addTable()
     */
    function addTable(PdfBlock $block) {
        if (!is_array($block->content))
            $block->content = array($block->content);

        if (!isset($block->height)) {
            $block->height = PrintTools::switchDistUnit(10, 'mm',
                                                     $this->general->distUnit);
        }

        $yRef = 0;
        foreach ($block->content as $table) {
            if (!$table instanceof TableElement || !$table->rows)
                continue;

            $table->rowBaseHeight = $block->height;
            
            $table->caption = $this->setTableMeta($block, $table, 'caption');
            $table->headers = $this->setTableMeta($block, $table, 'headers');

            // sets table width according to content
            $this->setTableWidth($block, $table);

            list($table->x0, $table->y0) = 
                $this->space->checkTableIn($block, $table);
            if ($yRef)
                $table->y0 = $yRef;
            
            $this->p->SetXY($table->x0, $table->y0);

            if ($table->caption->content && 
                is_string($table->caption->content)) {
                if (isset($table->caption->height))
                    $table->rowBaseHeight = $table->caption->height;
                $this->addTableCaption($table);
            }
          
            if ($table->headers->content) {
                if (isset($table->headers->height))
                    $table->rowBaseHeight = $table->headers->height;
                $this->addTableHeaders($table);
            }

            $table->rowBaseHeight = $block->height;
            $this->setTextLayout($block);
            $this->setBoxLayout($block);
            
            foreach ($table->rows as $row) {
                $this->addTableRow($block, $table, $row);
            }

            $this->p->Ln();
            $yRef = $this->p->GetY();
        }
    }

    /**
     * Draws a legend element (icon + caption) line.
     * @param PdfBlock legend block
     * @param array legend element data
     */
    private function addLegendItem(PdfBlock $block, $layer) {
        if (!$layer || !is_array($layer))
            return 0;
        
        $xi = $this->p->GetX();
        $yi = $this->p->GetY();
        $shift = $this->legendLevel * $this->legendShift;
       
        $iWidth = 0;
        $icon =& $layer['icon'];
        if ($icon) {
            
            $imageData = @getimagesize($icon);
            if (!$imageData)
                throw new CartoclientException('invalid icon path: ' . $icon);
                
            list($iWidth, $iHeight, $iType) = $imageData;
            
            switch($iType) {
                case 2: $iType = 'JPEG'; break;
                case 3: $iType = 'PNG'; break;
                default:
                    throw new CartoclientException(sprintf(
                        'unsupported format for icon: %s (layer: %s)',
                        $icon, $layer['label']
                        ));
            }

            $iWidth = PrintTools::switchDistUnit($iWidth, 'pt',
                                                 $this->general->distUnit);
            $iHeight = PrintTools::switchDistUnit($iHeight, 'pt',
                                                  $this->general->distUnit);
            
            if ($iHeight > $block->height) {
                $iWidth *= $block->height / $iHeight;
                $iHeight = $block->height;
                $yii = $yi;
            } else {
                $yii = $yi + ($block->height - $iHeight) / 2;
            }
        }

        $cWidth = $block->width - $shift - $iWidth - 3 * $block->padding;
        // 3*padding: one on each side and one between icon and label
        
        if ($cWidth) {
            $textWidth = $this->p->GetStringWidth($layer['label'])
                         + 2 * $block->padding; // simplified!
            $nbLines = ceil($textWidth / $cWidth);
            $cHeight = $nbLines * $block->height;
        } else {
            $cHeight = $block->height;
        }

        // if arriving at bottom of block allowed space
        if ($yi + $cHeight > $this->maxExtent['maxY']) {
            // FIXME: if inNewPage, may overlap footer blocks (page #,...) 
        
            // if "on map" or if not enough room to set a new column
            // stops displaying legend items at bottom of page
            if (!$block->inNewPage || 
                $xi + 2 * $block->width > $this->maxExtent['maxX'])
                return -1;

            // else displays legend items in new column:
            $xi += $block->width;
            $yi = $this->maxExtent['minY'];
            $yii = ($iHeight > $block->height) ? $yi
                   : ($yi + ($block->height - $iHeight) / 2);
        }


        // filling background
        $this->p->Rect($xi, $yi, $block->width, $cHeight, 'F');

        // actually drawing objects:
        if ($icon) {
            $this->p->Image($icon, $xi + $block->padding + $shift, $yii, 
                            $iWidth, $iHeight, $iType);
        }

        if ($cWidth) {
            $padding = ($icon ? 2 : 1) * $block->padding;
            $this->p->SetXY($xi + $shift + $iWidth + $padding, $yi);
            $this->p->MultiCell($cWidth, $block->height,
                                $layer['label'], 0, 'L', 0);
        }

        if ($icon || $cWidth)
            $this->p->SetXY($xi, $yi + $cHeight);

        if ($xi + $block->width > $this->maxExtent['topX'])
            $this->maxExtent['topX'] = $xi + $block->width;
        if ($yi + $cHeight > $this->maxExtent['topY'])
            $this->maxExtent['topY'] = $yi + $cHeight;

        foreach($layer['children'] as $subLayer) {
            $this->legendLevel++;
            if ($this->addLegendItem($block, $subLayer) < 0)
                return -1;
            $this->legendLevel--;
        }

        return 1;
    }

    /**
     * @param PdfBlock
     * @see PdfWriter::addLegend()
     */
    function addLegend(PdfBlock $block) {
        if (!$block->content || !is_array($block->content))
            return;
        
        if (!$block->inNewPage) {
            $block->inFlow = true;
            
            if (isset($this->blocks['overview']))
                $block->width = $this->blocks['overview']->width;
        }
        
        if (!$block->width)
            $block->width = PrintTools::switchDistUnit(50, 'mm',
                                                     $this->general->distUnit);

        // height is legend item height, not whole block one!
        if (!$block->height)
            $block->height = PrintTools::switchDistUnit(5, 'mm',
                                                     $this->general->distUnit);

        $this->setTextLayout($block);
        $this->setBoxLayout($block);
        $this->legendShift = PrintTools::switchDistUnit(5, 'mm',
                                                     $this->general->distUnit);
        
        list($x0, $y0) = $this->space->checkIn($block, true);
        $this->maxExtent['minX'] = $this->maxExtent['topX'] = $x0;
        $this->maxExtent['minY'] = $this->maxExtent['topY'] = $y0;
        
        list($this->maxExtent['maxX'], $this->maxExtent['maxY']) = 
            $this->space->getMaxExtent($block);
        
        $this->p->SetXY($x0, $y0);
        foreach ($block->content as $layer) {
            $this->legendLevel = 0;
            if ($this->addLegendItem($block, $layer) < 0)
                break;
        }
        
        // adds frame
        $this->p->Rect($x0, $y0, $this->maxExtent['topX'] - $x0,
                       $this->maxExtent['topY'] - $y0, 'D');
    }

    /**
     * Performs recurrent actions (blocks displaying...) before current
     * page is closed.
     */
    private function closePage() {
        if ($this->p->PageNo() == 0)
            return;

        // TODO: similar code is used in ClientExportPdf::getExport()
        // => make PdfWriter an abstract class instead of an interface
        // in order to factorize common code and methods.
        foreach ($this->blocks as $block) {
            if (!$block->multiPage)
                continue;
            
            if ($block->id == 'page') {
                $block->content = sprintf('%s %d/{nb}',
                                          I18n::gt('Page'),
                                          $this->p->PageNo());
            }
            
            switch ($block->type) {
                case 'image': $this->addGfxBlock($block); break;
                case 'text': $this->addTextBlock($block); break;
                default: // do nothing
            }
        }
    }

    /**
     * @see PdfWriter::finalizeDocument()
     */
    function finalizeDocument() {
        $this->closePage();
        return $this->p->Output($this->general->filename, 'S');
    }
}
?>
