<?php

require_once 'PHPUnit2/Framework/TestCase.php';

require_once(CARTOCLIENT_HOME . 'common/Serializable.php');

/**
 * Unit tests for class Serializable
 *
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class common_SerializableTest extends PHPUnit2_Framework_TestCase {

    public function testStringArray1() {
        
        $struct = new stdclass();
        $struct->strArray = array('toto', 'titi', 'tutu');
        
        $testObj = new SerializableTestClass1('tata');
        $testObj->unserialize($struct);
        
        $this->assertEquals($testObj->strArray[0], 'toto');
        $this->assertEquals($testObj->strArray[1], 'titi');
        $this->assertEquals($testObj->strArray[2], 'tutu');
    } 

    public function testStringArray2() {
        
        $struct = new stdclass();
        $struct->strArray = 'toto, titi, tutu';
        
        $testObj = new SerializableTestClass1('tata');
        $testObj->unserialize($struct);
        
        $this->assertEquals($testObj->strArray[0], 'toto');
        $this->assertEquals($testObj->strArray[1], 'titi');
        $this->assertEquals($testObj->strArray[2], 'tutu');
    } 

    public function testIntArray1() {
        
        $struct = new stdclass();
        $struct->intArray = array(1, 2, 3);
        
        $testObj = new SerializableTestClass1('tata');
        $testObj->unserialize($struct);
        
        $this->assertEquals($testObj->intArray[0], 1);
        $this->assertEquals($testObj->intArray[1], 2);
        $this->assertEquals($testObj->intArray[2], 3);
    } 

    public function testIntArray2() {
        
        $struct = new stdclass();
        $struct->intArray = '1, 2, 3';
        
        $testObj = new SerializableTestClass1('tata');
        $testObj->unserialize($struct);
        
        $this->assertEquals($testObj->intArray[0], 1);
        $this->assertEquals($testObj->intArray[1], 2);
        $this->assertEquals($testObj->intArray[2], 3);
    } 

    public function testBooleanArray1() {
        
        $struct = new stdclass();
        $struct->boolArray = array(true, false);
        
        $testObj = new SerializableTestClass1('tata');
        $testObj->unserialize($struct);
        
        $this->assertTrue($testObj->boolArray[0]);
        $this->assertFalse($testObj->boolArray[1]);
    } 

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
}

/**
 * Test class used to test abstract class Serializable
 *
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
        $this->objMap    = self::unserializeObject($struct, 'objMap'); 
    }
}

/**
 * Test class used to test abstract class Serializable
 *
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class SerializableTestClass2 extends Serializable {
    
    public $integer;
     
    function unserialize($struct) {
        $this->integer = (int)$struct->integer;
    }
}

?>
