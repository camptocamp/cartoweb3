<?php
/**
 * OgcLayerLoader plugin
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
 * @copyright 2005 Office International de l'Eau, Camptocamp
 * @package Plugins
 * @version $Id$
 */



/**
 * Server OgcLayerLoader class
 * @package Plugins
 */
class ServerOgcLayerLoader extends ClientResponderAdapter {


    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->log = LoggerManager::getLogger(__CLASS__);
    }
    

    /**
     * @see PluginBase::initialize()
     */
    public function Initialize() {
        $mapProj = $this->serverContext->getMapObj()->getProjection();
        if (empty($mapProj))
            throw new CartoserverException('Projection must be set in map ' .
                                           'file to add OGC layers!');
    }


    /**
     * @see ClientResponder::handlePreDrawing()
     * Dynamically insert OGC layers in the current mapfile
     */
    public function initializeRequest($request) {
        if (!empty($request->ogcLayers)){
                
                $map = new MapOverlay();
                $map->layers = $request->ogcLayers;
                $id = $this->getConfig()->ogcInsertLayerAfter;
            
            if (!empty($id)) {
                $index = 1;
                foreach($map->layers as $key => $layer) {
                    $layer->position = new PositionOverlay();
                    $layer->position->type = PositionOverlay::TYPE_RELATIVE;
                    $layer->position->index = $index++;
                    $layer->position->id = $id;
                    $map->layers[$key] = $layer;
                }
            }
            
            // Manage mapOverlay action
            try {
                $mapOverlay = $this->serverContext->getPluginManager()->mapOverlay;
            } catch(Exception $e) {    
                throw new CartoserverException('mapOverlay plugin not loaded, ' .
                    'and needed by ogcLayerLoader, add "mapOverlay" to your ' .
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
        
        // == UserLayer::ACTION_INSERT        
        if (!$request->userLayers[0]->action) {
            $corepluginLayers->addUserLayers($request->userLayers);
        } else {
            $corepluginLayers->removeUserLayers($request->userLayers);
        }
    
    }
}
