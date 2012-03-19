<?php
/**
 * Interface helpers for server plugins
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
 * @package Server
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>  
 * @version $Id$
 */

/**
 * Helper for server plugin interfaces
 *
 * Helpers are called by {@link PluginManager::callPluginsImplementing()}.
 * They implement functionnalities common to all plugins implementing an
 * interface.
 * @package Server
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>  
 */
class ServerPluginHelper {
    
    /**
     * @var Logger
     */
    protected $log;

    /**
     * Constructor
     */
    public function __construct() {
        $this->log = LoggerManager::getLogger(get_class($this));
    }
    
    /**
     * Common function caller
     *
     * Retrieves plugin request and saves result in MapResult.
     * @param ServerPlugin plugin
     * @param string function name
     */
    protected function callHandleFunction($plugin, $functionName) {
        
        $this->log->debug(sprintf('Calling callHandleFunction for plugin %s', 
                                  $plugin->getName()));
        
        $serverContext = $plugin->getServerContext();
        $mapRequest = $serverContext->getMapRequest();
        $request = $plugin->getRequest(true, $mapRequest);

        $requestName = $plugin->getName() . 'Request';
        $resultName = $plugin->getName() . 'Result';

        if (is_null($request)) {
            $this->log->warn("request variable $requestName not present: " .
                             'skipping plugin ' . get_class($plugin));
            return;
        }

        $this->log->warn("calling function $functionName");
        $result = $plugin->$functionName($request);

        $request = $serverContext->getMapRequest()->$requestName;

        if ($resultName) {
            if (!$result) {
                $this->log->warn(sprintf('plugin %s getResult returned false, '
                                         . 'not storing the information',
                                         $plugin->getName()));
            } else {
                if (isset($serverContext->getMapResult()->$resultName))
                    throw new CartoserverException(sprintf(
                          'result for plugin %s already stored, data collision',
                          $plugin->getName())); 
                $serverContext->getMapResult()->$resultName = $result;
            }
        }
    }
}

/**
 * Helper for {@link ClientResponder}
 * @package Server
 */
class ClientResponderHelper extends ServerPluginHelper {

    /**
     * Sets plugin's request
     *
     * Helper method only. This won't call plugin.
     */
    final public function setRequestHelper($plugin, $request) { 
        $plugin->overriddenRequest = $request;
    }

    /**
     * Overrides plugin's request
     *
     * Helper method only. This won't call plugin.
     */
    final public function overrideRequestHelper($plugin, $mapRequest) {
        if ($plugin->overriddenRequest) {
            $requestName = $plugin->getName() . 'Request';        
            $mapRequest->$requestName = $plugin->overriddenRequest;          
        }
    }

    /**
     * @param ServerPlugin
     */
    final public function initializeRequestHelper($plugin) {
        $this->callHandleFunction($plugin, 'initializeRequest');
    }

    /**
     * @param ServerPlugin
     */
    final public function handlePreDrawingHelper($plugin) {
        $this->callHandleFunction($plugin, 'handlePreDrawing');
    }

    /**
     * @param ServerPlugin
     */
    final public function handleDrawingHelper($plugin) {
        $this->callHandleFunction($plugin, 'handleDrawing');
    }

    /**
     * @param ServerPlugin
     */
    final public function handlePostDrawingHelper($plugin) {
        $this->callHandleFunction($plugin, 'handlePostDrawing');
    }
}

/**
 * Helper for {@link InitProvider}
 * @package Server
 */
class InitProviderHelper extends ServerPluginHelper {

    /**
     * Calls plugin's {@link InitProvider::getInit} and stores the result
     * in MapInfo
     * @param ServerPlugin
     */
    final public function getInitHelper($plugin) {
        $this->log->debug(sprintf('Calling getInit for plugin %s', 
                                  get_class($plugin)));
        
        $initName = $plugin->getName() . 'Init';
        
        $init = $plugin->getInit();
        
        $plugin->getServerContext()->getMapInfo()->$initName = $init;
    }    
}

/**
 * Helper for {@link CoreProvider}
 * @package Server
 */
class CoreProviderHelper extends ServerPluginHelper {

    /**
     * @param ServerPlugin
     */
    final public function handleCorePluginHelper($plugin) {
        $this->callHandleFunction($plugin, 'handleCorePlugin');
    }
}
