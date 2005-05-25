<?php
/**
 * Routing plugin, interface to external modules
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
 * External routing module chooser
 *
 * The module is selected using .ini's RoutingModuleClass.
 * @package Plugin
 */
class RoutingModule {
    
    /**
     * Routing module
     * @var RoutingModuleInterface
     */
    static private $routing;    
    
    /**
     * Initializes Routing module
     * @param ServerPluginConfig
     */
    static public function init(ServerPluginConfig $config) {
        self::$routing = new $config->routingModuleClass;
    }
    
    /**
     * Calls module's computePath
     * @param ServerPluginConfig
     * @param string node 1
     * @param string node 2
     * @param array array of key-value parameters
     * @return array array of Step
     */
    static public function computePath(ServerPluginConfig $config,
                                       $node1, $node2, $parameters) {
        return self::$routing->computePath($config, $node1, $node2, $parameters);
    }
}

/**
 * Routing module interface
 * @package Plugin
 */
interface RoutingModuleInterface {

    /**
     * Computes path
     * @param ServerPluginConfig
     * @param string node 1
     * @param string node 2
     * @param array array of key-value parameters
     * @return array array of Step
     */ 
    public function computePath(ServerPluginConfig $config,
                                $node1, $node2, $parameters);    
}

/**
 * Java routing module
 * @package Plugin
 */
class GeoToolsRouting implements RoutingModuleInterface {

    /**
     * Converts Java array of steps into PHP
     * @param Java array
     * @return array
     */
    private function convertSteps($javaSteps) {
        
        $steps = array();
        if (is_null($javaSteps) || $javaSteps->size() == 0) {
            return $steps;
        }
        $javaIterator1 = $javaSteps->iterator();
        while ($javaIterator1->hasNext()) {
            
            $javaStep = $javaIterator1->next();
            $step = null;
            $attributes = array();
            if ($javaStep->getClass()->getName()
                == 'org.cartoweb.routing.RoutingNode') {
                $step = new Node();
                $attributes['id'] = $javaStep->getId();                
            } else if ($javaStep->getClass()->getName()
                       == 'org.cartoweb.routing.RoutingEdge') {
                $step = new Edge();
                $attributes['id1'] = $javaStep->getNodeAId();
                $attributes['id2'] = $javaStep->getNodeBId();
            }
            
            $javaIterator2 = $javaStep->getAttributes()->entrySet()->iterator();
            while ($javaIterator2->hasNext()) {
                $javaEntry = $javaIterator2->next();
                $attributes[$javaEntry->getKey()] = $javaEntry->getValue();
            } 
            $step->attributes = $attributes;
            $steps[] = $step;
        }
        return $steps;
    }

    /**
     * @see RoutingModuleInterface::computePath()
     */
    public function computePath(ServerPluginConfig $config,
                                $node1, $node2, $parameters) {

        $projectHandler = $config->projectHandler;
        $projectRouting = "";
        if ($projectHandler->isProjectFile("plugins/routing/server/routing.jar")) {
            $projectRouting = CARTOSERVER_HOME
                              . $projectHandler->getPath("plugins/routing/server/routing.jar")
                              . ";";
        }

        java_set_library_path($projectRouting . CARTOSERVER_HOME . "plugins/routing/server/routing.jar;" .
                              CARTOSERVER_HOME . "include/geotools/module/gt2-main.jar;" .
                              CARTOSERVER_HOME . "include/geotools/plugin/shapefile/gt2-shapefile.jar;" .
                              CARTOSERVER_HOME . "include/geotools/shared/JTS-1.4.jar;" .
                              CARTOSERVER_HOME . "include/geotools/shared/geoapi-20050118.jar;" .
                              CARTOSERVER_HOME . "include/geotools/extension/graph/gt2-graph.jar");

        try { 
            $javaParameters = new Java("java.util.HashMap");
            foreach ($parameters as $key => $value) {
                $javaParameters->put($key, $value);
            }
            $external = new Java('org.cartoweb.routing.ExternalRoutingModule');         
            $javaPath = $external->computePath($node1,
                                               $node2,
                                               $javaParameters,
                                               $config->routingDataType,
                                               "file://" . CARTOSERVER_HOME . $config->routingNodesSource,
                                               "file://" . CARTOSERVER_HOME . $config->routingEdgesSource);

            return $this->convertSteps($javaPath);                                             
        } catch (Exception $e) {
            throw new CartoclientException($e->getMessage());
        }    
    }
}

?>