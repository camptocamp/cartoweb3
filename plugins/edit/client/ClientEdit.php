<?php
/**
 * Edit plugin
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
 * Contains the state of an edition.
 * @package Plugins
 */
class EditState {
    /** 
     * List of editable layers
     * @var string
     */
    public $layersList;
    
    /**
     * Cartoweb layer selected for edition
     * @var string
     */
    public $layer;
    
    /**
     * Shape Ids from get
     * @var string
     */
    public $featuresIds;
    
    /**
     * Shape type of the mapserver layer
     * @var string
     */
     public $shapeType;
     
    /**
     * List of attribute names of the selected layer
     * @var array
     */
    public $attributeNames;
    
    
    /**
     * List of attribute types of the selected layer
     * @var array
     */
    public $attributeTypes;
    
    /**
     * features
     * @var array
     */
    public $features;
    
    /**
     * Snapping selected for edition
     * @var boolean
     */
    public $snapping;

}

/**
 * Client Edit class
 * @package Plugins
 */
class ClientEdit extends ClientPlugin
                 implements Sessionable, GuiProvider, ServerCaller,
                    ToolProvider, FilterProvider {

    /**                    
     * @var Logger
     */
    private $log;
    
    /**
     * @var EditState
     */
    private $editState;
    
    /**
     * @var EditGeneral
     */
    private $general;
    
    const TOOL_POINT = 'edit_point';
    const TOOL_LINE = 'edit_line';
    const TOOL_POLYGON = 'edit_polygon';
    const TOOL_MOVE = 'edit_move';
    const TOOL_DEL_VERTEX = 'edit_del_vertex';
    const TOOL_ADD_VERTEX = 'edit_add_vertex';
    const TOOL_SEL = 'edit_sel';
    const TOOL_DEL_FEATURE = 'edit_del_feature';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }
    
    /*
     * @see FilterProvider
     */
    public function filterPostRequest(FilterRequestModifier $request) {
        // force edit_layer draw
        if (is_array ($request->getValue('layers')))
            $layers = $request->getValue('layers');
        else
            $layers = array();

        if (!is_null($request->getValue('edit_layer'))) {
            $layers = array_merge($layers, array($request->getValue('edit_layer')));
            $request->setValue('layers', $layers);
        }
    }
    
    /*
     * @see FilterProvider
     */
    public function filterGetRequest(FilterRequestModifier $request) {     
    }
    
    /**
     * Returns an array from a comma-separated list string.
     * @param array
     * @param boolean (default: false) true: returns a simplified array
     * @return array
     */
    protected function getArrayFromList($list, $simple = false) {
        $list = explode(',', $list);
        $res = array();
        foreach ($list as $d) {
            $d = trim($d);
            if ($simple) $res[] = $d;
            else $res[$d] = I18n::gt($d);
        }
        return $res;
    }

    /**
     * Returns an array from a comma-separated list of a ini parameter.
     * @param string name of ini parameter
     * @param boolean (default: false) true: returns a simplified array
     * @return array
     */
    protected function getArrayFromIni($name, $simple = false) {
        $data = $this->getConfig()->$name;
        if (!$data) return array();

        return $this->getArrayFromList($data, $simple);
    }
    
    /**
     * @see Sessionable::loadSession()
     */
    public function loadSession($sessionObject) {
        $this->editState = $sessionObject;
    }

    /**
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->editState = new EditState();
    }

    /**
     * @see Sessionable::saveSession()
     */
    public function saveSession() {
        return $this->editState;
    }
    
    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {
        
        $shape = $this->cartoclient->getHttpRequestHandler()->handleTools($this);
        $this->shapes = array();
        if ($shape) {
            $this->shapes[] = $shape;
        }
        
        $layer = $this->getHttpValue($request, 'edit_layer');
        if ($layer != '0' && $this->editState->layer != $layer) {
            $this->editState->features = array();
            $this->editState->layer = $layer;
            $this->editState->shapeType = null;
        }
            
        $this->editState->snapping = $this->getHttpValue($request, 'edit_snapping');
        
        // selection tool selected
        $tool = $this->getHttpValue($request, 'tool');
        $this->editSelection = ($tool == 'edit_sel' && $shape);
        
        // clicked on validate button, if not, navigation tool used so don't update or insert features
        $this->editValidateAll = $this->getHttpValue($request, 'edit_validate_all');
        
        // clear the editState features on cancel
        if (!empty($request['edit_cancel'])) {
            $this->editState->features = array();
        }
        
        foreach ($request as $key=>$value) {
            if (strpos($key, "edit_feature_") === false)
                continue;

            if (isset ($value['operation'])) {
                $id = substr($key, strlen("edit_feature_"));
                $this->updateFeaturesArray($id, $value);
            }
        }
    }
    
    /**
     * Updates the editState features array
     * @var $id feature id
     * @var $value array of values to affect to the feature
     */
    protected function updateFeaturesArray($id, $value) {
        // feature already exists in editState        
        if (array_key_exists($id, $this->editState->features))
            $feature = $this->editState->features[$id];
        else {
            $feature = new Feature();
            $feature->id = $id;
        }
        
        if (isset ($value['WKTString']))
            $feature->WKTString = $value['WKTString'];
        
        if (isset ($value['operation']))
            $feature->operation = $value['operation'];
        
        foreach ($value as $attribute=>$val) {
            // all values but "WKTString" and "operation" are feature attributes
            if ($attribute == 'WKTString' || $attribute == 'operation')
                continue;
            $feature->attributes[$attribute] = $val;
        }
        $this->editState->features[$id] = $feature;
    }
    
    /**
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {
        // layer to edit selected by url
        if (isset ($_GET['edit_layer_id']))
           $this->editState->layer = $_GET['edit_layer_id'];
        // features to edit selected by url
        if (isset ($_GET['edit_features_ids']))
           $this->featuresIds = $_GET['edit_features_ids'];
    }
    
    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {
        // authentification
        $editRoles = $this->getArrayFromIni('general.allowedRoles', true);
        $allowed = SecurityManager::getInstance()->hasRole($editRoles);
        
        // max number of new features
        if ($this->getConfig()->insertedFeaturesMaxNumber)
            $edit_max_insert = $this->getConfig()->insertedFeaturesMaxNumber;
        else
            $edit_max_insert = 0;
       
        $template->assign(array('edit_active' => true,
                               'edit_allowed' => $allowed,
                               'edit_snapping' => $this->editState->snapping,
                               'edit_shape_type' => $this->editState->shapeType,
                               'edit_max_insert' => $edit_max_insert));

                               
        // editable layers list
        $layersList =  $this->getConfig()->editLayers;
        
        $layersInit = $this->cartoclient->getMapInfo()->layersInit;
        $layersId = array();
        $layersLabel = array();
        
        if (!empty($layersList)) {
            $layersList = Utils::parseArray($layersList);
        } else return;
        
        foreach($layersInit->getLayers() as $layer) {
            if (! $layer instanceof Layer)
                continue;
            if (!in_array($layer->id, $layersList))
                continue;
            $layersId[] = $layer->id;
            $layersLabel[] = I18n::gt($layer->label); 
        }

        $template->assign(array('edit_layers_id' => $layersId,
                                'edit_layers_label' => $layersLabel,
                                'edit_layer_selected' => $this->editState->layer));

        // get attributes of the different features
        foreach ($this->editState->features as $feature) {
            $str = "";
            foreach ($feature->attributes as $key=>$val) {
                if (in_array($key, $this->editState->attributeNames))
                    $str .= "\"".$val."\",";
            }
            $str = substr($str, 0, strlen($str) - 1);
            $feature->attributesAsString = $str;
        }

        if (isset($this->editState->features) && $this->editState->features) {
            $template->assign(array('object_selected' => true, // not used yet
                                    'features' => $this->editState->features
                             ));
        }
        
        // get the attributes names list
        if (isset($this->editState->attributeNames) && $this->editState->attributeNames) {
            $str = "";
            foreach ($this->editState->attributeNames as $val)
                $str .= "\"".$val."\",";
            $str = substr($str, 0, strlen($str) - 1);
            // TODO internationalisation of field names
            $template->assign('attribute_names', $str);
        }
        
        // get the attributes types list
        if (isset($this->editState->attributeTypes) && $this->editState->attributeTypes) {
            $str = "";
            foreach ($this->editState->attributeTypes as $val)
                $str .= "\"".$val."\",";
            $str = substr($str, 0, strlen($str) - 1);
            $template->assign('attribute_types', $str);
        }
    }
    
    /**
     * @see ServerCaller::buildRequest()
     */
    public function buildRequest() {
        
        if (!isset ($this->editState->features))
            $this->editState->features = array();
            
        if ((isset($this->editSelection) && $this->editSelection)
          || isset($this->featuresIds)
          || isset($this->editValidateAll) &&  $this->editValidateAll
          || !isset($this->editState->shapeType)) {
            $editRequest = new EditRequest();
            if (isset($this->shapes) && isset($this->editSelection))
                $editRequest->shapes = $this->shapes;
            if (isset($this->editState->layer))
                $editRequest->layer = $this->editState->layer;
            if (isset($this->featuresIds))
                $editRequest->featuresIds = $this->featuresIds;
            if (isset($this->editState->features))
                $editRequest->features = $this->editState->features;
            if (isset($this->editValidateAll))
                $editRequest->validateAll = $this->editValidateAll;
                
            return $editRequest;
        }
    }

    /**
     * @see ServerCaller::initializeResult()
     */ 
    public function initializeResult($editResult) {
    }

    /**
     * @see ServerCaller::handleResult()
     */ 
    public function handleResult($editResult) {
        if (isset($editResult->shapeType))
            $this->editState->shapeType = $editResult->shapeType;
        if (isset($editResult->features) && !empty($editResult->features)) {
            foreach ($editResult->features as $feature) {
                $this->editState->features[$feature->id] = $feature;
            }
        }
        if (isset($editResult->attributeNames))
            $this->editState->attributeNames = $editResult->attributeNames;
        if (isset($editResult->attributeTypes))
            $this->editState->attributeTypes = $editResult->attributeTypes;
            
        // clear the editState on validation
        if (isset($this->editValidateAll) && $this->editValidateAll) {
            $this->editState->features = array();
        }
    }
    
    /**
     * @see ToolProvider::handleMainmapTool()
     */
    public function handleMainmapTool(ToolDescription $tool, 
                               Shape $mainmapShape) {
        return $mainmapShape;
    }
    
    /**
     * @see ToolProvider::handleKeymapTool()
     */
    public function handleKeymapTool(ToolDescription $tool, 
                            Shape $keymapShape) {
        /* nothing to do with the keymap */
    }
    
    /**
     * @see ToolProvider::handleApplicationTool()
     */
    public function handleApplicationTool(ToolDescription $tool) {
    }

    /**
     * Returns the edit tool : Point, Line and Polygon
     * Corresponds to the shapeType of the editLayer
     * @see ToolProvider::getTools()
     * @return array array of ToolDescription
     */
    public function getTools() {
        $editRoles = $this->getArrayFromIni('general.allowedRoles', true);
        $allowed = SecurityManager::getInstance()->hasRole($editRoles);
        
        $toolsArray = array();
        
        if (!$allowed) return $toolsArray;
        
        switch ($this->editState->shapeType) {
            case 'POINT':
                $toolsArray[] = new ToolDescription(self::TOOL_POINT, true,
                            90);
                break;
            case 'LINESTRING':
                $toolsArray[] = new ToolDescription(self::TOOL_LINE, true,
                            91);
                break;
            case 'POLYGON':
                $toolsArray[] = new ToolDescription(self::TOOL_POLYGON, true, 
                            92);
                break;
            default:
                break;
        }
        
        // TODO modify current tool if not any more in the toolbar
        // real case : user selected an editing tool and changes editing layer
        // with different type
        
        if ($this->editState->layer) {
            $toolsArray[] = new ToolDescription(self::TOOL_MOVE, true, 93);
            $toolsArray[] = new ToolDescription(self::TOOL_DEL_VERTEX, true, 94);
            $toolsArray[] = new ToolDescription(self::TOOL_ADD_VERTEX, true, 95);
            $toolsArray[] = new ToolDescription(self::TOOL_SEL, true, 96);
            $toolsArray[] = new ToolDescription(self::TOOL_DEL_FEATURE, true, 97);
        }
        return $toolsArray;
    }
}
