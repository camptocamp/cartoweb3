<?php
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * Contains the state of a hilight.
 */
class HilightState {

    public $calculateArea;
}

/**
 * Client plugin for displaying hilight results (actually to show the hilight area).
 *
 * @package Plugins
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class ClientHilight extends ClientPlugin implements SessionAble, ServerCaller {

    private $hilightState;
    private $area;

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
        $this->hilightState->calculateArea = 
                            !empty($request['hilight_calculate_area']);
    }

    // dependency: has to be called AFTER selection plugin 
    function buildMapRequest($mapRequest) {

        if (!empty($mapRequest->hilightRequest)) {
            $mapRequest->hilightRequest->calculateArea = 
                                    $this->hilightState->calculateArea;
        }
    }

    function handleResult($hilightResult) {
        if (!$hilightResult instanceof HilightResult) {
            $this->area = 'N/A';
            return;
        }

        $this->area = $hilightResult->area;
    }

    function renderForm($template) {
        if (!$template instanceof Smarty) {
            throw new CartoclientException('unknown template type');
        }
        
        $smarty = new Smarty_CorePlugin($this->cartoclient->getConfig(), $this);
        $smarty->assign('hilight_calculate_area', 
                                $this->hilightState->calculateArea); 

        $smarty->assign('hilight_area', $this->area); 
        
        $hilightOutput = $smarty->fetch('hilight.tpl');          
        $template->assign('hilight_result', $hilightOutput);
    }
}
?>
