<?php
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * PDFLib Lite implementation of PdfWriter
 * @package Plugins
 */
 class PdfLibLite implements PdfWriter {

    private $log;
    protected $p;
    protected $general;
    protected $format;
    protected $space;

    protected $pageWidth;
    protected $pageHeight;
    protected $isPageOpen = false;
    protected $images = array();

    function __construct(PdfGeneral $general, PdfFormat $format) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->general = $general;
        $this->format = $format;

        if ($this->general->selectedOrientation == 'portrait') {
            $this->pageWidth = $this->getInPt($this->format->smallDimension);
            $this->pageHeight = $this->getInPt($this->format->bigDimension);
        } else {
            $this->pageWidth = $this->getInPt($this->format->bigDimension);
            $this->pageHeight = $this->getInPt($this->format->smallDimension);
        }
        
        try {
            $this->p = new PDFlib();
        }
        catch (Exception $e) {
            $this->getException($e);
        }

        $params = array('width' => $this->pageWidth,
                        'height' => $this->pageHeight,
                        'horizontalMargin' => $this->format->horizontalMargin,
                        'verticalMargin' => $this->format->verticalMargin,
                        'YoAtTop' => false); // y = 0 at page bottom

        $this->space = new SpaceManager($params);
    }

    /**
     * Shortcut for distance units converter.
     */
    private function getInPt($dist) {
        return PrintTools::switchDistUnit($dist,
                                          $this->general->distUnit,
                                          'pt');
    }

    /**
     * Returns caught PDFLib exceptions.
     */
    private function getException(Exception $e) {
        if ($e instanceof PDFlibException) {
            throw new CartoclientException(
                sprintf("PDFLib exception occured:\n[%d] %s: %s",
                        $e->get_errnum(),
                        $e->get_apiname(),
                        $e->get_errmsg()));
        } else {
            print '<pre>'; print_r($e); print '</pre>';
            throw new CartoclientException('Unknown PDFLib exception occured');
        }
    }

    function initializeDocument() {
        try {
            $optlist = 'compatibility ' . $this->general->pdfVersion;
            $this->p->begin_document(false, $optlist);
        }
        catch (Exception $e) {
            $this->getException($e);
        }
    }

    /**
     * Adds a blank page to current PDF document.
     */
    function addPage() {
        //$optlist = 'topdown true';
        $optlist = false;
        
        try {   
            if ($this->isPageOpen)
                $this->p->end_page_ext(false);

            $this->p->begin_page_ext($this->pageWidth, $this->pageHeight, $optlist);
            $this->isPageOpen = true;
        }
        catch (Exception $e) {
            $this->getException($e);
        }
    }
    
    /**
     * Sets line dash pattern.
     */
    private function setDash($style) {
        switch ($style) {
            case 'dashed':
                $b = $w = 5;
                break;

            case 'dotted':
                $b = 1;
                $w = 4;
                break;

            case 'solid':default:
                $b = $w = 0;
        }
        
        try {
            $this->p->setdash($b, $w);
        }
        catch (Exception $e) {
            $this->getException($e);
        }
    }

    private function setStrokeColor($color) {
        $borderColor = PrintTools::switchColorToRgb($color);
        try {
            $this->p->setcolor('stroke', 'rgb', $borderColor[0] / 255,
                               $borderColor[1] / 255, $borderColor[2] / 255,
                               0);
        }
        catch (Exception $e) {
            $this->getException($e);
        }
    }

    private function setFillColor($color) {
        $bgColor = PrintTools::switchColorToRgb($color);
        try {
            $this->p->setcolor('fill', 'rgb', $bgColor[0] / 255, 
                               $bgColor[1] / 255, $bgColor[2] / 255, 0);
        }
        catch (Exception $e) {
            $this->getException($e);
        }
    }
    
    /**
     * Draws a rectangle borders and fills it.
     */
    private function drawBox(PdfBlock $block) {
        try {
            $this->p->save();
            
            $this->p->setlinewidth($this->getInPt($block->borderWidth));
            $this->setDash($block->borderStyle);
            $this->setStrokeColor($block->borderColor);
            $this->setFillColor($block->backgroundColor);
            
            $this->p->rect(100, 400,
                           $this->getInPt($block->width), 
                           $this->getInPt($block->height));
            $this->p->fill_stroke();
            
            $this->p->restore();
        }
        catch (Exception $e) {
            $this->getException($e);
        }
    }

    private function getTextAlign(PdfBlock $block) {
        
        switch (strtolower($block->textAlign)) {
            case 'center': $h = 50; break;
            case 'right': $h = 100; break;
            case 'left': default: $h = 0;
        }
        
        // TODO: check if vertical position evolution is not influenced by
        // the general position evolution (y increase from top to bottom).
        switch (strtolower($block->verticalAlign)) {
            case 'center': $v = 50; break;
            case 'bottom': $v = 0; break;
            case 'top': default: $v = 100;
        }

        if ($v == $h)
            return $v;
        
        return sprintf('{%d %d}', $h, $v); 
    }

    private function setFont(PdfBlock $block) {
        try {
            /*$fontStyle = false;
            if ($block->fontBold) $fontStyle .= 'bold';
            if ($block->fontItalic) $fontStyle .= 'italic';
            if (!$fontStyle) $fontStyle = 'normal';*/
            // TODO: handle font styles
            // => style appears directly in font name eg. Times-Italic 

            $optlist = false;
            $font = $this->p->load_font($block->fontFamily, 'host', $optlist);

            $this->p->setfont($font, $block->fontSize);

            return $font;
        }
        catch (Exception $e) {
            $this->getException($e);
        }        
    }

    function addTextBlock(PdfBlock $block) {
        // Note: *_textflow() methods are not available with PDFLib Lite 
        // version. Overload this method in an extended class to use them. 

        $font = $this->setFont($block);
        
        try {
            $block->width = $this->p->stringwidth($block->content, $font, 
                                                  $block->fontSize);
            $block->height = 30; 

            $this->drawBox($block);
            
            // text color (by using different stroke color and specifying
            // adapted "textrendering" option, one can outline letters.
            // TODO: enable this feature?)
            if ($block->fontUnderline)
                $this->setStrokeColor($block->color);
            $this->setFillColor($block->color);
            
            $orientation = ($block->orientation == 'vertical')
                           ? 'west' : 'north';

            $optstring = 'boxsize {%f %f} underline %s orientate %s ';
            $optstring .= 'position %s fitmethod auto textrendering 0';
            $optlist = sprintf($optstring,
                               $block->width, $block->height,
                               $block->fontUnderline ? 'true' : 'false',
                               $orientation,
                               $this->getTextAlign($block));
            
            $this->p->fit_textline($block->content, 100, 100, $optlist);
        }
        catch (Exception $e) {
            $this->getException($e);
        }
    }

    /**
     * Returns an identifier for the asked image. If not already available,
     * computes it and stores it, else gets it from images identifiers storage.
     */
    private function getImage($path) {
        if (in_array($path, $this->images))
            return $this->images[$path];
 
        try {
            $originalPath = $path;
            
            if (substr($path, 0, 4) == 'http') {
                // creates local copy if file gathered via http
                $tmpdir = PrintTools::getPdfDir();
                $tmpname = tempnam($tmpdir, 'pdfimage_');
                $tmpfile = fopen($tmpname, 'w');
                fwrite($tmpfile, file_get_contents($path));
                fclose($tmpfile);
                $path = $tmpname . strrchr($path, '.');
                rename($tmpname, $path);
            }

            $optlist = 'imagewarning true';
            $img = $this->p->load_image('auto', $path, $optlist);

            if (isset($tmpname))
                unlink($path);

            $this->images[$originalPath] = $img;
            return $img;
        }
        catch (Exception $e) {
            $this->getException($e);
        }
    }

    /**
     * Inserts an image in current PDF document.
     */
    private function addImage(PdfBlock $block) {
        try {
            $img = $this->getImage($block->content);
            $orientation = ($block->orientation == 'vertical')
                           ? 'west' : 'north';

            $optlist = sprintf('boxsize {%f %f} orientate %s position 50',
                               $this->getInPt($block->width),
                               $this->getInPt($block->height),
                               $orientation);

            $this->p->fit_image($img, 0, 400, $optlist);
            
            if ($block->singleUsage)
                $this->p->close_image($img);
        }
        catch (Exception $e) {
            $this->getException($e);
        }
    }
    
    function addGfxBlock(PdfBlock $block) {
        
        $this->drawBox($block);
        
        $this->addImage($block);
        
        /* 
        // PDFLib+PDI:
        if (gfx = pdf) $this->addPdf();
        else $this->addImage();
        */
    }

    function addTableCell() {}

    function addTableRow() {}

    function addTable() {}

    function finalizeDocument() {
        try {
            if ($this->isPageOpen)
                $this->p->end_page_ext(false);

            $this->p->end_document(false);

            return $this->p->get_buffer();
        }
        catch (Exception $e) {
            $this->getException($e);
        }
    }
 }
