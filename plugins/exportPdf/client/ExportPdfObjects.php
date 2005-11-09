<?php
/**
 * This file contains common classes used when PDF exporting maps.
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
 * @author Alexandre Saunier
 * @version $Id$
 */

/**
 * Provides various static tools.
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
    static public function switchDistUnit($dist, $from, $to) {
        if ($from == $to) return $dist;
        
        $ratio = 1;
        
        if ($from == 'cm') {
            $ratio *= 10;
            $from = 'mm';
        }
        elseif ($to == 'cm') {
            $ratio /= 10;
            $to = 'mm';
        }

        if ($from == 'in') {
            $ratio *= 72;
            $from = 'pt';
        }
        elseif ($to == 'in') {
            $ratio /= 72;
            $to = 'pt';
        }

        if ($from == 'mm' && $to == 'pt') {
            $ratio *= 72 / 25.4;
        } elseif ($from == 'pt' && $to == 'mm') {
            $ratio *= 25.4 / 72;
        } else {
            throw new CartoclientException("unknown dist unit: $from or $to");
        }

        return $dist * $ratio;
    }

    /**
     * Converts #xxyyzz hexadecimal color codes into RGB.
     * @param string hexadecimal color code
     * @return array array of RGB codes
     */
    static public function switchHexColorToRgb($color) {
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
    static public function switchColorToRgb($color) {
        if ($color{0} == '#')
            return self::switchHexColorToRgb($color);

        if (is_array($color))
            return $color;

        switch($color) {
            case 'black': return array(0, 0, 0);
            case 'red': return array(255, 0, 0);
            case 'green': return array(0, 255, 0);
            case 'blue': return array(0, 0, 255);
            case 'white': default: return array(255, 255, 255);
        }
    }

    /**
     * Tells if given path is relative.
     * @param string path
     * @return boolean true if relative
     */
    static public function isRelativePath($path) {
        return (substr($path, 0, 4) != 'http' && substr($path, 0, 1) != '/' &&
                substr($path, 1, 1) != ':');
    }
   
    /**
     * Retrieves text block content from external storage
     *
     * Storage info string is formatted as follows:
     * - file: "file~file/path" (URL, absolute or CartoWeb root-relative 
     * filesystem path
     * - DB: "db~DSN~SqlQuery
     * @param string storage info
     * @return string content
     */
    static public function getContent($storage) {
        $storageInfo = explode('~', $storage);
        switch($storageInfo[0]) {
            case 'file': return self::getFileContent($storageInfo);
            case 'db': return self::getDbContent($storageInfo);
            default: return $storage;
        }
    }

    /**
     * Retrieves text block content from file.
     * @param array storage info array('file', $filepath)
     * @return string content
     */
    static protected function getFileContent($info) {
        $filename = $info[1];
        if (self::isRelativePath($filename)) {
            $filename = CARTOWEB_HOME . $filename;
        }
     
        if (!is_readable($filename)) {
            throw new CartoclientException('PDF: Not readable file: ' .
                                           $filename);
        }
        
        $handle = fopen ($filename, 'r');
        $content = fread ($handle, filesize($filename));
        fclose ($handle);

        return $content;
    }

    /**
     * Retrieves text block content from DB
     * @param array storage info array('db', $dsn, $sql)
     * @return string content
     */
    static protected function getDbContent($info) {

        // Only SELECT queries are allowed:
        if (!isset($info[2]) || 
            strtolower(substr(trim($info[2]), 0, 6)) != 'select') {
            throw new CartoclientException('PDF: missing or illegal SQL query');
        }
        
        require_once 'DB.php';

        $db =& DB::connect($info[1]);
        if (DB::isError($db)) {
            throw new CartoclientException('PDF: failed connecting to DB: ' 
                                           . $db->getMessage());
        }

        $db->setFetchMode(DB_FETCHMODE_ORDERED);
        $results =& $db->getAll($info[2]);
        if (DB::isError($results)) {
            throw new CartoclientException('PDF: failed executing query: '
                                           . $results->getMessage());
        }

        $content = '';
        foreach ($results as $res) {
            $content .= implode(' ', $res) . "\n";
        }
        return $content;
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
    public $pdfEngine           = 'CwFpdf';
    
    /**
     * @var string
     */
    public $pdfVersion          = '1.3';
    
    /**
     * @var string
     */
    public $distUnit            = 'mm';
    
    /**
     * @var float
     */
    public $horizontalMargin    = 10;
    
    /**
     * @var float
     */
    public $verticalMargin      = 10;
    
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
    public $resolutions         = array(96);

    /**
     * @var int
     */
    public $mapServerResolution;

    /**
     * @var int
     */
    public $defaultResolution   = 96;
    
    /**
     * @var int
     */
    public $selectedResolution;
    
    /**
     * @var string
     */
    public $defaultOrientation  = 'portrait';
    
    /**
     * @var string
     */
    public $selectedOrientation;
    
    /**
     * @var array
     */
    public $activatedBlocks;
    
    /**
     * @var string
     */
    public $filename            = 'map-[date,dMY-His].pdf';

    /**
     * @var float
     */
    public $overviewScaleFactor = 10;

    /**
     * @var string
     */
    public $overviewColor;

    /**
     * @var string
     */
    public $overviewOutlineColor= 'red';

    /**
     * @var string
     */
    public $output              = 'redirection';

    /**
     * @var string
     */
    public $allowedRoles        = SecurityManager::ALL_ROLE;

    /**
     * @var boolean
     */
    public $importRemotePng     = false;
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

    /**
     * @var string
     */
    public $allowedRoles     = SecurityManager::ALL_ROLE;
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
    public $multiPage        = false;
    
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
    public $headers          = '';

    /**
     * @var boolean
     */
    public $standalone       = true;

    /**
     * @var string
     */
    public $allowedRoles     = SecurityManager::ALL_ROLE;

    /**
     * Tells if block content must be translated.
     * @var boolean
     */
    public $i18n             = false;
}

/**
 * Description of tabular blocks.
 * @package Plugins
 */
class TableElement {

    /**
     * @var mixed initially string than PdfBlock
     */
    public $caption = '';
    
    /**
     * @var mixed initially array than PdfBlock
     */
    public $headers = array();
    
    /**
     * @var array
     */
    public $rows    = array();
    
    /**
     * @var float
     */
    public $totalWidth = 0;
    
    /**
     * @var array
     */
    public $colsWidth = array();
    
    /**
     * @var float
     */
    public $x0;
    
    /**
     * @var float
     */
    public $y0;
    
    /**
     * @var float
     */
    public $rowBaseHeight;
}

// TODO: use an abstract class instead of an interface in order to factorize
// common methods!
/**
 * Interface for PDF generators tools.
 * @package Plugins
 */
interface PdfWriter {

    /**
     * Sets general data and opens PDF document.
     */
    public function initializeDocument();
   
    /**
     * Returns page width in PdfGeneral dist_unit.
     * @return float
     */
    public function getPageWidth();

    /**
     * Returns page height in PdfGeneral dist_unit.
     * @return float
     */
    public function getPageHeight();
    
    /**
     * Adds a new page to PDF document.
     */
    public function addPage();
    
    /**
     * Adds a block with textual content.
     * @param PdfBlock
     */
    public function addTextBlock(PdfBlock $block);
    
    /**
     * Adds a block with graphical (image, PDF...) content.
     * @param PdfBlock
     */
    public function addGfxBlock(PdfBlock $block);
   
    /**
     * Draws a tabular element cell.
     * @param string textual content
     * @param float width
     * @param float height
    */
    public function addTableCell($text, $width, $height);
    
    /**
     * Draws a table row.
     * @param PdfBlock
     * @param TableElement
     * @param array row data
     */
    public function addTableRow(PdfBlock $block, TableElement $table, $row);
   
    /**
     * Draws a table block.
     * @param PdfBlock
     */
    public function addTable(PdfBlock $block);
   
    /**
     * Draws the legend block.
     * @param PdfBlock
     */
    public function addLegend(PdfBlock $block);
    
    /**
     * Performs final PDF operations and outputs document.
     */
    public function finalizeDocument();
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
    protected $minX;
    
    /**
     * @var float
     */
    protected $maxX;
    
    /**
     * @var float
     */
    protected $minY;
    
    /**
     * @var float
     */
    protected $maxY;
    
    /**
     * Indicates if the Y-origin is at top of page.
     * @var boolean
     */
    protected $YoAtTop = true;

    /**
     * @var array
     */
    protected $allocated = array();
    
    /**
     * @var array
     */
    protected $levels = array();

    /**
     * Constructor.
     * @param array contains page max extent + Y-origin location.
     */
    public function __construct($params) {
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
     * Used to get any property value
     * @return mixed property value
     */
    public function __call($m, $a) {
        if (isset($this->$m))
            return $this->$m;

        return NULL;
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
    protected function allocateArea(PdfBlock $block, $x, $y) {
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
    protected function getY(PdfBlock $block, $minY, $maxY) {
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
    protected function getX(PdfBlock $block, $minX, $maxX) {
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
    protected function getBlockExtent($name = '') {
        if (!$name || !isset($this->levels[$name]))
            return array('minX' => $this->minX, 'minY' => $this->minY,
                         'maxX' => $this->maxX, 'maxY' => $this->maxY);

        $zIndex = $this->levels[$name];
        return $this->allocated[$zIndex][$name];
    }

    /**
     * Return x-coord of the block rightest point and 
      * y-coord of the block lowest allowed point.
     * @param PdfBlock
     * @return array (x-rightest, y-lowest)
     */
    public function getMaxExtent(PdfBlock $block) {
        $parent = isset($block->parent) ? $block->parent : '';
        $pExtent = $this->getBlockExtent($parent);
        return array($pExtent['maxX'], $pExtent['maxY']);
    }

    /**
     * Returns the nearest available reference point (min X, min Y)
     * according to the block positioning properties.
     * @param PdfBlock
     * @param boolean if true no space allocation is performed
     * @return array (X,Y) of reference point
     */
    public function checkIn(PdfBlock $block, $dontAllocate = false) {
        // TODO: handle blocks too high to fit below previous block and
        // that must be displayed with a X shift etc.?
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
        
        if ($dontAllocate)
            return array($x0, $y0);

        return $this->allocateArea($block, $x0, $y0);
    }

    /**
     * Determines next available reference point coordinates for given table.
     * @param PdfBlock table description block
     * @param TableElement table content
     * @return array (X,Y) of reference point
     */
    public function checkTableIn(PdfBlock $block, TableElement $table) {
        $tableBlock = clone $block;
        $tableBlock->width = $table->totalWidth;
        $tableBlock->inFlow = false;
        $tableBlock->verticalBasis = 'top';
        return $this->checkIn($tableBlock, true);
        // FIXME: Pdf Engines with YoAtTop = false will return incorrect Y!
    }

    /**
     * Returns given block available width (parent block width).
     * @param PdfBlock
     * @return float
     */
    public function getAvailableSpan(PdfBlock $block) {
        if (isset($block->parent)) {
            $extent = $this->getBlockExtent($block->parent);
            return $extent['maxX'] - $extent['minX'];
        }
        
        return $this->maxX - $this->minX; 
    }
}
?>
