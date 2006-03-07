<?php
/**
 * WMS Browser Plugin
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
 * Server WMS Browser
 * @package Plugins
 */
class ServerWmsBrowser extends ClientResponderAdapter {
    
    /**
     * Logger
     * @var string
     */
    private $log;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }
    
    public function initialize() {
        $mapProj = $this->serverContext->getMapObj()->getProjection();
        if (empty($mapProj))
            throw new CartoserverException('Projection must be set in map ' .
                                           'file to add WMS layers!');
    }
    
    /**
     * @see ClientResponder::initializeRequest
     * Dynamically insert WMS layers in the current mapfile
     */
    public function initializeRequest($request) {
        // add wms layers
        if (!empty($request->wmsLayers)) {
            $map = new MapOverlay();
            $map->layers = $request->wmsLayers;
            $id = $this->getConfig()->wmsInsertLayerAfter;
            if (empty($id)) {
                throw new CartoserverException('wmsInsertLayerAfter parameter ' .
                    'needed, set it in wmsBrowser server-side configuration file');
            }
            $index = 1;
            foreach ($map->layers as $key => $layer) {
                $layer->position = new PositionOverlay();
                $layer->position->type = PositionOverlay::TYPE_RELATIVE;
                $layer->position->index = $index++;
                $layer->position->id = $id;
                $map->layers[$key] = $layer;
            }

            try {
                $mapOverlay = $this->serverContext->getPluginManager()->mapOverlay;
            } catch(Exception $e) {    
                throw new CartoserverException('mapOverlay plugin not loaded, ' .
                    'and needed by wmsBrowser, add "mapOverlay" to your ' .
                    'server-side "loadPlugins" parameter');
            }
            $result = $mapOverlay->updateMap($map);
        }
        
        // insert added layers in layersInit object
        if (empty($request->userLayers))
            return;
        try {
            $corepluginLayers = $this->serverContext->getPluginManager()->layers;
        } catch (Exception $e) {
            throw new CartoserverException("Error accessing coreplugin layers");
        }
        $corepluginLayers->addUserLayers($request->userLayers);
    }
}
?>
