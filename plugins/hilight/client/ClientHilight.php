<?php
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * Contains the state of a hilight.
 */
class HilightState {
    public $retrieveAttributes;
}

/**
 * Client plugin for displaying hilight results (actually to show the hilight
 * attributes).
 *
 * @package Plugins
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class ClientHilight extends ClientPlugin
                    implements Sessionable, GuiProvider, ServerCaller {

    private $hilightState;
    private $layerResult;

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    function loadSession($sessionObject) {
        $this->hilightState = $sessionObject;
    }

    function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->hilightState = new HilightState();
    }
    
    function saveSession() {
        return $this->hilightState;
    }

    function handleHttpRequest($request) {
        $this->hilightState->retrieveAttributes = 
                            !empty($request['hilight_retrieve_attributes']);
    }

    // dependency: has to be called AFTER selection plugin 
    function buildMapRequest($mapRequest) {

        if (!empty($mapRequest->hilightRequest)) {
            $mapRequest->hilightRequest->retrieveAttributes = 
                                    $this->hilightState->retrieveAttributes;
        }
    }

    function handleResult($hilightResult) {
        if (is_null($hilightResult)) {
            return;
        }
        $this->layerResult = $hilightResult->layerResults[0];
    }

    private function decodeResults(LayerResult $layerResult) {
        
        $labelIndex = array_search('label', $layerResult->fields);
        if ($labelIndex === false)
            return null;
        foreach ($layerResult->resultElements as $resultElement) {
            $resultElement->values[$labelIndex] = 
                                    utf8_decode($resultElement->values[0]);
        }
        return $layerResult;
    }

    function renderForm($template) {
        if (!$template instanceof Smarty) {
            throw new CartoclientException('unknown template type');
        }
        
        if (!$this->getConfig()->retrieveAttributesActive) {
            $template->assign('hilight_result', '');
            return;
        }
        
        $smarty = new Smarty_CorePlugin($this->cartoclient->getConfig(), $this);
        $smarty->assign('hilight_retrieve_attributes', 
                                $this->hilightState->retrieveAttributes); 

        if (!is_null($this->layerResult))
            $this->layerResult = $this->decodeResults($this->layerResult);
        $smarty->assign('hilight_layer_result', $this->layerResult); 
        
        $hilightOutput = $smarty->fetch('hilight.tpl');          
        $template->assign('hilight_result', $hilightOutput);
    }
}
?>
