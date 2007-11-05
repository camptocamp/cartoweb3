<?php
/**
 * Geostat plugin client classes
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
 * @package Plugins
 */
class GeostatClientState {
    /**
     * @var boolean Is geostat enabled
     */
    public $status;
    
    /**
     * @var GeostatChoropleth
     */
    public $choroplethParams;
    
    /**
     * @var boolean True if choropleth was drawn
     */
    public $choroplethDrawn = false;
    
    /**
     * @var Classification Last known classification result for choropleth
     */
    public $choroplethClassification = NULL;
    
    /**
     * @var DistributionSummary Statistics about choropleth distribution
     */
    public $choroplethStats = NULL;
    
    /**
     * @var array List of integers corresponding to 
     * classification methods avalaible
     */
    public $choroplethAvalaibleClassifMethods;
    
    /**
     * @var array List of integers corresponding to 
     * colorLut generationa methods availaible
     */
    public $choroplethAvalaibleColorLutMethods;
}


/**                                               
 * Client Geostat
 * @package Plugins
 */
class ClientGeostat extends ClientPlugin 
                implements InitUser, GuiProvider, Sessionable, 
                ServerCaller, Ajaxable {

    /**
     * @var Logger
     */
    private $log;
    
    /**
     * @var GeostatInit
     */
    protected $geostatInit;
    
    /**
     * @var GeostatClientState
     */
    protected $geostatClientState;
    
    /**
     * @see ClientPlugin::__construct()
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }
    
    /**
     * @see InitUser::handleInit()
     */
    public function handleInit($geostatInit) {
         $this->geostatInit = $geostatInit;
         $this->log->debug('Geostat Init');
         $this->log->debug(print_r($geostatInit,true));
    }
    
    /**
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo,
                                  InitialMapState $initialMapState) {
        $this->geostatClientState = new GeostatClientState();
        $this->geostatClientState->choroplethParams = 
            new GeostatChoropleth();
            
        $this->geostatClientState->choroplethParams->bbox = new Bbox();
        
        $this->geostatClientState->choroplethParams->classificationMethod =
            $this->getConfig()->choroplethClassifMethodDefault;
        $this->geostatClientState->choroplethAvalaibleClassifMethods =
            array_combine(
            explode(',',$this->getConfig()->choroplethClassifMethodsList),
            explode(',',$this->getConfig()->choroplethClassifMethodsList)
            );
        $this->geostatClientState->choroplethParams->colorLutMethod =
            $this->getConfig()->choroplethColorRampMethodDefault;
        $this->geostatClientState->choroplethAvalaibleColorLutMethods =
            array_combine(
            explode(',',$this->getConfig()->choroplethColorRampMethodList),
            explode(',',$this->getConfig()->choroplethColorRampMethodList)
            );
            
        //Default Colors
        $configColorA = explode(',', $this->getConfig()->choroplethDefaultInitialColorA);
        $configColorB = explode(',', $this->getConfig()->choroplethDefaultInitialColorB);
        if (count($configColorA) != 3) {
            // default color A
            $colorA = new ColorRgb(99,255,202);            
        } else {
            $colorA = new ColorRgb((int)$configColorA[0],
                                   (int)$configColorA[1],
                                   (int)$configColorA[2]);
        }
        if (count($configColorB) != 3) {
            // default color B
            $colorB = new ColorRgb(54,38,211);
        } else {            
            $colorB = new ColorRgb((int)$configColorB[0],
                                   (int)$configColorB[1],
                                   (int)$configColorB[2]);
        }
        $this->geostatClientState->choroplethParams->colorInit = array(
            TwColorRgbHelper::ColorRgb2TwColorRgb($colorA), 
            TwColorRgbHelper::ColorRgb2TwColorRgb($colorB));
    }
    
    /**
     * @see Sessionable::loadSession()
     */
    public function loadSession($sessionObject) {
        $this->geostatClientState = $sessionObject;
    }
     
    /**
     * @see Sessionable::saveSession()
     */
    public function saveSession() {
        return $this->geostatClientState;
    }
     
    /**
     * Handles data coming from a post request
     * @param array $request HTTP request
     */
    public function handleHttpPostRequest($request) {
        $this->handleHttpRequest($request);
    }
     
    /**
     * Handles data coming from a get request
     * @param array $request HTTP request
     */
    public function handleHttpGetRequest($request) {
        $this->handleHttpRequest($request);
    }

    /**
     * @see ClientGeostat::handleHttpPostRequest()
     * @see ClientGeostat::handleHttpGetRequest()
     */
    protected function handleHttpRequest($requ) {
        if (array_key_exists('geostatStatus',$requ)) {
            $this->geostatClientState->status = true;
        } else {
            $this->geostatClientState->status = false;
        }

        if (array_key_exists('geostatChoroplethLayer',$requ)) {
            $this->geostatClientState->choroplethParams->layer = 
               $requ['geostatChoroplethLayer'];
        }
        if (array_key_exists('geostatChoroplethIndicator',$requ)) {
            $this->geostatClientState->choroplethParams->indicator = 
               $requ['geostatChoroplethIndicator'];
        }
        if (array_key_exists('geostatChoroplethClassifMethod',$requ)) {
            $this->geostatClientState->choroplethParams->classificationMethod =
               $requ['geostatChoroplethClassifMethod'];
        }
        if (array_key_exists('geostatChoroplethNbClasses',$requ)) {
            $this->geostatClientState->choroplethParams->nbBins =
            $requ['geostatChoroplethNbClasses'];
        }
        if (array_key_exists('geostatChoroplethBounds',$requ)) {
            $this->geostatClientState->choroplethParams->bounds =
                $requ['geostatChoroplethBounds'];
        }
        if (array_key_exists('geostatChoroplethColorMethod',$requ)) {
            $this->geostatClientState->choroplethParams->colorLutMethod =
                $requ['geostatChoroplethColorMethod'];
        }
        if (array_key_exists('geostatChoroplethColorA',$requ) &&
            array_key_exists('geostatChoroplethColorB',$requ )) {
            $colorArgb = 
                ColorRgb::hex2rgbArray($requ['geostatChoroplethColorA']);
            $colorBrgb = 
                ColorRgb::hex2rgbArray($requ['geostatChoroplethColorB']);
            $colorA = new ColorRgb($colorArgb[0],$colorArgb[1],$colorArgb[2]);
            $colorB = new ColorRgb($colorBrgb[0],$colorBrgb[1],$colorBrgb[2]);
            $this->geostatClientState->choroplethParams->colorInit = array(
                TwColorRgbHelper::ColorRgb2TwColorRgb($colorA), 
                TwColorRgbHelper::ColorRgb2TwColorRgb($colorB));
        }
        //We check if the first color is defined. If it's true, we suppose
        //that every color will be defined
        if (array_key_exists('geostatChoroplethClassColor0',$requ)) {
            $colors = array();
            for($i=0;
                $i<count($this->geostatClientState->choroplethParams->colors);
                $i++) {
                $color = 
                    ColorRgb::hex2rgbArray($requ['geostatChoroplethClassColor'.
                        strval($i)]);
                $colors[] = new ColorRgb($color[0],$color[1],$color[2]);    
            }
            $colorsRgb = array();
            foreach($colors as $color) {
                $colorsRgb[] = $color->getColorRgb();
            }
            $this->geostatClientState->choroplethParams->colors = 
            TwColorRgbHelper::ColorRgbArray2TwColorRgbArray($colorsRgb);
        }
    }

    /**
     * @see Ajaxable::ajaxGetPluginResponse()
     */
    public function ajaxGetPluginResponse(AjaxPluginResponse $ajaxPluginResponse) {
        $ajaxPluginResponse->addHtmlCode('geostat', $this->renderFormPrepare());
        $ajaxPluginResponse->addVariable('geostatFolderId', $this->getFolderId());
     }
      
    /**
     * @see ServerCaller::buildRequest()
     */
    public function buildRequest() {
        
        if($this->getConfig()->choroplethDataFromCurrentBoundingBox) {
            print $this->getConfig()->choroplethDataFromCurrentBoundingBox;
            $this->geostatClientState->choroplethParams->bbox = new Bbox();
        }
        $geostatRequest = new GeostatRequest();
        
        $geostatRequest->status = $this->geostatClientState->status;
        
        $geostatRequest->choroplethParams = 
            $this->geostatClientState->choroplethParams;
            
        return $geostatRequest;
    }
    
    /**
     * @see ServerCaller::initializeResult()
     */
    public function initializeResult($geostatResult) {
        $this->log->debug('Geostat Result');
        $this->log->debug($geostatResult);
        $this->geostatClientState->choroplethDrawn =
            $geostatResult->choroplethDrawn;
        if ($geostatResult->choroplethDrawn) {
            $this->geostatClientState->choroplethClassification =
                TwClassificationHelper::TwClassification2Classification(
                    $geostatResult->choroplethClassification);
            $this->geostatClientState->choroplethStats =
                TwDistributionSummaryHelper::TwDistSummary2DistSummary(
                    $geostatResult->choroplethStats);
        }
        
        $this->geostatClientState->choroplethParams =
            $geostatResult->choroplethParams;
    }
    
    /**
     * @see ServerCaller::handleResult()
     */
    public function handleResult($geostatResult) {}
    
    /**
     * @see Ajaxable::ajaxHandleAction()
     */
    public function ajaxHandleAction($actionName, PluginEnabler $pluginEnabler) {
        switch ($actionName) {
            case 'Geostat.UpdateMenu':
                $pluginEnabler->disableCoreplugins();
                $pluginEnabler->enablePlugin('geostat');
            break; 
            case 'Geostat.UpdateMap':
                $pluginEnabler->enablePlugin('geostat');
/*
                $pluginEnabler->setEnableLevel('geostat',
                        ClientPlugin::ENABLE_LEVEL_PROCESS);
*/
            break; 
            case 'Geostat.UpdateAll':
                $pluginEnabler->enablePlugin('geostat');
            break; 
        }

        if (strpos($actionName, 'Location.') === 0) {
            $pluginEnabler->enablePlugin('geostat');
        }
    }
    
    /**
     * This function builds geostat template
     * @return string
     */
    public function renderFormPrepare() {
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        
        $smarty->assign('geostat_switch',
                $this->renderSwitch());
        $smarty->assign('geostat_choropleth_dataset',
                $this->renderChoroplethDataset());
        $smarty->assign('geostat_choropleth_representation',
               $this->renderChoroplethRepresentation());
        $smarty->assign('geostat_data_source',
               $this->renderDataSource());
        
        return $smarty->fetch('geostat.tpl');    
    }
     
    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {
        $template->assign('geostat', $this->renderFormPrepare());
    }

    /**
     * This function return form about Geostat status
     * @return string
     */
    protected function renderSwitch() {
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);

        $smarty->assign('geostatStatusName', 'geostatStatus');
        $smarty->assign('geostatStatusSelected', $this->geostatClientState->status);
        return $smarty->fetch('geostat_switch.tpl');
    }
    
    /**
     * This function return form about datasource
     * @return string
     */
    protected function renderDataSource() {
        
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        
        //TODO: Maybe this must be move elsewhere ?
        //Maybe ServerGeostat::getConfLayers()...
        $geostatLayers = $this->geostatInit->serverConfigParams;
        $LayersChoroplethId = array();
        $LayersChoroplethDesc = array();
        $LayersChoroplethAttributes = array();
        $LayersChoroplethLabels = array();
        foreach($geostatLayers as $layer) {
            $this->log->debug('Describe layer');
            $this->log->debug(print_r($layer,true));
            $LayersId[] = $layer->msLayer;
            $LayersDesc[] = $layer->label;
            if ($layer->choropleth) {
                $LayersChoroplethId[] = $layer->msLayer;
                $LayersChoroplethDesc[] = $layer->label;
            }
            $LayersChoroplethAttributes[$layer->msLayer] = 
                explode(',',$layer->choropleth_attribs);
            $LayersChoroplethLabels[$layer->msLayer] = 
                explode(',',$layer->choropleth_attribs_label);
            
        }
        array_walk($LayersChoroplethDesc, 
            array($this, 'translateArrayElem'));
        array_walk_recursive($LayersChoroplethLabels, 
            array($this, 'translateArrayElem'));
        
        $smarty->assign('geostatChoroplethLayersId',$LayersChoroplethId);
        $smarty->assign('geostatChoroplethLayersDesc',$LayersChoroplethDesc);
        $smarty->assign('geostatChoroplethLayerSelected',
            $this->geostatClientState->choroplethParams->layer);
        $smarty->assign('geostatChoroplethIndicatorSelected',
            $this->geostatClientState->choroplethParams->indicator);
        if (array_key_exists($this->geostatClientState->choroplethParams->
            layer, $LayersChoroplethLabels)) {
            $smarty->assign('geostatChoroplethIndicatorsId',
                $LayersChoroplethAttributes[$this->geostatClientState->
                choroplethParams->layer]);
            $smarty->assign('geostatChoroplethIndicatorsDesc',
                $LayersChoroplethLabels[$this->geostatClientState->
                choroplethParams->layer]);
            
        } else {
            $smarty->assign('geostatChoroplethIndicatorsId',array());
            $smarty->assign('geostatChoroplethIndicatorsDesc',array());
        }
        
        return $smarty->fetch('geostat_data_source.tpl');
    }
    
    /**
     * This function return form about choropleth dataset
     * @return string
     */
    protected function renderChoroplethDataset() {
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        
        if ($this->geostatClientState->choroplethDrawn) {
            $smarty->assign('geostatChoroplethMin', 
                $this->geostatClientState->choroplethStats->minVal);
            $smarty->assign('geostatChoroplethMax', 
                $this->geostatClientState->choroplethStats->maxVal);
            $smarty->assign('geostatChoroplethNbVal', 
                $this->geostatClientState->choroplethStats->nbVal);
            $smarty->assign('geostatChoroplethMean', 
                $this->geostatClientState->choroplethStats->meanVal);
            $smarty->assign('geostatChoroplethStdDev', 
                $this->geostatClientState->choroplethStats->stdDevVal);
        } else {
            $emptyProperties = array('geostatChoroplethMin',
                'geostatChoroplethMax', 'geostatChoroplethNbVal',
                'geostatChoroplethMean', 'geostatChoroplethStdDev');
            foreach($emptyProperties as $prop) {
                $smarty->assign($prop,'N/A');
            }
        }
        $classificationMethod = array(
            Distribution::CLASSIFY_BY_EQUAL_INTERVALS => 'Equal Intervals',
            Distribution::CLASSIFY_BY_QUANTILS => 'Quantils',
            Distribution::CLASSIFY_BY_MODES => 'Modes',
            Distribution::CLASSIFY_WITH_BOUNDS => 'Custom'
            );
        $classificationMethod = array_intersect_key($classificationMethod, 
            $this->geostatClientState->choroplethAvalaibleClassifMethods);
        $smarty->assign('geostatChoroplethClassifMethod',
            $classificationMethod);
        $smarty->assign('geostatChoroplethClassifMethodSelected',
            $this->geostatClientState->choroplethParams->classificationMethod);
        
        $smarty->assign('geostatChoroplethNbBins',
            $this->geostatClientState->choroplethParams->nbBins);
        
        if (!is_null($this->geostatClientState->choroplethClassification) &&
                $this->geostatClientState->choroplethParams->
                classificationMethod != Distribution::CLASSIFY_BY_MODES) {
            $smarty->assign('geostatChoroplethBounds',
                $this->geostatClientState->choroplethClassification->
                getBoundsArray());
        } else {
             $smarty->assign('geostatChoroplethBounds',array());
        }
        
        return $smarty->fetch('geostat_choropleth_dataset.tpl');
    }
    
    /**
     * This function return form about choropleth representation
     * @return string
     */
    protected function renderChoroplethRepresentation() {
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        
        $colorMethod = array(ColorLut::METHOD_RGB_INTERPOLATION => 
                'RGB Interpolation',
            ColorLut::METHOD_HSV_INTERPOLATION => 'HSV Interpolation', 
            ColorLut::METHOD_MAX_DIFFERENCE => 'Max Difference',
            0 => 'Custom');
        $colorMethod = array_intersect_key($colorMethod,
            $this->geostatClientState->choroplethAvalaibleColorLutMethods);
        $smarty->assign('geostatChoroplethColorMethod',$colorMethod);
        $smarty->assign('geostatChoroplethColorMethodSelected',
            $this->geostatClientState->choroplethParams->colorLutMethod);
        $smarty->assign('geostatChoroplethColorAValue',
            '#' . TwColorRgbHelper::TwColorRgb2ColorRgb(
                $this->geostatClientState->choroplethParams->colorInit[0])->
                getRgbHexString());
        $smarty->assign('geostatChoroplethColorBValue',
            '#' . TwColorRgbHelper::TwColorRgb2ColorRgb(
                $this->geostatClientState->choroplethParams->colorInit[1])->
                getRgbHexString());
        
        if (!is_null($this->geostatClientState->choroplethClassification)) {
            $smarty->assign('geostatChoroplethLabels',
                $this->geostatClientState->choroplethClassification->
                    getLabelsArray());
            $choroplethClassesColor = array();
            foreach($this->geostatClientState->choroplethParams->colors 
                as $color) {
                $choroplethClassesColor[] = '#' . 
                TwColorRgbHelper::TwColorRgb2ColorRgb($color)->
                    getRgbHexString();
            }
            $smarty->assign('geostatChoroplethClassesColor',
                $choroplethClassesColor);
        } else {
               $smarty->assign('geostatChoroplethLabels',array());
               $smarty->assign('geostatChoroplethClassesColor',array());
        }
            
        return $smarty->fetch('geostat_choropleth_representation.tpl');
    }
    
    /**
     * Use this function with array_walk to translate array
     */
    protected function translateArrayElem(&$item) {
        $item = I18n::gt($item);
    } 

    protected function formatBoundsArray($boundsArray) {
            // Format numbers in the boundsArray
            $formattedBoundsArray = array();
            foreach ($boundsArray as $bound) {
                $formattedBoundsArray[] = number_format($bound, 2, '.', '');
            }
            return $$formattedBoundsArray;
    }
}

?>
