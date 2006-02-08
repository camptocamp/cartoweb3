<?php
/**
 * MapOverlay plugin
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
 * Server MapOverlay class
 * @package Plugins
 */
class ServerMapOverlay extends ServerPlugin {
    
    /**
     * @var Logger
     */
    private $log;

    /**
     * @var MapObj
     */
    protected $mapObj;


    /** 
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    /**
     * Update the color. 
     * 'property' is one of 'color', 'outlinecolor' or 'backroundcolor'
     *
     * @param ms_style_obj
     * @param ColorOverlay
     * @return ColorOverlay
     */
    protected function updateColor($msObject, $property, ColorOverlay $overlay) {

        $result = new ColorOverlay();
        // Remembers old ID
        $result->id = $overlay->id;

        switch ($overlay->action) {
        case BasicOverlay::ACTION_UPDATE:
            $msColor =& $msObject->$property;
            if (!is_null($overlay->red) && $msColor->red != $overlay->red) {
                $result->red = $overlay->red;
            }
            if (!is_null($overlay->green) && $msColor->green != $overlay->green) {
                $result->green = $overlay->green;
            }
            if (!is_null($overlay->blue) && $msColor->blue != $overlay->blue) {
                $result->blue = $overlay->blue;
            }
            if (!is_null($result->red) && !is_null($result->green) && 
                !is_null($result->blue)) {
                $msColor->setRGB($overlay->red, $overlay->green, $overlay->blue);
            }

            break;

        case BasicOverlay::ACTION_SEARCH:
            // search color is not permitted because it's mean create a new style
            throw new CartoserverException('search Color: operation not permitted');
            break;

        case BasicOverlay::ACTION_INSERT:
            throw new CartoserverException('insert Color: operation not permitted');
            break;

        case BasicOverlay::ACTION_REMOVE:    
            $msObject->$property->setRGB(-1, -1, -1);
            return NULL; 

        default:
            throw new CartoserverException('updateColor: unknown action');
            break;
        }
        return $result;
    }


    /**
     * Update style
     *
     * @param ms_class_obj
     * @param StyleOverlay
     * @result StyleOverlay
     */
    protected function updateStyle($msClass, StyleOverlay $overlay) {

        $result = new StyleOverlay();
        // Remembers old ID
        $result->id = $overlay->id;
        $msStyle = NULL;

        switch ($overlay->action) {
        case BasicOverlay::ACTION_UPDATE:
            $msStyle = $msClass->getStyle($overlay->index);

            if (is_null($msStyle)) {
                throw new CartoserverException('update Style: style not found, index = '
                                               . $overlay->index);
            }

            if (!is_null($overlay->symbol) && $msStyle->symbol != $overlay->symbol) {
                $result->symbol = $overlay->symbol;
                if (is_numeric($result->symbol)) {
                    $msStyle->set('symbol', $result->symbol);
                } else {
                    $msStyle->set('symbolname', $result->symbol);
                }
            }
            if (!is_null($overlay->size) && $msStyle->size != $overlay->size) {
                $result->size = $overlay->size;
                $msStyle->set('size', $result->size);
            }
            break;

        case BasicOverlay::ACTION_SEARCH:
            throw new CartoserverException('search Style: operation not permitted');
            break;

        case BasicOverlay::ACTION_INSERT:
            // FIXME: untested
            // Default position is at the end
            $pos = $msClass->numstyles;
            if (!is_null($overlay->position)) {
                switch ($overlay->position->type) {
                case PositionOverlay::TYPE_ABSOLUTE:
                    $pos = $overlay->position->index;
                    break;
                case PositionOverlay::TYPE_RELATIVE:
                    if (is_null($overlay->position->id)) {
                        throw new CartoserverException('insert Style: id cannot be null when position is relative');                       
                    }
                    for ($i = 0; $i < $msClass->numstyles; $i++) {
                        $msSearchStyle = $msClass->getStyle($i);
                        if ($msSearchStyle->name == $overlay->position->id) {
                            $pos = $i;
                            break;
                        }
                    }
                    if ($pos == $msStyle->numclasses) {
                        throw new CartoserverException('insert Style: id not found');                       
                    }
                    $pos += $overlay->position->index;
                    
                    break;
                }
            }
            if ($pos < 0) {
                $pos = 0;
            } elseif ($pos > $msClass->numstyles) {
                $pos = $msClass->numstyles;
            }
            $originalStyle = $this->getStyle($msClass, $overlay, true);
            if (is_null($originalStyle)) {
                $originalStyle = $msClass->getClass(0);
            }
            $msStyle = ms_newStyleObj($msClass, $originalStyle);
            for ($i = $msClass->numstyles - 1; $i > $pos; $i--) {
                $msStyle->movestyleup($i);
            }
            
            // since the class position has change, we need to fetch 
            // the style again (mapserver issue)
            $msStyle = $msClass->getStyle($pos);

            $result->index = $pos;
            break;
        case BasicOverlay::ACTION_REMOVE:
       
            $msClass->deletestyle($overlay->index);
            return NULL;
        default:
            throw new CartoserverException('updateStyle: unknown action');
            break;
        }
        
        if (!is_null($overlay->color) && $overlay->color->isValid()) {
            $resultColor = $this->updateColor($msStyle, 'color', $overlay->color);
            if (!is_null($resultColor)) {
                $result->color = $resultColor;
            }
        }
        if (!is_null($overlay->outlineColor) && $overlay->outlineColor->isValid()) {
            $resultColor = $this->updateColor($msStyle, 'outlinecolor', $overlay->outlineColor);
            if (!is_null($resultColor)) {
                $result->outlineColor = $resultColor;
            }
        }
        if (!is_null($overlay->backgroundColor) && $overlay->backgroundColor->isValid()) {
            $resultColor = $this->updateColor($msStyle, 'backgroundcolor', $overlay->backgroundColor);
            if (!is_null($resultColor)) {
                $result->backgroundColor = $resultColor;
            }
        }

        return $result;
    }

    /**
     * @param ms_class_obj
     * @param LabelOverlay
     *
     * @return LabelOverlay
     */
    private function updateLabel($msClass, LabelOverlay $overlay) {

        $result = new LabelOverlay();
        // Remembers old ID
        $result->id = $overlay->id;
        $msLabel = NULL;

        switch ($overlay->action) {
        case BasicOverlay::ACTION_UPDATE:
        
            $msLabel = $msClass->label;
            if (!is_null($overlay->font) && $msLabel->font != $overlay->font) {
                $result->font = $overlay->font;
                $msLabel->set('font', $result->font);
            }
            if (!is_null($overlay->size) && $msLabel->size != $overlay->size) {
                $result->size = $overlay->size;
                $msLabel->set('size', $result->size);
            }
            break;
        case BasicOverlay::ACTION_SEARCH:
        
            throw new CartoserverException('search Label: operation not permitted');
            break;
        case BasicOverlay::ACTION_INSERT:
            // TODO
            break;
        case BasicOverlay::ACTION_REMOVE:    
            // TODO
            return NULL;
        default:
            throw new CartoserverException('updatLabel: unknown action');
            break;

        }
        if (!is_null($overlay->color)) {
            $resultColor = $this->updateColor($msLabel, 'color', $overlay->color);
            if (!is_null($resultColor)) {
                $result->color = $resultColor;
            }
        }
        if (!is_null($overlay->outlineColor)) {
            $resultColor = $this->updateColor($msLabel, 'outlinecolor', $overlay->outlineColor);
            if (!is_null($resultColor)) {
                $result->outlineColor = $resultColor;
            }
        }
        if (!is_null($overlay->backgroundColor)) {
            $resultColor = $this->updateColor($msLabel, 'backgroundcolor', $overlay->backgroundColor);
            if (!is_null($resultColor)) {
                $result->backgroundColor = $resultColor;
            }
        }
        return $result;
    }

    /**
     * Return 
     *
     * @param ms_layer_obj
     * @param ClassOverlay
     * @param boolean
     * @return ms_class_obj
     */
    private function getClass($msLayer, $overlay, $copy = false) {

        if ($copy) {
            $index = $overlay->copyIndex;
            $name = $overlay->copyName;
        } else {
            $index = $overlay->index;
            $name = $overlay->name;
        }
        if (!is_null($index)) {
            return $msLayer->getClass($index);
        }
        
        // No getClassByName ??
        $msClass = NULL;
        for ($i = 0; $i < $msLayer->numclasses; $i++) {
            $class = $msLayer->getClass($i);
            if ($class->name == $name) {
                $msClass = $class;
            }
        }
        return $msClass;
    }

    /**
     * @param ms_layer_obj
     * @param integer
     */
    private function getClassIndex($msLayer, $name) {
        for ($i = 0; $i < $msLayer->numclasses; $i++) {
            $class = $msLayer->getClass($i);
            if ($class->name == $name) {
                return $i;
            }
        }
        return -1;
    }
    
    /**
     * @param ms_color_obj
     * @param string
     * @param ColorOverlay
     * @return boolean
     */
    public function checkColor($msObject, $property, ColorOverlay $overlay) {
        if (!is_null($overlay->red) &&
            $msObject->$property->red != $overlay->red) {
            return false;
        }
        if (!is_null($overlay->green) &&
            $msObject->$property->green != $overlay->green) {
            return false;
        }
        if (!is_null($overlay->blue) &&
            $msObject->$property->blue != $overlay->blue) {
            return false;
        }
        return true;
    }
    
    /**
     * @param ms_style_obj
     * @param StyleOverlay
     * @return boolean
     */
    public function checkStyle($msStyle, StyleOverlay $overlay) {
        
        if (!is_null($overlay->symbol)) {
            if (is_numeric($overlay->symbol)) {
                if ($overlay->symbol != $msStyle->symbol)
                    return false;
            } else {
                if ($overlay->symbol != $msStyle->symbolname)
                    return false;
            }
        }

        if (!is_null($overlay->size) && $overlay->size != $msStyle->size) {
            return false;
        }

        if (!is_null($overlay->color) &&
            !$this->checkColor($msStyle, 'color', $overlay->color)) {
            return false;
        }
        
        if (!is_null($overlay->outlineColor) &&
            !$this->checkColor($msStyle, 'outlinecolor', $overlay->outlineColor)) {
            return false;
            
        }
        
        if (!is_null($overlay->backgroundColor) &&
            !$this->checkColor($msStyle, 'backgroundcolor', $overlay->backgroundColor)) {
            return false;
        }

        return true;
    }
    
    /**
     * Finds out if class styles match overlay styles
     * @return boolean
     */
    public function checkClassStyles($msClass, ClassOverlay $overlay) {
        $debug = array();
        if (is_null($overlay->styles)) {
            return true;
        } else {
            // check if all styles are the same
            foreach ($overlay->styles as $style) {
                $found = false;
                for ($i = 0; $i < $msClass->numstyles; $i++) {
                    $debug[] = $msClass->getStyle($i);
                    if ($this->checkStyle($msClass->getStyle($i), $style)) {
                        // style match, check next $style
                        $found = true;
                        break;
                    } else {
                        // style mismatch, check next $msClass
                    }
                }
                if (!$found && $i == $msClass->numstyles) {
                    // at the end of ms styles and not found ...
                    return false;
                }
            }
            return true;        
        }
    }       
    
    /**
     * Finds out if class label matches overlay label
     * @param ms_class_obj 
     * @param ClassOverlay
     */
    public function checkClassLabel($msClass, ClassOverlay $overlay) {
        if (is_null($overlay->label)) {
            return true;
        } else {
            if (!is_null($overlay->label->font) && 
                $msClass->label->font != $overlay->label->font) {
                return false;
            }
            if (!is_null($overlay->label->size) && 
                $msClass->label->size != $overlay->label->size) {
                return false;
            }
            if (!is_null($overlay->label->color) &&
                !$this->checkColor($msClass->label, 'color', $overlay->label->color)) {
                return false;
            }
            if (!is_null($overlay->label->outlineColor) &&
                !$this->checkColor($msClass->label, 'outlinecolor', $overlay->label->outlineColor)) {
                return false;
            }
            if (!is_null($overlay->label->backgroundColor) &&
                !$this->checkColor($msClass->label, 'backgroundcolor', $overlay->label->backgroundColor)) {
                return false;
            }
            return true;
        }
    }

    /**
     * @param ms_layer_obj
     * @param ClassOverlay
     * @return ClassOverlay
     */
    protected function updateClass($msLayer, ClassOverlay $overlay) {

        $result = new ClassOverlay();
        // Remembers old ID
        $result->id = $overlay->id;
        $msClass = NULL;

        switch ($overlay->action) {
        case BasicOverlay::ACTION_UPDATE:

            $msClass = $this->getClass($msLayer, $overlay);
            if (is_null($msClass)) {
                throw new CartoserverException('update Class: can\'t find class');
            }
            
            $result->index = $this->getClassIndex($msLayer, $overlay->name);

            if (!is_null($overlay->index) && !is_null($overlay->name) &&
                $msClass->name != $overlay->name) {
                // Renaming #th class
                $result->name = $overlay->name;
                $msClass->set('name', $result->name);
            }

            // Setting properties
            if (!is_null($overlay->expression) &&
                $msClass->getExpression() != $overlay->expression) {
                    
                $result->expression = $overlay->expression;
                $msClass->setExpression($result->expression);
            }
            if (!is_null($overlay->minScale) &&
                $msClass->minscale != $overlay->minScale) {
                
                $msClass->minscale = $result->minScale = $overlay->minScale;
            }
            if (!is_null($overlay->maxScale) &&
                $msClass->maxscale != $overlay->maxScale) {
                
                $msClass->maxscale = $result->maxScale = $overlay->maxScale;
            }
            break;
        case BasicOverlay::ACTION_SEARCH:
            $nFound = 0;
            $originalClass = NULL;
            for ($i = 0; $i < $msLayer->numclasses; $i++) {
                $msSearchClass = $msLayer->getClass($i);
                
                if (substr($msSearchClass->name, 0, strlen($overlay->name) + 2)
                    == $overlay->name . '@@' || $msSearchClass->name == $overlay->name) {
                    $nFound ++;
                    
                    // Checking properties
                    if ((is_null($overlay->expression)
                         || $msSearchClass->getExpression() == $overlay->expression)
                        && $this->checkClassStyles($msSearchClass, $overlay)
                        && $this->checkClassLabel($msSearchClass, $overlay)) {     
                         $result->name = $msSearchClass->name;
                         $result->index = $this->getClassIndex($msLayer, $result->name);
                         $msClass = $msSearchClass;
                     }
                }           
                if ($msSearchClass->name == $overlay->name) {
                    $originalClass = $msSearchClass;
                } 
            }
            if ($nFound == 0) {
                // No classes found with that name, taking first
                $originalClass = $msLayer->getClass(0);
            }
            if (is_null($msClass)) {
                // No classes found with those options, adding a class
                $msClass = ms_newClassObj($msLayer, $originalClass);

                $result->name = $overlay->name . '@@' . $nFound;
                $msClass->set('name', $result->name);
                $result->index = $this->getClassIndex($msLayer, $result->name);

                // Setting new properties
                if ($msClass->getExpression() != $overlay->expression) {
                    
                    $result->expression = $overlay->expression; 
                    $msClass->setExpression($result->expression);
                }
            }
            break;
        case BasicOverlay::ACTION_INSERT:
            // Default position is at the end
            $pos = $msLayer->numclasses;
            if (!is_null($overlay->position)) {
                switch ($overlay->position->type) {
                case PositionOverlay::TYPE_ABSOLUTE:
                    $pos = $overlay->position->index;
                    break;
                case PositionOverlay::TYPE_RELATIVE:
                    if (is_null($overlay->position->id)) {
                        throw new CartoserverException('insert Class: id cannot be null when position is relative');
                    }
                    for ($i = 0; $i < $msLayer->numclasses; $i++) {
                        $msSearchClass = $msLayer->getClass($i);
                        if ($msSearchClass->name == $overlay->position->id) {
                            $pos = $i;
                            break;
                        }
                    }
                    if ($pos == $msLayer->numclasses) {
                        throw new CartoserverException('insert Class: id not found');                       
                    }
                    $pos += $overlay->position->index;
                    
                    break;
                }
            }
            if ($pos < 0) {
                $pos = 0;
            } elseif ($pos > $msLayer->numclasses) {
                $pos = $msLayer->numclasses;
            }
            $originalClass = $this->getClass($msLayer, $overlay, true);
            if (is_null($originalClass)) {
                $originalClass = $msLayer->getClass(0);
            }

            $msClass = ms_newClassObj($msLayer, $originalClass);
            
            $result->index = $pos;
            $result->name = $overlay->name;
            $msClass->set('name', $result->name);

            // Setting new properties
            if ($msClass->getExpression() != $overlay->expression) {                
                $result->expression = $overlay->expression; 
                $msClass->setExpression($result->expression);
            }
            
            for ($i = $msLayer->numclasses - 1; $i > $pos; $i--) {
                $msLayer->moveclassup($i);
            }
            
            // since the class position has change, we need to fetch 
            // the class again (mapserver issue)
            $msClass = $msLayer->getClass($pos);

            break;
        case BasicOverlay::ACTION_REMOVE: 
            
            $msClass = $this->getClass($msLayer, $overlay);
            $msLayer->removeClass($msClass->index); 

            return NULL;
        default:
            throw new CartoserverException('updateClass: unknown action');
            break;
        }

        if (!empty($overlay->styles)) {
            foreach ($overlay->styles as $style) {                
                $resultStyle = $this->updateStyle($msClass, $style);
                if (!is_null($resultStyle)) {
                    $result->styles[] = $resultStyle;
                }                
            }
        }
        if (!is_null($overlay->label)) {
            $resultLabel = $this->updateLabel($msClass, $overlay->label);
            if (!is_null($resultLabel)) {
                $result->label = $resultLabel;
            }
        }
        return $result;
    }
    
    /**
     * @param MetadataOverlay
     * @return MetadataOverlay
     */
    public function updateMetadata($msLayer, MetadataOverlay $overlay) {
        
        $result = new MetadataOverlay();
        // Remembers old ID
        $result->id = $overlay->id;
        
        switch ($overlay->action) {
        case BasicOverlay::ACTION_UPDATE:
            if (!is_null($overlay->name) && !is_null($overlay->value) &&
                $overlay->value != $msLayer->getMetadata($overlay->name) &&
                $msLayer->getMetadata($overlay->name) != "") {
                $result->name = $overlay->name;
                $result->value = $overlay->value;
                $msLayer->setMetadata($result->name, $result->value);
            }
            break;

        case BasicOverlay::ACTION_SEARCH:
            if (!is_null($overlay->name) && !is_null($overlay->value) &&
                $overlay->value != $msLayer->getMetadata($overlay->name)) {
                $result->name = $overlay->name;
                $result->value = $overlay->value;
                $msLayer->setMetadata($result->name, $result->value);
            }
            break;

        case BasicOverlay::ACTION_INSERT:
            if (!is_null($overlay->name) && !is_null($overlay->value)) {
                $result->name = $overlay->name;
                $result->value = $overlay->value;
                $msLayer->setMetadata($result->name, $result->value);
            }
            break;

        case BasicOverlay::ACTION_REMOVE:
            if (!is_null($overlay->name)) {
                $msLayer->removeMetadata($result->name);
            }

        default:
            throw new CartoserverException('updateMetadata: unknown action');
            break;
        }
        return $result;
    }
    
    
    public function getLayer($overlay, $copy = false) {

        if ($copy) {
            $index = $overlay->copyIndex;
            $name = $overlay->copyName;
        } else {
            $index = $overlay->index;
            $name = $overlay->name;
        }
        
        if (!is_null($index)) {
            return $this->mapObj->getLayer($index);
        } else {
            return $this->mapObj->getLayerByName($name);
        }
    }


    /**
     * @param LayerOverlay
     * @return LayerOverlay
     */
    protected function updateLayer(LayerOverlay $overlay) {
        $result = new LayerOverlay();
        // Remembers old ID
        $result->id = $overlay->id;
        $msLayer = NULL;
        
        switch ($overlay->action) {
        case BasicOverlay::ACTION_UPDATE:
            $msLayer = $this->getLayer($overlay);
            if (!is_null($overlay->index) && !is_null($overlay->name) &&
                $msLayer->name != $overlay->name) {
                // Renaming #th layer
                $result->name = $overlay->name;
                $msLayer->set('name', $result->name);
            }
           
            // Setting properties
            if (!is_null($overlay->connection) &&
                $msLayer->connection != $overlay->connection) {
                $result->connection = $overlay->connection;
                $msLayer->set('connection', $result->connection);
            }
            if (!is_null($overlay->connectionType) &&
                $msLayer->connectionType != $overlay->connectionType) {
                $result->connectionType = $overlay->connectionType;
                $msLayer->set('connectiontype', $result->connectionType);
            }
            if (!is_null($overlay->data) && 
                $msLayer->data != $overlay->data) {
                $result->data = $overlay->data;
                $msLayer->set('data', $result->data);
            }
            if (!is_null($overlay->maxScale) && 
                $msLayer->maxScale != $overlay->maxScale) {
                $result->maxScale = $overlay->maxScale;
                $msLayer->set('maxscale', $result->maxScale);
            }
            if (!is_null($overlay->minScale) && 
                $msLayer->minScale != $overlay->minScale) {
                $result->minScale = $overlay->minScale;
                $msLayer->set('minscale', $result->minScale);
            }
            if (!is_null($overlay->transparency) && 
                $msLayer->transparency != $overlay->transparency) {
                $result->transparency = $overlay->transparency;
                $msLayer->set('transparency', $result->transparency);
            }
            if (!is_null($overlay->type) && 
                $msLayer->type != $overlay->type) {
                $result->type = $overlay->type;
                $msLayer->set('type', $result->type);
            }
            break;

        case BasicOverlay::ACTION_SEARCH:
            $nFound = 0;
            for ($i = 0; $i < $this->mapObj->numlayers; $i++) {
                $msSearchLayer = $this->mapObj->getLayer($i);
                if (substr($msSearchLayer->name, 0, strlen($overlay->name) + 2)
                    == $overlay->name . '@@' || $msSearchLayer->name == $overlay->name) {
                    $nFound ++;
                    
                    // Checking properties
                    if ((is_null($overlay->connection) || 
                         $msSearchLayer->connection == $overlay->connection) &&
                        (is_null($overlay->connectionType) || 
                         $msSearchLayer->connectionType == $overlay->connectionType) &&
                        (is_null($overlay->data) || 
                         $msSearchLayer->data == $overlay->data) &&
                        (is_null($overlay->maxScale) ||
                         $msSearchLayer->maxScale == $overlay->maxScale) &&
                        (is_null($overlay->minScale) ||
                         $msSearchLayer->minScale == $overlay->minScale) &&
                        (is_null($overlay->transparency) || 
                         $msSearchLayer->transparency == $overlay->transparency) &&
                        (is_null($overlay->type) || 
                         $msSearchLayer->type == $overlay->type)) {
                         $result->name = $msSearchLayer->name;
                         $msLayer = $msSearchLayer;
                    }
                }            
            }
            if ($nFound == 0) {
                throw new CartoserverException('search Layer: no layers found with that name');
            }
            
            if (is_null($msLayer)) {
                // No layers found, adding a layer.
                $original = $this->mapObj->getLayerByName($overlay->name);
                $msLayer = ms_newLayerObj($this->mapObj, $original);
                
                $result->name = $overlay->name . '@@' . $nFound;
                $msLayer->set("name", $result->name);
                
                //Setting new properties
                if (!is_null($overlay->connection) &&
                    $msLayer->connection != $overlay->connection) {
                    $result->connection = $overlay->connection;
                    $msLayer->set('connection', $result->connection);
                }
                if (!is_null($overlay->connectionType) &&
                    $msLayer->connectionType != $overlay->connectionType) {
                    $result->connectionType = $overlay->connectionType;
                    $msLayer->set('connectiontype', $result->connectionType);
                }
                if (!is_null($overlay->data) && 
                    $msLayer->data != $overlay->data) {
                    $result->data = $overlay->data;
                    $msLayer->set('data', $result->data);
                }
                if (!is_null($overlay->maxScale) && 
                    $msLayer->maxScale != $overlay->maxScale) {
                    $result->maxScale = $overlay->maxScale;
                    $msLayer->set('maxscale', $result->maxScale);
                }
                if (!is_null($overlay->minScale) && 
                    $msLayer->minScale != $overlay->minScale) {
                    $result->minScale = $overlay->minScale;
                    $msLayer->set('minscale', $result->minScale);
                }
                if (!is_null($overlay->transparency) && 
                    $msLayer->transparency != $overlay->transparency) {
                    $result->transparency = $overlay->transparency;
                    $msLayer->set('transparency', $result->transparency);
                }
                if (!is_null($overlay->type) && 
                    $msLayer->type != $overlay->type) {
                    $result->type = $overlay->type;
                    $msLayer->set('type', $result->type);
                }     
            }
            break;
            
        case BasicOverlay::ACTION_INSERT:

            $msLayer = ms_newLayerObj($this->mapObj);
            
            //setting properties
            if (!is_null($overlay->connection)) {
                $result->connection = $overlay->connection;
                $msLayer->set('connection', $result->connection);
            }
            if (!is_null($overlay->connectionType)) {
                $result->connectionType = $overlay->connectionType;
                $msLayer->set('connectiontype', $result->connectionType);
            }
            if (!is_null($overlay->data)) {
                $result->data = $overlay->data;
                $msLayer->set('data', $result->data);
            }
            if (!is_null($overlay->maxScale)) {
                $result->maxScale = $overlay->maxScale;
                $msLayer->set('maxscale', $result->maxScale);
            }
            if (!is_null($overlay->minScale)) {
                $result->minScale = $overlay->minScale;
                $msLayer->set('minscale', $result->minScale);
            }
            if (!is_null($overlay->name)) {
                $result->name = $overlay->name;
                $msLayer->set('name', $result->name);
            }
            if (!is_null($overlay->transparency)) {
                $result->transparency = $overlay->transparency;
                $msLayer->set('transparency', $result->transparency);
            }
            if (!is_null($overlay->type)) {
                $result->type = $overlay->type;
                $msLayer->set('type', $result->type);
            }
            
            // no insertLayer function in PHP MapScript. see mapserver bug #762
            $msLayer->set("status", MS_ON);
            
            // layer position 
            if (!is_null($overlay->position)) {
                $msMap = $this->serverContext->getMapObj();

                switch ($overlay->position->type) {
                case PositionOverlay::TYPE_ABSOLUTE:
                    $pos = $overlay->position->index;
                    break;
                case PositionOverlay::TYPE_RELATIVE:
                    if (is_null($overlay->position->id)) {
                        throw new CartoserverException (
                            'id cannot be null when position is relative');
                    }
                    $msSearchLayer = $msMap->getLayerByName (
                        $overlay->position->id);
                    if ($msSearchLayer == FALSE)
                        throw new CartoserverException (
                            'insert Layer: id not found');

                    $pos = $msSearchLayer->index + $overlay->position->index;
                    if ($pos < 0) {
                       $pos = 0;
                    } else if ($pos > $msMap->numlayers) {
                       $pos = $msMap->numlayers;
                    }
                    break;
                }
                
                $order = $msMap->getlayersdrawingorder();
                $newOrder = array();
                foreach ($order as $key => $value) {
                    if ($key == $pos) {
                        $newOrder[$key] = $msLayer->index;
                    } else if ($key < $pos) {
                        $newOrder[$key] = $value;
                    } else {
                        $newOrder[$key] = $order[$key - 1];
                    }
                }
                $msMap->setlayersdrawingorder($newOrder);
            }
            break;
            
        case BasicOverlay::ACTION_REMOVE:

            if ($msLayer = $this->getLayer($overlay)) {
                // no removeLayer function in PHP MapScript. see mapserver bug #762
                $msLayer->set('status', MS_DELETE);
            }
            return NULL;
            
        default:
            throw new CartoserverException('updateLayer: unknown action');
            break;
        }
        
        $result->metadatas = array();
        if (!empty($overlay->metadatas)) {
            foreach ($overlay->metadatas as $metadata) {
                $resultMetadata = $this->updateMetadata($msLayer, $metadata);
                if (!is_null($resultMetadata)) {
                    $result->metadatas[] = $resultMetadata;
                }
            }
        }
        
        $result->classes = array();
        if (!empty($overlay->classes)) {
            foreach ($overlay->classes as $class) {
                $resultClass = $this->updateClass($msLayer, $class);
                if (!is_null($resultClass)) {
                    $result->classes[] = $resultClass;
                }
            }
        }

        if (!is_null($overlay->position) && isset($pos)) {
            $result->index = $pos;
        } else {
            $result->index = $msLayer->index;
        }

        return $result;
    }
    
    /**
     * Updates mapfile using a MapOverlay or a LayerOverlay, returns the same
     * object with useful info
     * @param BasicOverlay
     * @return BasicOverlay
     */
    public function updateMap(BasicOverlay $overlay) {
        $this->mapObj = $this->serverContext->getMapObj();
        $result = new MapOverlay();
        $result->layers = array();
        
        if ($overlay instanceof LayerOverlay) {

            // Only one layer
            $resultLayer = $this->updateLayer($overlay);
            if (!is_null($resultLayer)) {
                $result->layers[] = $resultLayer;
            }
        } else if ($overlay instanceof MapOverlay) {

            // Complete map
            switch ($overlay->action) {
            case BasicOverlay::ACTION_UPDATE:
                break;
            case BasicOverlay::ACTION_SEARCH:
                break;
            case BasicOverlay::ACTION_INSERT:
            case BasicOverlay::ACTION_REMOVE:
                throw new CartoserverException('updateMap: illegal action ' .
                                               $overlay->action);
                break;
            default:
                throw new CartoserverException('updateMap: unknown action');
                break;
            }
            
            if (!empty($overlay->layers)) {
                foreach ($overlay->layers as $layer) {
                    // Process each layer
                    $resultLayer = $this->updateLayer($layer);
                    if (!is_null($resultLayer)) {
                        $result->layers[] = $resultLayer;
                    }
                }
            }
        } else {
            throw new CartoserverException('updateMap: bad parameter type');
        }
        
        return $result;
    }
}

?>
