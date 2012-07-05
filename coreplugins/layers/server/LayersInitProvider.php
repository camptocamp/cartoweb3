<?php
/**
 * Creation of the LayersInit data structure.
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
 * @package CorePlugins
 * @version $Id$
 */

/**
 * Creates the LayersInit structure for the layers server plugin. This class
 * is separated from the ServerLayer class for code speratation and 
 * lisibility.
 */
class LayersInitProvider implements InitProvider {

    /**
     * @var Logger
     */
    private $log;
    
    /**
     * @var ServerContext
     */
    protected $serverContext;

    /**
     * @var MapInfo
     */
    public $layersInit;
    
    /**
     * @var ProjectHandler
     */
    protected $projectHandler;

    /**
     * @var string
     */
    protected $mapId;

    /**
     * @var ServerLayers
     */
    protected $serverLayers;

    /**
     * Constructor
     * @param ServerContext
     * @param string map id
     * @param ProjectHandler
     * @param ServerLayers
     */
    public function __construct(ServerContext $serverContext,
                                ServerLayers $serverLayers) {
        $this->log = LoggerManager::getLogger(__CLASS__);
        $this->serverContext = $serverContext;
        $this->mapId = $serverContext->getMapId();
        $this->projectHandler = $serverContext->getProjectHandler();

        $this->serverLayers = $serverLayers; 
    }

    /**
     * Fills dynamic general map information, like map name.
     */
    protected function fillDynamicMap() {
        $layersInit = $this->layersInit;
        $msMapObj = $this->serverContext->getMapObj();
        
        $availabilityIcons = array('notAvailableIcon' => 'na.png',
                                   'notAvailablePlusIcon' => 'nap.png',
                                   'notAvailableMinusIcon' => 'nam.png');
        foreach($availabilityIcons as $field => $icon) {
            if (!isset($layersInit->$field))
                $layersInit->$field = $icon;
            $layersInit->$field = $this->getIconUrl($layersInit->$field, false);
        }
    }

    /**
     * Fills properties of the given LayerBase object.
     * @param LayerBase
     */    
    public function fillDynamicLayerBase(LayerBase $layerBase) {

        if (!empty($layerBase->icon))
            $layerBase->icon = $this->getIconUrl($layerBase->icon, false);

        $meta = array();
        if (!empty($layerBase->metadata)) {
            foreach($layerBase->metadata as $key => $val) {
                $meta[] = sprintf('%s=%s', $key, Encoder::encode($val, 'config'));
            }
        }
        $layerBase->metadata = $meta;
    }

    /**
     * Returns the URL to the given icon. The URL is calculated using the 
     * Resource handler.
     * @param string icon relative path
     * @param boolean indicates if icon has been generated by Cartoserver
     * @return string
     */
    protected function getIconUrl($icon, $generated = false) {

        $resourceHandler = $this->serverContext->getResourceHandler();

        if ($generated) {
            return $resourceHandler->getGeneratedUrl($icon);
        } else {
            $project = $this->projectHandler->getProjectName();
            $mapId = $this->projectHandler->getMapName();
            
            return $resourceHandler->getIconUrl($project, $mapId, $icon);
        }
    }

    /**
     * Returns the relative path to the icons. It is relative to the directory
     * where generated images are stored.
     * @return string
     */
    protected function getIconsRelativePath() {
        $project = $this->projectHandler->getProjectName();
        $mapId = $this->projectHandler->getMapName();
        return implode('/', array('icons', $project, $mapId)) . '/';
    }

    /**
     * Returns symbols file path.
     * @return string
     */
    protected function getSymPath() {
        
        $mapName = $this->projectHandler->getMapName();
        $file = $mapName . '.sym';
        $path = $this->projectHandler->getPath('server_conf/' . $mapName . 
                                                                  '/', $file);
        return $this->symPath = CARTOWEB_HOME . $path . $file;
    }
    
    /**
     * Generates an icon image for classes, and returns its URL.
     * @param string class id
     * @param MapObj
     * @param ClassObj
     * @return string
     * @todo : check the opacity value, and modify it to 100%
     */
    protected function getClassIcon($classId, $msMapObj, $msLayerObj, $msClassIndex) {
        
        $writablePath = $this->serverContext->getConfig()->webWritablePath;
        $iconRelativePath = $this->getIconsRelativePath() . $classId . '.png';
        $iconAbsolutePath =  $writablePath . $iconRelativePath;
      
        Utils::makeDirectoryWithPerms(dirname($iconAbsolutePath), $writablePath);
        
        if (!file_exists($iconAbsolutePath) ||
            filemtime($this->serverContext->getMapPath()) > 
                                        filemtime($iconAbsolutePath) ||
            (file_exists($this->getSymPath()) && 
             filemtime($this->getSymPath()) > filemtime($iconAbsolutePath))) {

            $msClassObj = $msLayerObj->getClass($msClassIndex);
            $lgdIcon = $msClassObj->createLegendIcon($msMapObj->keysizex, 
                                                     $msMapObj->keysizey);
            if ($lgdIcon->saveImage($iconAbsolutePath) < 0)
                throw new CartoserverException("Failed writing $iconAbsolutePath");
            $this->serverContext->checkMsErrors();
    
            $otherRes = $this->serverLayers->getConfig()->legendResolutions;
            if ($otherRes) {
                            
                $otherRes = Utils::parseArray($otherRes);
                $oldRes = $msMapObj->resolution;
                
                $pluginManager = $this->serverContext->getPluginManager();   
                $layerPlugin = $pluginManager->layers;      
                   
                foreach ($otherRes as $res) {
    
                    $tempLayer = ms_newLayerObj($msMapObj, $msLayerObj);
                    $mul = $res / $oldRes;
                    $tempLayer->setMetaData('ratio_updated', '');
//                    $tempLayer->set('opacity', 100);
                    $layerPlugin->updateRatioParameters($tempLayer, $mul);
                    
                    $tempClass = $tempLayer->getClass($msClassIndex);                                
                    $lgdIcon = $tempClass->createLegendIcon($msMapObj->keysizex * $mul, 
                                                            $msMapObj->keysizey * $mul);
                    $iconRelativePath2 = $this->getIconsRelativePath() . $classId . '@' . $res . '.png';
                    $iconAbsolutePath2 = $writablePath . $iconRelativePath2;
                    if ($lgdIcon->saveImage($iconAbsolutePath2) < 0)
                        throw new CartoserverException("Failed writing $iconAbsolutePath2");
                    $this->serverContext->checkMsErrors();
                    $tempLayer->set('status', MS_DELETE);
                }
            }                                      
        }
        return $this->getIconUrl($iconRelativePath, true);
    }
        
    /**
     * Retrieve an icon from a distant WMS/SLD server
     * @param string layer id
     * @param MapObj
     * @param LayerObj
     * @return string
     */
    protected function getWmsIcon($LayerId, $msMapObj, $msLayer) {
        
        $writablePath = $this->serverContext->getConfig()->webWritablePath;
        $iconRelativePath = $this->getIconsRelativePath() . $LayerId . '.png';
        $iconAbsolutePath =  $writablePath . $iconRelativePath;
      
        Utils::makeDirectoryWithPerms(dirname($iconAbsolutePath), $writablePath);
        
        if (!file_exists($iconAbsolutePath) ||
             filemtime($this->serverContext->getMapPath()) >
             filemtime($iconAbsolutePath) ||
            (file_exists($this->getSymPath()) && 
             filemtime($this->getSymPath()) > filemtime($iconAbsolutePath))) {

            $wmsVersion = $msLayer->getMetadata('wms_server_version');
            $wmsName = $msLayer->getMetadata('wms_name');
            if (empty($wmsVersion) || empty($wmsName))
                throw new CartoserverException(
                    "Unable to retrieve WMS metadata on layer: $LayerId");

            $url = $msLayer->connection;
            $url .= '&Service=WMS&Request=getLegendGraphic&Format=image/png';
            $url .= sprintf('&Version=%s', $wmsVersion);
            $url .= sprintf('&Layer=%s', $wmsName);
            $url .= sprintf('&Width=%s', $msMapObj->keysizex);
            $url .= sprintf('&Height=%s', $msMapObj->keysizey);
            
            if ($style = $msLayer->getMetadata('wms_style')) 
                $url .= sprintf('&Style=%s', $style);
            
            if (!extension_loaded('curl'))
                throw new CartoserverException(
                    "Curl extension must be installed to use WMS legend graphic");
                
            $fp = @fopen($iconAbsolutePath, 'wb');
            if (!is_resource($fp))
                throw new CartoserverException(
                    "Failed writing $iconAbsolutePath");

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // It should be enough
            curl_exec($ch);
            if (curl_errno($ch))
                throw new CartoserverException(
                    "Failed WMS connection: ". curl_error($ch));
            curl_close($ch);  
            fclose($fp);

            if (!getimagesize($iconAbsolutePath)) {
                if (!unlink($iconAbsolutePath))
                    throw new CartoserverException(
                        "Failed deleting $iconAbsolutePath");
                return ''; // WMS server didn't return an usefull image
            }
        }

        return $this->getIconUrl($iconRelativePath, true);
    }
    
    /**
     * Fills properties of the given Layer object. It opens the underlying
     * corresponding mapserver layer object.
     * @param Layer
     */
    public function fillDynamicLayer(Layer $layer) {

        $layersInit = $this->layersInit;
        $msMapObj = $this->serverContext->getMapObj();
        
        $msLayer = $msMapObj->getLayerByName($layer->msLayer);
        if (!$msLayer)
            throw new CartoserverException('Could not find msLayer ' 
                                           . $layer->msLayer);

        $exportedValues = $msLayer->getMetadata('exported_values');
        if (!empty($exportedValues)) {
            foreach(explode(',', $exportedValues) as $metaKey) {
                $metaVal = $msLayer->getMetadata($metaKey);
                $layer->metadata[] = sprintf('%s=%s', $metaKey, $metaVal);
            }
        }

        if ($msLayer->minscaledenom > 0) $layer->minscaledenom = $msLayer->minscaledenom;
        else $layer->minscaledenom = 0;
        if ($msLayer->maxscaledenom > 0) $layer->maxscaledenom = $msLayer->maxscaledenom;
        else $layer->maxscaledenom = 0;
// Never use empty, it's true everytime and then wrongly affect 100 to opacity
        $this->log->debug( $msLayer->name .' opacity : ' .$msLayer->opacity);
        $layer->opacity = $msLayer->opacity;
        
        if($msLayer->connectiontype == MS_WMS && $msLayer->getMetadata('wms_legend_graphic'))
            $layer->icon = $this->getWmsIcon($layer->id, $msMapObj, $msLayer);
        
        for ($i = 0; $i < $msLayer->numclasses; $i++) {
            $msClass = $msLayer->getClass($i);
            if (!is_null($msClass->name) && 
                strlen(trim($msClass->name)) != 0) {
                 
                $layerClass = new LayerClass();

                Utils::copyVars($msClass, $layerClass);
                $layerClass->id = $layer->id . '_class_' . $i;
                $layerClass->label = Encoder::encode($msClass->name, 'config');

                 /* in mapserver 5.4.0-beta1 (2009-02-18) the metadata object 
                 was converted from an array to a ms_hashtable_obj */
                 if (is_object($layerClass->metadata) && 
                     strtolower(get_class($layerClass->metadata)) == 'ms_hashtable_obj') {
                     $metas = array();
                     $msmetas = $layerClass->metadata;
                     $key = null;
                     while ($key = $msmetas->nextKey($key)) {
                         $metas[] = $key . '=' . $msmetas->get($key);
                     }
                     $layerClass->metadata = $metas;
                 }

                if ($layersInit->autoClassLegend) {
                        $layerClass->icon = $this->getClassIcon($layerClass->id, 
                                                            $msMapObj,
                                                            $msLayer,
                                                            $i);
                }

                if ($msClass->minscaledenom >= $layer->minscaledenom)
                    $layerClass->minscaledenom = $msClass->minscaledenom;
                else $layerClass->minscaledenom = $layer->minscaledenom;

                if ($msClass->maxscaledenom > 0 && 
                    (!$layer->maxscaledenom ||
                     $msClass->maxscaledenom <= $layer->maxscaledenom))
                    $layerClass->maxscaledenom = $msClass->maxscaledenom;
                else $layerClass->maxscaledenom = $layer->maxscaledenom;
           
                $layersInit->addChildLayerBase($layer, $layerClass);
            }
        }
    }
    
    /**
     * Fills dynamic properties of all layer objects. It calls specific methods
     * for each kind of LayerBase and Layer objects.
     */
    protected function fillDynamicLayers() {

        // Create layers for all layers in mapfile
        $msLayerIds = array();
        foreach ($this->layersInit->layers as $layer) {
            if (!$layer instanceof Layer)
                continue;
            if (!empty($layer->msLayer))
                $msLayerIds[] = $layer->msLayer;
            else
                $msLayerIds[] = $layer->id;
        }
        
        $msMapObj = $this->serverContext->getMapObj();
        for ($i = 0; $i < $msMapObj->numlayers; $i++) {
        
            $msLayer = $msMapObj->getLayer($i);
            if (in_array($msLayer->name, $msLayerIds))
                continue;
            
            $newLayer = new Layer();
            $newLayer->id = $msLayer->name;
            $this->layersInit->addChildLayerBase(null, $newLayer);
        }

        foreach ($this->layersInit->layers as $layer) {
            if (empty($layer->label))
                $layer->label = $layer->id; 
            if ($layer instanceof Layer && empty($layer->msLayer))
                $layer->msLayer = $layer->id; 
            $layer->label = Encoder::encode($layer->label, 'config');
            
            $this->fillDynamicLayerBase($layer);
            
            // Skip layer groups for the rest of this loop
            if (!$layer instanceof Layer)
                continue;

            $this->fillDynamicLayer($layer);
        }
        
        // builds a root layer containing all layers, if none is present.

        // TODO: add constant for root layer.
        $rootLayer = $this->layersInit->getLayerById('root');
        if (is_null($rootLayer)) {
            $rootLayer = new LayerGroup();
            $rootLayer->id = 'root';
            $layerIds = array();
            foreach ($this->layersInit->layers as $layer) {
                if ($layer instanceof Layer && !($layer instanceof LayerClass))
                    $layerIds[] = $layer->id;                
            }
            $childSwitch = new ChildrenSwitch();
            $childSwitch->layers = $layerIds;
            $rootLayer->children = array($childSwitch);
            $this->layersInit->addChildLayerBase(null, $rootLayer);
        }
    }

    /**
     * @see InitProvider::getInit()
     */
    public function getInit() {
        $this->layersInit = new LayersInit();

        $iniArray = $this->serverLayers->getConfig()->getIniArray();
        $configStruct = StructHandler::loadFromArray($iniArray);
        $this->layersInit->unserialize($configStruct);

        if (!$this->layersInit->layers)
            $this->layersInit->layers = array();
         
        $this->fillDynamicMap();
        $this->fillDynamicLayers();

        return $this->layersInit;
    }
}
