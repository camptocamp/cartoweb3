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

    private $layersState;
    private $layers;
    private $selectedLayers = array();
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
        $this->log->debug("loading session:");
        $this->log->debug($sessionObject);
        $this->layersState = $sessionObject;
    }

    function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->log->debug("creating session:");

        $this->layersState = array();
        foreach ($initialMapState->layers as $initialLayerState) {
            $this->layersState[$initialLayerState->id] = $initialLayerState;
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
        if (isset($this->layers[$layername])) return $this->layers[$layername];
        else throw new CartoclientException("unknown layer name: $layername");
    }

    /**
     * Returns a list of current layer children, taking into account some
     * criteria such as aggregation, LayerClass name validity.
     */
    private function getLayerChildren($layer) {
        if(isset($this->childrenCache[$layer->id]))
            return $this->childrenCache[$layer->id];

        if ((!$layer instanceof LayerGroup || 
             !isset($layer->aggregate) || !$layer->aggregate) &&
            !empty($layer->children) && is_array($layer->children)) {
            $children = $this->filterAnonymLayerClasses($layer->children);
        } elseif (isset($layer->aggregate) && $layer->aggregate) {
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

        $this->getLayers();
        
        // disables all layers before selecting correct ones
        foreach ($this->layers as $layer) {
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

        // TODO: hidden layers
    }

    /**
     * Returns the list of activated layers.
     */
    private function getSelectedLayers() {
        if(!$this->selectedLayers || !is_array($this->selectedLayers)) {
            $this->getLayers();
            foreach ($this->layers as $layer) {
                if (@$this->layersState[$layer->id]->selected)
                    $this->selectedLayers[] = $layer->id;
            }
        }
        return $this->selectedLayers;
    }

    /**
     * Returns the list of LayerGroups that must be rendered unfolded.
     */
    private function getUnfoldedLayerGroups() {
        if(!$this->unfoldedLayerGroups || 
           !is_array($this->unfoldedLayerGroups)) {
            $this->getLayers();
            foreach ($this->layers as $layer) {
                if (@$this->layersState[$layer->id]->unfolded)
                    $this->unfoldedLayerGroups[] = $layer->id;
            }
        }
        return $this->unfoldedLayerGroups;
    }

    function buildMapRequest($mapRequest) {
        $layersRequest = new LayersRequest();
        $layersRequest->layerIds = $this->getSelectedLayers();
        $mapRequest->layersRequest = $layersRequest;
    }

    function handleMapResult($mapResult) {}

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
     * Removes LayerClasses with no name or empty name from current layer 
     * children list.
     */
    private function filterAnonymLayerClasses($children) {
        $validChildren = array();
        foreach ($children as $child) {
            $childLayer = $this->getLayerByName($child);
            if (!$childLayer instanceof LayerClass || 
                strlen(trim($childLayer->label)) != 0)
                $validChildren[] = $child;
        }
        return $validChildren;
    }
    

    /**
     * Recursively retrieves the list of Mapserver Classes bound to the layer
     * or its sublayers.
     */
    private function getClassChildren($layer) {
        if ($layer instanceof LayerClass && strlen(trim($layer->label)) != 0)
            return array($layer->id);
       
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
    private function drawLayer($layer, $forceSelection = false) {
        // TODO: build switch among various layout (tree, radio, etc.)

        // if parent is selected, children are selected too!
        $layerChecked = $forceSelection ||
                        in_array($layer->id, $this->selectedLayers);

        $childrenLayers = array();
        foreach ($this->getLayerChildren($layer) as $child) {
            $childLayer = $this->getLayerByName($child);
            $childrenLayers[] = $this->drawLayer($childLayer, $layerChecked);
        }

        $template =& $this->getSmartyObj();
        $groupFolded = !in_array($layer->id, $this->unfoldedLayerGroups);
        $layer->label = utf8_decode($layer->label);

        $template->assign(array('layerLabel' => I18n::gt($layer->label),
                                'layerId' => $layer->id,
                                'layerClassName' => $layer->className,
                                'layerLink' => $layer->link,
                                'layerChecked' => $layerChecked,
                                'groupFolded' => $groupFolded,
                                'nodeId' => $this->nodeId++,
                                'childrenLayers' => $childrenLayers,
                                ));
        
        if (!$groupFolded && $this->nodeId != 1) 
            $this->unfoldedIds[] = $this->nodeId - 1;
        
        $output_node = $template->fetch('node.tpl');
        $this->freeSmartyObj($template);
        return $output_node;
    }

    /**
     * Initializes layers selector interface
     */
    private function drawLayersList() {

        $this->smarty = new Smarty_CorePlugin($this->cartoclient->getConfig(),
                        $this);

        $this->getLayers();
        $this->getSelectedLayers();
        $this->getUnfoldedLayerGroups();

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
        $this->log->debug($this->layersState);

        return $this->layersState;
    }
}
?>
