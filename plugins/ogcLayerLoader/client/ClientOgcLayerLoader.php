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
 * @copyright 2006 Office International de l'Eau, Camptocamp
 * @package Plugins
 * @version $Id$
 */
require_once(dirname(__FILE__).'/OwsInfoHarwesterOLL.php');

/**
 * Contains the state of OgcLayerLoader plugin.
 * @package Plugins
 */
class OgcLayerLoaderState {
    /**
     * List of WMS layers ever added in the map
     * @var array of LayerOverlay    
     */
    public $addedOgcLayers;
}

/**
 * Client OgcLayerLoader class
 * @package Plugins
 */
class ClientOgcLayerLoader extends ClientPlugin 
                implements GuiProvider, ServerCaller, Sessionable {

    /**                    
     * Logger
     * @var string
     */
    private $log;
    
    /**
     * List of WMS layers to add and parameters.
     * @var array 
     */
    protected $addedOGCLayersParams;
  
    /**
     * List of wms layers ever added in the map object
     * @var array of LayerOverlay
     */
    protected $addedOGCLayers;

     /**
     * Remove all wms layers
     * @var boolean
     * @todo Add a method to remove all WMS layers 
     */
    protected $removeAllWmsLayers = false;


    /**
     * Constants for ClientOgcLayerLoader
     * @var String
     */
    const DEFAULT_WMS_VERSION = '1.1.1';
    const DEFAULT_WFS_VERSION = '1.0.0';
    const DEFAULT_WCS_VERSION = '1.0.0';
    const DEFAULT_METHOD= 'POST';
    const DEFAULT_SRS= 'EPSG:4326';
    const DEFAULT_TIMEOUT= '30';
    const DEFAULT_FORMAT= 'image/png';
    const DEFAULT_SERVICE= 'WMS';
    const SCALEFACTOR = 2000;

    /**
     * Constructor
     */
    public function __construct() {
        $this->log = LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    /**
     * Public fonction to add new layers in array addedOGCLayersParams
     * @param array $newLayer
     */
    public function setAddedOGCLayersParams($newLayers) {
        $this->addedOGCLayersParams += $newLayers;
    }

    /**
     * @see PluginBase::initialize()
     */
    public function initialize() {
        if (!$mapOverlay = $this->cartoclient->getPluginManager()->mapOverlay)
            throw new CartoclientException('mapOverlay plugin not loaded, ' .
                'and needed by OgcLayerLoader add "mapOverlay" to your ' .
                'client-side "loadPlugins" parameter');
}

    /**
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo, 
                                  InitialMapState $initialMapState) {
        $this->ogcLayerLoaderState = new OgcLayerLoaderState();
        $this->ogcLayerLoaderState->addedOgcLayers = array();
    }

    /**
     * @see Sessionable::loadSession()
     */
    public function loadSession($sessionObject) {
        $this->addedOGCLayers = $sessionObject->addedOgcLayers;
    }

    /**
     * Common processing for {@see ClientOgcLayerLoader::handleHttpPostRequest} and
     * {@see ClientOgcLayerLoader::handleHttpGetRequest}.
     * @param array HTTP request
     */
    protected function handleHttpRequest($request) {
        if (!empty($request['context_loader'])) {
            $this->addedOGCLayersParams = unserialize($request['context_loader_var']);
        } else {
            if (!empty($request['url'])) {
                $userLayerGroup=null;
                if (!empty($request['owsLayerList'])) {
                    $matches   = explode("#", $request['owsLayerList']);
                    $ogcLayers = $matches[0];
                    $ogcTitles = $matches[1];
                    $ogcSrs    = empty($matches[2]) ? self::DEFAULT_SRS : $matches[2];
                    $minScale  = $matches[3] ? $matches[3] : -1;
                    $maxScale  = $matches[4] ? $matches[4] : -1;

                    if (sizeof($matches) > 5) {
                        $wmsTime = $matches[5];
                        $wmsTimeExtent = $matches[6];
                    } else {
                        $wmsTime = null;
                        $wmsTimeExtent = null;
                    }
                    $idLayer = null;
                } elseif (!empty($request['ogclayers'])) {
                    $geosearch = null;
                    $ogcLayers = $request['ogclayers'];
                    $idLayer = null;
                    if (!empty($request['idLayer'])) {
                        $idLayer=$request['idLayer'];
                    }

                    if (!empty($request['ogctitles'])) {
                        $ogcTitles = $request['ogctitles'];
                    } else {
                        $geosearch = new OwsInfoHarwesterOLL($request['url']);
                        $layer = $geosearch->getLayer($ogcLayers);
                        if($layer != null) {
                            if(!empty($layer->Title)) {
                                $ogcTitles = (string) $layer->Title;
                            } else {
                                $ogcTitles = $ogcLayers;
                            }
                        }
                    }
                
                    $ogcSrs = empty($request['ogcsrs']) ? self::DEFAULT_SRS 
                                                    : $request['ogcsrs'];

                    if (!empty($layer->ScaleHint)) {
                        $maxScale = (int) $layer->ScaleHint['max'];
                        $minScale = (int) $layer->ScaleHint['min'];
                    } else { 
                        $maxScale = -1;
                        $minScale = -1;
                    }
        
                    if (!empty($request['userLayerGroup'])){
                        $userLayerGroup=$request['userLayerGroup'];
                    }

                    $wmsTime = null;
                    $wmsTimeExtent = null;
                    
                } else {
                    return null;
                }
                
                $service = (empty($request['service']) ? self::DEFAULT_SERVICE 
                                                        : $request['service']);
    
                if (empty($request['version']) && $service == 'WMS') {
                    $version = self::DEFAULT_WMS_VERSION;
                } elseif (empty($request['version']) && $service == 'WFS') {
                    $version = self::DEFAULT_WFS_VERSION;
                } elseif (empty($request['version']) && $service == 'WCS') {
                    $version = self::DEFAULT_WCS_VERSION;
                } else {
                   $version = $request['version'];
                }

                $this->addedOGCLayersParams[] = array(
                            'userLayerGroup' => $userLayerGroup,
                            'idLayer'=>$idLayer,
                            'url' => $request['url'],
                            'layers' => $ogcLayers,
                            'title' => $ogcTitles,
                            'service' => $service,// Default service is set to WMS
                            'version' => $version,
                            'method' => (empty($request['method']) ? self::DEFAULT_METHOD 
                                                                    : $request['method']),
                            'srs' => $ogcSrs,
                            // TODO : save SLDBODY and replace sld parameters                            
                            'sldbody' => (!empty($request['sldbody']) ?
                                            $request['sldbody']:null),
                            'sld' => !empty($request['sld']) ? $request['sld'] : null,
                            'format' => (empty($request['format']) ? self::DEFAULT_FORMAT 
                                                                    : $request['format']),
                            'timeout' => (empty($request['timeout']) ? self::DEFAULT_TIMEOUT 
                                                                    : $request['timeout']),
                            'maxscale' => $maxScale,
                            'minscale' => $minScale,
                            'wms_time' => $wmsTime,
                            'wms_timeextent'=> $wmsTimeExtent,
                            'wms_legend_graphic'=> 'true' );
            }
        }
    
        if (!empty($request['removeAllWmsLayers'])) {
            $this->removeAllWmsLayers = true;
        }

    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     * @param array HTTP request
     */
    public function handleHttpPostRequest($request) {
        $this->handleHttpRequest($request);
    }

    /**
     * @see GuiProvider::handleHttpGetRequest()
     * @param array HTTP request
     */
    public function handleHttpGetRequest($request) {
        $this->handleHttpRequest($request);
    }

    /**
     * @see ServerCaller::initializeResult()
     */
    public function initializeResult($result) {}

    /**
     * @param int scale
     */
    protected function computeScale ($scale)
    {
        return $scale*self::SCALEFACTOR;
    }

    /**
     * @see ServerCaller::handleResult()
     */
    public function handleResult($result) {}

    /**
     * @array $layerParams  
     */    
    protected function AddOgcLayer ($layerParams) {
        $layerOverlay = new LayerOverlay();

        switch ($layerParams["service"]) {
            case 'WMS':
                $layerOverlay->action = BasicOverlay::ACTION_INSERT;
                $layerOverlay->connection = $layerParams["url"];
                $layerOverlay->connectionType = 7; //MS_WMS;

                $layerOverlay->maxScale = ($layerParams["maxscale"]==-1 ? -1 
                                    : $this->computeScale ($layerParams["maxscale"]));
                $layerOverlay->minScale = ($layerParams["minscale"]==-1 ? -1 
                                    : $this->computeScale ($layerParams["minscale"]));

                $layerOverlay->name = $layerParams["layers"];
                $layerOverlay->type = 3; //MS_LAYER_RASTER;
                
                $metadataOverlay = new MetadataOverlay();
                $metadataOverlay->name = 'wms_srs';
                $metadataOverlay->value = $layerParams["srs"];
                $metadataOverlay->action = BasicOverlay::ACTION_INSERT;
                $layerOverlay->metadatas[] = $metadataOverlay;
                
                $metadataOverlay = new MetadataOverlay();
                $metadataOverlay->name = 'wms_name';
                $metadataOverlay->value = $layerParams["layers"];
                $metadataOverlay->action = BasicOverlay::ACTION_INSERT;
                $layerOverlay->metadatas[] = $metadataOverlay;
                
                $metadataOverlay = new MetadataOverlay();
                $metadataOverlay->name = 'wms_title';
                $metadataOverlay->value = $layerParams["title"];
                $metadataOverlay->action = BasicOverlay::ACTION_INSERT;
                $layerOverlay->metadatas[] = $metadataOverlay;
                
                $metadataOverlay = new MetadataOverlay();
                $metadataOverlay->name = 'wms_server_version';
                $metadataOverlay->value = $layerParams["version"];
                $metadataOverlay->action = BasicOverlay::ACTION_INSERT;
                $layerOverlay->metadatas[] = $metadataOverlay;
                
                $metadataOverlay = new MetadataOverlay();
                $metadataOverlay->name = 'wms_format';
                $metadataOverlay->value = $layerParams["format"];
                $metadataOverlay->action = BasicOverlay::ACTION_INSERT;
                $layerOverlay->metadatas[] = $metadataOverlay;
                
                $metadataOverlay = new MetadataOverlay();
                $metadataOverlay->name = 'wms_legend_graphic';
                $metadataOverlay->value = 'true';
                $metadataOverlay->action = BasicOverlay::ACTION_INSERT;
                $layerOverlay->metadatas[] = $metadataOverlay;
                
                $metadataOverlay = new MetadataOverlay();
                $metadataOverlay->name = 'wms_connectiontimeout';
                $metadataOverlay->value = $layerParams["timeout"];
                $metadataOverlay->action = BasicOverlay::ACTION_INSERT;
                $layerOverlay->metadatas[] = $metadataOverlay;
                
                if (empty($layerParams["sld"])){
                    $metadataOverlay = new MetadataOverlay();
                    $metadataOverlay->name = 'wms_sld_url';
                    $metadataOverlay->value = $layerParams["sld"];
                    $metadataOverlay->action = BasicOverlay::ACTION_INSERT;
                    $layerOverlay->metadatas[] = $metadataOverlay;
                    
                    $metadataOverlay = new MetadataOverlay();
                    $metadataOverlay->name = 'wms_time';
                    $metadataOverlay->value = $layerParams["wms_time"];
                    $metadataOverlay->action = BasicOverlay::ACTION_INSERT;
                    $layerOverlay->metadatas[] = $metadataOverlay;
                    
                    $metadataOverlay = new MetadataOverlay();
                    $metadataOverlay->name = 'wms_timeextent';
                    $metadataOverlay->value = $layerParams["wms_timeextent"];
                    $metadataOverlay->action = BasicOverlay::ACTION_INSERT;
                    $layerOverlay->metadatas[] = $metadataOverlay;
                }
                break;
                case 'WCS': 
                    throw new CartoclientException('WCS protocol are not yet' .
                            ' supported in Cartoweb.');
                break;
                case 'WFS':
                    throw new CartoclientException('WFS protocol are not yet' .
                            ' supported in Cartoweb.');               
                break;
            }

        return $layerOverlay;
    }


    /**
     * @see ServerCaller::buildRequest()
     * Add ogc layer using epsg:4326 by default, without scales and SLD if provided. 
     * Send server-side list of wms layers to dynamically insert in the mapfile
     */
    public function buildRequest() {
 
        $userLayers = array();
        if ($this->removeAllWmsLayers && !empty($this->addedOGCLayers)) {
            foreach($this->addedOGCLayers as $wmsLayer) {
                $layer = new Layer();
                $layer->id = $wmsLayer->name;
                $userLayer = new UserLayer();
                $userLayer->layer = $layer;
                $userLayer->action = UserLayer::ACTION_REMOVE;
                $userLayers[] = $userLayer;
            }

            $this->addedOGCLayers= array();  
        } 
        
        
        if (!empty($this->addedOGCLayersParams)){
            foreach ($this->addedOGCLayersParams as $params) {
                $this->addedOGCLayers[] = $this->AddOgcLayer($params);
                $layer = new Layer();
                $layer->id = $params["layers"];
                $layer->label = $params["title"];

                $layer->metadata["userLayerGroup"]=$params["userLayerGroup"];
                $layer->metadata["idLayer"]=$params["idLayer"];
                $layer->metadata["url"]=$params["url"];
                $layer->metadata["layers"]=$params["layers"];
                $layer->metadata["title"]=$params["title"];
                $layer->metadata["service"]=$params["service"];
                $layer->metadata["version"]=$params["version"];
                $layer->metadata["method"]=$params["method"];
                $layer->metadata["srs"]=$params["srs"];
                $layer->metadata["sldbody"]=$params["sldbody"];
                $layer->metadata["sld"]=$params["sld"];
                $layer->metadata["format"]=$params["format"];
                $layer->metadata["timeout"]=$params["timeout"];
                $layer->metadata["maxscale"]=$params["maxscale"];
                $layer->metadata["minscale"]=$params["minscale"];
                $layer->metadata["wms_time"]=$params["wms_time"];
                $layer->metadata["wms_timeextent"]=$params["wms_timeextent"];
                $layer->metadata["wms_legend_graphic"]=$params["wms_legend_graphic"];
                $layer->link=($params["idLayer"])?
                                $this->getConfig()->urlCatalog."srv/fr/metadata.show?" .
                                "currTab=simple&id=".$params["idLayer"] : null ;

                if($params["maxscale"]!=-1){
                    $layer->maxScale = $params["maxscale"];
                }
                if($params["minscale"]!=-1){
                    $layer->minScale = $params["minscale"];
                }
    
                $userLayer = new UserLayer();

                if($params["userLayerGroup"]!=null){
                    $userLayer->userLayerGroup = $params["userLayerGroup"];
                }
                
                $userLayer->layer = $layer;
                $userLayers[] = $userLayer;
            }
        }

        $this->addedOGCLayersParams = array();

        if (empty($this->addedOGCLayers) && empty($userLayers))
           return NULL;
        
        $ogcLayerLoaderRequest = new OgcLayerLoaderRequest();
        $ogcLayerLoaderRequest->ogcLayers = $this->addedOGCLayers;
        $ogcLayerLoaderRequest->userLayers = $userLayers;

        return $ogcLayerLoaderRequest;
    }
    
    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this); 
        if (!empty($this->addedOGCLayers) || !empty($this->addedOGCLayersParams)){ 
            $smarty->assign('wms_displayed', true);
        }
        $template->assign('ogcLayerLoader', $smarty->fetch('ogcLayerLoader.tpl'));
}
  

     /**
     * @see Sessionable::saveSession()
     */
    public function saveSession() {
        $this->ogcLayerLoaderState->addedOgcLayers = $this->addedOGCLayers;
        return $this->ogcLayerLoaderState;
    }
}
