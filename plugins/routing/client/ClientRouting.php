<?php
/**
 * Routing plugin, client
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
 * Contains the state of a path.
 * @package Plugins
 */
class RoutingState {

    /** 
     * @var string
     */
    public $graph = null;
    
    /** 
     * @var array
     */
    public $steps = null;
    
    /**
     * @var string
     */
    public $from = null;

    /**
     * @var string
     */
    public $to = null;

    /**
     * @var array
     */
    public $options = null;
}

/**
 * @package Plugins
 */
class ClientRouting extends ClientPlugin
                    implements Sessionable, GuiProvider, ServerCaller {

    /**
     * @var Logger
     */
    protected $log;

    /**
     * @var RoutingState
     */ 
    protected $routingState;
    
    /**
     * @var boolean
     */
    protected $doRouting;

    /** 
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->routingState = new RoutingState();

        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * @see Sessionable::loadSession()
     */
    public function loadSession($sessionObject) {
        $this->routingState = $sessionObject;
    }

    /**
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->routingState = new RoutingState();
    }
    
    /**
     * @see Sessionable::saveSession()
     */
    public function saveSession() {
        return $this->routingState;
    }
        
    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {
    
        $from = $this->getHttpValue($request, 'routing_from');
        $to   = $this->getHttpValue($request, 'routing_to');
        $options = $this->getHttpValue($request, 'routing_options');
        
        $this->doRouting = false;
        if ($from != $this->routingState->from
            || $to != $this->routingState->to
            || $options != $this->routingState->options) {
            $this->doRouting = true;
        }
        
        $this->routingState->from    = $from;
        $this->routingState->to      = $to;
        $this->routingState->options = $options;
    }

    /**
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {}
    
    /**
     * @see ServerCaller::buildRequest()
     */
    public function buildRequest() {
        
        $request = new RoutingRequest();
        $request->graph = $this->routingState->graph;
        if ($this->doRouting) {
            $request->stops = array($this->routingState->from,
                                    $this->routingState->to);
        }
        $request->parameters = array('options' => $this->routingState->options);        
        return $request;
    }

    /**
     * @see ServerCaller::initializeResult()
     */ 
    public function initializeResult($result) {
    
        if (isset($result->graph) && !is_null($result->graph)) {
            $this->routingState->graph = $result->graph;
        }
    }

    /**
     * @see ServerCaller::handleResult()
     */ 
    public function handleResult($result) {}
    
    /**
     * Draws routing specific template
     * @return string
     */ 
    protected function drawRouting() {
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $smarty->assign(array('routing_options_values' => array(0,1),
                              'routing_options_labels' => array(I18n::gt('fastest'),
                                                                I18n::gt('shortest'))));        
        $smarty->assign('routing_from',    $this->routingState->from);
        $smarty->assign('routing_to',      $this->routingState->to);
        $smarty->assign('routing_options', $this->routingState->options);
        return $smarty->fetch('routing.tpl');
    }
    
    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {
    
        $template->assign('routing_active', true);
        $template->assign('routing', $this->drawRouting());
    }
}

?>