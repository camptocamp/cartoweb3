<?php
/**
 * 
 * 
 * *****************************************************************************
 * WARNING: uncomplete class, lots of functionnalities are not yet implemented.
 * A lot of work has still to be done and some factorization should occur with
 * alternate class CwFpdf
 * *****************************************************************************
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
 * @version $Id$
 */

/**
 * @package Plugins
 * @author Alexandre Saunier
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
    protected $blocks;

    protected $pageWidth;
    protected $pageHeight;
    protected $isPageOpen = false;
    protected $images = array();

    /**
     * Constructor
     */
    public function __construct(ClientExportPdf $export) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->general =& $export->getGeneral();
        $this->format =& $export->getFormat();
        $this->blocks =& $export->getBlocks();

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

    protected function getPageDim($dim) {
        $dist = $this->{'page' . $dim};
        return PrintTools::switchDistUnit($dist,
                                          'pt', 
                                          $this->general->distUnit);       
    }

    protected function getPageWidth() {
        return getPageDim('Width');
    }

    protected function getPageHeight() {
        return getPageDim('Height');
    }

    /**
     * Shortcut for distance units converter.
     */
    protected function getInPt($dist) {
        return PrintTools::switchDistUnit($dist,
                                          $this->general->distUnit,
                                          'pt');
    }

    /**
     * Returns caught PDFLib exceptions.
     */
    protected function getException(Exception $e) {
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

    protected function initializeDocument() {
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
    protected function addPage() {
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
    protected function setDash($style) {
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

    protected function setStrokeColor($color) {
        $borderColor = Utils::switchColorToRgb($color);
        try {
            $this->p->setcolor('stroke', 'rgb', $borderColor[0] / 255,
                               $borderColor[1] / 255, $borderColor[2] / 255,
                               0);
        }
        catch (Exception $e) {
            $this->getException($e);
        }
    }

    protected function setFillColor($color) {
        $bgColor = Utils::switchColorToRgb($color);
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
    protected function drawBox(PdfBlock $block) {
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

    protected function getTextAlign(PdfBlock $block) {
        
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

    protected function setFont(PdfBlock $block) {
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

    protected function addTextBlock(PdfBlock $block) {
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
    protected function getImage($path) {
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
    protected function addImage(PdfBlock $block) {
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
    
    protected function addGfxBlock(PdfBlock $block) {
        
        $this->drawBox($block);
        
        $this->addImage($block);
        
        /* 
        // PDFLib+PDI:
        if (gfx = pdf) $this->addPdf();
        else $this->addImage();
        */
    }

    protected function addTableCell() {}

    protected function addTableRow() {}

    protected function addTable(PdfBlock $block) {}

    protected function addLegend(PdfBlock $block) {}

    protected function finalizeDocument() {
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
