<?php
/**
 * @package Tests
 * @version $Id$
 */

/**
 * Abstract test case
 */
require_once 'PHPUnit2/Framework/TestCase.php';

require_once(CARTOCOMMON_HOME . 'common/MapInfo.php');

/**
 * Unit tests for class MapInfo
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class common_MapInfoTest extends PHPUnit2_Framework_TestCase {

    public function testLayerBaseUnserialize() {
    
        $struct = new stdclass();
        $struct->id = 'toto';
        $struct->label = 'titi';
        
        $layerBase = new LayerBase();
        $layerBase->unserialize($struct);
        
        $this->assertEquals($layerBase->id, 'toto');
        $this->assertEquals($layerBase->label, 'titi');
    }

    public function testLayerContainerUnserialize() {
        
        $struct = new stdclass();
        $struct->id = 'toto';
        $struct->label = 'titi';
        $struct->children = 'tata, tutu, tete';
        
        $layerContainer = new LayerContainer();
        $layerContainer->unserialize($struct);
        
        $this->assertEquals($layerContainer->id, 'toto');
        $this->assertEquals($layerContainer->label, 'titi');
        $this->assertEquals($layerContainer->children[0], 'tata');                
        $this->assertEquals($layerContainer->children[1], 'tutu');                
        $this->assertEquals($layerContainer->children[2], 'tete');                
    }        

    public function testLayerGroupUnserialize() {
        
        $struct = new stdclass();
        $struct->id = 'toto';
        $struct->label = 'titi';
        $struct->children = 'tata, tutu, tete';
        
        $layerGroup = new LayerGroup();
        $layerGroup->unserialize($struct);
        
        $this->assertEquals($layerGroup->id, 'toto');
        $this->assertEquals($layerGroup->label, 'titi');
        $this->assertEquals($layerGroup->children[0], 'tata');                
        $this->assertEquals($layerGroup->children[1], 'tutu');                
        $this->assertEquals($layerGroup->children[2], 'tete');                
    }        

    public function testLayerUnserialize() {
        
        $struct = new stdclass();
        $struct->id = 'toto';
        $struct->label = 'titi';
        $struct->children = array('tata', 'tutu', 'tete');
        $struct->msLayer = 'tyty';
        
        $layer = new Layer();
        $layer->unserialize($struct);
        
        $this->assertEquals($layer->id, 'toto');
        $this->assertEquals($layer->label, 'titi');
        $this->assertEquals($layer->children[0], 'tata');                
        $this->assertEquals($layer->children[1], 'tutu');                
        $this->assertEquals($layer->children[2], 'tete');                
        $this->assertEquals($layer->msLayer, 'tyty');
    }        

    public function testLayerClassUnserialize() {
    
        $struct = new stdclass();
        $struct->id = 'toto';
        $struct->label = 'titi';
        $struct->name = 'tutu';
        
        $layerClass = new LayerClass();
        $layerClass->unserialize($struct);
        
        $this->assertEquals($layerClass->id, 'toto');
        $this->assertEquals($layerClass->label, 'titi');
        $this->assertEquals($layerClass->name, 'tutu');
    }

    public function testLocationUnserialize() {
    
        $struct = new stdclass();
        $struct->bbox = "12, 34, 56, 78";
        
        $location = new Location();
        $location->unserialize($struct);
        
        $this->assertEquals(get_class($location->bbox), 'Bbox');
        $this->assertEquals($location->bbox->minx, 12.0);
        $this->assertEquals($location->bbox->miny, 34.0);
        $this->assertEquals($location->bbox->maxx, 56.0);
        $this->assertEquals($location->bbox->maxy, 78.0);
    }

    public function testInitialLocationUnserialize() {
    
        $struct = new stdclass();
        $struct->bbox = '12, 34, 56, 78';
        
        $initialLocation = new InitialLocation();
        $initialLocation->unserialize($struct);
        
        $this->assertEquals(get_class($initialLocation->bbox), 'Bbox');
        $this->assertEquals($initialLocation->bbox->minx, 12.0);
        $this->assertEquals($initialLocation->bbox->miny, 34.0);
        $this->assertEquals($initialLocation->bbox->maxx, 56.0);
        $this->assertEquals($initialLocation->bbox->maxy, 78.0);
    }

    public function testLayerStateUnserialize() {
    
        $struct = new stdclass();
        $struct->id = 'toto';
        $struct->hidden = true;
        $struct->selected = 'false';
        $struct->folded = 1;
        
        $layerState = new LayerState();
        $layerState->unserialize($struct);
        
        $this->assertEquals($layerState->id, 'toto');
        $this->assertEquals($layerState->hidden, true);
        $this->assertEquals($layerState->selected, false);
        $this->assertEquals($layerState->folded, true);
    }

    public function testInitialMapStateUnserialize() {
    
        $structLocation = new stdclass();
        $structLocation->bbox = "12, 34, 56, 78";
        
        $structLayer1 = new stdclass();
        $structLayer1->id = 'titi';
        $structLayer1->hidden = false;
        $structLayer1->selected = 1;
        $structLayer1->folded = true;
        
        $structLayer2 = new stdclass();
        $structLayer2->id = 'tutu';
        $structLayer2->hidden = true;
        $structLayer2->selected = false;
        $structLayer2->folded = 0;
      
        $struct = new stdclass();
        $struct->id = 'toto';
        $struct->location = $structLocation;
        $struct->layers = array('titi' => $structLayer1, 'tutu' => $structLayer2);
        
        $initMapState = new InitialMapState();
        $initMapState->unserialize($struct);
        
        $this->assertEquals($initMapState->id, 'toto');
        $this->assertEquals(get_class($initMapState->location), 'InitialLocation');
        $this->assertEquals(get_class($initMapState->layers['titi']), 'LayerState');
        $this->assertEquals(get_class($initMapState->layers['tutu']), 'LayerState');
        $this->assertEquals(get_class($initMapState->location->bbox), 'Bbox');
        $this->assertEquals($initMapState->location->bbox->minx, 12.0);
        $this->assertEquals($initMapState->location->bbox->miny, 34.0);
        $this->assertEquals($initMapState->location->bbox->maxx, 56.0);
        $this->assertEquals($initMapState->location->bbox->maxy, 78.0);
        $this->assertEquals($initMapState->layers['titi']->id, 'titi');
        $this->assertEquals($initMapState->layers['titi']->hidden, false);
        $this->assertEquals($initMapState->layers['titi']->selected, true);
        $this->assertEquals($initMapState->layers['titi']->folded, true);
        $this->assertEquals($initMapState->layers['tutu']->id, 'tutu');
        $this->assertEquals($initMapState->layers['tutu']->hidden, true);
        $this->assertEquals($initMapState->layers['tutu']->selected, false);
        $this->assertEquals($initMapState->layers['tutu']->folded, false);
    }
    
    public function testMapInfoUnserialize() {
    
        $struct = new stdclass();
        $struct->mapId = 'toto';
        $struct->mapLabel = 'titi';
        
        $structLayer1 = new stdclass();
        $structLayer1->className = 'LayerGroup';
        $structLayer1->id = 'layer1';
        $structLayer1->label = 'layer1_label';
        $structLayer1->children = 'layer2, layer3';
        
        $structLayer2 = new stdclass();
        $structLayer2->className = 'Layer';
        $structLayer2->id = 'layer2';
        $structLayer2->label = 'layer2_label';
        $structLayer2->msLayer = 'layer2_msLayer';
        
        $structLayer3 = new stdclass();
        $structLayer3->className = 'Layer';
        $structLayer3->id = 'layer3';
        $structLayer3->label = 'layer3_label';
        $structLayer3->msLayer = 'layer2_msLayer';
        $structLayer3->children = 'layer4, layer5';
        
        $structLayer4 = new stdclass();
        $structLayer4->className = 'LayerClass';
        $structLayer4->id = 'layer4';
        $structLayer4->label = 'layer4_label';
        $structLayer4->name = 'layer4_name';
        
        $structLayer5 = new stdclass();
        $structLayer5->className = 'LayerClass';
        $structLayer5->id = 'layer5';
        $structLayer5->label = 'layer5_label';
        $structLayer5->name = 'layer5_name';
        
        $struct->layers = array ('layer1' => $structLayer1,
                                 'layer2' => $structLayer2,
                                 'layer3' => $structLayer3,
                                 'layer4' => $structLayer4,
                                 'layer5' => $structLayer5);

        $structLocation1 = new stdclass();
        $structLocation1->bbox = "12, 34, 56, 78";
        
        $structLayer6 = new stdclass();
        $structLayer6->id = 'titi';
        $structLayer6->hidden = false;
        $structLayer6->selected = 1;
        $structLayer6->folded = true;
        
        $structLayer7 = new stdclass();
        $structLayer7->id = 'tutu';
        $structLayer7->hidden = true;
        $structLayer7->selected = false;
        $structLayer7->folded = 0;
      
        $structInitMapState1 = new stdclass();
        $structInitMapState1->id = 'toto';
        $structInitMapState1->location = $structLocation1;
        $structInitMapState1->layers = array('titi' => $structLayer6, 'tutu' => $structLayer7);
          
        $structLocation2 = new stdclass();
        $structLocation2->bbox = "44, 33, 22, 11";

        $structInitMapState2 = new stdclass();
        $structInitMapState2->id = 'tata';
        $structInitMapState2->location = $structLocation2;
        $structInitMapState2->layers = array();
              
        $struct->initialMapStates = array('toto' => $structInitMapState1,
                                          'tata' => $structInitMapState2);
        
        $struct->extent = '12, 34, 56, 78';
        $struct->location = new stdclass();
        $struct->location->bbox = '11, 22, 33, 44';
        
        $mapInfo = new MapInfo();
        $mapInfo->unserialize($struct);

        $this->assertEquals($mapInfo->mapId, 'toto');                                  
        $this->assertEquals($mapInfo->mapLabel, 'titi');
        $this->assertEquals(get_class($mapInfo->layers['layer1']), 'LayerGroup');                                  
        $this->assertEquals(get_class($mapInfo->layers['layer2']), 'Layer');                                  
        $this->assertEquals(get_class($mapInfo->layers['layer3']), 'Layer');                                  
        $this->assertEquals(get_class($mapInfo->layers['layer4']), 'LayerClass');                                  
        $this->assertEquals(get_class($mapInfo->layers['layer5']), 'LayerClass');                                  
        $this->assertEquals(get_class($mapInfo->initialMapStates['toto']),
                            'InitialMapState');                                 
        $this->assertEquals(get_class($mapInfo->initialMapStates['tata']),
                            'InitialMapState');                                 
        $this->assertEquals(get_class($mapInfo->initialMapStates['toto']->location),
                            'InitialLocation');                                 
        $this->assertEquals(get_class($mapInfo->initialMapStates['tata']->location),
                            'InitialLocation');                                 
        $this->assertEquals(get_class($mapInfo->initialMapStates['toto']->location->bbox),
                            'Bbox');                                 
        $this->assertEquals(get_class($mapInfo->initialMapStates['tata']->location->bbox),
                            'Bbox');                                 
        $this->assertEquals(get_class($mapInfo->initialMapStates['toto']->layers['titi']),
                            'LayerState');                                 
        $this->assertEquals(get_class($mapInfo->initialMapStates['toto']->layers['tutu']),
                            'LayerState');                                 
        $this->assertEquals(get_class($mapInfo->extent), 'Bbox');                                  
        $this->assertEquals(get_class($mapInfo->location), 'Location');                                  
        $this->assertEquals(get_class($mapInfo->location->bbox), 'Bbox'); 
    }
    
    public function testMapInfoLayerById() {

        $struct = new stdclass();
        
        $structLayer1 = new stdclass();
        $structLayer1->className = 'LayerGroup';
        $structLayer1->id = 'layer1';
        $structLayer1->label = 'layer1_label';
        $structLayer1->children = 'layer2';
        
        $structLayer2 = new stdclass();
        $structLayer2->className = 'Layer';
        $structLayer2->id = 'layer2';
        $structLayer2->label = 'layer2_label';
        $structLayer2->children = 'layer3';
        
        $structLayer3 = new stdclass();
        $structLayer3->className = 'LayerClass';
        $structLayer3->id = 'layer3';
        $structLayer3->label = 'layer3_label';
        
        $struct->layers = array ('layer1' => $structLayer1,
                                 'layer2' => $structLayer2,
                                 'layer3' => $structLayer3);
        
        $mapInfo = new MapInfo();
        $mapInfo->unserialize($struct);
        
        $layer1 = $mapInfo->getLayerById('layer1');
        $layer2 = $mapInfo->getLayerById('layer2');
        $layer3 = $mapInfo->getLayerById('layer3');
        
        $this->assertEquals($layer1->label, 'layer1_label'); 
        $this->assertEquals($layer2->label, 'layer2_label'); 
        $this->assertEquals($layer3->label, 'layer3_label'); 
    }
}

?>
