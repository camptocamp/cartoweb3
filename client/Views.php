<?php
/**
 * Low level views-management and -storage classes
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
 * @package Client
 * @version $Id$
 */

/**
 * Global views management.
 * @package Client
 */
class ViewManager {

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var Cartoclient
     */
    private $cartoclient;

    /**
     * @var ViewContainer
     */
    private $wc;

    /**
     * @var ViewFilter
     */
    private $wf;
    
    /**
     * @var int
     */
    private $viewId;

    /**
     * @var stdClass
     */
    private $data;

    /**
     * @var string
     */
    private $message;

    /**
     * @var array
     */
    private $metasList;

    /**
     * @var array
     */
    private $metas;

    /**
     * @var array
     */
    private $viewablePlugins;

    /**
     * @var boolean
     */
    private $isActionSuccess;

    /**
     * @var string
     */
    private $sessionCacheLocation;
    
    const BASE_METAS = 'viewTitle, viewShow, viewLocationId';
    
    /**
     * Constructor
     * @param Cartoclient
     */
    public function __construct(Cartoclient $cartoclient) {

        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->cartoclient = $cartoclient;

        $this->log->debug('Loading Views Manager');

        $storage = $cartoclient->getConfig()->viewStorage;
        
        switch (strtolower($storage)) {
            case 'db':
                $dsn = $cartoclient->getConfig()->viewDsn;
                $showDevMsg = $cartoclient->getConfig()->showDevelMessages;
                $this->wc = new ViewDbContainer($dsn,
                                                $this->getMetasList(),
                                                $showDevMsg);
                break;

            case 'file':
            default:
                $this->wc = new ViewFileContainer($cartoclient);
        }

        $this->wf = new ViewFilter($cartoclient);
    }

    /**
     * Returns the list of plugins that have their data recorded in views
     * (set in client.ini "viewablePlugins" parameter).
     * @return array
     */
    private function getViewablePlugins() {
        if (!isset($this->viewablePlugins)) {
            $this->viewablePlugins = explode(',', $this->cartoclient
                                                  ->getConfig()
                                                  ->viewablePlugins);
            $this->viewablePlugins = array_map('filterViewable', 
                                               $this->viewablePlugins);
        }
        return $this->viewablePlugins;
    }

    /**
     * @param ClientSession
     * @return stdClass collection of viewable plugins states 
     */
    private function getPluginSession($sessionData) {
        $pluginSession = clone $sessionData->pluginStorage;
        foreach ($pluginSession as $pluginName => $val) {
            if (!in_array($pluginName, $this->getViewablePlugins())) {
                unset($pluginSession->$pluginName);
            }
        }
        return $pluginSession; 
    }

    /**
     * Checks view ID validity (non zero positive integer).
     * @param mixed viewId
     * @param boolean if true, no message is issued when ID is invalid
     * @return int view ID if valid, else 0
     */
    public function checkViewId($viewId, $mute = false) {
        if (empty($viewId) || !preg_match('/^([0-9]*)$/', $viewId)) {
            if (!$mute) {
                $this->message = I18n::gt('Action halted: invalid view Id');
            }
            return 0;
        }
        return $viewId;
    }

    /**
     * Gets id from given request parameter and checks its validity.
     * @param string request parameter name
     * @return int id
     */
    private function getId($source) {
        return $this->checkViewId($this->getRequestValue($source));
    }

    /**
     * Checks that user is allowed to perform special view actions.
     * @return boolean
     */
    public function hasRole($mute = false) {
        $requiredPerm = $this->cartoclient->getConfig()->viewAuth;
        if (!SecurityManager::getInstance()->hasRole($requiredPerm)) {
            if (!$mute) {
                $this->message = I18n::gt(
                                 'You are not allowed to perform this action');
            }
            return false;
        }
        
        return true;
    }

    /**
     * Returns path of cached session file.
     * @return string
     */
    public function getSessionCacheLocation() {
        if (!isset($this->sessionCacheLocation)) {
            $this->sessionCacheLocation = 
                sprintf('%swww-data/views/%s/default.txt',
                        CARTOWEB_HOME, $this->cartoclient->getConfig()->mapId);
        }
        return $this->sessionCacheLocation;
    }
    
    /**
     * Returns default session.
     * @return ClientSession
     */
    public function getDefaultSessionData() {
        $sessionData = new ClientSession;
        
        // gets default session from cached session file
        $path = $this->getSessionCacheLocation();
        if (is_readable($path)) {
            $sessionData->pluginStorage = unserialize(file_get_contents($path));
        }
        
        return $sessionData;
    }

    /**
     * Writes cached session file.
     * @param ClientSession
     * @return bool true if success
     */
    public function makeSessionCache(ClientSession $clientSession) {
        if (!$clientSession->pluginStorage) {
            return false;
        }
        
        $path = $this->getSessionCacheLocation();
        if (!is_dir(dirname($path))) {
            Utils::makeDirectoryWithPerms(dirname($path),
                $this->cartoclient->getConfig()->webWritablePath);
        }
        
        return file_put_contents($path,
                                 serialize($clientSession->pluginStorage));
    }
    
    /**
     * Main view handler: detects what action to perform.
     * @param ClientSession
     * @return bool true if some view has been processed
     */
    public function handleView(&$sessionData) {

        $savedMetasList = $this->getMetasList();
        $processed = false;
        
        do {
            // loading view?
            if (!empty($_REQUEST['viewBrowse']) || 
                !empty($_REQUEST['viewLoad'])) {

                if (!empty($_REQUEST['viewBrowse'])) {
                    $this->viewId = $this->getId('viewBrowseId');
                }
                
                if (!$this->viewId) {
                    $this->viewId = $this->getId('viewLoadId');
                }
                
                if (!$this->viewId) {
                    $this->viewId = $this->getId('viewLoadTitleId');
                }

                if (!$this->viewId) {
                    break;
                } else {
                    $this->message = '';
                }
    
                // gets view from storage
                $viewData = $this->wc->select($this->viewId);

                if (!$this->getActionSuccess()) {
                    $this->message = $this->wc->getMessage();
                    break;
                }
                
                $this->data = $this->wf->decapsulate($viewData);
                $this->data = $this->wf->checkVersion($this->data, $this->viewId);
    
                if ($this->data) {
                    // overrides session with view content
                    foreach (get_object_vars($this->data) as $plugin => $val) {
                        if (!in_array($plugin, $this->getViewablePlugins())) {
                            unset($this->data->$plugin);
                        }
                    }

                    // if no session is available yet, gets cached one:
                    if (empty($sessionData)) {
                        $sessionData = $this->getDefaultSessionData();
                    }
                    
                    $sessionData->pluginStorage = StructHandler::mergeOverride(
                                                   $sessionData->pluginStorage,
                                                   $this->data, true);
                    
                    // Transmits some REQUESTed data to keep user interface
                    // consistent + views plugin data.
                    $savedVars = sprintf('%s,%s,%s',
                                         'tool, js_folder_idx, project',
                                         'collapse_keymap, handleView',
                                         $this->cartoclient
                                              ->getConfig()->viewSavedRequest);
                    // FIXME: collapsible keymap status is not correcty passed
                    $savedVars = explode(',', $savedVars);
                    $savedVars = array_unique($savedVars);
                    $savedVars = array_map('trim', $savedVars);
                    $savedRequest = array();
                    foreach ($_REQUEST as $var => $val) {
                        if (substr($var, 0, 4) == 'view' || 
                            in_array($var, $savedVars)) {
                            $savedRequest[$var] = $val;
                        }
                    }
                    $_REQUEST = $_COOKIE + $savedRequest;
                    
                    // restore some metas for views plugin displaying
                    $this->getMetas();
                    unset($this->metas['weight']);
                    $this->metas['viewLocationId'] = $this->wc->getLocationId();
                
                    $processed = true;
                }
            }
            
            // saving new view?
            elseif (!empty($_REQUEST['viewSave'])) {
                
                // checks permission
                if (!$this->hasRole()) {
                    break;
                }
                
                $pluginSession = $this->getPluginSession($sessionData);
                $data = $this->wf->encapsulate($pluginSession);
                $this->setMetasFromRequest();
                $this->wc->insert($data, $this->metas);

                $processed = true;
            }
    
            // updating view?
            elseif (!empty($_REQUEST['viewUpdate'])) {

                // checks permission
                if (!$this->hasRole()) {
                    break;
                }

                $this->viewId = $this->getId('viewUpdateId');
                if (!$this->viewId) {
                    break;
                }
    
                $pluginSession = $this->getPluginSession($sessionData);
                $data = $this->wf->encapsulate($pluginSession);
                $this->setMetasFromRequest();
                $this->wc->update($this->viewId, $data, $this->metas);
            
                $processed = true;
            } 
            
            // deleting view?
            elseif (!empty($_REQUEST['viewDelete'])) {
                
                // checks permission
                if (!$this->hasRole()) {
                    break;
                }
                
                $this->viewId = $this->getId('viewDeleteId');

                if (!$this->viewId) {
                    break;
                }
                
                $this->wc->delete($this->viewId);

                $processed = true;
            }
        
            $this->message = $this->wc->getMessage();
        
        } while(false);

        $this->metasList = $savedMetasList;
        return $processed;
    }

    /**
     * Returns value of given request parameter if exists.
     * @param string parameter name
     * @return string parameter value
     */
    private function getRequestValue($name) {
        $req = array_key_exists($name, $_REQUEST) ? $_REQUEST[$name] : '';
        if ($req) {
            $req = strip_tags($req);
        }
        return $req;
    }

    /**
     * Sets metadata from request.
     */
    private function setMetasFromRequest() {
        foreach ($this->getMetasList() as $metaName) {
            $this->metas[$metaName] = $this->getRequestValue($metaName);
            
            if ($metaName == 'viewShow') {
                $this->metas[$metaName] = ($this->metas[$metaName] == 'on');  
            }
        }
    }

    /**
     * Returns view container instance (file or DB using).
     * @return ViewContainer
     */
    public function getViewContainer() {
        return $this->wc;
    }

    /**
     * Returns result message if any.
     * @return string
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * Returns current view ID.
     * @return int
     */
    public function getViewId() {
        if (!isset($this->viewId)) {
            $this->viewId = $this->wc->getViewId();
        }
        return $this->viewId;
    }

    /**
     * Returns ViewManager::metasList, list of metadata fields.
     * @return array list of metadata fields
     */
    public function getMetasList() {
        if (!isset($this->metasList)) {
            $metas = sprintf('%s,%s',
                             self::BASE_METAS,
                             $this->cartoclient->getConfig()->viewMetas);
            $metas = explode(',', $metas);
            $metas = array_unique($metas);
            $this->metasList = array_map('trim', $metas);
        }
        return $this->metasList;
    }

    /**
     * Returns view catalog.
     * @see ViewContainer::getCatalog()
     */
    public function getCatalog() {
        return $this->wc->getCatalog();
    }

    /**
     * Returns metadata.
     * @return array metadata
     */
    public function getMetas() {
        if (!isset($this->metas)) {
            $this->metas =& $this->wc->getMetas();
        }
        return $this->metas;
    }

    /**
     * @return boolean
     */
    public function getActionSuccess() {
        if (!isset($this->isActionSuccess)) {
            $this->isActionSuccess =& $this->wc->getActionSuccess();
        }
        return $this->isActionSuccess;
    }
}

/**
 * @param string raw plugin name
 * @return string client-side plugin classname
 */
function filterViewable($pluginName) {
    return 'Client' . ucfirst(trim($pluginName));
}

/**
 * Views writing/reading filters
 * @package Client
 */
class ViewFilter {

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var Cartoclient
     */
    private $cartoclient;

    /**
     * @var array
     */
    private $pluginsVersions = array();

    /**
     * @var stdClass
     */
    private $defaultSession;
    
    /**
     * Constructor
     * @param Cartoclient
     */
    public function __construct(Cartoclient $cartoclient) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->cartoclient = $cartoclient;
    }

    /**
     * Returns plugin base name.
     * @param string
     * @param bool if true, result is uppercased
     * @return string
     */
    private function getPluginName($pluginName, $uppercase = false) {
        // 6 = strlen('Client')
        $baseName = substr($pluginName, 6, strlen($pluginName) - 6);
        if ($uppercase) {
            return strtoupper($baseName);
        }
            
        return strtolower($baseName{0}) . substr($baseName, 1);
    }

    /**
     * Returns current version of given plugin session container.
     * @param string client plugin name.
     * @return int
     */
    public function getRecorderVersion($pluginName) {
        $versionMarker = sprintf('%s_SESSION_VERSION',
                                 $this->getPluginName($pluginName, true));
        return defined($versionMarker)
               ? eval('return ' . $versionMarker . ';') : 1;
    }
    
    /**
     * Encapsulates view data within an XML document.
     * @param stdClass
     * @return string
     */
    public function encapsulate($sessionData) {
        $plugins = array();
        foreach ($sessionData as $pluginName => $pluginData) {
            
            $plugins[$pluginName] = array(
                'recorderVersion' => $this->getRecorderVersion($pluginName),
                'data'            => htmlspecialchars($pluginData),
                );
        }

        $smarty = new Smarty_Cartoclient($this->cartoclient);
        $smarty->assign(array('charset' => Encoder::getCharset(),
                              'plugins' => $plugins,
                              ));
        return $smarty->fetch('viewdata.xml.tpl');
    }

    /**
     * Extracts view data from its XML storage.
     * @param string view in XML format
     * @return stdClass
     */
    public function decapsulate($viewXml) {
        $xml = simplexml_load_string($viewXml);
        $view = new stdClass;
        foreach ($xml->plugin as $plugin) {
            $pluginName = (string)$plugin['name'];
            $view->$pluginName = html_entity_decode(trim((string)$plugin));

            $this->pluginsVersions[$pluginName] = (int)$plugin['recorderversion']; 
        }
        return $view;
    }

    /**
     * Checks that view format is not outdated. If yes, tries to "repair".
     * @param  stdClass
     * @param int view id
     * @return stdClass
     */
    public function checkVersion($data, $viewId) {
       
        foreach ($data as $pluginName => &$pluginVal) {
            if ($this->pluginsVersions[$pluginName] 
                < $this->getRecorderVersion($pluginName)) {
                // view is outdated!

                $viewVersion = $this->pluginsVersions[$pluginName];
                $recorderVersion = $this->getRecorderVersion($pluginName);
                $msg = sprintf('view #%d outdated: %s is v%d, v%d expected.',
                               $viewId, $pluginName,
                               $viewVersion, $recorderVersion);
                $this->log->warn($msg);
              
                if ($this->cartoclient->getConfig()->viewUpgradeOutdated &&
                    $this->upgrade($pluginName, $pluginVal,
                                   $viewVersion, $recorderVersion)) {
                    // upgrades plugin view data format
                    $this->log->debug("Upgraded $pluginName view data");
                    $status = 'upgraded';
                } else {
                    // neutralizes the current plugin part
                    $this->log->debug("Disabled $pluginName view data");
                    unset($data->$pluginName);
                    $status = 'disabled';
                }

                // logs error
                if ($this->cartoclient->getConfig()->viewLogErrors) {
                    $msg = sprintf("* %s - mapId: %s - %s => %s\n",
                                   date('Y-m-d H:i:s'),
                                   $this->cartoclient->getConfig()->mapId,
                                   $msg, $status);
                    $fp = fopen(CARTOWEB_HOME . 'log/viewErrors.log', 'a');
                    fwrite($fp, $msg);
                    fclose($fp);
                }
            }
        }

        return $data;
    }

    /**
     * Returns path of plugin view filters file.
     * @param string plugin name
     * @return string filter filepath, empty if no file found
     */
    private function getFilterFilePath($pluginName) {
        $path = sprintf('/%s/client/ViewsUpgrade.php', $pluginName);
        $projectPath = sprintf('%s/%s/',
                               ProjectHandler::PROJECT_DIR,
                               $this->cartoclient->getProjectHandler()
                                                 ->getProjectName());
        $locations = array($projectPath . 'coreplugins',
                           $projectPath . 'plugins',
                           'coreplugins', 'plugins');
        foreach ($locations as $location) {
            $testedPath = CARTOWEB_HOME . $location . $path;
            if (file_exists($testedPath)) {
                return $testedPath;
            }
        }

        return '';
    }

    /**
     * Detects sequence of filters needed to upgrade data.
     * @param string plugin name
     * @param int initial version
     * @param int final version
     * @return array empty if detection failed
     */
    private function getFiltersSequence($pluginName, $initialVersion,
                                                     $finalVersion) {
        $name = ucfirst($pluginName);
        $sequence = array();
        for ($i = $initialVersion; $i < $finalVersion; $i++) {
            // filtername format is for instance MyPluginV34ToV35
            $filterName = sprintf('%sV%dToV%d', $name, $i, $i + 1);
            if (!class_exists($filterName) || 
                !is_subclass_of($filterName, 'ViewUpgrader')) {
                $this->log->warn("Filter $filterName not found");
                return array();
            }
            $sequence[] = $filterName;
        }
        return $sequence;
    }

    /**
     * Returns cached session plugin storage.
     * @param string plugin name, if empty method will return full storage 
     * @return mixed
     */
    private function getDefaultData($storage = '') {
        if (!isset($this->defaultSession)) {
            $this->defaultSession = $this->cartoclient->getViewManager()
                                         ->getDefaultSessionData()
                                         ->pluginStorage;
        }
        
        if (!$storage) {
            return $this->defaultSession;
        }
        
        if (isset($this->defaultSession->$storage)) {
            return unserialize($this->defaultSession->$storage);
        }
        
        return ''; 
    }
    
    /**
     * Tries to upgrade view data format.
     *
     * WARNING: only works with object formats!
     * @param string plugin classname
     * @param string serialized plugin view data
     * @param int initial version
     * @param int final version
     * @return bool true if success
     */
    private function upgrade($pluginName, &$pluginVal, 
                            $initialVersion, $finalVersion) {
       
        $shortName = $this->getPluginName($pluginName);
        
        // checks if conversion filter is available
        $filterPath = $this->getFilterFilePath($shortName);
        if (!$filterPath) {
            $this->log->warn("Failed finding $pluginName filters file"); 
            return false;
        }
        require_once($filterPath);

        // checks that correct sequence of filters is available
        $sequence = $this->getFiltersSequence($shortName, $initialVersion, 
                                                           $finalVersion);
        if (!$sequence) {
            return false;
        }
        
        // performs sequentially filters transformations
        $data = unserialize($pluginVal);
        
        foreach ($sequence as $filter) {
            $f = new $filter;
            if (!$f->upgrade($data)) {
                $this->log->warn("$filter upgrade failed");
                return false;
            }
        }
        
        // merges with default session to retrieve missing data
        // FIXME: does not work => merge comes too late, all default session
        // data are crushed when merging.
        /*if ($defaultData = $this->getDefaultData($pluginName)) {
            $data = StructHandler::mergeOverride($defaultData, $data, true); 
        }*/

        $pluginVal = serialize($data);
        
        // TODO: save upgraded view data to avoid re-upgrading next time?
        
        return true;
    }
}

/**
 * Views recording/loading.
 * @package Client
 */
abstract class ViewContainer {

    /**
     * @var Logger
     */
    protected $log;
    
    /**
     * @var string
     */
    protected $action;

    /**
     * @var int
     */
    protected $viewId;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $metas;

    /**
     * @var array
     */
    protected $catalog;

    /**
     * @var array
     */
    protected $writeActions = array('insert', 'update', 'delete');

    /**
     * @var boolean
     */
    protected $isActionSuccess;
    
    /**
     * @var int
     */
    protected $weight;

    /**
     * @var int
     */
    protected $locationId = 0;

    const BASE_WEIGHT = 100000;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }
    
    /**
     * Opens a connection to storage resource.
     */
    abstract protected function openResource();

    /**
     * Closes connection to storage resource.
     */
    abstract protected function closeResource();

    /**
     * Processes (read/write) content of storage resource.
     */
    abstract protected function processResource();

    /**
     * Locks access to storage resource.
     */
    abstract protected function lockResource();

    /**
     * Unlocks access to storage resource.
     */
    abstract protected function unlockResource();

    /**
     * Updates metadata with weight info and removes viewLocationId info.
     */
    protected function setWeightMeta() {
        $this->computeWeight();

        // removes viewLocationId meta
        unset($this->metas['viewLocationId']);

        // adds weight meta
        $this->metas['weight'] = $this->weight;
    }

    /**
     * Sets various data while loading a view.
     */
    protected function setSelectData() {
        $this->locationId = $this->computeLocationId();
    }

    /**
     * Performs view processing.
     * @param string action type
     * @return boolean if true, action succeeded
     */
    private function doAction($action) {
        $this->action = $action;

        $this->isActionSuccess = false;
        if ($this->openResource()) {
            
            if ($this->action != 'select') {
                $this->getCatalog();
                $this->lockResource();
                if ($this->action != 'delete') {
                    $this->setWeightMeta();
                }
            } else {
                $this->setSelectData();   
            }
            
            $this->isActionSuccess = $this->processResource();
            
            if ($this->action != 'select') {
                $this->unlockResource();
            }
            
            $this->closeResource();

            if ($this->action != 'select') {
                $isCatalogWritten = $this->writeCatalog();
                // TODO: if error while writing catalog => disp error message?
            }
        }

        if (empty($this->message)) {
            switch ($this->action) {
                case 'select':
                    $this->message = sprintf(I18n::gt('Loaded view #%d'),
                                             $this->viewId);
                    break;

                case 'insert':
                    $this->message = sprintf(
                                      I18n::gt('New view recorded with id %d'),
                                      $this->viewId);
                    break;
                
                case 'update':
                    $this->message = sprintf(I18n::gt('View #%d updated'),
                                             $this->viewId);
                    break;

                case 'delete':
                    $this->message = sprintf(I18n::gt('View #%d deleted'),
                                             $this->viewId);
                    break;
            }
        }

        return $this->isActionSuccess;
    }
    
    /**
     * Wrapper for 'select' action.
     * @param int view ID
     * @return stdClass plugin storage infos
     */
    public function select($viewId) {
        $this->viewId = $viewId;
        $this->doAction('select');
        return $this->data;
    }

    /**
     * Wrapper for 'insert' action.
     * @param stdClass collection of viewable plugins state objects
     * @param array metadata
     */
    public function insert($data, $metas) {
        $this->data =& $data;
        $this->metas =& $metas;
        $this->doAction('insert');
    }

    /**
     * Wrapper for 'update' action.
     * @param int view ID
     * @param stdClass collection of viewable plugins state objects
     * @param array metadata
     */
    public function update($viewId, $data, $metas) {
        $this->viewId = $viewId;
        $this->data =& $data;
        $this->metas =& $metas;
        $this->doAction('update');
    }

    /**
     * Wrapper for 'delete' action.
     * @param int view ID
     */
    public function delete($viewId) {
        $this->viewId = $viewId;
        $this->doAction('delete');
    }

    /**
     * Returns views catalog.
     * @return array 
     */
    public function getCatalog() {
        if (!isset($this->catalog)) {
            $this->catalog = $this->readCatalog();
        }
        return $this->catalog;
    }

    /**
     * Reads views catalog content.
     */
    abstract protected function readCatalog();

    /**
     * Writes views catalog content.
     */
    abstract protected function writeCatalog();

    /**
     * Adds new/updated view in views catalog.
     */
    protected function updateCatalog() {
        if ($this->action == 'delete') {
            unset($this->catalog[$this->viewId]);
            return;
        }
        
        $viewTitle = !empty($this->metas['viewTitle']) ?
                     $this->metas['viewTitle'] : 'view ' . $this->viewId;
        $viewShow = (bool)$this->metas['viewShow'];
        $weight = $this->metas['weight'];

        $this->catalog[$this->viewId] = array('viewTitle' => $viewTitle,
                                              'viewShow'  => $viewShow,
                                              'weight'    => $weight);
    }

    /**
     * Returns natural-orderedviews weights list.
     * @return array
     */
    private function getWeights() {
        $weights = array();
        foreach ($this->getCatalog() as $id => $data) {
            $weights[$id] = $data['weight'];
        }
        natsort($weights);
        return $weights;
    }

    /**
     * Computes weight of current view to determine its location 
     * in the views list.
     */
    private function computeWeight() {

        if (!isset($_REQUEST['viewSave']) && 
            empty($_REQUEST['viewLocationUpdate'])) {
            // weight is updated only when creating a view or when
            // the view location has changed.
            $this->weight = $this->catalog[$this->viewId]['weight'];
            return;
        }
       
        if (isset($_REQUEST['viewSave']) && 
            empty($this->metas['viewLocationId'])) {
           
            // default: weight is a multiple of self::BASE_WEIGHT
            if (empty($this->viewId)) {
                $this->setViewId();
            }
            $this->weight = self::BASE_WEIGHT * $this->viewId; 
        
        } elseif (isset($this->metas['viewLocationId'])) {
        
            $viewLocId =& $this->metas['viewLocationId'];
           
            $weights = $this->getWeights();
            $ids = array_keys($weights);
            
            if ($viewLocId == 0) {
                // place view at the end of the list
                $maxViewId = max($ids);
                $this->weight = $weights[$maxViewId] +
                                self::BASE_WEIGHT * ($maxViewId + 1);
            } else {
                $k = array_search($viewLocId, $ids);
            
                if ($k === 0) {
                    // place view at the beginning of the list
                    $this->weight = $weights[$viewLocId];
                } else {
                    $prevViewLocId = $ids[$k - 1];
                    $this->weight = $weights[$viewLocId] +
                                    $weights[$prevViewLocId];
                }
            }
            // weight is the mean of the weights of the views
            // right near the view wished location
            $this->weight = floor($this->weight / 2);
        }
    }

    /**
     * Returns ID of view located right after current view.
     * @return int
     */
    private function computeLocationId() {
        $weights = $this->getWeights();
        $ids = array_keys($weights);
        $kmax = max(array_keys($ids));
        $k = array_search($this->viewId, $ids);
        
        if ($k === false || $k == $kmax) {
            return 0;
        }
        
        return $ids[$k + 1];
    }

    /**
     * Computes next available view ID.
     * @return int
     */
    protected function setViewId() {
        $this->getCatalog();    
        if (!$this->catalog) {
            $this->viewId = 1;
        } else {
            $this->viewId = max(array_keys($this->catalog)) + 1;    
        }
    }

    /**
     * @return string action result message
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * @return int current view ID
     */
    public function getViewId() {
        return isset($this->viewId) ? $this->viewId : '';
    }

    /**
     * Returns metadata.
     * @return array
     */
    public function getMetas() {
        return $this->metas;
    }

    /**
     * @return boolean
     */
    public function getActionSuccess() {
        return $this->isActionSuccess;
    }

    /**
     * @return int
     */
    public function getLocationId() {
        return $this->locationId;
    }
}

/**
 * Views recording/loading to/from file.
 * @package Client
 */
class ViewFileContainer extends ViewContainer {
    
    /**
     * @var string
     */
    private $viewPath;
    
    /**
     * @var string
     */
    private $handle;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var string
     */
    private $catalogFile;

    /**
     * @var Cartoclient
     */
    private $cartoclient;

    /**
     * Constructor
     * @param Cartoclient
     */
    public function __construct(Cartoclient $cartoclient) {
        parent::__construct();
        $this->log->debug('Using File storage as ViewContainer.');

        $this->cartoclient = $cartoclient;

        $this->viewPath = CARTOWEB_HOME . 'www-data/views/';
        $this->viewPath .= $cartoclient->getConfig()->mapId . '/';
       
        if (!is_dir($this->viewPath)) {
            Utils::makeDirectoryWithPerms($this->viewPath, 
                $this->cartoclient->getConfig()->webWritablePath);
        }
       
        $this->catalogFile = $this->viewPath . 'catalog.txt';
    }

    /**
     * @see ViewContainer::readCatalog()
     * @return array
     */
    protected function readCatalog() {
        if (is_readable($this->catalogFile)) {
            $f = fopen($this->catalogFile, 'r');
            if (in_array($this->action, $this->writeActions)) {
                flock($f, LOCK_EX);
            }
            $catalog = unserialize(fread($f, filesize($this->catalogFile)));
            fclose($f);
        }
        
        if (empty($catalog) || !is_array($catalog)) {
            return array();
        }

        return $catalog;
    }

    /**
     * @see ViewContainer::writeCatalog()
     */
    protected function writeCatalog() {
        if (!isset($this->catalog)) {
            return false;
        }

        $f = fopen($this->catalogFile, 'w');
        if (is_writable($this->catalogFile)) {
            if (in_array($this->action, $this->writeActions)) {
                flock($f, LOCK_EX);
            }
            fwrite($f, serialize($this->catalog));
            fclose($f);
            return true;
        }

        fclose($f);
        return false;
    }

    /**
     * Returns view-storage filepath.
     * @return string filepath
     */
    private function getFileName() {
        if (!isset($this->fileName)) {
            if (empty($this->viewId)) {
                $this->setViewId();
            }
            
            $this->fileName = $this->viewPath . $this->viewId . '.txt';
        }
        return $this->fileName;
    }
    
    /**
     * Opens view-storage file.
     * @see ViewContainer::openResource()
     * @return boolean true if success
     */
    protected function openResource() {
        $mode = ($this->action == 'select') ? 'r' : 'w';
        $this->getFileName();
        
        
        $this->handle = @fopen($this->fileName, $mode);
        if (!$this->handle) {
            $this->message = sprintf(I18n::gt('Failed to open view #%d'), 
                                     $this->viewId);
            return  false;
        }
        return true;
    }

    /**
     * Closes view-storage file.
     * @see ViewContainer::closeResource()
     */
    protected function closeResource() {
        fclose($this->handle);
    }

    /**
     * @see ViewContainer::processResource()
     * @return boolean true if success
     */
    protected function processResource() {
        switch ($this->action) {
            
            case 'select':
                if (!is_readable($this->fileName)) {
                    $this->message = sprintf(I18n::gt('Unable to read view #%d'),
                                             $this->viewId);
                    $this->data = false;
                    return false;
                }
                $data = fread($this->handle, filesize($this->fileName));
                $this->readXmlContent($data);
                break;

            case 'insert':
            case 'update':
                if (!is_writeable($this->fileName)) {
                    $this->message = sprintf(I18n::gt('Unable to write view #%d'),
                                             $this->viewId);
                    return false;
                }
                if (fwrite($this->handle, $this->writeXmlContent())) {
                    $this->updateCatalog();
                }
                break;
        }

        return true;
    }

    /**
     * @see ViewContainer::lockResource()
     */
    protected function lockResource() {
        return flock($this->handle, LOCK_EX);
    }
    
    /**
     * @see ViewContainer::unlockResource()
     */
    protected function unlockResource() {
        flock($this->handle, LOCK_UN);
    }

    /**
     * Reads XML content and assigns view info.
     * @param string
     */
    private function readXmlContent($content) {
        $xml = simplexml_load_string($content);
    
        $this->data = html_entity_decode((string)$xml->sessionData);
        
        $metas = get_object_vars($xml->metadata);
        $this->metas = array();
        foreach ($metas as $name => $val) {
            $this->metas[$name] =  html_entity_decode((string)$val);
        }
    }

    /**
     * Writes an XML string containing the view data and metadata.
     * @return string XML
     */
    private function writeXmlContent() {
        $data = htmlspecialchars($this->data);
        $this->metas = array_map('htmlspecialchars', $this->metas);
    
        $smarty = new Smarty_Cartoclient($this->cartoclient);

        $smarty->assign(array('charset'     => Encoder::getCharset(),
                              'metas'       => $this->metas,
                              'sessionData' => $data,
                              ));
        
        return $smarty->fetch('view.xml.tpl');
    }

    /**
     * Deletes given view.
     * @see ViewContainer::delete()
     * @return boolean true if deletion succeeded
     */
    public function delete($viewId) {
        $this->viewId = $viewId;
        $this->getFileName();
        $deleted = (file_exists($this->fileName) && unlink($this->fileName));
        if ($deleted) {
           $this->getCatalog();
            unset($this->catalog[$viewId]);
            $this->writeCatalog();
            
            $this->message = sprintf(I18n::gt('View #%d deleted'), 
                                     $this->viewId);
        } else {
            $this->message = sprintf(I18n::gt('Failed deleting view #%d'),
                                     $this->viewId);
        }
        return $deleted;
    }
}

/**
 * Views recording/loading to/from database.
 * @package Client
 */
class ViewDbContainer extends ViewContainer {

    /**
     * @var string
     */
    private $dsn;
    
    /**
     * @var DB
     */
    private $db;

    /**
     * @var array
     */
    private $metasList;

    /**
     * @var boolean
     */
    private $showDevMsg;
    
    /**
     * Constructor
     * @param string database DSN
     * @param array list of metadata fields names
     * @param boolean if true, DB error messages are more verbose
     */
    public function __construct($dsn, $metasList, $showDevMsg) {
        parent::__construct();
        $this->log->debug('Using DB storage as ViewContainer.');
        
        $this->dsn =& $dsn;
        $this->metasList =& $metasList;
        $this->showDevMsg = $showDevMsg;

        require_once 'DB.php';
    }

    /**
     * @see ViewContainer::openResource()
     */
    protected function openResource() {
        $options = array('persistent' => true);
        $this->db =& DB::connect($this->dsn, $options);
        if (DB::isError($this->db)) {
            $message = $this->db->getMessage();
            $this->message = $this->showDevMsg ?
                             I18n::gt('Unable to open ViewDbContainer: ')
                             . $message
                             : I18n::gt('Service failed.');
            $this->log->warn('Unable to open ViewDbContainer: ' . $message);
            return false;
        }
        return true;
    }

    /**
     * @see ViewContainer::closeResource()
     */
    protected function closeResource() {
        // FIXME: if we close connection here, external requests such as
        // views listing asked by Views plugin crash...
        //$this->db->disconnect();
    }

    /**
     * @see ViewContainer::processResource()
     */
    protected function processResource() {
 
        if (isset($this->viewId)) {
            $this->viewId = addslashes($this->viewId);
        } else {
            $this->setViewId();
        }

        switch ($this->action) {

            case 'select':
                $sql = sprintf('SELECT %s, sessiondata
                                FROM views WHERE views_id = %d',
                               strtolower(implode(',', $this->metasList)), 
                               $this->viewId);
                break;

            case 'insert':
                $this->filterMetas();
                $sql = sprintf("INSERT INTO views (views_id, views_ts, sessiondata,
                               %s) VALUES (%d, 'now()', '%s', '%s')",
                               implode(', ', $this->metasList),
                               $this->viewId,
                               addslashes($this->data),
                               implode("', '", $this->metas)
                               );
                break;
                
            case 'update':
                $this->filterMetas();
                $sql = sprintf("UPDATE views 
                               SET views_ts = 'now()', sessiondata = '%s', 
                                %s WHERE views_id = %d",
                               addslashes($this->data),
                               $this->makeMetasSql(),
                               $this->viewId);
                break;

            case 'delete':
                $sql = sprintf('DELETE FROM views WHERE views_id = %d', 
                               $this->viewId);
                break;         
        }

        $res =& $this->db->query($sql);
        if (DB::isError($res)) {
            $message = sprintf('%s : %s',
                               $res->getMessage(), $res->getUserInfo());
            $this->message = I18n::gt('Unable to process view.');
            $this->message .= $this->showDevMsg ? $message
                              : I18n::gt('Service failed.');
            $this->log->warn('Unable to process view.' . $message);
            return false;
        }

        switch ($this->action) {
        
            case 'select':
                if (!$res->numRows()) {
                    $this->message = sprintf(I18n::gt('No result for view #%d'),
                                             $this->viewId);
                    return false;
                }
    
                $row =& $res->fetchRow(DB_FETCHMODE_OBJECT);
                $this->data = $row->sessiondata;
                
                $this->metas = array();
                foreach ($this->metasList as $meta) {
                    $dbmeta = strtolower($meta);
                    $this->metas[$meta] = isset($row->$dbmeta) 
                                          ? $row->$dbmeta : '';
                }
                if (isset($this->metas['viewShow'])) {
                    $this->metas['viewShow'] = 
                        $this->getBool($this->metas['viewShow']);
                }
                break;

            case 'update':
            case 'delete':
                if ($this->db->affectedRows() > 1) {
                    $this->message = sprintf(I18n::gt(
                        'More than one view affected while editing view #%d'),
                                             $this->viewId);
                    return false;
                }
                
                $this->updateCatalog();
                break;

            case 'insert':
                $this->updateCatalog();
                break;
        }
        
        return true;
    }

    /**
     * Performs some manipulations on metadata.
     */
    private function filterMetas() {
        if (isset($this->metas['viewShow']) && $this->metas['viewShow'] == '') {
            $this->metas['viewShow'] = 0;
        }

        foreach ($this->metas as &$meta) {
            $meta = addslashes($meta);
        }
    }

    /**
     * Removes/adds some metas titles.
     */
    private function updateMetasList() {
        $k = array_search('viewLocationId', $this->metasList);
        unset($this->metasList[$k]);

        array_push($this->metasList, 'weight');
    }

    /**
     * @see ViewContainer::setWeightMeta()
     */
    protected function setWeightMeta() {
        parent::setWeightMeta();
        $this->updateMetasList();
    }

    /**
     * @see ViewContainer::setSelectData()
     */
    protected function setSelectData() {
        parent::setSelectData();
        $this->updateMetasList();
    }

    /**
     * Builds the metadata querystring
     * @return string
     */
    private function makeMetasSql() {
        $str = array();
        foreach ($this->metas as $name => $val) {
            $str[] = "$name='$val'";
        }
        return implode(',', $str);
    }
    
    /**
     * @see ViewContainer::lockResource()
     */
    protected function lockResource() {
        // TODO
        /*
        switch (substr($this->dsn, 0, 5)) {
            // TODO: use SQL SET TRANSACTION?
            
            case 'pgsql':
                $sql = 'LOCK TABLE views IN SHARE ROW EXCLUSIVE MODE';
                break;

            case 'mysql':
                $sql = 'LOCK TABLES views';
                break;

            default:
                // no lock?
        }*/
    }
    
    /**
     * @see ViewContainer::unlockResource()
     */
    protected function unlockResource() {
        // nothing to do: locks are release automatically at end of script
    }

    /**
     * @see ViewContainer::readCatalog()
     */
    protected function readCatalog() {
  
        $catalog = array();
        
        if (!isset($this->db) && !$this->openResource()) {
            return $catalog;
        }
  
        // TODO: lock
         
        $sql = 'SELECT views_id, viewtitle, viewshow, weight FROM views';
        $res =& $this->db->query($sql);
        if (DB::isError($res)) {
            $this->message = I18n::gt('Unable to build views catalog');
            return $catalog;
        }

        
        while ($row =& $res->fetchRow(DB_FETCHMODE_OBJECT)) {
            $viewShow = $this->getBool($row->viewshow);
            $catalog[$row->views_id] = array('viewTitle' => $row->viewtitle,
                                             'viewShow'  => $viewShow,
                                             'weight'    => $row->weight,
                                             );
        }

        return $catalog;
    }

    /**
     * Tells if given value is true or false.
     * @param value to test
     * @return boolean
     */
    private function getBool($info) {
        return (!empty($info) && $info != 'f'); 
    }

    /**
     * @see ViewContainer::writeCatalog()
     */
    protected function writeCatalog() {
        // Nothing to do (everything is done in processResource())
    }
}

/**
 * Basis of views upgrade filters
 *
 * This class must be extended to define plugin view data filters.
 * Extended classes must be stored in &lt;plugin&gt;/client/ViewsUpgrade.php
 * and named for instance MyPluginV34ToV35. Each filter must be design to
 * upgrade view data from given version N to version N+1.
 *
 * This class provides generic filtering methods
 * @package client
 */
abstract class ViewUpgrader {

    /**
     * @var stdClass
     */
    protected $storage;

    /**
     * Upgrades given plugin storage. 
     * @param stdclass plugin storage to upgrade
     * @return bool true if success
     */
    public function upgrade(&$storage) {
        // currently only supports object containers
        // TODO: support arrays by converting them to objects?
        if (!is_object($storage)) {
            return false;
        }
        $this->storage =& $storage;
        $this->callFilters();
        return true;
    }

    /**
     * Executes upgrade filters. To be redefined in extended filters.
     */
    abstract protected function callFilters();

    /**
     * Removes given property.
     * @param string property name
     */
    protected function remove($from) {
        unset($this->storage->$from);
    }

    /**
     * Adds a new property.
     * @param string new property name
     * @param mixed value of new property
     */
    protected function add($to, $value) {
        if (is_object($value)) {
            $this->storage->$to = StructHandler::deepClone($value);
        } else {
            $this->storage->$to = $value;
        }
    }
    
    /**
     * Updates name of property.
     * @param string old name
     * @param string new name
     */
    protected function rename($from, $to) {
        if (!isset($this->storage->$from)) {
            return;
        }
        $this->add($to, $this->storage->$from);
        $this->remove($from);
    }

    // TODO: define a method to retrieve some data in cached default session???

    // Define filter-specific transformers in extended class!
}
?>
