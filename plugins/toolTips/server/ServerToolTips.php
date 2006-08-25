<?php
/**
 * Server toolTips plugin
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
 * Server ToolTips class
 * @package Plugins
 */

class ServerToolTips extends ClientResponderAdapter {
    /**
     * @var Logger
     */
    private $log;

    //TODO : rename $imagemapLayers -> areaLayers ?

    /**
     * List of imagemapable layer
     * @var array
     */
    protected $imagemapLayers = array();

    /**
     * List of imagemapable layer names
     * @var array
     */
    protected $imagemapLayersNames = array();

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }
    
    /**
     * Tells if we should retrieve attributes
     * @return array array of imagemapable layers' names
     */
    protected function isAttributesRetrievable($layerId) {
        foreach ($this->imagemapLayers as $layer) {
            if ($layer['id'] == $layerId) {
                return $layer['retrieveAttributes'];
            }
        }
        return false;
    }

    /**
     * Returns list of msLayers' names with given
     * imagemapable layers' id
     * @return array array of imagemapable layers' names
     */
    protected function getImagemapLayersNames($layers) {
        if (empty($this->imagemapLayersNames)) {

            $msMapObj = $this->serverContext->getMapObj();
            $layersInit = $this->serverContext->getMapInfo()->layersInit;

            $this->imagemapLayersNames = array();
            
            foreach ($layers as $val) {
                $layer = $layersInit->getMsLayerById($msMapObj, $val['id']);
                if ($layer->status)
                    $this->imagemapLayersNames[] = $layer->name;
            }
        }
        return $this->imagemapLayersNames;
    }

    /**
     * @see ClientResponder::initializeRequest
     */
    public function initializeRequest($requ) {
        $this->imagemapLayers = $requ->imagemapLayers;
    }

    /**
     * @see ClientResponder::handlePostDrawing()
     */
    public function handlePostDrawing($requ) {
        $toolTipsResult = new ToolTipsResult();


        $toolTipsResult->imagemapFeatures = array();

        $this->prepareQuery();

        $toolTipsResult->imagemapHtmlCode = $this->computeImagemap();

        // get features attributes only if there are areas generated
        // (ie shapes on current map for imagemapable layers)
        if ($toolTipsResult->imagemapHtmlCode) {
            $toolTipsResult->imagemapFeatures = $this->computeFeatures();
        }

        return $toolTipsResult;
    }

    /**
     * Prepares the map query
     * i.e. set STATUS, set TEMPLATE for imagemapable layers
     */
    protected function prepareQuery() {
        $msMapObj = $this->serverContext->getMapObj();

        for ($i = 0; $i < $msMapObj->numlayers; $i++) {
            $layer = $msMapObj->getLayer($i);

            if (!in_array($layer->name,
                $this->getImagemapLayersNames($this->imagemapLayers))) {
                $layer->set('status', MS_OFF);
            } else {
                $this->setMsLayerTemplate($layer);
            }
        }
    }

    /**
     * Sets TEMPLATE paramater for given layer
     * @param msLayer
     */
    protected function setMsLayerTemplate($layer) {
        // TODO: check if security is good with absolute paths
        if ($layer->type == MS_LAYER_POINT) {
            $layer->set('template', realpath(dirname(__FILE__) . 
                '/../templates/points.html'));
            echo realpath(dirname(__FILE__) . '../templates/points.html');
        } else if ($layer->type == MS_LAYER_POLYGON) {
            $layer->set('template', realpath(dirname(__FILE__) . 
                '/../templates/polygons.html'));
            echo realpath(dirname(__FILE__) . '../templates/points.html');
        } else {
            throw new CartoserverException('Layer type unsupported for areas ' .
                    "in layer: $layer->name");
        }
    }

    /**
     * Builds the imagemap html code
     * @return generated html code
     */
    protected function computeImagemap() {
        $msMapObj = $this->serverContext->getMapObj();
        $rect = $msMapObj->extent;

        $res = @$msMapObj->queryByRect($rect);

        if ($res == MS_SUCCESS) {
            return $msMapObj->processquerytemplate(null, MS_FALSE);
        } else {
            return false;
        }

    }

    /**
     * Builds the imagemapable features attributes
     * @return array of features
     */
    protected function computeFeatures() {
        $msMapObj = $this->serverContext->getMapObj();

        $defaultQuerySelection = new QuerySelection();
        $defaultQuerySelection->selectedIds = array();

        $features = array();

        $layerNames = $this->getImagemapLayersNames($this->imagemapLayers);

        for ($i = 0; $i < $msMapObj->numlayers; $i++) {
            $layer = $msMapObj->getLayer($i);
            if (in_array($layer->name, $layerNames)) {
                $querySelection = clone($defaultQuerySelection);
                $querySelection->layerId = $layer->name;

                $bbox = new Bbox();
                $bbox->setFromMsExtent($msMapObj->extent);
                $features = array_merge($this->queryLayer($bbox, $querySelection),
                                        $features);
            }
        }
        return $features;
    }

    /**
     * Executes query on layer
     *
     * Query can be done using a {@link Bbox}, a list of Ids, or both
     * @param Bbox
     * @param QuerySelection
     * @return Table
     */
    protected function queryLayer($bbox, $querySelection) {
        // ID attribute
        $idAttribute = $this->serverContext
            ->getIdAttribute($querySelection->layerId);

        if (!$idAttribute)
            throw new CartoserverException('id_attribute_string not set for' .
                    " layer : $querySelection->layerId");

        
        // don't retrieve attributes for area_async layers
        if ($this->isAttributesRetrievable($querySelection->layerId)) {
            // Attributes to be returned
            $attributes = $this->getAttributes($querySelection->layerId);
        } else {
            $attributes = array();
        }
       

        // query the layer using mapquery coreplugin
        $pluginManager = $this->serverContext->getPluginManager();
        if (!empty($pluginManager->mapquery)) {
            $resultBbox = $pluginManager->mapquery
                              ->queryByBbox($querySelection->layerId, $bbox);
        }

        $layerFeatures = array();
        foreach ($resultBbox as $result) {

            $feature = new ImagemapFeature();
            $feature->setId($result->values[$idAttribute]);
            $feature->setLayer($querySelection->layerId);
            if ($result->values) {
                foreach ($attributes as $attribute) {
                    $feature->attributes[$attribute] = 
                        $result->values[$attribute];
                }
            }
            $layerFeatures[] = $feature;
        }
        return $layerFeatures;
    }

    /**
     * Returns list of attributes to be returned
     * @param string layer id
     * @return array
     */
    protected function getAttributes($layerId) {

        $msMapObj = $this->serverContext->getMapObj();

        $layersInit = $this->serverContext->getMapInfo()->layersInit;
        $msLayer = $layersInit->getMsLayerById($msMapObj, $layerId);
        if (empty($msLayer)) {
            return array();
        }

        $returnedAttributesMetadataName = 'query_returned_attributes';

        $retAttrString = $msLayer->getMetaData($returnedAttributesMetadataName);

        if (empty($retAttrString)) {
            // fallback to header property for compatibility
            $retAttrString = $msLayer->header;
            if (!empty($retAttrString))
                $this->log->warn('Using compatibility header property for layer'
                                 . " instead of $returnedAttributesMetadataName"
                                 . ' metadata field, please update your '
                                 . 'Mapfile!!');
        }
        if (empty($retAttrString)) {
            $this->log->warn('no filter for returned attributes, ' .
                             'returning everything');
            return array();
        }
        return explode(' ', $retAttrString);
    }

}

?>