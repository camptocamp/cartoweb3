<?php
/**
 * wmsBrowserLight plugin
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
 * @copyright 2006 Office International de l'Eau, Camptocamp SA
 * @package Plugins
 * @version $Id$
 *
 */
require_once(dirname(__FILE__).'/OwsInfoHarwester.php');

/**
 * Contains the state of WmsBrowserLight plugin.
 * @package Plugins
 */
class WmsBrowserLightState {

    /**
     * Initial server info (url and label)
     * @var array
     */
    public $servers;

    /**
     * Current user server url if user type one in
     * @var string
     */
     public $userServer;
}

/**
 * Client WmsBrowserLight class
 * @package Plugins
 */
class ClientWmsBrowserLight extends ClientPlugin
    implements GuiProvider, Sessionable {

    /**
     * Session object used to store list of servers.
     *
     * The list of servers is coming from the ini file or from the GeoNetwork node
     * defined in the configuration file.
     * @var object
     */
    protected $wmsBrowserLightState;

    /**                    
     * Logger
     * @var string
     */
    private $log;

    /**                    
     * Servers
     * @var array
     */
    private $servers;

    /**                    
     * User server
     * @var string
     */
    public $userServer;

    /**                    
     * catalogtype in order if the catalogue is from the config file (ini) or from GeoNetwork (gn).
     * @var string
     */
    private $catalogtype;

    /**
     * Current project
     * @var string
     */
    protected $project;

    /**
     * Value of the protocol to search for in a GeoNetwork node. 
     *
     * Usually the value is OGC:WMS-1.1.1-http-get-map in GeoNetwork 2.0.2.
     * It is used to search for WMS layers.
     * @var string
     */
    const GnWmsProtocol = 'OGC:WMS-1.1.1-http-get-map';


    /**
     * Constructor     
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }


    /**
     * You need the ogcLayerLoader in order to add the layer to your project.
     * @see PluginBase::initialize()
     */
    public function initialize() {
        if (!$layers = $this->cartoclient->getPluginManager()->getPlugin('ogcLayerLoader'))
            throw new CartoclientException('ogcLayerLoader plugin not loaded, ' .
                'is needed by WmsBrowserLight. Add "ogcLayerLoader" to your ' .
                'client-side "loadPlugins" parameter');
    }


    /**
     * Session in wmsBrowserLight is used to store the list of servers 
     * comming from the ini file or the GeoNetwork node used. 
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo, 
                                  InitialMapState $initialMapState) {
        $this->wmsBrowserLightState                = new WmsBrowserLightState();
        $this->wmsBrowserLightState->servers    = array();
    }

    /**
     * @see Sessionable::loadSession()
     */
    public function loadSession($sessionObject) {
        $this->servers        = $sessionObject->servers;
        $this->userServer     = $sessionObject->userServer;
    }
    
    /**
     * @see Sessionable::saveSession()
     */
    public function saveSession() {
        $this->wmsBrowserLightState->servers    = $this->servers;
        $this->wmsBrowserLightState->userServer = $this->userServer;
        return $this->wmsBrowserLightState;
    }


    /**
     * Query GeoNetwork node to search for metadata about services.
     * Login to the node if required.
     * @param string queryType to specify you search for a list of services 
     *        according to the query or get one metadata record
     * @param string item is the identifier of the metadata record to search for
     */
    private function queryGeonetwork($queryType, $item = null) {         
        switch ($queryType){
            // make a generic search using GN xml search method 
            case 'search': 
                $gnUrl = $this->getConfig()->gn . '/srv/'.$this->getConfig()->gnlang . 
                         '/xml.search?' . $this->getConfig()->gnQuery;
                break;
            // Get one metadata sheet using id (id coming from case search)
            case 'mdget': 
                $gnUrl = $this->getConfig()->gn . '/srv/' . $this->getConfig()->gnlang .
                         '/xml.metadata.get?id=' . $item;
                break;                
            default:
                throw new CartoclientException('queryGeonetwork parameter is invalid');
                break;
        }

        // create a new curl resource
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, 'gn');     // Used to store login info
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'gn');
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER,0);
        
        // Logged to GN node if needed
        if (($this->getConfig()->gnpassword) && ($this->getConfig()->gnuser)) {
            $gnLogin = $this->getConfig()->gn . '/srv/' . $this->getConfig()->gnlang .
                       '/xml.user.login?username=' . $this->getConfig()->gnuser . 
                       '&password=' . $this->getConfig()->gnpassword;
            curl_setopt($ch, CURLOPT_URL, $gnLogin);
            curl_exec($ch);
        }

        curl_setopt($ch, CURLOPT_URL, $gnUrl);

        // grab URL and pass it to the browser
        $xml = curl_exec($ch);
    
        // close curl resource, and free up system resources
        curl_close($ch);
        
        return $xml;
    }
    
    /**
     * Common processing for 
     * {@see ClientWmsBrowserLight::handleHttpPostRequest} 
     * and {@see ClientWmsBrowserLight::handleHttpGetRequest}
     * @param array HTTP request
     */
    protected function handleHttpRequest($request) {

        // owsInfoHarwester ajax process to get all layer from a capability    
        if (isset($request['owsInfoHarwester']) && $request['owsInfoHarwester']) {
            $formRenderer = $this->getCartoclient()->getFormRenderer();
            $formRenderer->setCustomForm(false);
            $this->getCartoclient()->setInterruptFlow(true);  
    
            // Save last server user input in session
            $array = print_r($this->servers, true);
            if (strstr($array, $request['owsUrl']) === false) {
                $this->userServer = $request['owsUrl'];
            } else { 
                $this->userServer = null;
            }
    
            print $this->owsInfoHarwester($request['owsUrl']);
            return null;
            
        } elseif (!isset($this->servers)) {
            // Load server from ini file or GN node if not set.
            // Load the type of catalog to be used (local ini file or GeoNetwork node)
            // TODO : Here we should add a check on servers in order to remove them
            // from interface when they are down.
            
            $this->catalogtype = $this->getConfig()->catalogtype;
            if ($this->catalogtype == "ini") {
                // Load server list
                $servers = ConfigParser::parseObjectArray($this->getConfig(),
                                                        'servers',
                                                        array('label', 'url'));
                foreach ($servers as $server) {
                    $this->servers[] = array('label' => $server->label,
                                             'url'      => $server->url); 
                } 
            } elseif ($this->catalogtype == "gn") {
                // TODO : Query on ScopeCd when it will be possible in GN
                // TODO : Cache the server list. Only stored in Session for the time being.
    
                // Search for services on a GeoNetwork node
                $result = simplexml_load_string($this->queryGeonetwork('search'));
                $nb = $result->summary['count'];
                if ($nb == 0) {
                    $this->servers = null;
                } else {
                    // Loop on each record and search for the URI end point.
                    $id = $result->xpath('//id');
    
                    foreach ($id as $item) {
                        $md = simplexml_load_string($this->queryGeonetwork('mdget', $item));
                        $label = $md->dataIdInfo->idCitation->resTitle;
                        foreach ($md->distInfo->distTranOps->onLineSrc as $src) {
                            if ((string) $src->protocol == self::GnWmsProtocol) {
                                $this->servers[] = array('label' => (string)$label,
                                                         'url'   => (string)$src->linkage); 
    
                            }
                        }                
                    }
                }
            } else {
                throw new CartoclientException('Invalid configuration, ' .
                        'Variable catalogtype MUST be equal to "ini" or "gn"');
            }
        }
    }
    
    /**
     * @see GuiProvider::handleHttpPostRequest()
     * @param array HTTP request
     */
    public function owsInfoHarwester($url) {
        $GeoSearch = new OwsInfoHarwester;
        $GeoSearch->getWmsLayers($url);
        return $GeoSearch->response;
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
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $smarty->assign('servers', $this->servers);
        $smarty->assign('userServerOn', ($this->userServer?1:0));
        $smarty->assign('userServer', $this->userServer);
        // TODO : Here we should save on session the last one selected
        $template->assign('wmsBrowserLight', $smarty->fetch('wmsBrowserLight.tpl'));
     }


}


?>
