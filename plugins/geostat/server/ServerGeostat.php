<?php
/**
 * Geostat plugin server classes
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
 * @copyright 2006 Camptocamp SA
 * @package Plugins
 * @version $Id$
 */


/**
 * Server Geostat
 * @package Plugins
 */
class ServerGeostat extends ClientResponderAdapter
                implements InitProvider {

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var GeostatResult
     */
    protected $geostatResult;

    /**
     * @see ServerPlugin::__construct()
     */
    public function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * @see InitProvider::getInit()
     */
    public function getInit() {
        $geostatInit = new GeostatInit();

        $geostatInit->serverConfigParams = $this->getConfLayers();

        return $geostatInit;
    }

    /**
     * @see ClientResponder::handlePreDrawing()
     */
    public function handlePreDrawing($requ) {

        $this->geostatResult = new GeostatResult();
        $this->geostatResult->choroplethParams =
            $requ->choroplethParams;

        $this->log->debug('Drawing Geostat');

        if ( $requ->status == true &&
            $requ->choroplethParams->layer &&
            $requ->choroplethParams->layer != 'def' &&
            $requ->choroplethParams->indicator &&
            $requ->choroplethParams->indicator != 'def') {
            try {
                $this->drawChoropleth($requ->choroplethParams);
            } catch (Exception $e) {
                throw new CartoserverException("Cannot draw choropleth" .
                    "\n" . $e->getMessage());

            }
        } else {
            $this->geostatResult->choroplethDrawn = false;
        }

        return $this->geostatResult;
    }

    /**
     * @see ServerGeostat::handlePreDrawing()
     * @param  GeostatChoropleth Choropleth definition in the GeostatRequest
     */
    protected function drawChoropleth($choroplethParams) {
        $this->log->
            debug(sprintf('Drawing Choropleth for layer %s with indicator %s',
            $choroplethParams->layer, $choroplethParams->indicator));

        $distribution = $this->getDistribFromSpatialData(
            $choroplethParams->layer, $choroplethParams->indicator);
        if (is_null($distribution)) {
            $this->log->warn('We got an empty distribution');;
            $this->geostatResult->choroplethDrawn = false;
            return 1;
        }

        //Initialize mapObject and Layer
        $mapObj = $this->serverContext->getMapObj();
        $msLayer = $mapObj->getLayerByName($choroplethParams->layer);

        if ($choroplethParams->nbBins < 1 ||
                $choroplethParams->nbBins > $distribution->getNbVal()) {
            $choroplethParams->nbBins = $distribution->sturgesRule();
            $this->log->debug(sprintf('Number of bins set to default : %d',
                $choroplethParams->nbBins));
            $this->geostatResult->choroplethParams->nbBins =
                $choroplethParams->nbBins;
        }
        //We must change classification method if indicator is of type string
        $msLayer->open();
        $msLayer->whichShapes($mapObj->extent);
        $shape = $msLayer->nextShape();
        $dataElem = $shape->values[$choroplethParams->indicator];
        $msLayer->close();
        if (!is_numeric($dataElem) &&
                $choroplethParams->classificationMethod !=
                Distribution::CLASSIFY_BY_MODES) {
            $choroplethParams->classificationMethod =
                Distribution::CLASSIFY_BY_MODES;
        }
        if ( is_float($dataElem) &&
                 $choroplethParams->classificationMethod ==
                 Distribution::CLASSIFY_BY_MODES) {
            $choroplethParams->classificationMethod =
                Distribution::CLASSIFY_BY_EQUAL_INTERVALS;
        }
        $classification = $distribution->classify(
            $method = $choroplethParams->classificationMethod,
            $nbBins = $choroplethParams->nbBins,
            $classOther = true,
            $bounds =$choroplethParams->bounds);

        $this->log->debug('Result of classification');
        $this->log->debug(print_r($classification,true));

        if (count($choroplethParams->colors) !=
                $choroplethParams->nbBins ||
                $choroplethParams->colorLutMethod != 0) {
            if (count($choroplethParams->colorInit) < 2) {
                $colorA = new ColorRgb(99,255,202);
                $colorB = new ColorRgb(54,38,211);
                $choroplethParams->colorInit = array($colorA, $colorB);
            }

            $colorPalette = new ColorLut($choroplethParams->colorInit);
            $choroplethParams->colors =
                $colorPalette->getColorsByWellKnownMethod(
                $choroplethParams->colorLutMethod,
                $choroplethParams->nbBins);
        }

        $overlayClasses = array();
        $classIndex = 0;
        $boundsArray = $classification->getBoundsArray();
        if ($choroplethParams->classificationMethod ==
                Distribution::CLASSIFY_BY_MODES) {
            $msLayer->set('classitem',$choroplethParams->indicator);
        }

        foreach($classification->getLabelsArray() as $label) {
            $overlayClasses[$classIndex] = new ClassOverlay();
            $overlayClasses[$classIndex]->action = BasicOverlay::ACTION_INSERT;
            $overlayClasses[$classIndex]->index = $classIndex;
            if ($choroplethParams->classificationMethod !=
                    Distribution::CLASSIFY_BY_MODES) {
                $overlayClasses[$classIndex]->expression = sprintf(
                    '([%s] >= %.20f and [%s] < %.20f)',
                    $choroplethParams->indicator,
                    $boundsArray[$classIndex],
                    $choroplethParams->indicator,
                    $boundsArray[$classIndex+1]);
            } else {
                $overlayClasses[$classIndex]->expression =
                    sprintf("%s", $label);
                if ($label == 'Others') {
                    $overlayClasses[$classIndex]->expression = '';
                }
            }
            $overlayClasses[$classIndex]->name = $label;

            $overlayColor = new ColorOverlay();
            $overlayColor->red = $choroplethParams->colors[$classIndex]->
                getColorRgb()->getRedLevel();
            $overlayColor->blue = $choroplethParams->colors[$classIndex]->
                getColorRgb()->getBlueLevel();
            $overlayColor->green = $choroplethParams->colors[$classIndex]->
                getColorRgb()->getGreenLevel();

            $overlayStyle = new StyleOverlay();
            $overlayStyle->index = 0;
            $overlayStyle->color = $overlayColor;
            $overlayClasses[$classIndex]->styles = array($overlayStyle);

            $classIndex++;
        }
        
        //Correction for the first class
        //We connot trust Mapserver for float equality
        $overlayClasses[0]->expression = sprintf(
            '([%s] >= %.20f and [%s] < %.20f)',
            $choroplethParams->indicator,
            $boundsArray[0] - 0.001,
            $choroplethParams->indicator,
            $boundsArray[1]);

        //Correction for the last class
        $classIndex--;
        if ($choroplethParams->classificationMethod !=
            Distribution::CLASSIFY_BY_MODES) {
        $overlayClasses[$classIndex]->expression=sprintf(
                    '([%s] >= %.20f and [%s] <= %.20f)',
                    $choroplethParams->indicator,
                    $boundsArray[$classIndex],
                    $choroplethParams->indicator,
                    $boundsArray[$classIndex+1]+0.001);
        }

        $layerOverlay = new LayerOverlay();
        $layerOverlay->action = BasicOverlay::ACTION_UPDATE;
        $layerOverlay->name = $choroplethParams->layer;
        $layerOverlay->classes = $overlayClasses;

        $mapOverlay = $this->serverContext->getPluginManager()->mapOverlay;
        $mapOverlay->updateMap($layerOverlay);

        //Remove the template class
        $msLayer->removeClass(0);

        $msLayer->set('status', 1);

        $mapObj->save('/tmp/debug3.map');

        $this->geostatResult->choroplethClassification = $classification;
        $this->geostatResult->choroplethStats =
            new DistributionSummary($distribution);

        $this->geostatResult->choroplethDrawn = true;
        $this->geostatResult->choroplethParams = $choroplethParams;

    }

    /**
     * @param string $layer Name of the requested layer
     * @param string $attrib Name of the requested attrib
     * @return Distribution
     */
    protected function getDistribFromSpatialData($layer, $attrib) {
        $values = array();
        $mapObj = $this->serverContext->getMapObj();
        $msLayer = $mapObj->getLayerByName($layer);
        $this->log->debug($msLayer);

        $msLayer->open();
        $bbox = new Bbox (
            $this->geostatResult->choroplethParams->bbox->minx,
            $this->geostatResult->choroplethParams->bbox->miny,
            $this->geostatResult->choroplethParams->bbox->maxx,
            $this->geostatResult->choroplethParams->bbox->maxy);
        $this->geostatResult->choroplethParams->bbox=$bbox;
        
        if($this->geostatResult->choroplethParams->bbox == new Bbox()) {
            $this->geostatResult->choroplethParams->bbox = new Bbox();
            $this->geostatResult->choroplethParams->bbox->
                setFromMsExtent($mapObj->extent);
        }
        
        $rect = ms_newRectObj();
        $rect->set('minx',$this->geostatResult->choroplethParams->bbox->minx);
        $rect->set('miny',$this->geostatResult->choroplethParams->bbox->miny);
        $rect->set('maxx',$this->geostatResult->choroplethParams->bbox->maxx);
        $rect->set('maxy',$this->geostatResult->choroplethParams->bbox->maxy);
        $msLayer->whichShapes($rect);
        try {
            while ($shape = $msLayer->nextShape()) {
                $values[] = $shape->values[$attrib];
            }
        } catch (Exception $e) {
            $msLayer->close();
            $this->geostatResult->choroplethParams->indicator = 'def';
            $values = array();
            return NULL;
        }
        $msLayer->close();

        return new Distribution($values);
    }

    /**
     * Parse geostat.ini layers parameters
     * @return array Parameters for each layer
     */
    protected function getConfLayers() {
        return ConfigParser::parseObjectArray( $this->getConfig(), 'geostat',
        array('mslayer','label','choropleth','symbols', 'choropleth_attribs',
            'choropleth_attribs_label','symbols_attribs',
            'symbols_attribs_label'));
    }
}
?>
