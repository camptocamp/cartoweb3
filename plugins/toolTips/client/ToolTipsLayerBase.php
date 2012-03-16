<?php
/**
 * ToolTips classes base.
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
 * @package Plugins
 */
class ToolTipsLayerBase {

    /**
     * Layer Id
     * @var string 
     */    
    protected $id;

    /**
     * Layer label
     * @var string 
     */    
    protected $label;

    /**
     * Layer template filename
     * @var string 
     */    
    protected $template = 'layerResult.tpl';
    
    /**
     * Returned attributes and their value
     * @var array of key => value array representing attributes values
     */    
    protected $returnedAttributes = array();

    /**
     * Constructor
     */
    public function __construct() {}

    /**
     * Sets id of the layer.
     * @param string Id of the layer
     */
    public function setId($id) {
        $this->id = $id;
    }
    
    /**
     * Gets id.
     * @return string
     */
    public function getId() {
        return $this->id;
    }        
    
    /**
     * Sets label of the layer.
     * @param string Label of the layer
     */
    public function setLabel($label) {
        $this->label = $label;
    }    
    
    /**
     * Returns label. If not defined, returns id.
     * @return string
     */
    public function getLabel() {
        return isset($this->label) ? $this->label : $this->getId();
    }
    
    /**
     * Sets the template file to use for current layer.
     */
    public function setTemplate($template) {
        $this->template = $template;
    }    

    /**
     * Returns the filename of the template used for current layer.
     * @return string
     */
    public function getTemplate() {
        return $this->template;
    }  
}
