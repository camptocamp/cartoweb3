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

    private $pageWidth;
    private $pageHeight;
    private $isPageOpen = false;

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
    }

    /**
     * Shortcut for distance units converter.
     */
    private function getInPt($dist) {
        return PrintConvertor::switchDistUnit($dist,
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

    //function addPage($insertBeforePageNb = false) {
    function addPage() {
        $optlist = 'topdown true';
        
        //if ($insertBeforePageNb)
        //    $optlist .= ' pagenumber ' . $insertBeforePageNb;
           
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

    function addTextBlock() {}

    function addImage() {}
    
    function addGfxBlock() {
        /* 
        // PDFLib Lite:
        $this->addImage();
        
        // PDFLib+PDI:
        if (gfx = pdf) $this->addPdf();
        else $this->addImage();
        */

        // Draw common block part.
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
