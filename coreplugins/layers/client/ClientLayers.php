<?php
/**
 * Layers selection interface
 * @package CorePlugins
 * @author Sylvain Pasche, Alexandre Saunier
 * @version $Id$
 */

/**
 * Container for session-saved data. See also {@link ClientLayers}.
 * @package CorePlugins
 */
class LayersState {
   
    /**
     * @var array
     */
    public $layersData;
    
    /**
     * @var array
     */
    public $hiddenSelectedLayers;

    /**
     * @var array
     */
    public $hiddenUnselectedLayers;
    
    /**
     * @var array
     */
    public $frozenSelectedLayers;
    
    /**
     * @var array
     */
    public $frozenUnselectedLayers;
    
    /**
     * @var array
     */
    public $nodesIds;
    
    /**
     * @var array
     */
    public $dropDownSelected;
}

/**
 * Handles layers selection interface
 * @package CorePlugins
 */
class ClientLayers extends ClientPlugin
                   implements Sessionable, GuiProvider, ServerCaller {
    /**
     * @var Logger
     */
    private $log;
    
    /**
     * @var Smarty_CorePlugin 
     */
    private $smarty;

    /**
     * @var LayersState
     */
    private $layersState;

    /**
     * List of LayerState objects. See {@link LayerState}.
     * @var array
     */
    private $layersData;
    
    /**
     * @var array
     */
    private $hiddenSelectedLayers;
    
    /**
     * @var array
     */
    private $hiddenUnselectedLayers;

    /**
     * @var array
     */
    private $frozenSelectedLayers;
    
    /**
     * @var array
     */
    private $frozenUnselectedLayers;
    
    /**
     * @var array
     */
    private $layers;
    
    /**
     * @var array
     */
    private $selectedLayers = array();
    
    /**
     * @var array
     */
    private $hiddenLayers = array();
    
    /**
     * @var array
     */
    private $frozenLayers = array();
    
    /**
     * @var array
     */
    private $unfoldedLayerGroups = array();
    
    /**
     * @var array
     */
    private $unfoldedIds = array();
    
    /**
     * Incrementor for node (layer) id in displayed interface. 
     * @var int
     */
    private $nodeId = 0;

    /**
     * @var array
     */
    private $nodesIds = array();
    
    /**
     * @var array
     */
    private $childrenCache = array();

    /**
     * @var float
     */
    private $currentScale;
    
    /**
     * @var string
     */
    private $mapId;

    const BELOW_RANGE_ICON = 'nam.png';
    const ABOVE_RANGE_ICON = 'nap.png';

    /**
     * Constructor
     */
    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    /**
     * Retrieves session-saved layers data.
     * @see Sessionable::loadSession()
     */
    function loadSession($sessionObject) {
        $this->log->debug('loading session:');
        $this->log->debug($sessionObject);
        $this->layersState = $sessionObject;
        
        $this->layersData =& $this->layersState->layersData;
        
        $this->hiddenSelectedLayers 
            =& $this->layersState->hiddenSelectedLayers;
        
        $this->hiddenUnselectedLayers
            =& $this->layersState->hiddenUnselectedLayers;
        
        $this->frozenSelectedLayers
            =& $this->layersState->frozenSelectedLayers;
        
        $this->frozenUnselectedLayers
            =& $this->layersState->frozenUnselectedLayers;
    }

    /**
     * Initializes layers session data and initially populates some properties.
     * @see Sessionable::CreateSession()
     */
    function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->log->debug('creating session:');

        $this->layersState = new LayersState();
        
        $this->layersState->layersData =& $this->layersData;
        
        $this->layersState->hiddenSelectedLayers =& $this->hiddenSelectedLayers;
        
        $this->layersState->hiddenUnselectedLayers
            =& $this->hiddenUnselectedLayers;
        
        $this->layersState->frozenSelectedLayers =& $this->frozenSelectedLayers;
        
        $this->layersState->frozenUnselectedLayers
            =& $this->frozenUnselectedLayers;
            
        $this->layersData = array();
        foreach ($initialMapState->layers as $initialLayerState) {
            $this->layersData[$initialLayerState->id] = $initialLayerState;
        }

        $this->hiddenUnselectedLayers = array();
        $this->hiddenSelectedLayers = $this->fetchHiddenSelectedLayers('root');

        $this->frozenUnselectedLayers = array();
        $this->frozenSelectedLayers = $this->fetchFrozenSelectedLayers('root');
        
        $this->selectedLayers = array(); // resets selectedLayers array

        foreach ($this->getLayers() as $layer) {
            if (!isset($this->layersData[$layer->id])) {
                $this->layersData[$layer->id] = new LayerState;
                $this->layersData[$layer->id]->id = $layer->id;
            }
        }
    }

    /**
     * Returns the list of Layer|LayerGroup|LayerClass objects available 
     * in MapInfo.
     * @return array
     */
    private function getLayers() {
        if(!is_array($this->layers)) {
            $mapInfo = $this->getCartoclient()->getMapInfo();
            $this->layers = array();
            foreach ($mapInfo->getLayers() as $layer)
                $this->layers[$layer->id] = $layer;
        }
        return $this->layers;
    }

    /**
     * Returns the Layer|LayerGroup|LayerClass object whose name is passed.
     * @param string name of layer
     * @return LayerBase layer object of type Layer|LayerGroup|LayerClass
     */
    private function getLayerByName($layername) {
        $layers =& $this->getLayers();

        if (isset($layers[$layername])) 
            return $layers[$layername];
        else 
            throw new CartoclientException("unknown layer name: $layername");
    }

    /**
     * Returns a list of current layer children, taking into account some
     * criteria such as aggregation, LayerClass name validity.
     * @param LayerBase layer object of type Layer|LayerGroup|LayerClass
     * @return array array of layers names
     */
    private function getLayerChildren($layer) {
        if(isset($this->childrenCache[$layer->id]))
            return $this->childrenCache[$layer->id];

        if ((!$layer instanceof LayerGroup || !isset($layer->aggregate) || 
             !$layer->aggregate) && !empty($layer->children) && 
            is_array($layer->children)) {
            
            // layer has children which are not aggregated OR has children
            // but is not a layerGroup (ie is a Layer):
            
            $children = array();
            foreach ($layer->children as $child) {
                if (!in_array($child, $this->getHiddenLayers()))
                    $children[] = $child;
            }
            
        } elseif (isset($layer->aggregate) && $layer->aggregate) {
            
            // layer is a LayerGroup with aggregated children:
            
            $children = $this->getClassChildren($layer);
        
        } else $children = array();
       
        // may impact children displaying, see method definition
        $this->fetchLayerIcon($layer, $children);

        $this->childrenCache[$layer->id] = $children;
        return $children;
    }

    /**
     * Handles layers-related POST'ed data and updates layers statuses.
     * @see GuiProvider::handleHttpPostRequest() 
     */
    function handleHttpPostRequest($request) {
        $this->log->debug('update form:');
        $this->log->debug($this->layersState);

        // disables all layers before selecting correct ones
        foreach ($this->getLayers() as $layer) {
            $this->layersData[$layer->id]->selected = false;
            $this->layersData[$layer->id]->unfolded = false;
        }

        // selected dropdowns:
        $this->layersState->dropDownSelected = array();

        // selected layers:
        if (!@$request['layers']) $request['layers'] = array();
        foreach ($request as $k => $v) {
        
            if (strstr($k, 'layers_dropdown_')) {
                $id = substr($k, 16); // 16 = strlen('layers_dropdown_')
                $this->layersState->dropDownSelected[$id] = $v;
            
            } elseif (strstr($k, 'layers_') && 
                      !in_array($v, $request['layers'])) {
            
                $request['layers'][] = $v;
                unset($request[$k]);
            }
        }
        $this->log->debug('requ layers');
        $this->log->debug($request['layers']);
 
        foreach ($request['layers'] as $layerId) {
            $this->layersData[$layerId]->selected = true;
        }

        // unfolded layergroups:
        $this->nodesIds =& $this->layersState->nodesIds;
       
        if (!@$request['openNodes']) $request['openNodes'] = false;
        $openNodes = array_unique(explode(',', $request['openNodes']));

        foreach ($openNodes as $nodeId) {
            if (isset($this->nodesIds[$nodeId]))
                $this->layersData[$this->nodesIds[$nodeId]]->unfolded = true;
        }
    }

    /**
     * Handles data from GET request. Not used/implemented yet.
     * @see GuiProvider::handleHttpGetRequest()
     */
    function handleHttpGetRequest($request) {}
    
    /**
     * Returns a list of layers that match passed condition.
     * Is in fact a code factorizer for get*Layers() methods.
     * @param string name of some LayerBase property. See {@link LayerBase}.
     * @param string name of ClientLayers property that contains data
     * @param boolean if true, refreshes storage content (default to false)
     * @return array
     */
    private function getMatchingLayers($stateProperty, $storageName,
                                       $refresh = false) {
        if($refresh || !$this->$storageName || 
           !is_array($this->$storageName)) {
            foreach ($this->getLayers() as $layer) {
                if (@$this->layersData[$layer->id]->$stateProperty)
                    $this->{$storageName}[] = $layer->id;
            }
        }
        return $this->$storageName;
    }

    /**
     * Returns the list of activated layers.
     * @param boolean optional (default: false), if true, forces result refresh
     * @return array
     */
    private function getSelectedLayers($refresh = false) {
        return $this->getMatchingLayers('selected', 'selectedLayers', 
                                        $refresh);
    }

    /**
     * Returns the list of LayerGroups that must be rendered unfolded.
     * @return array
     */
    private function getUnfoldedLayerGroups() {
        return $this->getMatchingLayers('unfolded', 'unfoldedLayerGroups');
    }

    /**
     * Returns the list of explicitely hidden layers.
     * @return array
     */
    private function getHiddenLayers() {
        return $this->getMatchingLayers('hidden', 'hiddenLayers');
    }

    /**
     * Returns the list of explicitely frozen layers.
     * @return array
     */
    private function getFrozenLayers() {
        return $this->getMatchingLayers('frozen', 'frozenLayers');
    }

    /**
     * Recursively retrieves selected hidden layers (not transmitted by the
     * browser). Since "hidden" property is inheritated by layers
     * from their declared-as-hidden parents, those layers selection statuses
     * are retrieved as well.
     * @param string layer name
     * @param boolean (default: false) if true, transmits 'hidden' status to 
     * children layers
     * @param boolean (default: false) if true, transmits 'selected' status to
     * children layers
     * @return array
     */
    private function fetchHiddenSelectedLayers($layerId, 
                                               $forceHidden = false,
                                               $forceSelected = false) {
        $layer = $this->getLayerByName($layerId);
        if (!$layer || $layer instanceof LayerClass) return array();

        return $this->fetchRecursively($layer, 'hidden',
                                       $forceHidden, $forceSelected);
    }

    /**
     * Recursively retrieves selected frozen layers.
     * @param string layer name
     * @param boolean (default: false) if true, transmits 'frozen' status to
     * children layers
     * @param boolean (default: false) if true, transmits 'selected' status to
     * children layers
     * @return array
     * @see fetchHiddenSelectedLayers()
     */
    private function fetchFrozenSelectedLayers($layerId,
                                               $forceFrozen = false,
                                               $forceSelected = false) {
        $layer = $this->getLayerByName($layerId);
        if (!$layer || $layer instanceof LayerClass ||
            in_array($layerId, $this->hiddenSelectedLayers) ||
            in_array($layerId, $this->hiddenUnselectedLayers))
            return array();

        return $this->fetchRecursively($layer, 'frozen',
                                       $forceFrozen, $forceSelected);
    }

    /**
     * Performs common recursive job for fetchHiddenSelectedLayers() and
     * fetchFrozenSelectedLayers().
     * @param LayerBase
     * @param string type of layers detection: 'hidden' or 'frozen'
     * @param boolean inherited status (see above type)
     * @param boolean inherited selection status
     * @return array
     * @see fetchHiddenSelectedLayers()
     * @see fetchFrozenSelectedLayers()
     */
    private function fetchRecursively($layer, $type, 
                                      $forceFixed, $forceSelected) {
        $getFixedLayers = 'get' . ucfirst($type) . 'Layers';
        $fixedUnselectedLayers = $type . 'UnselectedLayers';
        $fetchFixedSelectedLayers = 'fetch' . ucfirst($type) . 'SelectedLayers';
        
        $fixedSelectedLayers = array();
        
        // $forceFixed: "fixed" status is inheritated by children layers.
        // $forceSelected: is true if parent was selected...
        $isFixed = $forceFixed ||
                    in_array($layer->id, $this->$getFixedLayers());
        if ($isFixed) {
            if ($forceSelected ||
                in_array($layer->id, $this->getSelectedLayers())) {
                $isSelected = true;
                $fixedSelectedLayers[] = $layer->id;
            } else {
                $isSelected = false;
                $this->{$fixedUnselectedLayers}[] = $layer->id;
            }
        }

        foreach ($layer->children as $child) {
            $newList = $this->$fetchFixedSelectedLayers($child, $isFixed,
                                                      $isFixed && $isSelected);
            if ($newList) {
                $fixedSelectedLayers = array_merge($fixedSelectedLayers,
                                                   $newList);
                $fixedSelectedLayers = array_unique($fixedSelectedLayers);
            }
        }
        return $fixedSelectedLayers;
    }
    
    /**
     * Determines activated layers by recursively browsing LayerGroups.
     * Only keeps Layer objects that are not detected as hidden AND 
     * not selected.
     * @param array list of layers names
     * @return array list of children, grand-children... of given layers
     */
    private function fetchChildrenFromLayerGroup($layersList) {
        if (!$layersList || !is_array($layersList)) return false;

        $cleanList = array();
        foreach ($layersList as $key => $layerId) {
            $layer = $this->getLayerByName($layerId);
            if (!$layer) continue;

            // removes non Layer objects
            if ($layer instanceof Layer) {
                if (in_array($layerId, $this->getSelectedLayers()) ||
                    (!in_array($layerId, $this->hiddenUnselectedLayers) &&
                    !in_array($layerId, $this->frozenUnselectedLayers)))
                    $cleanList[] = $layerId;
                continue;
            }

            // no use to browse more if object is not a LayerGroup
            if (!$layer instanceof LayerGroup) continue;

            // recursively gets sublayers from current layer children
            $newList = $this->fetchChildrenFromLayerGroup($layer->children);
            if ($newList) {
                $cleanList = array_merge($cleanList, $newList);
                $cleanList = array_unique($cleanList);
            }
        }       
        return array_unique($cleanList);
    }

    /**
     * Sets selected layers list in MapRequest.
     * @see ServerCaller::buildMapRequest()
     */
    function buildMapRequest($mapRequest) {
        $selectedLayers = array_merge($this->hiddenSelectedLayers,
                                      $this->frozenSelectedLayers);
        foreach ($selectedLayers as $layerId)
            $this->layersData[$layerId]->selected = true;
        
        $layersRequest = new LayersRequest();
        $layersRequest->layerIds = $this->getSelectedLayers(true);
        $layersRequest->layerIds =& 
            $this->fetchChildrenFromLayerGroup($layersRequest->layerIds);
        $mapRequest->layersRequest = $layersRequest;
    }

    /**
     * @see ServerCaller::initializeResult()
     */
    function initializeResult($mapResult) {}

    /**
     * @see ServerCaller::handleResult()
     */
    function handleResult($mapResult) {}

   /**
     * Recursively retrieves the list of Mapserver Classes bound to the layer
     * or its sublayers.
     * @param LayerBase
     * @return array array of layers names
     */
    private function getClassChildren($layer) {
        if ($layer instanceof LayerClass) return array($layer->id);
       
        elseif(!isset($layer->children) || !is_array($layer->children) ||
            !$layer->children)
            return array();

        $classChildren = array();
        foreach ($layer->children as $child) {
            $childLayer = $this->getLayerByName($child);
            $sub = $this->getClassChildren($childLayer);
            $classChildren = array_merge($classChildren, $sub);
            $classChildren = array_unique($classChildren);
        }
        return $classChildren;
    }

    /**
     * Retrieves current scale from location plugin
     * @return float
     */
    private function getCurrentScale() {
        $pluginManager = $this->getCartoclient()->getPluginManager();
        if (!isset($this->currentScale) && !empty($pluginManager->location)) {
            $this->currentScale = $pluginManager->location->getCurrentScale();
        }
        return $this->currentScale;
    }
    
    /**
     * Returns layer icon filename if any.
     * @param LayerBase
     * @param array list of layer children names (default: empty array)
     * @return string
     */
    private function fetchLayerIcon($layer, &$children = array()) {
        if (!$layer->icon || $layer->icon == 'none') {
            $layer->icon = false;
        
            if ($layer instanceof Layer ||
                ($layer instanceof LayerGroup && $layer->aggregate)) {

                if ($this->setOutofScaleIcon($layer)) {
                    $children = array();
                    return $layer->icon;
                }
                
                $nbChildren = count($children);
                if (!$nbChildren) 
                    return false;

                // if layer has no icon, tries using first class icon
                $i = 0;
                do {
                    $childLayer = $this->getLayerByName($children[$i++]);
                    $layer->icon = $this->fetchLayerIcon($childLayer);
                }
                while (($layer->icon == self::BELOW_RANGE_ICON ||
                        $layer->icon == self::ABOVE_RANGE_ICON) &&
                       isset($children[$i]));

                // in addition, if layer has only one class, 
                // does not display it
                if ($nbChildren == 1 ||
                    $layer->icon == self::BELOW_RANGE_ICON ||
                    $layer->icon == self::ABOVE_RANGE_ICON) 
                    $children = array();
            }
        } elseif ($this->setOutofScaleIcon($layer))
            $children = array();
        
        return $layer->icon;
    }

    /**
     * Substitutes out-of-scale icons if current scale is out of the layer
     * range of scales.
     * @param LayerBase
     * @return boolean
     */
    private function setOutofScaleIcon($layer) {
        if ($layer->minScale && 
            $this->getCurrentScale() < $layer->minScale) {
            $layer->icon = self::BELOW_RANGE_ICON;
            return true;
        }
        
        if ($layer->maxScale &&
            $this->getCurrentScale() > $layer->maxScale) {
            $layer->icon = self::ABOVE_RANGE_ICON;
            return true;
        }
        
        return false;
    }

    /**
     * Deals with every single layer and recursively calls itself 
     * to build sublayers. 
     * @param LayerBase
     * @param boolean (default: false) if true transmits 'selected' status to
     * children
     * @param boolean (default: false) if true transmits 'frozen' status to
     * children
     * @param string (default: 'tree') rendering of layers
     * @param int (default: 0) id of parent layer in displayed interface
     * @return array array of layer children and grand-children... data 
     */
    private function fetchLayer($layer, $forceSelection = false,
                                        $forceFrozen = false,
                                        $layerRendering = 'tree', 
                                        $parentId = 0) {
        
        // if level is root and root is hidden (no layers menu displayed):
        if ($layer->id == 'root' && $this->layersData['root']->hidden)
            return array();

        $element = array();

        // if parent is selected, children are selected too!
        $layerChecked = $forceSelection ||
                        in_array($layer->id, $this->getSelectedLayers());
        $layerFrozen = $forceFrozen ||
                       in_array($layer->id, $this->getFrozenLayers());

        $childrenLayers = array();
        $element['elements'] =& $childrenLayers;
        $childrenRendering = ($layer instanceof LayerGroup && 
                              $layer->rendering) ?
                             $layer->rendering : 'tree';

        $isDropDown = ($layer instanceof LayerGroup && 
                       $layer->rendering == 'dropdown');
        
        if ($isDropDown) {
            $isRadioContainer = false;
            $dropDownChildren = array();
            if (isset($this->layersState->dropDownSelected[$parentId])) {
                $dropDownSelected = 
                    $this->layersState->dropDownSelected[$parentId];
            } else
                $i = 0;
        } else {
            $isRadioContainer = ($layer instanceof LayerGroup &&
                                 $layer->rendering == 'radio');
        }
        
        foreach ($this->getLayerChildren($layer) as $child) {
            $childLayer = $this->getLayerByName($child);
            
            if ($isDropDown) {
                $dropDownChildren[$childLayer->id] = 
                                                  I18n::gt($childLayer->label);
                
                if (isset($dropDownSelected)) {
                    if ($dropDownSelected != $childLayer->id)
                        continue; 
                } elseif ($i++) continue;
            }
           
            $childrenLayers[] = $this->fetchLayer($childLayer, $layerChecked,
                                                  $layerFrozen, 
                                                  $childrenRendering, 
                                                  $layer->id);
        }

        $groupFolded = !in_array($layer->id, $this->getUnfoldedLayerGroups());
        $layer->label = utf8_decode($layer->label);
        $this->layersState->nodesIds[$this->nodeId] = $layer->id;
        $layerOutRange = 0;

        if ($isDropDown) {
            if (!isset($dropDownSelected)) $dropDownSelected = false;
            $element = array_merge($element,
                                 array('dropDownChildren' => $dropDownChildren,
                                       'dropDownSelected' => $dropDownSelected,
                                    ));
        } else {
            $nextscale = false;
            switch($layer->icon) {
                case self::ABOVE_RANGE_ICON:
                    $layerOutRange = 1;
                    if ($layer->maxScale) $nextscale = $layer->maxScale;
                    break;

                case self::BELOW_RANGE_ICON:
                    $layerOutRange = -1;
                    if ($layer->minScale) $nextscale = $layer->minScale;
                    break;
            }
            $element['nextscale'] = $nextscale;
        }

        $element = array_merge($element,
                          array('layerLabel'       => I18n::gt($layer->label),
                                'layerId'          => $layer->id,
                                'layerClassName'   => $layer->className,
                                'layerLink'        => $layer->link,
                                'layerIcon'        => $layer->icon,
                                'layerOutRange'    => $layerOutRange,
                                'layerChecked'     => $layerChecked,
                                'layerFrozen'      => $layerFrozen,
                                'layerRendering'   => $layerRendering,
                                'isDropDown'       => $isDropDown,
                                'isRadioContainer' => $isRadioContainer,
                                'groupFolded'      => $groupFolded,
                                'parentId'         => $parentId,
                                'nodeId'           => $this->nodeId++,
                                ));
        
        if (!$groupFolded && $this->nodeId != 1) 
            $this->unfoldedIds[] = $this->nodeId - 1;
        
        return $element;
    }

    /**
     * Initializes layers selector interface.
     * @return string result of a Smarty fetch
     */
    private function drawLayersList() {

        $this->smarty = new Smarty_CorePlugin(
                            $this->getCartoclient()->getConfig(),
                            $this);

        $this->layersState->nodesIds = array();
        $this->mapId = $this->getCartoclient()->projectHandler->getMapName();
        
        $rootLayer = $this->getLayerByName('root');
        $element = $this->fetchLayer($rootLayer);
 
        if (!$element) return false;

        $startOpenNodes = implode('\',\'', $this->unfoldedIds);

        $this->smarty->assign(array('element'        => $element,
                                    'startOpenNodes' => $startOpenNodes,
                                    'mapId'          => $this->mapId,
                                    ));
                                    
        return $this->smarty->fetch('layers.tpl');
    }

    /**
     * Assigns the layers interface output in the general CartoClient template.
     * @see GuiProvider::renderForm()
     */
    function renderForm($template) {
        if (!$template instanceof Smarty) {
            throw new CartoclientException('unknown template type');
        }

        $template->assign('layers', $this->drawLayersList());
        
        $template->assign('locales', I18n::getLocales());
    }

    /**
     * Saves layers data in session.
     * @see Sessionable::saveSession()
     */
    function saveSession() {
        $this->log->debug('saving session:');
        $this->log->debug($this->layersState);

        return $this->layersState;
    }
}
?>
