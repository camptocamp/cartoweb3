<?php
/**
 * @package Server
 * @version $Id$
 */

/**
 * @package Server
 */
class MapInfoHandler {

    /**
     * @var Logger
     */
    private $log;
    
    /**
     * @var ServerContext
     */
    private $serverContext;

    /**
     * @var string
     */
    public $configMapPath;

    /**
     * @var MapInfo
     */
    public $mapInfo;
    
    /**
     * @var ProjectHandler
     */
    private $projectHandler;

    /**
     * @var string
     */
    private $iniPath;
    
    /**
     * @var string
     */
    private $symPath;
    
    /**
     * @var string
     */
    private $mapPath;

    /**
     * Constructor
     * @param ServerContext
     * @param string map id
     * @param ProjectHandler
     */
    public function __construct(ServerContext $serverContext, $mapId, 
                                ProjectHandler $projectHandler) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->serverContext = $serverContext;
        $this->projectHandler = $projectHandler;
        $this->loadMapInfo($mapId);
    }

    /**
     * Process mapInfo after being loaded from the configuration.
     * Does some basic consistency checks, fills some information.
     * @param MapInfo
     * @return MapInfo
     */
    private function fixupMapInfo(MapInfo $mapInfo) {
        if (isset($mapInfo->layers))
            foreach ($mapInfo->layers as $layer) {
                if (empty($layer->label))
                    $layer->label = $layer->id; 
                if ($layer instanceof Layer && empty($layer->msLayer))
                    $layer->msLayer = $layer->id; 
                $layer->label = Encoder::encode($layer->label, 'config');
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

    /**
     * Returns given config-typed file path.
     * @param string storage property name
     * @param string directory name
     * @param string file extension
     * @return string
     */
    private function getPath($storage, $dir, $ext = false) {
        if(!isset($this->$storage)) {
            $mapName = $this->projectHandler->getMapName();
            $file = ($ext) ? ($mapName . '.' . $ext) : false;
            $path = $this->projectHandler->getPath($dir . $mapName . '/', $file);
            $this->$storage = CARTOSERVER_HOME . $path . $file;
        }
        return $this->$storage;
    }
    
    /**
     * Returns ini file path.
     * @return string
     */
    public function getIniPath() {
        return $this->getPath('iniPath', 'server_conf/', 'ini');
    }

    /**
     * Returns symbols file path.
     * @return string
     */
    public function getSymPath() {
        return $this->getPath('symPath', 'server_conf/', 'sym');
    }

    /**
     * Returns mainmap path.
     * @param ServerContext
     * @return string
     */
    private function getMapPath(ServerContext $serverContext) {
        if (!isset($this->mapPath))
            $this->mapPath = $serverContext->getMapPath();
        return $this->mapPath;
    }

    /**
     * Retrieves Map Info from ini file.
     * @param string map id
     * @return MapInfo
     */
    private function loadMapInfo($mapId) {

        $mapName = $this->projectHandler->getMapName();
        $iniPath = $this->getIniPath();
        $configStruct = StructHandler::loadFromIni($iniPath);
        $this->mapInfo = new MapInfo();
        $this->mapInfo->unserialize($configStruct->mapInfo);
        $this->mapInfo = $this->fixupMapInfo($this->mapInfo);
        return $this->mapInfo;
    }

    /**
     * @return MapInfo
     */
    public function getMapInfo() {
        return $this->mapInfo;
    }

    /**
     * Fills dynamic general map information, like map name.
     * @param ServerContext
     */
    private function fillDynamicMap(ServerContext $serverContext) {
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

    /**
     * Fills properties of the given LayerBase object.
     * @param ServerContext
     * @param LayerBase
     */    
    private function fillDynamicLayerBase(ServerContext $serverContext, 
                                          LayerBase $layerBase) {

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
     * @param ServerContext
     * @param Layer
     */
    private function fillDynamicLayer(ServerContext $serverContext, Layer $layer) {

        $mapInfo = $this->mapInfo;
        $msMapObj = $serverContext->getMapObj();
        
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

    /**
     * Fills dynamic properties of all layer objects. It calls specific methods
     * for each kind of LayerBase and Layer objects.
     * @param ServerContext
     */
    private function fillDynamicLayers(ServerContext $serverContext) {
        $mapInfo = $this->mapInfo;
        $layers = $mapInfo->getLayers();
        
        if ($mapInfo->autoClassLegend) 
            $this->getMapPath($serverContext);

        foreach ($layers as $layer) {
            $this->fillDynamicLayerBase($serverContext, $layer);
            
            // Skip layer groups for the rest of this loop
            if (!$layer instanceof Layer)
                continue;

            $this->fillDynamicLayer($serverContext, $layer);
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
     * Fills dynamic general keymap information.
     * @param ServerContext
     */
    private function fillDynamicKeymap(ServerContext $serverContext) {
        
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

    /**
     * Fills dynamic properties for map, layers and keymap objects.
     * @param ServerContext
     */
    public function fillDynamic(ServerContext $serverContext) {
        $this->mapInfo->timestamp = $serverContext->getTimestamp();
        $this->fillDynamicMap($serverContext);
        $this->fillDynamicLayers($serverContext);
        $this->fillDynamicKeymap($serverContext);
    }
}
?>
