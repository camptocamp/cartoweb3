<?php
/**
 * Geostat plugin common classes
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
 * @copyright 2006 Camptocamp SA
 * @package Plugins
 * @version $Id$
 */

/**
 * Themamap include
 */
require_once(CARTOWEB_HOME . 'include/themamap/Distribution.php');
require_once(CARTOWEB_HOME . 'include/themamap/Classification.php');
require_once(CARTOWEB_HOME . 'include/themamap/ColorLut.php');

/**
 * Logger
 */
//$log = LoggerManager::getLogger(__METHOD__);


/**
 * @package Plugins
 */
class GeostatRequest extends CwSerializable {
    
    /**
     * @var boolean 
     */
    public $status=false;
    
    /**
     * @var GeostatChoropleth Choropleth parameters as request by client
     */
    public $choroplethParams;
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->status = self::unserializeValue($struct, 'status', 'boolean');
        $this->choroplethParams = 
        self::unserializeObject($struct,'choroplethParams',
            'GeostatChoropleth');
        
    }
    
}


/**
 * Geostat Choropleth Parameters Class
 * @package Plugins
 */
class GeostatChoropleth extends CwSerializable {
    /**
     * @var string Layer used for choropleth
     */
    public $layer;
    
    /**
     * @var string Attribute to use as indicator
     */
    public $indicator;
    
    /**
     * @var Bbox Bounding box of choropleth dataset
     */
    public $bbox;
    
    /**
     * @var int Classification Method. Use const defined in Distribution class
     * from themamp
     */
    public $classificationMethod = Distribution::CLASSIFY_BY_EQUAL_INTERVALS;
    
    /**
     * @var int Number of bins (classes)
     */
    public $nbBins = NULL;
    
    /**
     * @var numeric[] Array of bounds for classification
     * This is use with $classificationMethod = 0 (Custom)
     */
    public $bounds = array();
    
    /**
     * @var mixed[] Array of labels for classes
     * Changing labels is not supported yet !
     */
    public $labels = array();
    
    /**
     * @var int Method to use for create colors. Use const defined in ColorLut
     * class from themamap.
     */
    public $colorLutMethod = ColorLut::METHOD_HSV_INTERPOLATION;
    
    /**
     * @var TwColor[] Array of 2 colors. They are needed to initialize colors
     * generation.
     */
    public $colorInit = array();
    
    /**
     * @var TwColor[] Array of colors
     */
    public $colors = array();
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->layer = self::unserializeValue($struct, 'layer', 'string');
        $this->indicator =   
            self::unserializeValue($struct, 'indicator', 'string');
        $this->bbox = self::unserializeObject($struct, 'bbox', 'Bbox');
        $this->classificationMethod = self::unserializeValue($struct,
            'classificationMethod', 'int');
        $this->nbBins = self::unserializeValue($struct, 'nbBins', 'int');
        $this->bounds = self::unserializeArray($struct,'bounds');
        $this->labels = self::unserializeArray($struct,'labels');
        $this->colorLutMethod = self::unserializeValue($struct,
            'colorLutMethod', 'int');
        $this->colorInit = self::unserializeArray($struct,'colorInit');
        $this->colors = self::unserializeArray($struct,'colors');
    }
    
}


/**
 * @package Plugins
 */
class GeostatResult extends CwSerializable {
    
    /**
     * @var boolean True if choropleth was drawn
     */
    public $choroplethDrawn = false;
    
    /**
     * @var GeostatChoropleth Parameters as modified by server
     */
    public $choroplethParams;
    
    /**
     * @var Classification Result of the classification for choropleth layer
     */
    public $choroplethClassification;
    
    /**
     * @var DistributionSummary Statistics about choropleth distribution
     */
    public $choroplethStats;
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->choroplethDrawn = self::unserializeValue($struct,
            'choroplethDrawn', 'boolean');
        $this->choroplethParams = self::unserializeObject($struct,
            'choroplethParams', 'GeostatChoropleth');
        $this->choroplethClassification = self::unserializeObject($struct,
            'choroplethClassification', 'TwClassification');
        $this->choroplethStats = self::unserializeObject($struct,
            'choroplethStats', 'TwDistributionSummary');
        
    }
}


/**
 * @package Plugins
 */
class GeostatInit extends CwSerializable {
    
    /**
     * @var array Layers parameters
     */
    public $serverConfigParams = array();
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->serverConfigParams = 
            self::unserializeArray($struct,'serverConfigParams');
    }
}

/**
 * @package Plugins
 */
class GeostatLayerParams extends CwSerializable {
    /**
     * @var string Mapfile layer
     */
    public $msLayer;
    
    /**
     * @var string Label
     */
    public $label;
    
    /**
     * @var boolean Layer avalaible for choropleth display
     */
    public $choropleth;
    
    /**
     * @var boolean Layer avalaible for symbols display
     */
    public $symbols;
    
    /**
     * @var string Attributes avalaible for choropleth display
     */
    public $choropleth_attribs;
    
    /**
     * @var string Labels for attributes avalaible for choropleth
     */
    public $choropleth_attribs_label;
    
    /**
     * @var string Attributes avalaible for symbol display
     */
    public $symbols_attribs;
    
    /**
     * @var string Labels for attributes avalaible for symbols
     */
    public $symbols_attribs_label;
    
    public function unserialize($struct) {
        $this->msLayer = self::unserializeValue($struct, 'msLayer', 'string');
        $this->label = self::unserializeValue($struct, 'label', 'string');
        $this->choropleth = 
            self::unserializeValue($struct, 'choropleth', 'boolean');
        $this->symbols =  self::unserializeValue($struct, 'symbols', 'boolean');
        $this->choropleth_attribs =  
            self::unserializeValue($struct,'choropleth_attribs', 'string');
        $this->choropleth_attribs_label =  
            self::unserializeValue($struct,'choropleth_attribs_label',
            'string');
        $this->symbols_attribs =  
            self::unserializeValue($struct,'symbols_attribs', 'string');
        $this->symbols_attribs_label =  
            self::unserializeValue($struct,'symbols_attribs_label', 'string');
    }
}

/*********************
 * Themamap Wrappers *
 *********************/
/**
 * @package Plugins
 * Themamap wrapper for ColorRgb class
 */ 
class TwColorRgb extends CwSerializable {
    
    /**
     * @see ColorRgb
     */
    public $redLevel;
    
    /**
     * @see ColorRgb
     */
    public $greenLevel;
    
    /**
     * @see ColorRgb
     */
    public $blueLevel;
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->redLevel = self::unserializeValue($struct, 'redLevel', 'int');
        $this->greenLevel =
            self::unserializeValue($struct, 'greenLevel', 'int');
        $this->blueLevel = self::unserializeValue($struct, 'blueLevel', 'int');
    }
}

/**
 * @package Plugins
 * Themamap wrapper for Classification class
 */
class TwClassification extends CwSerializable {
    /**
     * @see Classification
     * @var TwBin[]
     */
    public $bins;
    
    /**
     * @see CwSerializable::unserialize()
     */
     public function unserialize($struct) {
          $this->bins = 
            self::unserializeArray($struct,'bins');
     }
}

/**
 * @package Plugins
 * Themamap wrapper for Bin class
 */
class TwBin extends CwSerializable {
    /**
     * @see Bin
     */
    public $label;
    
    /**
     * @see Bin
     */
    public $nbVal;
     
    /**
     * @see Bin
     */
    public $lowerBound;
     
    /**
     * @see Bin
     */
    public $upperBound;
    
    /**
     * @see Bin
     */
    public $isLast;
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->label = self::unserializeValue($struct, 'label', 'string');
        $this->nbVal = self::unserializeValue($struct, 'nbVal', 'integer');
        $this->lowerBound = 
            self::unserializeValue($struct, 'lowerBound', 'float');
        $this->upperBound = 
            self::unserializeValue($struct, 'upperBound', 'float');
        $this->isLast = self::unserializeValue($struct, 'isLast', 'boolean');
     }
}

/**
 * @package Plugins
 * Themamap wrapper for DistributionSummary class
 */
class TwDistributionSummary extends CwSerializable {
    
    /**
     * @see DistributionSummary
     */
    public $nbVal;
    
    /**
     * @see DistributionSummary
     */
    public $minVal;
    
    /**
     * @see DistributionSummary
     */
    public $maxVal;
    
    /**
     * @see DistributionSummary
     */
    public $meanVal;
    
    /**
     * @see DistributionSummary
     */
    public $stdDevVal;
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->nbVal = self::unserializeValue($struct, 'nbVal', 'integer');
        $this->minVal = self::unserializeValue($struct, 'minVal', 'decimal');
        $this->maxVal = self::unserializeValue($struct, 'maxVal', 'decimal');
        $this->meanVal = self::unserializeValue($struct, 'meanVal', 'decimal');
        $this->stdDevVal = 
            self::unserializeValue($struct, 'stdDevVal', 'decimal');
    }
}

/**
 * @package Plugins
 */
class TwColorRgbHelper {
    /**
     * Convert TwColorRgb to ColorRgb
     * @return ColorRgb
     */
    public static function TwColorRgb2ColorRgb($twColorRgb) {
        $colorRgb = new ColorRgb(
            $twColorRgb->redLevel,
            $twColorRgb->greenLevel,
            $twColorRgb->blueLevel);
        
        return $colorRgb;
    }
    
    /**
     * Convert ColorRgb to TwColorRgb
     * @return ColorRgb
     */
    public static function ColorRgb2TwColorRgb($colorRgb) {
        $twColorRgb = new TwColorRgb();
        try {
            if (get_class($colorRgb) != 'ColorRgb') {
                throw new Exception();
            }
        } catch (Exception $e) {
            throw new CartoCommonException('This object is not RGB Color');
        }
            $twColorRgb->redLevel = $colorRgb->getRedLevel();
            $twColorRgb->greenLevel = $colorRgb->getGreenLevel();
            $twColorRgb->blueLevel = $colorRgb->getBlueLevel();
        
        
       return $twColorRgb;
    }
    
    /**
     * Convert TwColorRgb array to ColorRgb array
     * @return ColorRgb[]
     */
    public static function TwColorRgbArray2ColorRgbArray($twColorRgbArray) {
        $colorArray = array();
        foreach($twColorRgbArray as $twColor) {
            $colorArray[] = TwColorRgbHelper::TwColorRgb2ColorRgb($twColor);
        }
        
        return $colorArray;
    }
    
    /**
     * Convert ColorRgb to TwColorRgb
     * @return ColorRgb
     */
    public static function ColorRgbArray2TwColorRgbArray($colorRgbArray) {
        $twColorArray = array();
        foreach($colorRgbArray as $color) {
            $twColorArray[] = TwColorRgbHelper::ColorRgb2TwColorRgb($color);
        }
        return $twColorArray;
    }
}

/**
 * @package Plugins
 */
class TwBinHelper {
    /**
     * Convert TwBin to Bin
     * @return Bin
     */
    public static function TwBin2Bin($twBin) {
        $bin = new Bin(
            $twBin->nbVal,
            $twBin->label,
            $twBin->lowerBound,
            $twBin->upperBound,
            $twBin->isLast);
        return $bin;
    }
    
    /**
     * Convert Bin to TwBin
     * @return TwBin
     */
    public static function Bin2TwBin($bin) {
        $twBin = new TwBin();
        $twBin->nbVal = $bin->getNbVal();
        $twBin->label = $bin->getLabel();
        $twBin->lowerBound = $bin->getLowerBound();
        $twBin->upperBound = $bin->getUpperBound();
        $twBin->isLast = $bin->isLastBin();
        return $twBin;
    }
}

/**
 * @package Plugins
 */
class TwClassificationHelper {
    /**
     * Convert Classification to TwClassification
     * @return TwClassification
     */
    public static function Classification2TwClassification($classification) {
        $twClassification = new TwClassification();
        $twBins = array();
        foreach($classification->getBins() as $bin) {
            $twBins[] = TwBinHelper::Bin2TwBin($bin);
        }
        $twClassification->bins = $twBins;
        
        return $twClassification;
    }
    
    /**
     * Convert TwClassification to Classification
     * @return Classification
     */
    public static function TwClassification2Classification($twClassification) {
        $bins = array();
        foreach($twClassification->bins as $TwBin) {
            $bins[] = TwBinHelper::TwBin2Bin($TwBin);
        }
        $classification = new Classification($bins);       
        
        return $classification;
    }
}

/**
 * @package Plugins
 */
class TwDistributionSummaryHelper {
    /**
     * Convert DistributionSummary to TwDistributionSummary
     * @return TwDistributionSummary
     */
    public static function DistSummary2TwDistSummary($summary) {
        $twSummary = new TwDistributionSummary();
        $twSummary->nbVal = $summary->nbVal;
        $twSummary->minVal = $summary->minVal;
        $twSummary->maxVal = $summary->maxVal;
        $twSummary->meanVal = $summary->meanVal;
        $twSummary->stdDevVal = $summary->stdDevVal;
        
        return $twSummary;
    }
    
    /**
     * Convert TwDistributionSummary to DistributionSummary
     * @return DistributionSummary
     */
    public static function TwDistSummary2DistSummary($twSummary) {
        /**
         * Hack : DistributionSummary Constructor need a distribution
         * We will override value after
         */
        $values = array(0,1);
        $distribution = new Distribution($values);
        $summary = new DistributionSummary($distribution);
        $summary->nbVal = $twSummary->nbVal;
        $summary->minVal = $twSummary->minVal;
        $summary->maxVal = $twSummary->maxVal;
        $summary->meanVal = $twSummary->meanVal;
        $summary->stdDevVal = $twSummary->stdDevVal;
        
        return $summary;
    }
    
}
