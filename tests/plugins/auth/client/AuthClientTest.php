<?php
/**
 * Tests for Auth client plugins
 * 
 * @package Tests
 * @version $Id$
 */

/**
 * Abstract test case
 */
require_once 'PHPUnit2/Framework/TestCase.php';

require_once(CARTOCOMMON_HOME . 'common/Utils.php');
require_once(CARTOCLIENT_HOME . 'client/Cartoclient.php');
require_once(CARTOCLIENT_HOME . 'client/ClientPlugin.php');
require_once(CARTOCLIENT_HOME . 'plugins/auth/client/ClientAuth.php');

/**
 * Unit tests for client Auth plugin
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class plugins_auth_client_AuthClientTest extends PHPUnit2_Framework_TestCase {
    
    public function testAuth() {
        $cartoclient = new Cartoclient();
        $authConfig = $cartoclient->getPluginManager()->auth->getConfig();
         
        $iniContainer = new IniSecurityContainer($authConfig);
        $this->assertFalse($iniContainer->checkUser('non_existant', ''));
        $this->assertFalse($iniContainer->checkUser('toto', 'badpassword'));
        $this->assertTrue($iniContainer->checkUser('toto', '$123pgz'));
        $this->assertFalse($iniContainer->checkUser('alice', 'badpassword'));
        $this->assertTrue($iniContainer->checkUser('alice', '/%12pw'));
    }
    
    public function testRoles() {
    
        $cartoclient = new Cartoclient();
        $authConfig = $cartoclient->getPluginManager()->auth->getConfig();
        $iniContainer = new IniSecurityContainer($authConfig);

        $securityManager = SecurityManager::getInstance();
        
        $this->assertFalse($securityManager->hasRole('admin'));
        $securityManager->setSecurityContainer($iniContainer);        
        $this->assertFalse($securityManager->hasRole('admin'));

        $this->assertFalse($securityManager->checkUser('toto', 'badpassword'));
        $this->assertTrue($securityManager->checkUser('toto', '$123pgz'));

        $securityManager->setUser('toto');        

        $this->assertTrue($securityManager->hasRole('admin'));
        $this->assertFalse($securityManager->hasRole('nonexistant_role'));
        $this->assertTrue($securityManager->hasRole(array('editor')));

        $securityManager->setUser('alice');        
        $this->assertFalse($securityManager->hasRole('admin'));
        $this->assertFalse($securityManager->hasRole('nonexistant_role'));
        $this->assertTrue($securityManager->hasRole(array('editor')));
    }
}

?>