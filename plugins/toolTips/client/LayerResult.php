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
 * @package CorePlugins
 * @version $Id$
 */

class LayerResult {

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
     * Layer custom template
     * @var string 
     */    
    protected $template;
    
    /**
     * Returned attributes and their value
     * @var array of key => value array representing attributes values
     */    
    protected $returnedAttributes = array();

    /**
     * Html code representing the result
     * @var string
     */    
    protected $resultHtml;

    /**
     * Constructor
     */
    public function __construct() {}

    /**
     * Sets id
     */
    public function setId($id) {
        $this->id = $id;
    }
    
    /**
     * Gets id
     */
    public function getId() {
        return $this->id;
    }        
    
    /**
     * Sets label
     */
    public function setLabel($label) {
        $this->label = $label;
    }    
    
    /**
     * Returns label
     * If not defined returns id
     */
    public function getLabel() {
        return isset($this->label) ? $this->label : $this->getId();
    }
    
    /**
     * Set layer custom template
     */
    public function setCustomTemplate($template) {
        $this->template = $template;
    }    

    /**
     * Returns custom template name
     */
    public function getCustomTemplate() {
        return $this->template;
    }  

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
        // TODO: check if array index exists
        return $this->returnedAttributes[$name];
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
        // Assigns a key => value attributes array to layerResults smarty
        // variable
        $smarty->assign('layerId', $this->getId());
        $smarty->assign('layerLabel',
            Encoder::encode($this->getLabel(), 'config'));
        
        $template = $this->getCustomTemplate();
        if ($template) {
            foreach ($this->returnedAttributes as $key => $value) {
                $smarty->assign($key, $value);
            }
            return $smarty->fetch($template);
        } else {
            $smarty->assign('layerResults', $this->returnedAttributes);
            return $smarty->fetch('layerResult.tpl');
        }
    }
}
 
?>