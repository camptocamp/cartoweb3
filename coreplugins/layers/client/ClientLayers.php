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

    private function getLayers() {
        if(!is_array($this->layers)) {
            $mapInfo = $this->cartoclient->getMapInfo();
            $this->layers = array();
            foreach ($mapInfo->getLayers() as $layer)
                $this->layers[$layer->id] = $layer;
        }
        return $this->layers;
    }

    private function getLayerByName($layername) {
        if (isset($this->layers[$layername])) return $this->layers[$layername];
        else throw new CartoclientException("unknown layer name: $layername");
    }

    private function getNodesIds($layer) {
        if ((!isset($layer->aggregate) || !$layer->aggregate) && 
            !empty($layer->children) && is_array($layer->children)) {
            foreach ($layer->children as $child) {
                $childLayer = $this->getLayerByName($child);
                $this->getNodesIds($childLayer);
            }
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

    private function getSmartyObj() {
        if(count($this->smartyPool)) return array_shift($this->smartyPool);
        
        $this->smartyNb++;
        return new Smarty_CorePlugin($this->cartoclient->getConfig(), $this);
    }

    private function freeSmartyObj($template) {
        array_push($this->smartyPool, $template);
    }

    private function getClassChildren($layer) {
        if ($layer instanceof Layer && isset($layer->children) &&
            is_array($layer->children))
            return $layer->children;
       
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

    private function drawLayer($layer, $forceSelection = false) {
        // TODO: build switch among various layout (tree, radio, etc.)

        // if parent is selected, children are selected too!
        $layerChecked = $forceSelection ||
                        in_array($layer->id, $this->selectedLayers);

        if ((!$layer instanceof LayerGroup || 
             !isset($layer->aggregate) || !$layer->aggregate) &&
            !empty($layer->children) && is_array($layer->children)) {
            $children =& $layer->children;
        } elseif (isset($layer->aggregate) && $layer->aggregate) {
            $children = $this->getClassChildren($layer);
        } else $children = array();

        $childrenLayers = array();
        foreach ($children as $child) {
            $childLayer = $this->getLayerByName($child);
            $childrenLayers[] = $this->drawLayer($childLayer, $layerChecked);
        }

        // TODO: handle LayerClass as well. When a LayerGroup is aggregated
        // all its children's classes must however be displayed

        $template =& $this->getSmartyObj();
        $groupFolded = !in_array($layer->id, $this->unfoldedLayerGroups);

        $template->assign(array('layerLabel' => I18n::gt($layer->label),
                                'layerId' => $layer->id,
                                'layerClassName' => $layer->className,
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
                                    'expand' => 'expand tree', #i18n
                                    'close' => 'close tree', #i18n
                                    'check' => 'check all', #i18n
                                    'uncheck' => 'uncheck all', #i18n
                                    ));

        return $this->smarty->fetch('layers.tpl');
    }

    function renderForm($template) {
        if (!$template instanceof Smarty) {
            throw new CartoclientException('unknown template type');
        }

        // Change gettext domain
        $cartoclient = $this->getCartoclient();
        I18n::textdomain($cartoclient->getConfig()->mapId);

        $layersOutput = $this->drawLayersList();
        $template->assign('layers', $layersOutput);
    }

    function saveSession() {
        $this->log->debug("saving session:");
        $this->log->debug($this->layersState);

        return $this->layersState;
    }
}
?>
