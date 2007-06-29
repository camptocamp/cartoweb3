<?php
/**
 * Layer Result builder for ToolTips plugin
 * Extensible class to build a layer result for one feature
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

require_once('ToolTipsLayerBase.php');

/**
 * @package Plugins
 */
class LayerResult extends ToolTipsLayerBase {

    /**
     * HTML code rendering the result
     * @var string
     */    
    protected $resultHtml;

    /**
     * Constructor
     */
    public function __construct() {}

    /**
     * Adds attribute to the layer result
     * @param string name of the attribute
     * @param string value of the attribute
     */
    public function addAttribute($name, $value) {
        $this->returnedAttributes[$name] = $value;
    }    

    /**
     * Adds attributes to the layer result
     * @param array associative array (keys => values)
     */
    public function addAttributes($associativeArray) {
        foreach ($associativeArray as $key => $value) {
            $this->returnedAttributes[$key] = $value;
        }
    }    

    /**
     * Returns attribute value with given name
     * @param string name of the attribute 
     */
    public function getAttribute($name) {
        return !empty($this->returnedAttributes[$name]) ?
               $this->returnedAttributes[$name] : NULL;
    }    

    /**
     * Return a key => value attribute array
     * @return array array of attributes (key => value)
     */
    public function getAttributes() {
        return $this->returnedAttributes;
    }    

    /**
     * Sets result html code with given html code
     * @param string html code
     */
    public function setResultHtml($htmlCode) {
        $this->resultHtml = $htmlCode;
    }    

    /**
     * Returns result html code
     */
    public function getResultHtml() {
        return $this->resultHtml;
    }    

    /**
     * Renders the attributes list as HTML
     * @param LayerResult
     * @return string HTML code
     */
    public function renderResult($smarty) {
        $smarty->assign(array('layerId'      => $this->getId(),
                              'layerLabel'   => $this->getLabel(),
                              'layerResults' => Encoder::encode($this->getAttributes(), 'config')));
        return $smarty->fetch($this->getTemplate());
    }
}
?>
