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
 * @version $Id $
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
$log =& LoggerManager::getLogger(__METHOD__);

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
     * @var Color[] Array of 2 colors. They are needed to initialize colors
     * generation.
     */
    public $colorInit = array();
    
    /**
     * @var Color[] Array of colors
     */
    public $colors = array();
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->layer = self::unserializeValue($struct, 'layer', 'string');
        $this->indicator =  
            self::unserializeValue($struct, 'indicator', 'string');
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
            'choroplethDrawn', 'bool');
        $this->choroplethParams = self::unserializeObject($struct,
            'choroplethParams', 'GeostatChoropleth');
        $this->choroplethClassification = self::unserializeObject($struct,
            'choroplethClassification', 'Classification');
        $this->choroplethStats = self::unserializeObject($struct,
            'choroplethStats', 'DistributionSummary');
        
    }
}


/**
 * @package Plugins
 */
class GeostatInit extends CwSerializable {
    
    /**
     * @var DistributionSummary Statistics about choropleth distribution
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

?>
