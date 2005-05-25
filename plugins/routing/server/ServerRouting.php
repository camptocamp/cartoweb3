<?php
/**
 * Routing plugin, server
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

require_once(CARTOSERVER_HOME . 'plugins/routing/server/RoutingModule.php');

/**
 * @package Plugins
 */
class ServerRouting extends ClientResponderAdapter {

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var array
     */
    private $shapes;

    /** 
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * May convert stops identifiers sent by client to nodes identifiers
     * useable by external routing module
     * @param array
     * @return array
     */
    protected function convertNodeIds($stops) {
        return $stops;
    }

    /**
     * May add parameters to those sent by client
     * @param array
     * @return array
     */
    protected function addParameters($parameters) {
        return $parameters;
    }

    /**
     * May generate a list of shapes to draw path on map using plugin Outline
     * @param array array of Step
     * @param array array of StyledShape 
     */
    protected function drawRoutingResult($steps) {
        return array();
    } 
    
    /**
     * May add attributes to the routing result
     * @param array array of Step
     */
    protected function addStepsAttributes($steps) {
        return $steps;
    }

    /**
     * @see ClientResponder::initializeRequest()
     */
    public function initializeRequest($requ) {
     
        $resultShapes = array();
        $resultSteps = array();
        $result = new RoutingResult();        

        if (count($requ->stops) > 0) {
            RoutingModule::init($this->getConfig());
        
            $convertedStops = $this->convertNodeIds($requ->stops);
      
            for ($i = 0; $i < count($convertedStops) - 1; $i++) {
                $parameters = $this->addParameters($requ->parameters);
         
                $steps = RoutingModule::computePath($this->getConfig(),
                                                    $convertedStops[$i],
                                                    $convertedStops[$i+1],
                                                    $parameters);
                $steps2 = $this->addStepsAttributes($steps);
                if ($steps2) {
                    $resultSteps = array_merge($resultSteps, $steps2);
                }
                $shapes = $this->drawRoutingResult($steps);
                if ($shapes) {
                    $resultShapes = array_merge($resultShapes, $shapes);
                }                                       
            }
            $result->path = $resultShapes;
            $result->steps = $resultSteps;
        } else {
            $resultShapes = $requ->path;
        }

        $this->shapes = $resultShapes;
        return $result;
    }

    /**
     * Result is set in initializeRequest but Outline must be called 
     * in handlePreDrawing 
     * @see ClientResponder::handlePreDrawing()
     */    
    public function handlePreDrawing($requ) {
        
        $pluginManager = $this->serverContext->getPluginManager();
        if (empty($pluginManager->outline))
            throw new CartoserverException("outline plugin not loaded, "
                    . "and needed for the path drawing");
        $pluginManager->outline->draw($this->shapes);        
    }
}

?>