<?php
/**
 *
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
 * @version $Id$
 */
require_once(CARTOCOMMON_HOME . 'common/PluginBase.php');

/**
 * @package Server
 */
interface ClientResponder {
 
    /**
     * Handles the request at the plugin initialisation phase.
     * @param mixed plugin request
     * @see Cartoserver::doGetMap()
     */
    public function initializeRequest($requ);

    /**
     * Handles the request just before plugins should draw in the map
     * @param mixed plugin request
     * @see Cartoserver::doGetMap()
     */
    public function handlePreDrawing($requ);

    /**
     * Handles the request when the plugin should draw on the map
     * @param mixed plugin request
     * @see Cartoserver::doGetMap()
     */
    public function handleDrawing($requ);

    /**
     * Handles the request after the plugins have drawn the image
     * @param mixed plugin request
     * @see Cartoserver::doGetMap()
     */
    public function handlePostDrawing($requ);
}

/**
 * @package Server
 */
interface InitProvider {

    /**
     * Creates plugin init object that will be stored in MapInfo 
     * @return mixed plugin init object
     */
    public function getInit();
}

/**
 * @package Server
 */
interface CoreProvider {
    
    /**
     * Handles request at special places for core plugins
     * @param mixed plugin request
     * @see Cartoserver::doGetMap()
     */
    public function handleCorePlugin($requ);
}
 
/**
 * Server plugin
 * @package Server
 */
abstract class ServerPlugin extends PluginBase {

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var ServerConfig
     */
    private $config;

    /**
     * @var ServerContext
     */
    protected $serverContext;

    /**
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * @return ServerContext
     */
    public function getServerContext() {
        return $this->serverContext;   
    }

    /**
     * Initializes plugin configuration
     * @param ServerContext
     */
    public function initializeConfig($initArgs) {
        $this->serverContext = $initArgs;
        
        $this->config = new ServerPluginConfig($this->getName(),
                                     $this->serverContext->getProjectHandler());
    }

    /**
     * @return ServerConfig
     */
    final public function getConfig() {
        return $this->config;
    }
    
    /**
     * @return boolean
     */
    public function useExtendedRequest() {
        return false;
    }
    
    /**
     * @return boolean
     */
    public function useExtendedResult() {
        return false;
    }

    /**
     * @return boolean
     */
    public function useExtendedInit() {
        return false;
    }
}

/**
 * Adapter for interface {@link ClientResponder}
 *
 * This class implements all interface methods so child classes won't need
 * to add an empty method when they're not implemented.
 * @package Server
 */
abstract class ClientResponderAdapter extends ServerPlugin
                                      implements ClientResponder {

    /**
     * @see ClientResponder::initializeRequest()
     */
    public function initializeRequest($requ) {}

    /**
     * @see ClientResponder::handlePreDrawing()
     */
    public function handlePreDrawing($requ) {}

    /**
     * @see ClientResponder::handleDrawing()
     */
    public function handleDrawing($requ) {}

    /**
     * @see ClientResponder::handlePostDrawing()
     */
    public function handlePostDrawing($requ) {}
} 

?>
