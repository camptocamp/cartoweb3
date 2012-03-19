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

require_once(CARTOWEB_HOME . 'common/CwSerializable.php');

/**
 * Unit tests for class Serializable
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class common_SerializableTest extends PHPUnit_Framework_TestCase {

    /**
     * Tests string array unserialization (from array)
     */
    public function testStringArray1() {
        
        $struct = new stdclass();
        $struct->strArray = array('toto', 'titi', 'tutu');
        
        $testObj = new SerializableTestClass1('tata');
        $testObj->unserialize($struct);
        
        $this->assertEquals('toto', $testObj->strArray[0]);
        $this->assertEquals('titi', $testObj->strArray[1]);
        $this->assertEquals('tutu', $testObj->strArray[2]);
    } 

    /**
     * Tests string array unserialization (from comma-separated string)
     */
    public function testStringArray2() {
        
        $struct = new stdclass();
        $struct->strArray = 'toto, titi, tutu';
        
        $testObj = new SerializableTestClass1('tata');
        $testObj->unserialize($struct);
        
        $this->assertEquals('toto', $testObj->strArray[0]);
        $this->assertEquals('titi', $testObj->strArray[1]);
        $this->assertEquals('tutu', $testObj->strArray[2]);
    } 

    /**
     * Tests int array unserialization (from array)
     */
    public function testIntArray1() {
        
        $struct = new stdclass();
        $struct->intArray = array(1, 2, 3);
        
        $testObj = new SerializableTestClass1('tata');
        $testObj->unserialize($struct);
        
        $this->assertEquals(1, $testObj->intArray[0]);
        $this->assertEquals(2, $testObj->intArray[1]);
        $this->assertEquals(3, $testObj->intArray[2]);
    } 

    /**
     * Tests int array unserialization (from comma-separated string)
     */
    public function testIntArray2() {
        
        $struct = new stdclass();
        $struct->intArray = '1, 2, 3';
        
        $testObj = new SerializableTestClass1('tata');
        $testObj->unserialize($struct);
        
        $this->assertEquals(1, $testObj->intArray[0]);
        $this->assertEquals(2, $testObj->intArray[1]);
        $this->assertEquals(3, $testObj->intArray[2]);
    } 

    /**
     * Tests boolean array unserialization (from array)
     */
    public function testBooleanArray1() {
        
        $struct = new stdclass();
        $struct->boolArray = array(true, false);
        
        $testObj = new SerializableTestClass1('tata');
        $testObj->unserialize($struct);
        
        $this->assertTrue($testObj->boolArray[0]);
        $this->assertFalse($testObj->boolArray[1]);
    } 

    /**
     * Tests boolean array unserialization (from comma-separated string)
     */
    public function testBooleanArray2() {
        
        $struct = new stdclass();
        $struct->boolArray = 'true, false, 1, 0';
        
        $testObj = new SerializableTestClass1('tata');
        $testObj->unserialize($struct);
        
        $this->assertTrue($testObj->boolArray[0]);
        $this->assertFalse($testObj->boolArray[1]);
        $this->assertTrue($testObj->boolArray[2]);
        $this->assertFalse($testObj->boolArray[3]);
    } 

    /**
     * Tests object unserialization
     */
    public function testObjectSimple() {
        
        $struct1 = new stdclass();
        $struct2 = new stdclass();
        $struct2->className = 'SerializableTestClass2';
        $struct2->integer   = 123;
        $struct1->obj = $struct2;
        
        $testObj = new SerializableTestClass1('tata');
        $testObj->unserialize($struct1);
        
        $this->assertEquals('SerializableTestClass2', get_class($testObj->obj));
        $this->assertEquals(123, $testObj->obj->integer);
    } 

    /**
     * Tests object hierarchy unserialization
     */
    public function testObjectRecurse() {
        
        $struct1 = new stdclass();
        $struct2 = new stdclass();
        $struct3 = new stdclass();
        $struct3->className = 'SerializableTestClass2';
        $struct3->integer   = 123;
        $struct2->className = 'SerializableTestClass1';
        $struct2->obj = $struct3;
        $struct1->obj = $struct2;
        
        $testObj = new SerializableTestClass1('tata');
        $testObj->unserialize($struct1);
        
        $this->assertEquals('SerializableTestClass1', get_class($testObj->obj));
        $this->assertEquals('SerializableTestClass2', get_class($testObj->obj->obj));
        $this->assertEquals(123, $testObj->obj->obj->integer);
    } 
    
    /**
     * Tests object map unserialization
     */
    public function testObjectMap() {
        
        $struct1 = new stdclass();
        $struct2 = new stdclass();
        $struct3 = new stdclass();
        $struct3->className = 'SerializableTestClass2';
        $struct3->integer   = 123;
        $struct2->className = 'SerializableTestClass1';
        $struct1->objMap = array('obj1' => $struct3, 'obj2' => $struct2);

        $testObj = new SerializableTestClass1('tata');
        $testObj->unserialize($struct1);
        
        $this->assertEquals('SerializableTestClass2', get_class($testObj->objMap['obj1']));
        $this->assertEquals('SerializableTestClass1', get_class($testObj->objMap['obj2']));
        $this->assertEquals(123, $testObj->objMap['obj1']->integer);        
    }
    
    /**
     * Tests complex object hierarchy unserialization
     */
    public function testComplete() {
    
        $structRoot = new stdclass();
        $structRoot->className = 'SerializableTestClass1';
        $structRoot->str = 'toto';
        $structRoot->strArray = 'titi, tutu';
        $structRoot->intArray = array(123, 456);
        $structRoot->boolArray = 'true, false, 1, 0';
        
        $structObj1 = new stdclass();
        $structObj1->className = 'SerializableTestClass2';
        $structObj1->integer = 111;
        
        $structObj2 = new stdclass();
        $structObj2->className = 'SerializableTestClass2';
        $structObj2->integer = 222;
        
        $structObj3 = new stdclass();
        $structObj3->className = 'SerializableTestClass1';
        $structObj3->strArray = array('tete', 'tyty');
        $structObj3->intArray = '333, 444';
        $structObj3->boolArray = array(true, false);
        $structObj3->obj = $structObj1;
        
        $structObj4 = new stdclass();
        $structObj4->className = 'SerializableTestClass2';
        $structObj4->integer = 555;
        $structRoot->obj = $structObj4;
        
        $structRoot->objMap = array ('obj2' => $structObj2, 'obj3' => $structObj3);
        
        // Property testClass exists, so unserializeObject knows the class
        $testObj = CwSerializable::unserializeObject($structRoot);

        $this->assertEquals('SerializableTestClass1', get_class($testObj));
        $this->assertEquals('SerializableTestClass2', get_class($testObj->obj));
        $this->assertEquals('SerializableTestClass2',
                            get_class($testObj->objMap['obj2']));
        $this->assertEquals('SerializableTestClass1',
                            get_class($testObj->objMap['obj3']));       
        $this->assertEquals('SerializableTestClass2',
                            get_class($testObj->objMap['obj3']->obj));       
    }
}

/**
 * Test class used to test abstract class CwSerializable (all types of attribute)
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class SerializableTestClass1 extends CwSerializable {
    
    /**
     * @var string
     */ 
    public $str;
    
    /**
     * @var array
     */
    public $strArray;
    
    /**
     * @var array
     */
    public $intArray;
    
    /** 
     * @var array
     */
    public $boolArray;
    
    /**
     * @var Object
     */ 
    public $obj;
    
    /**
     * @var array
     */
    public $objMap;
    
    /**
     * Constructor
     * @param string
     */
    function __construct($my_str = '') {
        parent::__construct();
        $str = $my_str;
    }
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->str       = self::unserializeValue($struct, 'str');
        $this->strArray  = self::unserializeArray($struct, 'strArray');
        $this->intArray  = self::unserializeArray($struct, 'intArray', 'int');
        $this->boolArray = self::unserializeArray($struct, 'boolArray', 'boolean');
        $this->obj       = self::unserializeObject($struct, 'obj');
        $this->objMap    = self::unserializeObjectMap($struct, 'objMap'); 
    }
}

/**
 * Test class used to test abstract class CwSerializable (simple)
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class SerializableTestClass2 extends CwSerializable {
    
    /**
     * @var int
     */
    public $integer;
     
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->integer = (int)$struct->integer;
    }
}
