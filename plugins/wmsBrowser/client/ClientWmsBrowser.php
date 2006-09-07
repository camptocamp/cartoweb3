<?php
/**
 * WmsBrowser plugin
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

include_once dirname(__FILE__).'/WmsServerManager.php';
include_once dirname(__FILE__).'/WmsDatabase.php';
define('WMS_CACHE_DIR', CARTOWEB_HOME . 'www-data/wms_cache/');

/**
 * Contains the state of wmsBroser plugin.
 * @package Plugins
 */
class WmsBrowserState {
    /**
     * List of wms layers ever added in the map
     * @var array of LayerOverlay    
     */
    public $addedWmsLayers;

    /**
     * Url of active wms server
     * @var string
     */
    public $activeServerUrl;

    /**
     * Layer to add name
     * @var string
     */
    public $wmsLayerToAdd;
    
    /**
     * Open nodes in wms layers tree
     * @var array
     */
    public $openNodes;
}

/**
 * Client wmsBrowser class
 * @package Plugins
 */
class ClientWmsBrowser extends ClientPlugin
implements GuiProvider, Sessionable, ServerCaller, InitUser {

    /**                    
     * Logger
     * @var string
     */
    private $log;

    /**                    
     * Server side initialised
     * @var boolean
     */
    private $wmsBrowserInit;

    /**
     * Current project
     * @var string
     */
    protected $project;

    /**
     * WmsBrowser State object (session object)
     * @var object
     */
    protected $wmsBrowserState;

    /**
     * WmsServerManager instance
     * @var object
     */
    protected $wmsServerManager;
    /**
     * Url of active wms server
     * @var string
     */
    protected $activeServerUrl;

    /**
     * List of wms layers ever added in the map object
     * @var array of LayerOverlay
     */
    protected $addedWmsLayers;
    
    /**
     * Layer to add name
     * @var string
     */
    protected $wmsLayerToAdd;
    
    /**
     * Remove all wms layers
     * @var boolean
     */
    protected $removeAllWmsLayers = false;
    
    // exploreWmsLayersIframe iframe
    /**
     * Control listing of layers from 'active' server
     * @var boolean
     */
    protected $listLayers = false;

    /**
     * Open nodes in wms layers tree
     * @var array
     */
    protected $openNodes = array();

    // manageServers popup
    /**
     * User command
     * @var string
     */    
    protected $userCommand = '';

    /**
     * User comment
     * @var string
     */
    protected $userComment;

    /**
     * Url of user 's selected server
     * @var string
     */
    protected $selectedServerUrl;    

    /**
     * New server url
     * @var string
     */    
    protected $newServerUrl;

    /**
     * User log
     * @var string
     */
    protected $userLog = array('action'   => '',
                               'case'     => -1,
                               'nServers' => 0);
    
    /**
     * User log status (OK | Failed)
     * @var boolean
     */
    protected $userLogStatus = true;

    /**
     * Popup first load
     * @var boolean
     */
    protected $noFirstLoad = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    /**
     * @see PluginBase::initialize()
     */
    public function initialize() {
        if (!extension_loaded('dbase'))
            throw new  CartoclientException('dbase extension required. ' .
                'Please load it in php.ini file.');
        if (!$mapOverlay = $this->cartoclient->getPluginManager()->mapOverlay)
            throw new CartoclientException('mapOverlay plugin not loaded, ' .
                'and needed by wmsBrowser add "mapOverlay" to your ' .
                'client-side "loadPlugins" parameter');

        if (!is_dir(WMS_CACHE_DIR)) {
            Utils::makeDirectoryWithPerms(WMS_CACHE_DIR, 
                $this->cartoclient->getConfig()->webWritablePath);
        }
        
        $this->wmsServerManager = new WmsServerManager();
    }

    /**
     * @see InitUser::handleInit()
     */
    public function handleInit($wmsBrowserInit) {
        $this->wmsBrowserInit = $wmsBrowserInit;
    }

    /**
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo, 
                                  InitialMapState $initialMapState) {
        $this->wmsBrowserState = new WmsBrowserState();
        $this->wmsBrowserState->addedWmsLayers = array();
        $this->wmsBrowserState->activeServerUrl = '';
        $this->wmsBrowserState->wmsLayerToAdd = '';
        $this->wmsBrowserState->openNodes = array();
        
        /* if ServerWmsBrowser is not loaded, handleInit is totally bypassed, 
        hence the check in the next operation */
        if (!$this->wmsBrowserInit) {
            throw new CartoclientException('WmsBrowser plugin is not loaded'.
                                                            ' on server side');
        }
    }

    /**
     * @see Sessionable::loadSession()
     */
    public function loadSession($sessionObject) {
        $this->addedWmsLayers = $sessionObject->addedWmsLayers;
        $this->activeServerUrl = $sessionObject->activeServerUrl;
        $this->wmsLayerToAdd = $sessionObject->wmsLayerToAdd;
        $this->openNodes = $sessionObject->openNodes;
    }

    /**
     * Common processing for {@see ClientWmsBrowser::handleHttpPostRequest} and
     * {@see ClientWmsBrowser::handleHttpGetRequest}.
     * @param array HTTP request
     */
    protected function handleHttpRequest($request) {
        $this->project = $this->cartoclient->getProjectHandler()->
            getProjectName();

        // manage exploreWmsLayers popup
        if (isset($request['exploreWmsLayers']) && 
            $request['exploreWmsLayers']) {
            
            $formRenderer = $this->getCartoclient()->getFormRenderer();
            $formRenderer->setCustomForm(false);
            $this->getCartoclient()->setInterruptFlow(true);

            print $this->drawExploreWmsLayers();
        }

        // manage iframe located in the exploreWmsLayers popup
        if (isset($request['exploreWmsLayersIframe']) && 
            $request['exploreWmsLayersIframe']) {
            
            $formRenderer = $this->getCartoclient()->getFormRenderer();
            $formRenderer->setCustomForm(false);
            $this->getCartoclient()->setInterruptFlow(true);

            $activeServerUrl = $this->getHttpValue($request, 'wmsServers');
            if ($activeServerUrl != '') 
                $this->activeServerUrl = $activeServerUrl;
            if (!empty($this->activeServerUrl)) $this->listLayers  = true;
            
            $openNodes = $this->getHttpValue($request, 'openNodes');
            $openNodes = array_unique(Utils::parseArray($openNodes));
            if (!empty($openNodes)) $this->openNodes = $openNodes;
            
            $selectedWmsLayerName = 
                $this->getHttpValue($request, 'selectedWmsLayer');
            $addWmsLayer = $this->getHttpValue($request, 'addWmsLayer');
            
            
            if ($this->activeServerUrl != '' && 
                $selectedWmsLayerName != '' && $addWmsLayer) {
                $this->wmsLayerToAdd = $selectedWmsLayerName;
            }
            
            print $this->drawExploreWmsLayersIframe();
        }

        // manage manageServers popup
        if (isset($request['manageServersPopup']) && 
            $request['manageServersPopup']) {
            
            $formRenderer = $this->getCartoclient()->getFormRenderer();
            $formRenderer->setCustomForm(false);
            $this->getCartoclient()->setInterruptFlow(true);  

            $this->newServerUrl = $this->getHttpValue($request, 'url');
            $this->noFirstLoad = $this->getHttpValue($request, 'noFirstLoad');
            $this->selectedServerUrl = 
                $this->getHttpValue($request, 'selectedServer');
            $this->userCommand = $this->getHttpValue($request, 'command');
            $this->userComment = $this->getHttpValue($request, 'comment');

            print $this->drawManageServersPopup();
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
     * @see ServerCaller::buildRequest()
     * Send server-side list of wms layers to dynamically insert in the mapfile
     */
    public function buildRequest() {
        $userLayers = array();
        if ($this->removeAllWmsLayers && !empty($this->addedWmsLayers)) {
            foreach($this->addedWmsLayers as $wmsLayer) {
                $layer = new Layer();
                $layer->id = $wmsLayer->name;
                $userLayer = new UserLayer();
                $userLayer->layer = $layer;
                $userLayer->action = UserLayer::ACTION_REMOVE;
                $userLayers[] = $userLayer;
            }
            $this->addedWmsLayers = array();
        }
        if (!empty($this->wmsLayerToAdd)) {
                $wmsLayer = $this->wmsServerManager->createWmsLayer(
                    $this->activeServerUrl, $this->wmsLayerToAdd);
                $this->addedWmsLayers[] = $wmsLayer;

                $layer = new Layer();
                $layer->id = $wmsLayer->name;
                $userLayer = new UserLayer();
                $userLayer->layer = $layer;
                $userLayers[] = $userLayer;
        }
        
        $this->wmsLayerToAdd = '';
        if (empty($this->addedWmsLayers) && empty($userLayers))
            return NULL;
        
        $wmsBrowserRequest = new WmsBrowserRequest();
        $wmsBrowserRequest->wmsLayers = $this->addedWmsLayers;
        $wmsBrowserRequest->userLayers = $userLayers;
        
        return $wmsBrowserRequest;
    }

    /**
     * @see ServerCaller::initializeResult()
     */
    public function initializeResult($result) {}

    /**
     * @see ServerCaller::handleResult()
     */
    public function handleResult($result) {}

    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {
        $this->smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $tpl = $this->smarty->fetch('wmsBrowser.tpl');

        $template->assign('wmsBrowser', $tpl);
    }

    /**
     * @see Sessionable::saveSession()
     */
    public function saveSession() {
        
        $this->wmsBrowserState->addedWmsLayers =
            $this->addedWmsLayers;
        $this->wmsBrowserState->activeServerUrl = 
            $this->activeServerUrl;
        $this->wmsBrowserState->wmsLayerToAdd = 
            $this->wmsLayerToAdd;
        $this->wmsBrowserState->openNodes = 
            $this->openNodes;

        return $this->wmsBrowserState;
    }

    /* =====================================================
       exploreWmsLayers popup management
       ===================================================== */
    /**
     * Draws ExploreWmsLayers popup
     * @return string Smarty generated HTML content
     */
    protected function drawExploreWmsLayers() {
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $smarty->assign(array('project'    => $this->project));

        return $smarty->fetch('exploreWmsLayers.tpl');
    }

    /* =====================================================
       exploreWmsLayersIframe iframe management
       ===================================================== */
    /**
     * Open wms cache databases, find server whose url is passed and build 
     * the server layers tree.
     * @param string server url (this server must ever be registred in server
     * database) 
     * @return array server layers tree
     */
    protected function buildServerLayersTree($serverUrl) {
        $wmsLayers = array();
        // get server
        $dbServer = WmsDatabase::getDb(WmsDatabase::DB_SERVER);
        $serverRec = $this->wmsServerManager->getServerByUrl($dbServer, 
                                                             $serverUrl);
        WmsDatabase::dbaseClose($dbServer, false);
        $serverId = $serverRec['server_id'];
        $wmsLayers[$serverId] = $serverRec;
        $wmsLayers[$serverId]['layers'] = array();
        $level = 0;
        $nodesIds = array($serverId);
        
        // get server layers
        $dbCapab = WmsDatabase::getDb(WmsDatabase::DB_CAPABILITIES);
        $nDbCapab = dbase_numrecords($dbCapab);
        $currentLayer = $serverId;
        for ($i=1; $i<=$nDbCapab; $i ++) {
            $capabRec = WmsDatabase::getRecordById($dbCapab, $i);
            if ($serverId == $capabRec['server_id']) {
                $wmsLayer = $this->wmsServerManager->fetchLayerMetadatas(
                    $serverRec, $capabRec, false, false, true, true);
                $wmsLayer['layer_id'] = $capabRec['layer_id'];
                $wmsLayer['latlonboundingbox'] = 
                    str_replace(' ', ',', $wmsLayer['latlonboundingbox']);
                $wmsLayer['srs'] = substr($wmsLayer['srs'], 0, 
                                          strpos($wmsLayer['srs'], ' '));
                $wmsLayer['depth'] = $capabRec['depth'];
                $wmsLayer['layers'] = array();

                // compute the layer depth in the tree
                $layerDepth = strlen($capabRec['depth']);
                if ($layerDepth > $level) {
                    $nodesIds[++$level] = $wmsLayer['layer_id'];
                } else if ($layerDepth < $level) {
                    $nDepthMove = $level - $layerDepth;
                    for ($j = 0; $j < $nDepthMove; $j ++) {
                        $level--;
                        array_pop($nodesIds);
                    }
                    $nodesIds[$level] = $wmsLayer['layer_id'];
                } else  {
                    $nodesIds[$level] = $wmsLayer['layer_id'];
                }
                
                $wmsLayer['node_id'] = implode('.', $nodesIds);
                if (in_array($wmsLayer['node_id'], $this->openNodes))
                    $wmsLayer['groupFolded'] = false;
                else
                    $wmsLayer['groupFolded'] = true;

                // add the layer to the server layers array in the right place
                $childrenLayer =& $wmsLayers;
                for ($j = 0; $j < $level; $j++) {
                    $childrenLayer =& $childrenLayer
                        [$nodesIds[$j]]['layers'];
                }
                $childrenLayer[$wmsLayer['layer_id']] = $wmsLayer;
            }
        }
        WmsDatabase::dbaseClose($dbCapab, false);

        return $wmsLayers[$serverId];
    }

    /**
     * Draws ExploreWmsLayersIframe iframe
     * @return string Smarty generated HTML content
     */
    protected function drawExploreWmsLayersIframe() {
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        
        // fetch server properties
        $wmsServers = $this->wmsServerManager->buildServersList();
        $smarty->assign(array('project'           => $this->project,
                              'wmsServers'        => $wmsServers,
                              'selectedWmsServer' => $this->activeServerUrl));

        // fetch selected server layers properties
        if ($this->listLayers) {
            $wmsLayers = $this->buildServerLayersTree($this->activeServerUrl);
            $startOpenNodes = implode("','", $this->openNodes);
            $smarty->assign(array('listLayers'     => $this->listLayers,
                                  'wmsLayers'      => $wmsLayers,
                                  'startOpenNodes' => $startOpenNodes));
        }

        return $smarty->fetch('exploreWmsLayersIframe.tpl');
    }

    /* =====================================================
       manageServers popup management
       ===================================================== */
    /**
     * Control wether url doesn't contain any WMS stuff
     * @param string url
     * @return boolean true if url is valid, else return false
     */
    protected function controlUrl($url) {
        $keywords = array('WMTVER', 'VERSION', 'REQUEST', 'BBOX', 'LAYERS',
                          'SRS', 'WIDTH', 'HEIGHT', 'FORMAT', 'QUERY_LAYERS',
                          'INFO_FORMAT');
        $url = Utils::parseArray($url, '?');
        
        if (count($url) == 1)
            return true;
        $log = array();

        parse_str($url[1], $urlParams);
        foreach ($urlParams as $key => $value) {
            if (in_array(trim(strtoupper($key)), $keywords)) {
                $log[] = sprintf(' * %s => %s', $key, $value);
            }
        }
        
        if (count($log) >= 1) {
            // only return error if some invalid parameter was included in the url
            $this->userLogStatus = false;
            $this->userLog['urlParams'] = $log;
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * Adds a server to the servers database and print user log.
     * @param string server url
     * @param string user comment.
     */
    protected function add($newServerUrl, $userComment) {
        $this->userLog['action'] = 'ADD';
        $this->userLog['serverUrl'] = $newServerUrl;

        if (!$this->controlUrl($newServerUrl)) {
            $this->userLogStatus = false;
            $this->userLog['case'] = 0;
            return;
        }
        if (!$this->wmsServerManager->addServer('', $newServerUrl, 
                                                $userComment)) {
            $this->userLogStatus = false;
            $this->userLog['case'] = 1;
            return;
        }
        if (!$this->wmsServerManager->testServer($newServerUrl)) {
            $this->wmsServerManager->removeServer($newServerUrl);
            $this->userLogStatus = false;
            $this->userLog['case'] = 2;
            return;
        }
        if (!$this->wmsServerManager->refreshServer($newServerUrl)) {
            $this->userLogStatus = false;
            $this->userLog['case'] = 3;
        }
    }

    /**
     * Updates the given servers with the specified information and print 
     * user log.
     * @param string server url
     * @param string new url.
     * @param string new user comment.
     */
    protected function update($serverUrl, $newServerUrl, 
                              $userComment) {
        $this->userLog['action'] = 'UPDATE';
        $this->userLog['serverUrl'] = $serverUrl;
        $this->userLog['newServerUrl'] = $newServerUrl;
        if (!$this->controlUrl($newServerUrl)) {
            $this->userLogStatus = false;
            $this->userLog['case'] = 0;
            return;
        }
        $this->wmsServerManager->updateServer($serverUrl, '', 
                                              $newServerUrl, $userComment);
        if (! $this->wmsServerManager->testServer($newServerUrl)) {
            $this->wmsServerManager->setServerStatus($newServerUrl, 0);
            $this->userLogStatus = false;
            $this->userLog['case'] = 1;
            return;
        }
        if (!$this->wmsServerManager->refreshServer($newServerUrl)) {
            $this->userLogStatus = false;
            $this->userLog['case'] = 2;
        }
    }

    /**
     * Sets a server's status to connected and print user log.
     * @param string server url
     */
    public function connect($serverUrl) {
        $this->userLog['action'] = 'CONNECT';
        $this->userLog['serverUrl'] = $serverUrl;
        if ($serverUrl == '') {
            $this->userLogStatus = false;
            $this->userLog['case'] = 0;
            return;
        }
        if (!$this->wmsServerManager->testServer($serverUrl)) {
            $this->wmsServerManager->setServerStatus($serverUrl, 0);
            $this->userLogStatus = false;
            $this->userLog['case'] = 1;
            return;
        }
        $this->wmsServerManager->setServerStatus($serverUrl, 1);
    }

    /**
     * Sets a server status to disconnected and print user log.
     * @param string server url
     */
    public function disconnect($serverUrl) {
        $this->userLog['action'] = 'DISCONNECT';
        $this->userLog['serverUrl'] = $serverUrl;
        if ($serverUrl == '') {
            $this->userLogStatus = false;
            $this->userLog['case'] = 0;
            return;
        }
        $this->wmsServerManager->setServerStatus($serverUrl, 0);
    }

    /**
     * Removes a server from the servers database and print user log.
     * @param string server url
     */
    public  function remove($serverUrl) {
        $this->userLog['action'] = 'REMOVE';
        $this->userLog['serverUrl'] = $serverUrl;
        if ($serverUrl == '') {
            $this->userLogStatus = false;
            $this->userLog['case'] = 0;
            return;
        }
        $this->wmsServerManager->removeServer($serverUrl);
    }

    /**
     * Refreshs server entries by removing all related records
     * and re-downloading/parsing the capabilities file and print user log.
     * @param string server url
     */
    public function refresh($serverUrl) {
        $this->userLog['action'] = 'REFRESH';
        $this->userLog['serverUrl'] = $serverUrl;
        if ($serverUrl == '') {
            $this->userLogStatus = false;
            $this->userLog['case'] = 0;
            return;
        }
        if (!$this->wmsServerManager->testServer($serverUrl)) {
            $this->wmsServerManager->setServerStatus($serverUrl, 0);
            $this->userLogStatus = false;
            $this->userLog['case'] = 1;
            return;
        }
        if (!$this->wmsServerManager->refreshServer($serverUrl)) {
            $this->userLogStatus = false;
            $this->userLog['case'] = 2;
        }
    }

    /**
     * Tests the availablility of a server and print user log.
     * @param string server url
     */
    public function test($serverUrl) {
        $this->userLog['action'] = 'TEST';
        $this->userLog['serverUrl'] = $serverUrl;
        if ($serverUrl == '') {
            $this->userLogStatus = false;
            $this->userLog['case'] = 0;
            return;
        }
        if (!$this->wmsServerManager->testServer($serverUrl)) {
            $this->userLogStatus = false;
            $this->userLog['case'] = 1;
        }
    }

    /**
     * Draws ManageServers popup
     * @return string Smarty generated HTML content
     */
    protected function drawManageServersPopup() {
        if ($this->userCommand != '') {
            switch ($this->userCommand) {
            case 'ADD':
                $this->add($this->newServerUrl, $this->userComment);
                break;
            case 'UPDATE':
                $this->update($this->selectedServerUrl, 
                              $this->newServerUrl, $this->userComment);
                break;
            case 'CONNECT':
                $this->connect($this->selectedServerUrl);
                break;
            case 'DISCONNECT':
                $this->disconnect($this->selectedServerUrl);
                break;
            case 'REMOVE':
                $this->remove($this->selectedServerUrl);
                break;
            case 'REFRESH':
                $this->refresh($this->selectedServerUrl);
                break;
            case 'TEST':
                $this->test($this->selectedServerUrl);
                break;
            default:
                throw new CartoclientException(sprintf('%s is not a ' .
                        'valid user command', $this->userCommand));
                break;
            }
        }

        $servers = $this->wmsServerManager->buildServersList(true); 
        $this->userLog['nServers'] = count($servers);
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $smarty->assign(array('project'             => $this->project,
                              'noFirstLoad'         => $this->noFirstLoad,
                              'userLogStatus'       => $this->userLogStatus,
                              'userLog'             => $this->userLog,
                              'servers'             => $servers));   

        return $smarty->fetch('manageServers.tpl');
    }
}
?>
