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
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }


    /**
     * Send to Cartoclient current ordered layers, labels and
     * exclusion layer list
     */
    public function getInit() {
        $this->log->debug('setting layerReorder init');

        $msMapObj = $this->serverContext->getMapObj();
        $layersOrder = $msMapObj->getlayersdrawingorder();
        $layersInit = $this->serverContext->getMapInfo()->layersInit;

        $labels = array();
        $transparency = array();
        $extents = array();
        $mapExtent = $msMapObj->extent;

        foreach ($layersInit->getLayers() as $name => $layer) {
            if (!$layer instanceof Layer) continue;

            // retrieve layer label
            $labels[$layer->msLayer] =
                I18nNoop::gt($layersInit->getLayerById($name)->label);

            $msCurrentLayer = $layersInit->getLayerById($name)->msLayer;

            // retrieve layer transparency 
            $transparency[$layer->msLayer]
                = $msMapObj->getLayerByName($msCurrentLayer) ->transparency;
            if (empty($transparency[$layer->msLayer]))
                $transparency[$layer->msLayer] = '100';

        }


        // Init Object creation
        $layers = array();
        foreach ($layersOrder as $layer) {
            $id = $msMapObj->getLayer($layer)->name;

            $layerInit = new LayerInit();
            $layerInit->id = $id;
            $layerInit->label = $labels[$id];
            $layerInit->transparency = $transparency[$id];

            $layers[] = $layerInit;
        }

        $layerReorderInit = new LayerReorderInit();
        $layerReorderInit->layers = $layers;

        $this->log->debug($layerReorderInit);
        return $layerReorderInit;
    }


    /**
     * Reorder MapServer layers lists and transparency from CartoClient request
     */
    public function initializeRequest($requ) {

        $this->log->debug('layerReorder initializeRequest: ');
        $this->log->debug($requ);

        // Update layer reorder
        $msMapObj = $this->serverContext->getMapObj();
        $layersOrder = array_flip($msMapObj->getlayersdrawingorder());
        foreach ($layersOrder as $layer) {
            $layerOrderIds[] = $msMapObj->getLayer($layer)->name;
        }
        $layerOrderIds = array_flip($layerOrderIds);

        $layerReorder = array();
        foreach ($requ->layerIds as $layerId) {
            $layerReorder[] = $layerOrderIds[$layerId];
        }

        $msMapObj->setlayersdrawingorder($layerReorder);

        // Update transparency level
        if (!empty($requ->layerTransparencies)) {
            foreach ($requ->layerTransparencies as $layerTransparency) {
                $msMapObj->getLayerByName($layerTransparency->id)
                    ->set('transparency', $layerTransparency->transparency);
            }
        }
    }

}

?>
