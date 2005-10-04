<?php
/**
 * LayerReorder plugin
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
 * Contains the state of layerReorder
 * @package Plugins
 */
class LayerReorderState {

    /**
     * Initial Ms Layers Ids array
     * @var array
     */
    public $layerIds;

    /**
     * Initial Layers Labels array sorted
     * @var array
     */
    public $layerLabels;

    /**
     * Array of all MapServer layers id ordered
     * @var array
     */
    public $orderedMsLayerIds;
    
    /**
     * Array of MapServer layers id selected (currently displayed)
     * @var array
     */
    public $selectedMsLayerIds;

}

/**
 * Client layereReorder class
 * @package Plugins
 */
class ClientLayerReorder extends ClientPlugin
    implements InitUser, ServerCaller, GuiProvider, Sessionable {

        
    /**                    
     * @var Logger
     */
    private $log;

    /** 
     * Ms Layer Ids ordered
     * @var array
     */
    protected $orderedMsLayerIds;

    /**
     * Initial Ms Layers Ids array
     * @var array
     */
    protected $layerIds;

    /**
     * Initial Layers Labels array sorted
     * @var array
     */
    protected $layerLabels;

    /**
     * Array of MapServer layers id selected (currently displayed)
     * @var array
     */
    protected $selectedMsLayerIds;

    /**
     * LayerReorder State object (session object)
     * @var object
     */
    protected $layerReorderState;

    /**
     * Layer order exclusion list to put on top of the stack
     */
    protected $topLayers;

    /**
     * Layer order exclusion list to put on bottom of the stack
     */
    protected $bottomLayers;
    

    /**
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }
    

    /**
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo, 
                                  InitialMapState $initialMapState) { 

        $this->layerReorderState = new LayerReorderState;
        $this->layerReorderState->layers = array();
        $this->layerReorderState->orderedMsLayerIds = array();
    }


    /**
     * @see Sessionable::loadSession()
     */
    public function loadSession($sessionObject) {
        $this->layerIds = $sessionObject->layerIds;
        $this->layerLabels = $sessionObject->layerLabels;
        $this->orderedMsLayerIds = $sessionObject->orderedMsLayerIds;
        $this->selectedMsLayerIds = $sessionObject->selectedMsLayerIds;
    }


    /**
     * @see Sessionable::saveSession()
     */
    public function saveSession() {
        $this->selectedMsLayerIds = $this->getSelectedMsIds();

        $this->layerReorderState->layerIds = $this->layerIds;
        $this->layerReorderState->layerLabels = $this->layerLabels;
        $this->layerReorderState->orderedMsLayerIds = $this->orderedMsLayerIds;
        $this->layerReorderState->selectedMsLayerIds = 
            $this->selectedMsLayerIds;
        return $this->layerReorderState;
    }


    /**
     * @see InitUser::handleInit()
     */
    public function handleInit($initObject) {
        $layers = $initObject->layers;
        foreach ($layers as $layer) {
            $this->layerIds[] = $layer->id;
            $this->layerLabels[] = $layer->label;
        }

        if (empty($this->orderedMsLayerIds)) {
            $this->orderedMsLayerIds = $this->layerIds;
        }
        
        // retrieve config setting
        $this->topLayers = array();
        $this->bottomLayers = array();
        
        $topLayers = $this->getConfig()->topLayers;
        if (!empty($topLayers)) {
            $this->topLayers = array_map('trim', explode(',', $topLayers));
        }

        $bottomLayers = $this->getConfig()->bottomLayers;
        if (!empty($bottomLayers)) {
            $this->bottomLayers = array_map('trim', 
                explode(',', $bottomLayers));
        }
    }


    /**
     * @see ServerCaller::buildRequest()
     */
    public function buildRequest() {
        $layerReorderRequest = new LayerReorderRequest();
        $layerReorderRequest->layerIds = $this->orderedMsLayerIds;

        return $layerReorderRequest;
    }


    /**
     * @see ServerCaller::initializeResult()
     */
    public function initializeResult($result) {}


    /**
     * Retrieve selected layers MapServer Ids (rightly ordered)
     * @return array
     */
    protected function getSelectedMsIds() {

        $selected = array();
        $orderedSelected = array();

        // retrieve selected MapServer Ids
        $plugin = $this->cartoclient->getPluginManager()->getPlugin('layers');
        $layerIds = $plugin->getLayerIds();
        $mapIds = $this->getCwLayerIds();

        // keep selected layers
        foreach ($layerIds as $id) {
            if (!in_array($id, $this->topLayers) && 
                !in_array($id, $this->bottomLayers)) {
                    $selected[] = $mapIds[$id];
            }
        }
       
        // order them 
        foreach ($this->orderedMsLayerIds as $layer) {
            if (in_array($layer, $selected)) {
                $orderedSelected[] = $layer;
            }
        }
        
        return $orderedSelected;
    }


    /**
     * Retrieve CW3 layer ids array, with for each the corresponding MapServer 
     * layer Id as value.
     * @return array
     */
    public function getCwLayerIds() {

        $cwLayerIds = array();
        $plugin = $this->cartoclient->getPluginManager()->getPlugin('layers');
        
        foreach ($plugin->getLayersInit()->layers as $layer) {
            if ($layer instanceof Layer) {
                 $cwLayerIds[$layer->id] = $layer->msLayer;
            }
        }

        return $cwLayerIds;
    }

    
    /**
     * Return selected layer labels array, rightly ordered
     * @return array
     */
    protected function getSelectedCwLayerLabels() {

        // retrieve label for each msLayer
        $labels = array();
        foreach ($this->layerLabels as $key => $layer) {
            $labels[$this->layerIds[$key]] = I18n::gt($layer);
        }

        // retrieve selected msLayer labels
        $selected = array();
        foreach ($this->getSelectedMsIds() as $id) {
            $selected[] = $labels[$id];
        }

        return $selected;
    }
    

    /**
     * @see ServerCaller::handleResult()
     */ 
    public function handleResult($result) {}


    /**
     * Handles data coming from a post request
     * @param array HTTP request
     */
    public function handleHttpPostRequest($request) {
        if (!empty($request['layersReorder'])) {
            $this->handleRequest(explode(',', $request['layersReorder']));
        }
    }


    /**
     * Handles data coming from a get request
     * @param array HTTP request
     */
    public function handleHttpGetRequest($request) {
        if (!empty($request['layersReorder'])) {
            $this->handleRequest(explode(',', $request['layersReorder']));
        }
    }


    /**
     * Common method to handle both Get or Post request
     * @param array layers to reorder
     */
    protected function handleRequest($layers) {

        $this->orderedMsLayerIds = array();
        $mapIds = $this->getCwLayerIds();

        // put config top layers on top of the stack 
        foreach ($this->topLayers as $layer) {
            $this->orderedMsLayerIds[] = $mapIds[$layer];
        }

        // put new ordered msLayer on the stack (IHM use reverse order...)
        $layers = array_reverse($layers, true);
        foreach ($layers as $id) {
           $this->orderedMsLayerIds[] = $this->selectedMsLayerIds[$id];
        }
        
        // add to the stack all other msLayer (undisplayed ones)
        foreach ($this->layerIds as $layer) {
            if (!in_array($layer, $this->orderedMsLayerIds) && 
                !in_array($layer, $this->bottomLayers)) {
                    $this->orderedMsLayerIds[] = $layer;
            }
        }

        // end with config bottom layers
        foreach ($this->bottomLayers as $layer) {
            $this->orderedMsLayerIds[] = $mapIds[$layer];
        }
    }


    /**
     * Manages form output rendering
     * @param string Smarty template object
     */
    public function renderForm(Smarty $template) {
        
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        // IHM use reverse order...
        $smarty->assign('layerReorder', 
            array_reverse($this->getSelectedCwLayerLabels(), true), true);
        $output = $smarty->fetch('layerReorder.tpl');
        $template->assign('layerReorder', $output);
    }
    
}

?>
