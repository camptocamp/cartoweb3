<?php
/**
 * Layer Reorder Plugin
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

require_once(CARTOWEB_HOME . 'common/BasicTypes.php');


/**
 * Server Layer Reorder
 * @package Plugins
 */
class ServerLayerReorder extends ClientResponderAdapter
                         implements InitProvider {

    /**
     * @var Logger
     */
    private $log;

    /**
     * Client request
     */
    public $request;


    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->log = LoggerManager::getLogger(__CLASS__);
    }

    /**
     * @see ClientResponder::initializeRequest()
     */
    public function initializeRequest($requ) {
        $this->request = $requ;
    }

    /**
     * Send to Cartoclient current ordered layers, labels and
     * exclusion layer list
     */
    public function getInit() {
        $this->log->debug('setting layerReorder init');

        $msMapObj = $this->serverContext->getMapObj();
        $layersOrder = $msMapObj->getlayersdrawingorder();
        $layers = array();
        foreach ($layersOrder as $layer) {
            $id = $msMapObj->getLayer($layer)->name;
            if (empty($id)) continue;
            $layers[] = $id;
        }

        $layerReorderInit = new LayerReorderInit();
        $layerReorderInit->layers = $layers;

        $this->log->debug($layerReorderInit);
        return $layerReorderInit;
    }


    /**
     * Reorder MapServer layers lists and opacity from Cartoclient request
     */
    public function handlePreDrawing($requ) {

        $this->log->debug('layerReorder initializeRequest: ');
        $this->log->debug($requ);

        // update layer reorder
        $msMapObj = $this->serverContext->getMapObj();
        
        $layersOrder = $msMapObj->getlayersdrawingorder();
        foreach ($layersOrder as $layer) {
            $layerOrderIds[$layer] = $msMapObj->getLayer($layer)->name;
        }
        
        // insert user added layers in reodering layers list
        $layerIds = $requ->layerIds;
        $layerReorderResult = new LayerReorderResult();
        $first = true;    
        $key = -1; 
        $insertUserLayersAfter = -1;
        foreach ($layerOrderIds as $layer) {
            if (in_array($layer, $layerIds)) {
                $key = array_search($layer, $layerIds);
            } else {
                if ($first) {
                    $first = false;
                    if ($key != -1)
                        $insertUserLayersAfter = $key;
                }
                $userLayers[] = $layer;
            }
        }
        if (isset($userLayers)) {
            $nUserLayers = count($userLayers);
            $n = count($layerIds) - 1;
            for ($i = $n ; $i > $insertUserLayersAfter ; $i--) {
                $layerIds[$i + $nUserLayers] = $layerIds[$i];
            }
            foreach ($userLayers as $userLayer) {
                $layerIds[++$insertUserLayersAfter] = $userLayer;
            }
            $layerReorderResult->layers = $layerIds;
        }
        
        $layerOrderIds = array_flip($layerOrderIds);

        $layerReorder = array();
        foreach ($layerIds as $layerId) {
            if (isset($layerOrderIds[$layerId])) {
                $layerReorder[] = $layerOrderIds[$layerId];
            }
        }

        $msMapObj->setlayersdrawingorder($layerReorder);

        // update opacity level
        if (!empty($requ->layerOpacities)) {
//okay            die() "<pre>".print_r($requ,1)."</pre>");
            foreach ($requ->layerOpacities as $layerOpacity) {
                $layer = $msMapObj->getLayerByName($layerOpacity->id);
                if (!empty($layer)) {
                    $layer->set('opacity', $layerOpacity->opacity);
                }
            }
        }
        
        return $layerReorderResult;
    }

}
