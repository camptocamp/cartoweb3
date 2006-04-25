<?php
/**
 * Client authentication based on PEAR:Auth
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
 * 
 * @package Plugins
 * @version $Id$
 */
 
require_once('Auth/Auth.php');
require_once('Auth/Container.php');

require_once(CARTOWEB_HOME . 'common/SecurityManager.php');

/**
 * Security container which reads the usernames, passwords and roles out of a
 * plugin .ini file.
 * @package Plugins
 */
class IniSecurityContainer extends SecurityContainer {

    /**
     * The map of usernames => passwords
     * @var array
     */
    protected $passwordsMap = array();
    
    /**
     * The map of usernames => roles
     * @var array
     */
    protected $roleMap = array();

    /**
     * Constructor, taking a plugin configuration.
     * 
     * @param ClientPluginConfig the plugin configuration to use for fetching
     * users and roles.
     */
    public function __construct(ClientPluginConfig $config) {
        foreach($config->getIniArray() as $key => $val) {
            if (strpos($key, 'users.') === 0) {
                $user = substr($key, strlen('users.'));
                $this->passwordsMap[$user] = $val;
            }
            if (strpos($key, 'roles.') === 0) {
                $user = substr($key, strlen('roles.'));
                $roles = Utils::parseArray($val);
                $this->roleMap[$user] = $roles;
            }
        }
    }

    /**
     * @see SecurityContainer::checkUser()
     */
    public function checkUser($username, $password) {
         if (!array_key_exists($username, $this->passwordsMap))
            return false;
         $md5 = md5($password);
         return $md5 == $this->passwordsMap[$username];               
    }

    /**
     * @see SecurityContainer::getRoles()
     */     
    public function getRoles($username) {
        if (array_key_exists($username, $this->roleMap))
            return $this->roleMap[$username];
        return array();
    }
}

/**
 * Security container which reads the usernames, passwords and roles 
 * from database.
 * @package Plugins
 */
class DbSecurityContainer extends SecurityContainer {

    /**
     * Database object
     * @var DB
     */
    protected $db;

    /**
     * @var ClientPluginConfig
     */
    protected $config;

    /**
     * Constructor
     */
    public function __construct(ClientPluginConfig $config) {
        require_once('DB.php');
        $this->config = $config;                
    }

    /**
     * Returns the Pear::DB database connection.
     * @return DB
     */    
    protected function getDb() {
        if ($this->db)
            return $this->db;
        
        if (!$this->config->dbSecurityDsn)
            throw new CartoclientException('Missing dbSecurityDsn parameter');
        $dsn = $this->config->dbSecurityDsn;
        
        $this->db = DB::connect($dsn);
        Utils::checkDbError($this->db);
        return $this->db;        
    }

    /**
     * @see SecurityContainer::checkUser()
     */
    public function checkUser($username, $password) {

        $db = $this->getDb();
        $exists = $db->query(sprintf($this->config->dbSecurityQueryUser,
                                addslashes($username), addslashes($password)));
        Utils::checkDbError($exists);        

        return !is_null($exists->fetchRow());
    }

    /**
     * @see SecurityContainer::getRoles()
     */     
    public function getRoles($username) {

        $db = $this->getDb();
        
        // FIXME: roles are in coma separated string value. We should 
        //  support queries returning multiple rows, one per role.
        
        $roles = $db->getOne(sprintf($this->config->dbSecurityQueryRoles,
                                addslashes($username)));
        Utils::checkDbError($roles);        

        if (is_null($roles))
            return array();

        return Utils::parseArray($roles);
    }
}

/**
 * Extends the PEAR::Auth container to proxy if authentication requests to the
 * SecurityManager.
 * @package Plugins
 */
class ProxyAuthContainer extends Auth_Container {
    
    /**
     * Current security manager where username/password authentication 
     * request are proxied.
     * @var SecurityManager
     */
    protected $securityManager;
    
    /**
     * Constructor
     * 
     * @param SecurityManager the manager where to proxy auth requests.
     */
    public function __construct(SecurityManager $securityManager) {
        $this->securityManager = $securityManager;
    } 
    
    /**
     * @see Auth_Container::fetchData
     */
    public function fetchData($username, $password){    
        return $this->securityManager->checkUser($username, $password);
    }
}

/**
 * Client authentication plugin based on PEAR:Auth
 */
class ClientAuth extends ClientPlugin implements GuiProvider, ServerCaller {
    
    /**
     * Pear::Auth object for managing the authentication
     * @var Auth
     */
    protected $auth;
 
    /**
     * True to store the fact that the user authentication failed.
     * @var boolean
     */
    protected $loginFailed;

    /**
     * Avoids to display the login form twice in some cases.
     * @var boolean
     */
    protected $isFormDisplayed = false;

    /**
     * Auth session name prefix
     */
    const AUTH_SESSION_KEY = 'CW3_auth_session_key';

    /**
     * Common code called by all Pear::Auth callbacks. It handles the login page
     * display.
     * @param string reason the page is called for
     * @return string Smarty result
     */
    protected function authCallback($reason) {

        if ($this->isFormDisplayed) {
            return '';
        }
        $this->isFormDisplayed = true;
        
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);

        if ($this->loginFailed) {
            $reason = 'loginFailed';
            $this->loginFailed = false;
        }
        
        $fullPageRestriction = ($this->getCartoclient()->getConfig()
                                ->securityAllowedRoles != 'all');
        
        $smarty->assign(array('reason'              => $reason,
                              'fullPageRestriction' => $fullPageRestriction,
                              ));
        
        return $smarty->fetch('login_page.tpl');
    }
    
    /**
     * Callback for Pear::Auth, when the user logs in
     */
    public function loginCallback() {
        $output = $this->authCallback('login');
        $this->cartoclient->getFormRenderer()->setSpecialOutput($output);
    }
    
    /**
     * Callback for Pear::Auth, when the user logs out
     */
    public function logoutCallback() {
        $output = $this->authCallback('logout');
        $this->cartoclient->getFormRenderer()->setSpecialOutput($output);
    }
    
    /**
     * Callback for Pear::Auth, when the login failed
     */
    public function failedLoginCallback() {
        $this->loginFailed = true;
    }

    /**
     * Returns the security container. The security container class to use is chosen 
     *  from the securityContainer config parameter.
     *
     * @return SecurityContainer
     */
    protected function getSecurityContainer() {
    
        $securityContainer = $this->getConfig()->securityContainer;
        if (!$securityContainer)
            $securityContainer = 'ini';
        
        $securityContainerClass = ucfirst($securityContainer) . 'SecurityContainer';
        
        if (!class_exists($securityContainerClass)) 
            throw new CartoclientException(
                             "Invalid security container: $securityContainer");
        
        $iniContainer = new $securityContainerClass($this->getConfig());
        return $iniContainer;       
    }

    /**
     * @see PluginBase::initialize()
     */
    public function initialize() {

        $securityManager = SecurityManager::getInstance();
        $securityManager->setSecurityContainer($this->getSecurityContainer());
        
        $proxyAuthContainer = new ProxyAuthContainer($securityManager);
        
        // FIXME: this is an ugly hack to prevent the unit tests to shout.
        //  A better solution is to be found.
        if (isset($GLOBALS['headless'])) {
            return;
        }
       
        $authSessionName = str_replace(Cartoclient::CLIENT_SESSION_KEY,
                                       self::AUTH_SESSION_KEY,
                                       $this->cartoclient->getSessionName());
        $options = array('sessionName' => $authSessionName);
       
        $this->auth = new Auth($proxyAuthContainer, $options, 
                               array($this, 'loginCallback'), true);

        $this->auth->setLogoutCallback(array($this, 'logoutCallback'));
        $this->auth->setFailedLoginCallback(array($this, 'failedLoginCallback'));

        $this->auth->setShowLogin(false);
        $this->auth->start();
        
        $username = $this->auth->getUsername();
        $securityManager->setUser($username);
        
        if (empty($this->loginFailed) && 
            $this->getCartoclient()->clientAllowed()) {
            return;
       }

        $this->showLogin();
    }
    
    /**
     * Interrupts CartoWeb flow of operation. Necessary, when displaying the
     * login/logout page.
     */
    protected function interruptFlow() {
        $formRenderer = $this->getCartoclient()->getFormRenderer();
        $formRenderer->setCustomForm(false);
        $this->getCartoclient()->setInterruptFlow(true);
    }
    
    /**
     * Displays the Pear::Auth login page. It interrupts cartoweb flow.
     */
    protected function showLogin() {
        $showLogin = $this->auth->showLogin;
        $this->auth->setShowLogin(true);
        $this->auth->login();
        $this->auth->setShowLogin($showLogin);
        $this->interruptFlow();
    }

    /**
     * Handles Get and Post requests
     */
    protected function handleHttpCommonRequest($request){
        if (isset($request['logout'])) {
            $this->auth->logout();
            $this->interruptFlow();
        }

        if (isset($request['login'])) {
            $this->showLogin();
        }
    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {
        $this->handleHttpCommonRequest($request);
    }

    /**
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {
        $this->handleHttpCommonRequest($request);
    }

    /**
     * Draws the login/logout buttons. Their availibility depends on whether the
     * user is logged in or not.
     */
    protected function drawAuth() {

        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $anonymous = SecurityManager::getInstance()->hasRole(
                                            SecurityManager::ANONYMOUS_ROLE);
        $smarty->assign('show_login', $anonymous);
        $smarty->assign('show_logout', !$anonymous);
        return $smarty->fetch('auth.tpl');
    }

    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $smarty) {

        $auth_active = $this->getConfig()->authActive;

        $smarty->assign(array('auth_active' => $auth_active));

        if ($auth_active)
            $smarty->assign('auth', $this->drawAuth());
    }   
    
    /**
     * @see ServerCaller::buildRequest()
     */
    public function buildRequest() {
        // TODO
    }

    /**
     * @see ServerCaller::initializeResult()
     */ 
    public function initializeResult($result) {
        // TODO
    }

    /**
     * @see ServerCaller::handleResult()
     */ 
    public function handleResult($outlineResult) {}
}

?>
