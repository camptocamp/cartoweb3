<?php
/**
 * @package Common
 */

/**
 * Abstract class for accessing list of users, checking their passwords and
 * getting their roles
 * @package Common
 */
abstract class SecurityContainer {
    
    /**
     * Check if a username, password pair is valid. It must return false if the
     * user is unknown or its password is invalid.
     * 
     * @param string the username to check
     * @param the user's password
     * @return boolean true if the pair is valid.
     */
    abstract function checkUser($username, $password);
    
    /**
     * Returns the list of rules belonging to a user.
     * 
     * @param string username
     * @return array a list of roles associated to the user
     */
    abstract function getRoles($username);
}

/**
 * Class which manages security in Cartoweb
 * 
 * It is used to authenticate a username/password pair, and handle the roles
 * associated to a user. This class has only one instance available at any time,
 * using getInstance static method.
 * 
 * Once the user is authenticated, it is made known the this manager through
 * setUser or setUserAndRoles. From there on, security checks can be done using
 * hasRole method.
 * @package Common
 */
class SecurityManager {

    /**
     * @var SecurityContainer current security container
     */
    private $securityContainer;
    
    /**
     * @var string the current authenticated username, or empty of none.
     */
    private $username = '';

    /**
     * @var array the list of roles associated to the current authenticated
     * user.
     */
    private $roles;

    /**
     * Constants for pre-defined roles.
     */
    const ALL_ROLE = 'all';
    const ANONYMOUS_ROLE = 'anonymous';
    const LOGGED_IN_ROLE = 'loggedIn';

    /**
     * @var SecurityManager singleton
     */
    private static $instance;

    /**
     * Constructor
     */
    public function __construct() {
        $this->roles = $this->getPredefinedRoles(true);
        self::$instance = $this;
    }
    
    /**
     * Returns the instance of this class. There is only one during the
     * cartoclient/server lifetime.
     */
    public static function getInstance() {
        if (is_null(self::$instance))
            self::$instance = new SecurityManager();
        return self::$instance;
    }
    
    /**
     * Returns the pre-defined roles for a user.
     * 
     * @param boolean true if we want the roles for an anomymous user.
     */
    private function getPredefinedRoles($anonymous) {
        $roles[] = self::ALL_ROLE;
        if ($anonymous)
            $roles[] = self::ANONYMOUS_ROLE;
        else
            $roles[] = self::LOGGED_IN_ROLE;

        return $roles;
    }

    /**
     * Sets the current SecurityContainer database
     *
     * @param SecurityContainer new SecurityContainer to set.
     */
    public function setSecurityContainer(SecurityContainer $securityContainer) {
        $this->securityContainer = $securityContainer;
    }

    /**
     * Removes the current SecurityConainer. No user will be authenticated since
     * then.
     */
    public function clearSecurityContainer() {
        $this->securityContainer = null;
    }

    /**
     * Check if a username, password pair is valid.
     * 
     * @param string the username
     * @param string its password
     * @return boolean true if the pair is valid.
     */
    public function checkUser($username, $password) {
        if (is_null($this->securityContainer))
            return false;
        return $this->securityContainer->checkUser($username, $password);
    }
    
    /**
     * Sets the username and its associated roles for the current authenticated
     * user.
     * Warning: Please see the #setUser() note about client plugins.
     * 
     * @param string the authenticated username
     * @param array the list of associated roles of the user
     */
    public function setUserAndRoles($username, $roles) {
        $this->username = $username;
        $anonymous = empty($username);
        $this->roles = array_merge($this->getPredefinedRoles($anonymous), $roles);
    }

    /**
     * Sets the current authenticated user. Its roles will be fetched from the
     * current SecurityContainer
     * Warning: for client plugins managing authentication, the setUser() or
     * setUserAndRoles() MUST be called before or in the initialize() Plugin
     * method. Otherwise, security constraints could be bypassed.
     * 
     * @param string the authenticated username
     */        
    public function setUser($username) {
        $this->username = $username;
        
        $roles = array();
        if (!is_null($this->securityContainer)) {
            $roles = $this->securityContainer->getRoles($username);
        }
        $this->setUserAndRoles($username, $roles);
    }
    
    /**
     * @return string Returns the current authenticated username
     */
    public function getUser() {
        return $this->username;
    }

    /**
     * @return array Returns the current roles associated to the current user.
     */
    public function getRoles() {
        return $this->roles;
    }
    
    /**
     * Check if the current user has the given role, or at least one role among
     * the one given, if it is an array.
     * 
     * @role array/string the name of the role to check, or an array of roles.
     * @return boolean true if the user has the given role, or at least on of
     * them.
     */
    public function hasRole($roles) {
        if (is_array($roles)) {
            return count(array_intersect($roles, $this->roles)) > 0; 
        } else {
            return in_array($roles, $this->roles);
        }
    }
}

?>
