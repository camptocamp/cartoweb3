<?php
/**
 * Client authentification based on PEAR:Auth
 * 
 * @package Plugins
 * @version $Id$
 */
 
require_once('Auth/Auth.php');
require_once('Auth/Container.php');

require_once(CARTOCOMMON_HOME . 'common/SecurityManager.php');

/**
 * Security container which reads the usernames, passwords and roles out of a
 * plugin .ini file.
 */
class IniSecurityContainer extends SecurityContainer {

    /**
     * The map of usernames => passwords
     * @var array
     */
    private $passwordsMap = array();
    
    /**
     * The map of usernames => roles
     * @var array
     */
    private $roleMap;

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
                $roles = ConfigParser::parseArray($val);
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
 * Extends the PEAR::Auth container to proxy is authentification requests to the
 * SecurityManager.
 */
class ProxyAuthContainer extends Auth_Container {
    
    /**
     * Current security manager where username/password authentification 
     * request are proxied.
     * @var SecurityManager
     */
    private $securityManager;
    
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
 * Client authentification plugin based on PEAR:Auth
 */
class ClientAuth extends ClientPlugin implements GuiProvider, ServerCaller {
    
    /**
     * Pear::Auth object for managing the authentication
     * @var Auth
     */
    private $auth;
 
    /**
     * True to store the fact that the user authentication failed.
     * @var boolean
     */
    private $loginFailed;
 
    /**
     * Common code called by all Pear::Auth callbacks. It handles the login page
     * display.
     */
    private function authCallback($reason) {
        
        $smarty = new Smarty_CorePlugin($this->getCartoclient(), $this);

        if ($this->loginFailed) {
            $reason = 'loginFailed';
            $this->loginFailed = false;
        }
        $smarty->assign('reason', $reason);
        
        return $smarty->display('login_page.tpl');
    }
    
    /**
     * Callback for Pear::Auth, when the user logs in
     */
    public function loginCallback() {
        $this->authCallback('login');
    }
    
    /**
     * Callback for Pear::Auth, when the user logs out
     */
    public function logoutCallback() {
        $this->authCallback('logout');
    }
    
    /**
     * Callback for Pear::Auth, when the login failed
     */
    public function failedLoginCallback() {
        $this->loginFailed = true;
    }

    /**
     * @see PluginBase::initialize()
     */
    public function initialize() {

        $iniContainer = new IniSecurityContainer($this->getConfig());
        $securityManager = SecurityManager::getInstance();
        $securityManager->setSecurityContainer($iniContainer);
        
        $proxyAuthContainer = new ProxyAuthContainer($securityManager);
        
        // FIXME: this is an ugly hack to prevent the unit tests to shout.
        //  A better solution is to be found.
        if (isset($GLOBALS['headless'])) {
            return;
        }
        
        $this->auth = new Auth($proxyAuthContainer, array(), 
                                        array($this, 'loginCallback'), true);

        $this->auth->setLogoutCallback(array($this, 'logoutCallback'));
        $this->auth->setFailedLoginCallback(array($this, 'failedLoginCallback'));

        $this->auth->setShowLogin(false);
        $this->auth->start();
        
        $username = $this->auth->getUsername();
        $securityManager->setUser($username);
        
        if ($this->getCartoclient()->clientAllowed())
            return;
        $this->showLogin();
    }
    
    /**
     * Interrupts cartoweb flow of operation. Necessary, when displaying the
     * login/logout page.
     */
    private function interruptFlow() {
        $formRenderer = $this->getCartoclient()->getFormRenderer();
        $formRenderer->setCustomForm(false);
        $this->getCartoclient()->setInterruptFlow(true);
    }
    
    /**
     * Displays the Pear::Auth login page. It interrupts cartoweb flow.
     */
    private function showLogin() {
        $showLogin = $this->auth->showLogin;
        $this->auth->setShowLogin(true);
        $this->auth->login();
        $this->auth->setShowLogin($showLogin);
        $this->interruptFlow();
    }

    /**
     * Handles Get and Post requests
     */
    private function handleHttpCommonRequest($request){
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
    private function drawAuth() {

        $smarty = new Smarty_CorePlugin($this->getCartoclient(), $this);
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
     * @see ServerCaller::buildMapRequest()
     */
    public function buildMapRequest($mapRequest) {
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
