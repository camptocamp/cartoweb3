<?php
/**
 * @package Server
 * @version $Id$
 */

/**
 * @package Server
 */
class MapInfoHandler {
    private $log;
    private $serverContext;

    public $configMapPath;

    public $mapInfo;
    
    private $projectHandler;

    private $iniPath;
    private $symPath;
    private $mapPath;

    function __construct($serverContext, $mapId, $projectHandler) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->serverContext = $serverContext;
        $this->projectHandler = $projectHandler;
        $this->loadMapInfo($mapId);
    }

    /**
     * Process mapInfo after being loaded from the configuration.
     * Does some basic consistency checks, fills some information.
     */
    private function fixupMapInfo(MapInfo $mapInfo) {
        if (isset($mapInfo->layers))
            foreach ($mapInfo->layers as $layer) {
                if (empty($layer->label))
                    $layer->label = $layer->id; 
                if ($layer instanceof Layer && empty($layer->msLayer))
                    $layer->msLayer = $layer->id; 
                $layer->label = utf8_encode($layer->label);
            }
        
        if (isset($mapInfo->initialMapStates)) {
            foreach ($mapInfo->initialMapStates as $state) {
                if (!isset($state->location)) {
                    $this->serverContext->getMapObj();
                    $state->location = new InitialLocation();
                    $state->location->bbox = new Bbox();
                    $state->location->bbox->setFromMsExtent(
                                    $this->serverContext->getMaxExtent());
                }
            }
        }
        return $mapInfo;
    }

    private function getPath($storage, $dir, $ext = false) {
        if(!isset($this->$storage)) {
            $mapName = $this->projectHandler->getMapName();
            $file = ($ext) ? ($mapName . '.' . $ext) : false;
            $path = $this->projectHandler->getPath($dir . $mapName . '/', $file);
            $this->$storage = CARTOSERVER_HOME . $path . $file;
        }
        return $this->$storage;
    }
    
    function getIniPath() {
        return $this->getPath('iniPath', 'server_conf/', 'ini');
   }

    function getSymPath() {
        return $this->getPath('symPath', 'server_conf/', 'sym');
    }

    function getMapPath($serverContext) {
        if (!isset($this->mapPath))
            $this->mapPath = $serverContext->getMapPath();
        return $this->mapPath;
    }

    private function loadMapInfo($mapId) {

        $mapName = $this->projectHandler->getMapName();
        $iniPath = $this->getIniPath();
        $configStruct = StructHandler::loadFromIni($iniPath);
        $this->mapInfo = new MapInfo();
        $this->mapInfo->unserialize($configStruct->mapInfo);
        $this->mapInfo = $this->fixupMapInfo($this->mapInfo);
        return $this->mapInfo;
    }

    function getMapInfo() {
        return $this->mapInfo;
    }

    /**
     * Fills dynamic general map information, like map name.
     */
    private function fillDynamicMap($serverContext) {
        $mapInfo = $this->mapInfo;
        $msMapObj = $serverContext->getMapObj();
        
        $mapInfo->mapLabel = $msMapObj->name;
        
        $availabilityIcons = array('notAvailableIcon' => 'na.png',
                                   'notAvailablePlusIcon' => 'nap.png',
                                   'notAvailableMinusIcon' => 'nam.png');
        foreach($availabilityIcons as $field => $icon) {
            if (!isset($mapInfo->$field))
                $mapInfo->$field = $icon;
            $mapInfo->$field = $this->getIconUrl($mapInfo->$field, false);
        }
    }
    
    private function fillDynamicLayers($serverContext) {
        $mapInfo = $this->mapInfo;
        $layers = $mapInfo->getLayers();
        $msMapObj = $serverContext->getMapObj();
        
        if ($mapInfo->autoClassLegend) $this->getMapPath($serverContext);

        foreach ($layers as $layer) {
            if (!$layer instanceof Layer)
                continue;

            $msLayer = $msMapObj->getLayerByName($layer->msLayer);
            if (!$msLayer)
                throw new CartoserverException('Could not find msLayer ' 
                                               . $layer->msLayer);

            if ($msLayer->minscale > 0) $layer->minScale = $msLayer->minscale;
            else $layer->minScale = 0;
            if ($msLayer->maxscale > 0) $layer->maxScale = $msLayer->maxscale;
            else $layer->maxScale = 0;
            
            if ($layer instanceof Layer && !empty($layer->icon))
                $layer->icon = $this->getIconUrl($layer->icon, false);
            
            for ($i = 0; $i < $msLayer->numclasses; $i++) {
                $msClass = $msLayer->GetClass($i);
                if (isset($msClass->name) && 
                    strlen(trim($msClass->name)) != 0) { 
                    $layerClass = new LayerClass();

                    copy_vars($msClass, $layerClass);
                    $layerClass->id = $layer->id . '_class_' . $i;
                    $layerClass->label = utf8_encode($msClass->name);
               
                    if ($mapInfo->autoClassLegend) {
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
               
                    $mapInfo->addChildLayerBase($layer, $layerClass);
                }
            }
        }
    }

    private function fillDynamicKeymap($serverContext) {
        
        $msMapObj = $serverContext->getMapObj();
        $referenceMapObj = $msMapObj->reference;

        $serverContext->checkMsErrors();

        $dim = new Dimension($referenceMapObj->width, $referenceMapObj->height);
        $bbox = new Bbox();
        $bbox->setFromMsExtent($referenceMapObj->extent);
        
        $mapInfo = $this->mapInfo;
        $mapInfo->keymapGeoDimension = new GeoDimension();
        $mapInfo->keymapGeoDimension->dimension = $dim;
        $mapInfo->keymapGeoDimension->bbox = $bbox;
    }

    function fillDynamic($serverContext) {
        $this->mapInfo->timeStamp = $serverContext->getTimeStamp();
        $this->fillDynamicMap($serverContext);
        $this->fillDynamicLayers($serverContext);
        $this->fillDynamicKeymap($serverContext);
    }

    /**
     * Returns the relative path to the icons. It is relative to the directory
     * for storing generated images.
     */
    private function getIconsRelativePath() {
        $project = $this->projectHandler->getProjectName();
        $mapId = $this->projectHandler->getMapName();
        return implode('/', array('icons', $project, $mapId)) . '/';
    }

    /**
     * Returns the URL to the given icon. The URL is calculated using the 
     * Resource handler.
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
     */
    private function getClassIcon($classId, $msMapObj, $msClassObj) {
        
        $iconRelativePath = $this->getIconsRelativePath() . $classId . '.png';
        $iconAbsolutePath = $this->serverContext->config->writablePath .
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
}
?>
