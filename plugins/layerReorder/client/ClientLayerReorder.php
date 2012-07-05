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
     * Layers user Opacity array indexed by msLayer name
     * @var array
     */
    public $layerUserOpacities;

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
    implements InitUser, ServerCaller, GuiProvider, Sessionable, Ajaxable {

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
     * Initial Layers Opacity array sorted
     * @var array
     */
    protected $layerOpacities;

    /**
     * Layers user Opacities array indexed by msLayer name
     * @var array
     */
    public $layerUserOpacities;

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
     * Opacity level array allowed for each Ms layer
     */
    protected $opacityLevels;


    /**
     * Constructor
     */
    public function __construct() {

        $this->log = LoggerManager::getLogger(__CLASS__);
        $this->layerUserOpacities = array();
        parent::__construct();
    }


    /**
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo,
                                  InitialMapState $initialMapState) {

        $this->layerReorderState = new LayerReorderState;
        $this->layerReorderState->layerIds = array();
        $this->layerReorderState->orderedMsLayerIds = array();
        $this->layerReorderState->layerUserOpacities = array();
    }


    /**
     * @see Sessionable::loadSession()
     */
    public function loadSession($sessionObject) {
        $this->layerIds = $sessionObject->layerIds;
        $this->layerLabels = $sessionObject->layerLabels;
        $this->orderedMsLayerIds = $sessionObject->orderedMsLayerIds;
        $this->selectedMsLayerIds = $sessionObject->selectedMsLayerIds;
        $this->layerUserOpacities 
            = $sessionObject->layerUserOpacities;
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
        $this->layerReorderState->layerUserOpacities 
            = $this->layerUserOpacities;

        return $this->layerReorderState;
    }


    /**
     * @see InitUser::handleInit()
     */
    public function handleInit($initObject) {

        // retrieve opacity config setting
        $opacityLevels = $this->getConfig()->opacityLevels;
        if (!empty($opacityLevels)) {
            $this->opacityLevels = Utils::parseArray($opacityLevels);
        } else {
            $this->opacityLevels = array('10', '25', '50', '75', '100');
        }

        // init properties from init result
        $this->orderedMsLayerIds = $initObject->layers;
        $this->setMsLayerProperties();

        // handle top and bottom exclusion setting
        $this->topLayers = array();
        $this->bottomLayers = array();

        $topLayers = $this->getConfig()->topLayers;
        if (!empty($topLayers)) {
            $this->topLayers = Utils::parseArray($topLayers);
        }

        $bottomLayers = $this->getConfig()->bottomLayers;
        if (!empty($bottomLayers)) {
            $this->bottomLayers = Utils::parseArray($bottomLayers);
        }
    }


    /**
     * Sets mapfile layers properties
     */
    protected function setMsLayerProperties() {
        $this->layerIds = array();
        $this->layerLabels = array();
        $this->layerOpacities = array();
        $corepluginLayers = 
            $this->cartoclient->getPluginManager()->getPlugin('layers');
        $layersInit = $corepluginLayers->getLayersInit();
        $layers = $layersInit->getLayers();
        foreach ($this->orderedMsLayerIds as $msLayer) {
            foreach ($layers as $layer) {
                if (isset($layer->msLayer) && $layer->msLayer == $msLayer) {
// die('<pre>Layer '.$layer->label.' opacity='.$layer->opacity.'</pre>');                    
                    $this->layerIds[] = $layer->msLayer;
                    $this->layerLabels[] = $layer->label;
                    $this->layerOpacities[] = $layer->opacity;
                    if (!isset($this->layerUserOpacities[$layer->msLayer])) {
                        $this->layerUserOpacities[$layer->msLayer]
                            = $this->getCloserOpacity($layer->opacity);
                    }
                    break;
                }
            }
        }
//okay        die('<pre>layerUserOpacities '.print_r($this->layerUserOpacities,1).'</pre>');
        
    }
    
    
    /**
     * @see ServerCaller::buildRequest()
     */
    public function buildRequest() {
        $layerReorderRequest = new LayerReorderRequest();
        $layerReorderRequest->layerIds = $this->orderedMsLayerIds;

        foreach($this->layerUserOpacities as $layer => $opacity) {
            $layerOpacity = new LayerOpacity();
            $layerOpacity->id = $layer;
            $layerOpacity->opacity = $opacity;
            $layerReorderRequest->layerOpacities[] = $layerOpacity;
        }
//okay        die('<pre>layerUserOpacities '.print_r($layerReorderRequest,1).'</pre>');
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

    /*
     * Returns the ordered list of MapServer layers ids
     * @return array
     */
    public function getOrderedMsLayerIds() {
        return $this->orderedMsLayerIds;
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
    protected function getRenderSelectedLayers() {

        // retrieve label for each msLayer
        $labels = array();
        foreach ($this->layerLabels as $key => $layer) {
            $labels[$this->layerIds[$key]] = I18n::gt($layer);
        }

        // retrieve CW3 layer ids
        $layerIds = array_flip($this->getCwLayerIds());

        // retrieve selected msLayer properties
        $selected = array();

        foreach ($this->getSelectedMsIds() as $id) {

            $selected[] = array(
                        'id' => $layerIds[$id],
                        'label' => $labels[$id],
                        'opacity' => $this->layerUserOpacities[$id]
                        );
        }

        return $selected;
    }


    /**
     * Return closer opacity value from available levels 
     * @param int opacity opacity value in map file
     * @return int
     */
    public function getCloserOpacity($opacity) {

        if (in_array($opacity, $this->opacityLevels)) {
            return $opacity;
        }

        foreach ($this->opacityLevels as $level) {
            if ($level > $opacity) {
                return $level;
            }
        }

        return $level;
    }


    /**
     * @see ServerCaller::handleResult()
     */
    public function handleResult($layerReorderResult) {
        
        if (empty($layerReorderResult->layers)) {
            return;
        }
        $this->orderedMsLayerIds = $layerReorderResult->layers;
        $this->setMsLayerProperties();
    }


    /**
     * Handles data coming from a post request
     * @param array HTTP request
     */
    public function handleHttpPostRequest($request) {

        $this->handleRequest($request);
    }


    /**
     * Handles data coming from a get request
     * @param array HTTP request
     */
    public function handleHttpGetRequest($request) {

        $this->handleRequest($request);
    }


    /**
     * Common method to handle both Get or Post request
     * @param array layers to reorder
     */
    protected function handleRequest($request) {

        if (!empty($request['layersReorderLayers'])) {
            $this->orderedMsLayerIds = 
                Utils::parseArray($request['layersReorderLayers']);

        } else if (!empty($request['layersReorder'])) {
            $layers = explode(',', $request['layersReorder']);

            $this->orderedMsLayerIds = array();
            $mapIds = $this->getCwLayerIds();

            // put config top layers on top of the stack
            foreach ($this->topLayers as $layer) {
                $this->orderedMsLayerIds[] = $mapIds[$layer];
            }
//@TODO recheck carefully with the opacity change 
            // put new ordered msLayer on the stack (IHM use reverse order...)
            $layers = array_reverse($layers, true);
            foreach ($layers as $id) {
                if(isset($request['layersOpacity_' . $id])) {
                    $this->layerUserOpacities[$this->selectedMsLayerIds[$id]]
                        = $request['layersOpacity_' . $id];
                }
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
    }

    /**
     * This method factors the plugin output for both GuiProvider::renderForm()
     * and Ajaxable::ajaxGetPluginResponse().
     * @return array array of variables and html code to be assigned
     */
    protected function renderFormPrepare() {
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        // IHM use reverse order...
        $smarty->assign('layerReorder',
            array_reverse($this->getRenderSelectedLayers(), true), true);

        if ($this->getConfig()->enableOpacity) {
            $levels = array();
            foreach($this->opacityLevels as $level) {
                $levels[$level] = sprintf('%s%%', $level);
            }
            $smarty->assign('layerOpacityOptions', $levels);
            $smarty->assign('enableOpacity', true);
        }

        return $smarty->fetch('layerReorder.tpl');
    }

    /**
     * @see GuiProvider::renderForm()
     * FIXME: when all the values in the $assignArray are to be assigned,
     *        an automatism will be created to avoid coding the same piece
     *        of code all the time. @see bug #1354
     */
    public function renderForm(Smarty $template) {
        $template->assign('layerReorder', $this->renderFormPrepare());        
    }

    /**
     * @see Ajaxable::ajaxGetPluginResponse()
     * FIXME: when all the values in the $assignArray are to be assigned,
     *        an automatism will be created to avoid coding the same piece
     *        of code all the time. @see bug #1354
     */
    public function ajaxGetPluginResponse(AjaxPluginResponse $ajaxPluginResponse) {
        $ajaxPluginResponse->addHtmlCode('gui', $this->renderFormPrepare());
    }

    /**
     * @see Ajaxable::ajaxHandleAction()
     */
    public function ajaxHandleAction($actionName, PluginEnabler $pluginEnabler) {
        switch ($actionName) {
            case 'LayerReorder.Apply':
                $pluginEnabler->disableCoreplugins();
                $pluginEnabler->enablePlugin('location');
                $pluginEnabler->enablePlugin('layers');
                $pluginEnabler->enablePlugin('images');
                $pluginEnabler->enablePlugin('layerReorder');
            break;
            case 'Layers.LayerShowHide':
            case 'Layers.LayerDropDownChange':
            default:
                $pluginEnabler->enablePlugin('layerReorder');
            break;
        }
    }

}
