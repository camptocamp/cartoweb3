<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * @package CorePlugins
 */
class LayersState {
    public $layersData;
    public $hiddenSelectedLayers;
    public $hiddenUnselectedLayers;
    public $frozenSelectedLayers;
    public $frozenUnselectedLayers;
    public $nodesIds;
    public $dropDownSelected;
}

/**
 * @package CorePlugins
 */
class ClientLayers extends ClientCorePlugin {
    private $log;
    private $smarty;
    private $smartyPool = array();
    private $smartyNb = 0;

    private $layersState;
    private $layersData;
    private $hiddenSelectedLayers;
    private $hiddenUnselectedLayers;
    private $frozenSelectedLayers;
    private $frozenUnselectedLayers;
    
    private $layers;
    private $selectedLayers = array();
    private $hiddenLayers = array();
    private $frozenLayers = array();
    private $unfoldedLayerGroups = array();
    private $unfoldedIds = array();
    private $nodeId = 0;
    private $nodesIds = array();
    private $childrenCache = array();

    private $currentScale;
    private $mapId;

    const BELOW_RANGE_ICON = 'nam.png';
    const ABOVE_RANGE_ICON = 'nap.png';

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

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
     */
    private function getLayerByName($layername) {
        $layers =& $this->getLayers();

        if (isset($layers[$layername])) return $layers[$layername];
        else throw new CartoclientException("unknown layer name: $layername");
    }

    /**
     * Returns a list of current layer children, taking into account some
     * criteria such as aggregation, LayerClass name validity.
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

    function handleHttpRequest($request) {
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
     * Returns a list of layers that match passed condition.
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
     */
    private function getSelectedLayers($refresh = false) {
        return $this->getMatchingLayers('selected', 'selectedLayers', 
                                        $refresh);
    }

    /**
     * Returns the list of LayerGroups that must be rendered unfolded.
     */
    private function getUnfoldedLayerGroups() {
        return $this->getMatchingLayers('unfolded', 'unfoldedLayerGroups');
    }

    /**
     * Returns the list of explicitely hidden layers.
     */
    private function getHiddenLayers() {
        return $this->getMatchingLayers('hidden', 'hiddenLayers');
    }

    /**
     * Returns the list of explicitely frozen layers.
     */
    private function getFrozenLayers() {
        return $this->getMatchingLayers('frozen', 'frozenLayers');
    }

    /**
     * Recursively retrieves selected hidden layers (not transmitted by the
     * browser). Since "hidden" property is inheritated by layers
     * from their declared-as-hidden parents, those layers selection statuses
     * are retrieved as well.
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
     * See also fetchHiddenSelectedLayers().
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
     * Performs common recusrive job for fetchHiddenSelectedLayers() and
     * fetchFrozenSelectedLayers().
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
     * not selected
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

    function handleResult($mapResult) {}

    /**
     * Retrieves a Smarty object either by picking one in the available 
     * template objects list (smartyPool) or by getting a new instance of CW3 
     * Smarty class if no object is available.
     */
    private function getSmartyObj() {
        if(count($this->smartyPool)) return array_shift($this->smartyPool);
        
        $this->smartyNb++;
        return new Smarty_CorePlugin($this->getCartoclient()->getConfig(), $this);
    }

    /**
     * Add the Smarty object to the list of available ones (smartyPool).
     */
    private function freeSmartyObj($template) {
        array_push($this->smartyPool, $template);
    }

    /**
     * Recursively retrieves the list of Mapserver Classes bound to the layer
     * or its sublayers.
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
     * Retrieves current scale from MapResult object.
     */
    private function getCurrentScale() {
        if (!isset($this->currentScale)) {
            $this->currentScale = 
                $this->getCartoclient()->getMapResult()->locationResult->scale;
        }
        return $this->currentScale;
    }
    
    /**
     * Returns layer icon filename if any.
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
     */
    private function drawLayer($layer, $forceSelection = false,
                                       $forceFrozen = false,
                                       $layerRendering = 'tree', 
                                       $parentId = 0) {
        
        // if level is root and root is hidden (no layers menu displayed):
        if ($layer->id == 'root' && $this->layersData['root']->hidden)
            return false;

        // if parent is selected, children are selected too!
        $layerChecked = $forceSelection ||
                        in_array($layer->id, $this->getSelectedLayers());
        $layerFrozen = $forceFrozen ||
                       in_array($layer->id, $this->getFrozenLayers());

        $childrenLayers = array();
        $childrenRendering = ($layer instanceof LayerGroup && 
                              $layer->rendering) ?
                             $layer->rendering : 'tree';

        $isDropDown = ($layer instanceof LayerGroup && 
                       $layer->rendering == 'dropdown');
        
        if ($isDropDown) {
            $dropDownChildren = array();
            if (isset($this->layersState->dropDownSelected[$parentId])) {
                $dropDownSelected = 
                    $this->layersState->dropDownSelected[$parentId];
            } else
                $i = 0;
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
           
            $childrenLayers[] = $this->drawLayer($childLayer, $layerChecked,
                                                 $layerFrozen, 
                                                 $childrenRendering, 
                                                 $layer->id);
        }

        $template =& $this->getSmartyObj();
        $groupFolded = !in_array($layer->id, $this->getUnfoldedLayerGroups());
        $layer->label = utf8_decode($layer->label);
        $this->layersState->nodesIds[$this->nodeId] = $layer->id;
        $layerOutRange = 0;

        if ($isDropDown) {
            if (!isset($dropDownSelected)) $dropDownSelected = false;
            $template->assign(array('dropDownChildren' => $dropDownChildren,
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
            $template->assign('nextscale', $nextscale);
        }

        $template->assign(array('layerLabel'     => I18n::gt($layer->label),
                                'layerId'        => $layer->id,
                                'layerClassName' => $layer->className,
                                'layerLink'      => $layer->link,
                                'layerIcon'      => $layer->icon,
                                'layerOutRange'  => $layerOutRange,
                                'layerChecked'   => $layerChecked,
                                'layerFrozen'    => $layerFrozen,
                                'layerRendering' => $layerRendering,
                                'isDropDown'     => $isDropDown,
                                'groupFolded'    => $groupFolded,
                                'parentId'       => $parentId,
                                'nodeId'         => $this->nodeId++,
                                'childrenLayers' => $childrenLayers,
                                'mapId'          => $this->mapId,
                                ));
        
        if (!$groupFolded && $this->nodeId != 1) 
            $this->unfoldedIds[] = $this->nodeId - 1;
        
        $output_node = $template->fetch('node.tpl');
        $this->freeSmartyObj($template);
        return $output_node;
    }

    /**
     * Initializes layers selector interface.
     */
    private function drawLayersList() {

        $this->smarty = new Smarty_CorePlugin(
                            $this->getCartoclient()->getConfig(),
                            $this);

        $this->layersState->nodesIds = array();
        $this->mapId = $this->getCartoclient()->projectHandler->getMapName();
        
        $rootLayer = $this->getLayerByName('root');
        $rootNode = $this->drawLayer($rootLayer);
        
        if (!$rootNode) return false;

        $this->log->debug('Building of layers items: ' .
            $this->smartyNb + 1 . ' Smarty objects used.');
        
        $startOpenNodes = implode('\',\'', $this->unfoldedIds);

        $this->smarty->assign(array('layerlist' => $rootNode,
                                    'startOpenNodes' => $startOpenNodes,
                                    ));

        return $this->smarty->fetch('layers.tpl');
    }

    function renderForm($template) {
        if (!$template instanceof Smarty) {
            throw new CartoclientException('unknown template type');
        }

        $layersOutput = $this->drawLayersList();
        $template->assign('layers', $layersOutput);
    }

    function saveSession() {
        $this->log->debug('saving session:');
        $this->log->debug($this->layersState);

        return $this->layersState;
    }
}
?>
