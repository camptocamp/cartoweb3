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
    private $serverContext;

    /**
     * @var MapInfo
     */
    public $layersInit;
    
    /**
     * @var ProjectHandler
     */
    private $projectHandler;

    /**
     * @var string
     */
    private $symPath;
    
    /**
     * @var string
     */
    private $mapPath;

    /**
     * @var string
     */
    private $mapId;

    /**
     * @var ServerLayers
     */
    private $serverLayers;

    /**
     * Constructor
     * @param ServerContext
     * @param string map id
     * @param ProjectHandler
     * @param ServerLayers
     */
    public function __construct(ServerContext $serverContext,
                                ServerLayers $serverLayers) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->serverContext = $serverContext;
        $this->mapId = $serverContext->getMapId();
        $this->projectHandler = $serverContext->getProjectHandler();

        $this->serverLayers = $serverLayers; 
    }

    /**
     * Returns symbols file path.
     * @return string
     */
    private function getSymPath() {
        
        if(!isset($this->symPath)) {
            $mapName = $this->projectHandler->getMapName();
            $file = $mapName . '.sym';
            $path = $this->projectHandler->getPath('server_conf/' . $mapName . '/', $file);
            $this->symPath = CARTOSERVER_HOME . $path . $file;
        }
        return $this->symPath;
    }

    /**
     * Returns mainmap path.
     */
    private function getMapPath() {
        if (!isset($this->mapPath))
            $this->mapPath = $this->serverContext->getMapPath();
        return $this->mapPath;
    }

    /**
     * Fills dynamic general map information, like map name.
     */
    private function fillDynamicMap() {
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
    private function fillDynamicLayerBase(LayerBase $layerBase) {

        if (!empty($layerBase->icon))
            $layerBase->icon = $this->getIconUrl($layerBase->icon, false);

        $meta = array();
        if (!empty($layerBase->metadata)) {
            foreach($layerBase->metadata as $key => $val) {
                $meta[] = sprintf('%s=%s', $key, $val);
            }
        }
        $layerBase->metadata = $meta;
    }
    
    /**
     * Fills properties of the given Layer object. It opens the underlying
     * corresponding mapserver layer object.
     * @param Layer
     */
    private function fillDynamicLayer(Layer $layer) {

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

        if ($msLayer->minscale > 0) $layer->minScale = $msLayer->minscale;
        else $layer->minScale = 0;
        if ($msLayer->maxscale > 0) $layer->maxScale = $msLayer->maxscale;
        else $layer->maxScale = 0;

        for ($i = 0; $i < $msLayer->numclasses; $i++) {
            $msClass = $msLayer->GetClass($i);
            if (isset($msClass->name) && 
                strlen(trim($msClass->name)) != 0) { 
                $layerClass = new LayerClass();

                copy_vars($msClass, $layerClass);
                $layerClass->id = $layer->id . '_class_' . $i;
                $layerClass->label = Encoder::encode($msClass->name);
           
                if ($layersInit->autoClassLegend) {
                    $layerClass->icon = $this->getClassIcon($layerClass->id, 
                                                            $msMapObj,
                                                            $msClass);
                }

                if ($msClass->minscale >= $layer->minScale)
                    $layerClass->minScale = $msClass->minscale;
                else $layerClass->minScale = $layer->minScale;

                if ($msClass->maxscale > 0 && 
                    (!$layer->maxScale ||
                     $msClass->maxscale <= $layer->maxScale))
                    $layerClass->maxScale = $msClass->maxscale;
                else $layerClass->maxScale = $layer->maxScale;
           
                $layersInit->addChildLayerBase($layer, $layerClass);
            }
        }
    }

    /**
     * Fills dynamic properties of all layer objects. It calls specific methods
     * for each kind of LayerBase and Layer objects.
     */
    private function fillDynamicLayers() {
        $layersInit = $this->layersInit;
        $layers = $layersInit->getLayers();
                
        if ($layersInit->autoClassLegend) 
            $this->getMapPath($this->serverContext);

        foreach ($layers as $layer) {
            $this->fillDynamicLayerBase($layer);
            
            // Skip layer groups for the rest of this loop
            if (!$layer instanceof Layer)
                continue;

            $this->fillDynamicLayer($layer);
        }
    }

    /**
     * Returns the relative path to the icons. It is relative to the directory
     * where generated images are stored.
     * @return string
     */
    private function getIconsRelativePath() {
        $project = $this->projectHandler->getProjectName();
        $mapId = $this->projectHandler->getMapName();
        return implode('/', array('icons', $project, $mapId)) . '/';
    }

    /**
     * Returns the URL to the given icon. The URL is calculated using the 
     * Resource handler.
     * @param string icon relative path
     * @param boolean indicates if icon has been generated by Cartoserver
     * @return string
     */
    private function getIconUrl($icon, $generated = false) {

        $urlProvider = $this->serverContext->getResourceHandler()
                                    ->getUrlProvider();
        if ($generated) {
            return $urlProvider->getGeneratedUrl($icon);
        } else {
            $project = $this->projectHandler->getProjectName();
            $mapId = $this->projectHandler->getMapName();
            
            return $urlProvider->getIconUrl($project, $mapId, $icon);
        }
    }

    /**
     * Generates an icon image for classes, and returns its URL.
     * @param string class id
     * @param MapObj
     * @param ClassObj
     * @return string
     */
    private function getClassIcon($classId, $msMapObj, $msClassObj) {
        
        $iconRelativePath = $this->getIconsRelativePath() . $classId . '.png';
        $iconAbsolutePath = $this->serverContext->getConfig()->writablePath .
                                                         $iconRelativePath;
        if (!is_dir(dirname($iconAbsolutePath)))
            mkdir(dirname($iconAbsolutePath), 0755, true);
            
        if (!file_exists($iconAbsolutePath) ||
            filemtime($this->mapPath) > filemtime($iconAbsolutePath) ||
            (file_exists($this->getSymPath()) && 
             filemtime($this->getSymPath()) > filemtime($iconAbsolutePath))) {
            $lgdIcon = $msClassObj->createLegendIcon($msMapObj->keysizex, 
                                                     $msMapObj->keysizey);
            if ($lgdIcon->saveImage($iconAbsolutePath) < 0)
                throw new CartoserverException("Failed writing $iconAbsolutePath");
        }
        return $this->getIconUrl($iconRelativePath, true);
    }

    /**
     * @see InitProvider::getInit()
     */
    public function getInit() {
        $this->layersInit = new LayersInit();

        $iniArray = $this->serverLayers->getConfig()->getIniArray();
        $configStruct = StructHandler::loadFromArray($iniArray);
        $this->layersInit->unserialize($configStruct);

        // TODO: take action if the user did not provide layer configuration
        if (!$this->layersInit->layers) {
            throw new CartoserverException('Missing layer definition ' .
                    '(no root layer in layers.ini)');
        }
        
        foreach ($this->layersInit->layers as $layer) {
            if (empty($layer->label))
                $layer->label = $layer->id; 
            if ($layer instanceof Layer && empty($layer->msLayer))
                $layer->msLayer = $layer->id; 
            $layer->label = Encoder::encode($layer->label, 'config');
        }
         
        $this->fillDynamicMap();
        $this->fillDynamicLayers();
        
        return $this->layersInit;
    }
}
?>