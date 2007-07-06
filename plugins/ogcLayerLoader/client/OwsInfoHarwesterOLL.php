<?php
/**
 * OwsInfoHarwester
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
 * @copyright 2006 Office International de l'Eau, Camptocamp
 * @package Plugins
 * @version $Id$
 *
 */

/**
 * Used to parse Capability file from a WMS services. 
 * Called by OgcLayerLoader plugin using Ajax.
 * @package Plugins
 */

class OwsInfoHarwesterOLL {

    /**                    
     * Logger
     * @var string
     */
    private $log;    
    
    /**
     * HTML response to return.
     * @var string
     */
    public $layers = array();
    
    /**
     * WMS Version to use
     * @var string
     */
    const DEFAULT_WMS_VERSION = '1.1.1';
    
    /**
     * Maximum execution time for the http request
     * @var int
     */
    private $maxExecutionTime;

    /**
     * Constructor
     * @param ServerContext
     * @param string map id
     * @param ProjectHandler
     * @param ServerLayers
     */
    public function __construct($url, $maxExecutionTime=300) {

        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->maxExecutionTime = $maxExecutionTime;

        // Add ? if needed
        if (!strpos ($url, '?'))
            $url .= '?'; 

        // And append GetCapabilities parameters
        $url = ($url.'&SERVICE=WMS&REQUEST=GetCapabilities&VERSION='.self::DEFAULT_WMS_VERSION);

        if (preg_match('/^(http|https):\/\/'.'/',$url)){
            if (@get_headers($url)) {
                set_time_limit($this->maxExecutionTime);
                $capa = @simplexml_load_file(urlencode($url));
                if (!empty($capa)) {
                    foreach ($capa->Capability->Layer as $src) {
                        $this->getLayers($src);
                    }
                }
            }
        }
    }

    /**
     * Get Layers in a xml (capabilities) doc and create an HTML option form element
     * @param array xml which correspond to a layer node from a GetCapabilities document
     */
    protected function getLayers($xml) {
        foreach ($xml as $src) {
            if (!empty($src->Title) && !empty($src->Name)) {
                $this->layers[] = $src;
            }
            $this->getLayers($src->Layer);
        }
    }
    
    /**
     * Get the layer name in the xml capabilities
     * @param String $name
     */
    public function getLayer($name){
        if (empty($this->layers)) {
        	return null;
        }
        foreach ($this->layers as $layer){
            if ($layer->Name == $name){
                return $layer;
            }                            
        }
    }
}

?>
