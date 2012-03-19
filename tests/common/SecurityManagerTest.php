<?php
/**
 *
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
 * @package Tests
 * @version $Id$
 */

/**
 * Abstract test case
 */
require_once 'PHPUnit/Framework/TestCase.php';

require_once(CARTOWEB_HOME . 'common/Accounting.php');
require_once(CARTOWEB_HOME . 'common/Utils.php');
require_once(CARTOWEB_HOME . 'common/SecurityManager.php');

/**
 * Security container with hard coded users, passwords and roles, for testing.
 */
class TestSecurityContainer extends SecurityContainer {

    /**
     * @see SecurityContainer::checkUser()
     */
    public function checkUser($username, $password) {
        $passwordsMap = array('toto' => '$123pgz',
                              'alice' => '/%12pw',
                              'bob' => 'bm12Gh');
         if (!array_key_exists($username, $passwordsMap))
            return false;
         return $password == $passwordsMap[$username];               
    }

    /**
     * @see SecurityContainer::getRoles()
     */    
    public function getRoles($username) {
        $roleMap = array('toto' => array('admin', 'editor'),
                         'alice' => array('editor'),
                         'bob' => array('admin', 'canprint'));
                         
        if (array_key_exists($username, $roleMap))
            return $roleMap[$username];
        return array();
    }
}

/**
 * Unit tests for class SecurityManager
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class common_SecurityManagerTest extends PHPUnit_Framework_TestCase {

    public function testLogin() {
        $securityManager = new SecurityManager();
        $securityManager->setSecurityContainer(new TestSecurityContainer());
        
        $this->assertEquals('', $securityManager->getUser());
        $this->assertEquals(array(SecurityManager::ALL_ROLE, 
              SecurityManager::ANONYMOUS_ROLE), $securityManager->getRoles());
        
        $this->assertFalse($securityManager->checkUser('notexistinguser', ''));
        $this->assertFalse($securityManager->checkUser('toto', 'badpassword'));
        $this->assertTrue($securityManager->checkUser('toto', '$123pgz'));

        $this->assertFalse($securityManager->checkUser('alice', 'badpassword'));
        $this->assertTrue($securityManager->checkUser('alice', '/%12pw'));

        $securityManager->clearSecurityContainer();
        $this->assertFalse($securityManager->checkUser('toto', '$123pgz'));
    }
    
    public function testInitialRoles() {

        $securityManager = new SecurityManager();

        $this->assertEquals('', $securityManager->getUser());
        $this->assertEquals(array(SecurityManager::ALL_ROLE, 
              SecurityManager::ANONYMOUS_ROLE), $securityManager->getRoles());
        
        $this->assertFalse($securityManager->hasRole('admin'));
        $this->assertFalse($securityManager->hasRole(array('admin')));
        $this->assertFalse($securityManager->hasRole(array('admin', 'foo')));
        $this->assertTrue($securityManager->hasRole('all'));
        $this->assertTrue($securityManager->hasRole(array('all')));
        $this->assertTrue($securityManager->hasRole(array('all', 'foo')));
    }
    
    public function testRoles() {

        $securityManager = new SecurityManager();

        $securityManager->setSecurityContainer(new TestSecurityContainer());

        $securityManager->setUser('toto');
        $this->assertEquals('toto', $securityManager->getUser());
        $this->assertEquals(array(SecurityManager::ALL_ROLE, 
                          SecurityManager::LOGGED_IN_ROLE,  'admin', 'editor'), 
                                            $securityManager->getRoles());
        
        $this->assertTrue($securityManager->hasRole('admin')); 
        $this->assertTrue($securityManager->hasRole('all')); 
        $this->assertTrue($securityManager->hasRole(array('admin'))); 
        $this->assertFalse($securityManager->hasRole('foo')); 
        $this->assertFalse($securityManager->hasRole(array('foo')));
        
        $this->assertTrue($securityManager->hasRole(array('admin', 'dummy'))); 
        $this->assertFalse($securityManager->hasRole(array('foo', 'dummy'))); 
         
        $securityManager->setUser('');
        $this->assertEquals(array(SecurityManager::ALL_ROLE, 
              SecurityManager::ANONYMOUS_ROLE), $securityManager->getRoles());
        
        $this->assertFalse($securityManager->hasRole(array('admin', 'dummy')));
        
        $securityManager->setUser('alice');
        $this->assertEquals(array(SecurityManager::ALL_ROLE, 
                          SecurityManager::LOGGED_IN_ROLE, 'editor'), 
                                            $securityManager->getRoles());
        $this->assertTrue($securityManager->hasRole('all')); 
        $this->assertFalse($securityManager->hasRole('admin')); 
        $this->assertTrue($securityManager->hasRole(array('editor'))); 
    }
}
