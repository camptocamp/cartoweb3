<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * @package CorePlugins
 */
class ClientLayers extends ClientCorePlugin {
    private $log;
    private $smarty;
    private $smartyPool = array();
    private $smartyNb = 0;

    private $savedData;
    private $layersState;
    private $hiddenSelectedLayers;
    private $hiddenUnselectedLayers;
    
    private $layers;
    private $selectedLayers = array();
    private $hiddenLayers = array();
    private $unselectableLayers = array();
    private $unfoldedLayerGroups = array();
    private $unfoldedIds = array();
    private $nodeId = 0;
    private $nodesIds = array();
    private $childrenCache = array();

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    function loadSession($sessionObject) {
        $this->log->debug('loading session:');
        $this->log->debug($sessionObject);
        $this->savedData = $sessionObject;
        
        $this->layersState =& $this->savedData['layersState'];
        $this->hiddenSelectedLayers 
            =& $this->savedData['hiddenSelectedLayers'];
        $this->hiddenUnselectedLayers
            =& $this->savedData['hiddenUnselectedLayers'];
    }

    function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->log->debug('creating session:');

        $this->savedData = array();
        $this->savedData['layersState'] =& $this->layersState;
        $this->savedData['hiddenSelectedLayers']
            =& $this->hiddenSelectedLayers;
        $this->savedData['hiddenUnselectedLayers']
            =& $this->hiddenUnselectedLayers;
            
        $this->layersState = array();
        foreach ($initialMapState->layers as $initialLayerState) {
            $this->layersState[$initialLayerState->id] = $initialLayerState;
        }

        $this->hiddenUnselectedLayers = array();
        $this->hiddenSelectedLayers = $this->fetchHiddenSelectedLayers('root');
        $this->selectedLayers = array(); // resets selectedLayers array

        foreach ($this->getLayers() as $layer) {
            if (!isset($this->layersState[$layer->id])) {
                $this->layersState[$layer->id] = new LayerState;
                $this->layersState[$layer->id]->id = $layer->id;
            }
        }
    }

    /**
     * Returns the list of Layer|LayerGroup|LayerClass objects available 
     * in MapInfo.
     */
    private function getLayers() {
        if(!is_array($this->layers)) {
            $mapInfo = $this->cartoclient->getMapInfo();
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
            
            // layer has children which are aggregated OR has children
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

        $this->childrenCache[$layer->id] = $children;
        return $children;
    }

    /**
     * Recursively populates the array ('HTML id' => 'Layer id').
     */
    private function getNodesIds($layer) {
        foreach ($this->getLayerChildren($layer) as $child) {
            $childLayer = $this->getLayerByName($child);
            $this->getNodesIds($childLayer);
        }

        $this->nodesIds[] = $layer->id;
    }

    function handleHttpRequest($request) {
        $this->log->debug('update form:');
        $this->log->debug($this->layersState);

        // disables all layers before selecting correct ones
        foreach ($this->getLayers() as $layer) {
            $this->layersState[$layer->id]->selected = false;
            $this->layersState[$layer->id]->unfolded = false;
        }

        // selected layers:
        if (!@$request['layers']) $request['layers'] = array();
        $this->log->debug('requ layers');
        $this->log->debug($request['layers']);
        
        foreach ($request['layers'] as $layerId) {
            $this->layersState[$layerId]->selected = true;
        }

        // unfolded layergroups:
        $rootLayer = $this->getLayerByName('root');
        $this->getNodesIds($rootLayer);
       
        if (!@$request['openNodes']) $request['openNodes'] = false;
        $openNodes = array_unique(explode(',', $request['openNodes']));

        foreach ($openNodes as $nodeId) {
            if (isset($this->nodesIds[$nodeId]))
                $this->layersState[$this->nodesIds[$nodeId]]->unfolded = true;
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
                if (@$this->layersState[$layer->id]->$stateProperty)
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
     * Returns the list of hidden layers.
     */
    private function getHiddenLayers() {
        return $this->getMatchingLayers('hidden', 'hiddenLayers');
    }

    /**
     * Returns the list of disabled (frozen) layers.
     */
    private function getUnselectableLayers() {
        return $this->getMatchingLayers('unselectable', 'unselectableLayers');
    }

    /**
     * Recursively retrieves selected hidden layers (not transmitted by the
     * browser). Since "hidden" property is inheritated by layers
     * from their declared-as-hidden parents, those layers selection statuses
     * are retrieved as well.
     */
    private function fetchHiddenSelectedLayers($layerId, 
                                               $forceHidden = false) {
        $layer = $this->getLayerByName($layerId);
        if (!$layer || $layer instanceof LayerClass) return array();

        $hiddenSelectedLayers = array();
        
        // $forceHidden: is true if parent was hidden. As a result all its
        // children are hidden too.
        $isHidden = $forceHidden ||
                    in_array($layerId, $this->getHiddenLayers());
        if ($isHidden) {
            if (in_array($layerId, $this->getSelectedLayers()))
                $hiddenSelectedLayers[] = $layerId;
            else $this->hiddenUnselectedLayers[] = $layerId;
        }

        foreach ($layer->children as $child) {
            $newList = $this->fetchHiddenSelectedLayers($child, $isHidden);
            if ($newList) {
                $hiddenSelectedLayers = array_merge($hiddenSelectedLayers,
                                                    $newList);
                $hiddenSelectedLayers = array_unique($hiddenSelectedLayers);
            }       
        }       
        return $hiddenSelectedLayers;
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
                    !in_array($layerId, $this->hiddenUnselectedLayers))
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
        foreach ($this->hiddenSelectedLayers as $layerId) {
            $this->layersState[$layerId]->selected = true;
        }
        
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
        return new Smarty_CorePlugin($this->cartoclient->getConfig(), $this);
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
     * Deals with every single layer and recursively calls itself 
     * to build sublayers. 
     */
    private function drawLayer($layer, $forceSelection = false,
                                       $forceUnselectable = false) {
        // TODO: build switch among various layout (tree, radio, etc.)

        // if parent is selected, children are selected too!
        $layerChecked = $forceSelection ||
                        in_array($layer->id, $this->getSelectedLayers());
        $layerUnselectable = $forceUnselectable ||
                             in_array($layer->id, 
                                      $this->getUnselectableLayers());

        $childrenLayers = array();
        foreach ($this->getLayerChildren($layer) as $child) {
            $childLayer = $this->getLayerByName($child);
            $childrenLayers[] = $this->drawLayer($childLayer, $layerChecked,
                                                 $layerUnselectable);
        }

        $template =& $this->getSmartyObj();
        $groupFolded = !in_array($layer->id, $this->getUnfoldedLayerGroups());
        $layer->label = utf8_decode($layer->label);

        $template->assign(array('layerLabel'        => I18n::gt($layer->label),
                                'layerId'           => $layer->id,
                                'layerClassName'    => $layer->className,
                                'layerLink'         => $layer->link,
                                'layerChecked'      => $layerChecked,
                                'layerUnselectable' => $layerUnselectable,
                                'groupFolded'       => $groupFolded,
                                'nodeId'            => $this->nodeId++,
                                'childrenLayers'    => $childrenLayers,
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

        $this->smarty = new Smarty_CorePlugin($this->cartoclient->getConfig(),
                        $this);

        $rootLayer = $this->getLayerByName('root');
        $rootNode = $this->drawLayer($rootLayer);
        
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
        $this->log->debug($this->savedData);

        return $this->savedData;
    }
}
?>
