<?php
/**
 * @package Tests
 * @version $Id$
 */

/**
 * Abstract test case
 */
require_once 'PHPUnit2/Framework/TestCase.php';

require_once(CARTOCLIENT_HOME . 'common/Serializable.php');

/**
 * Unit tests for class Serializable
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class common_SerializableTest extends PHPUnit2_Framework_TestCase {

    /**
     * Tests string array unserialization (from array)
     */
    public function testStringArray1() {
        
        $struct = new stdclass();
        $struct->strArray = array('toto', 'titi', 'tutu');
        
        $testObj = new SerializableTestClass1('tata');
        $testObj->unserialize($struct);
        
        $this->assertEquals($testObj->strArray[0], 'toto');
        $this->assertEquals($testObj->strArray[1], 'titi');
        $this->assertEquals($testObj->strArray[2], 'tutu');
    } 

    /**
     * Tests string array unserialization (from comma-separated string)
     */
    public function testStringArray2() {
        
        $struct = new stdclass();
        $struct->strArray = 'toto, titi, tutu';
        
        $testObj = new SerializableTestClass1('tata');
        $testObj->unserialize($struct);
        
        $this->assertEquals($testObj->strArray[0], 'toto');
        $this->assertEquals($testObj->strArray[1], 'titi');
        $this->assertEquals($testObj->strArray[2], 'tutu');
    } 

    /**
     * Tests int array unserialization (from array)
     */
    public function testIntArray1() {
        
        $struct = new stdclass();
        $struct->intArray = array(1, 2, 3);
        
        $testObj = new SerializableTestClass1('tata');
        $testObj->unserialize($struct);
        
        $this->assertEquals($testObj->intArray[0], 1);
        $this->assertEquals($testObj->intArray[1], 2);
        $this->assertEquals($testObj->intArray[2], 3);
    } 

    /**
     * Tests int array unserialization (from comma-separated string)
     */
    public function testIntArray2() {
        
        $struct = new stdclass();
        $struct->intArray = '1, 2, 3';
        
        $testObj = new SerializableTestClass1('tata');
        $testObj->unserialize($struct);
        
        $this->assertEquals($testObj->intArray[0], 1);
        $this->assertEquals($testObj->intArray[1], 2);
        $this->assertEquals($testObj->intArray[2], 3);
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
        
        $this->assertEquals(get_class($testObj->obj), 'SerializableTestClass2');
        $this->assertEquals($testObj->obj->integer, 123);
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
        
        $this->assertEquals(get_class($testObj->obj), 'SerializableTestClass1');
        $this->assertEquals(get_class($testObj->obj->obj), 'SerializableTestClass2');
        $this->assertEquals($testObj->obj->obj->integer, 123);
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
        
        $this->assertEquals(get_class($testObj->objMap['obj1']), 'SerializableTestClass2');
        $this->assertEquals(get_class($testObj->objMap['obj2']), 'SerializableTestClass1');
        $this->assertEquals($testObj->objMap['obj1']->integer, 123);        
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
        $testObj = Serializable::unserializeObject($structRoot);

        $this->assertEquals(get_class($testObj), 'SerializableTestClass1');
        $this->assertEquals(get_class($testObj->obj), 'SerializableTestClass2');
        $this->assertEquals(get_class($testObj->objMap['obj2']), 'SerializableTestClass2');
        $this->assertEquals(get_class($testObj->objMap['obj3']), 'SerializableTestClass1');       
        $this->assertEquals(get_class($testObj->objMap['obj3']->obj), 'SerializableTestClass2');       
    }
}

/**
 * Test class used to test abstract class Serializable (all types of attribute)
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class SerializableTestClass1 extends Serializable {
    
    public $str;
    public $strArray;
    public $intArray;
    public $boolArray;
    public $obj;
    public $objMap;
    
    function __construct($my_str) {
        parent::__construct();
        $str = $my_str;
    }
    
    function unserialize($struct) {
        $this->str       = $struct->str;
        $this->strArray  = self::unserializeArray($struct, 'strArray');
        $this->intArray  = self::unserializeArray($struct, 'intArray', 'int');
        $this->boolArray = self::unserializeArray($struct, 'boolArray', 'boolean');
        $this->obj       = self::unserializeObject($struct, 'obj');
        $this->objMap    = self::unserializeObjectMap($struct, 'objMap'); 
    }
}

/**
 * Test class used to test abstract class Serializable (simple)
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class SerializableTestClass2 extends Serializable {
    
    public $integer;
     
    function unserialize($struct) {
        $this->integer = (int)$struct->integer;
    }
}

?>
