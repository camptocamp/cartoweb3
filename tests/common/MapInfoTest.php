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

require_once(CARTOWEB_HOME . 'common/MapInfo.php');
require_once(CARTOWEB_HOME . 'coreplugins/layers/common/Layers.php');

/**
 * Unit tests for class MapInfo
 * @package Tests
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */
class common_MapInfoTest extends PHPUnit_Framework_TestCase {

    public function testLayerBaseUnserialize() {
    
        $struct = new stdclass();
        $struct->id = 'toto';
        $struct->label = 'titi';
        
        $layerBase = new LayerBase();
        $layerBase->unserialize($struct);
        
        $this->assertEquals('toto', $layerBase->id);
        $this->assertEquals('titi', $layerBase->label);
    }

    public function testLayerContainerUnserialize() {
        
        $struct = new stdclass();
        $struct->id = 'toto';
        $struct->label = 'titi';
        $struct->children = 'tata, tutu, tete';
        
        $layerContainer = new LayerContainer();
        $layerContainer->unserialize($struct);

        $this->assertEquals('toto', $layerContainer->id);
        $this->assertEquals('titi', $layerContainer->label);
        $this->assertEquals('tata', $layerContainer->children['default']->layers[0]);                
        $this->assertEquals('tutu', $layerContainer->children['default']->layers[1]);                
        $this->assertEquals('tete', $layerContainer->children['default']->layers[2]);                
    }        

    public function testLayerGroupUnserialize() {
        
        $struct = new stdclass();
        $struct->id = 'toto';
        $struct->label = 'titi';
        $struct->children = 'tata, tutu, tete';
        
        $layerGroup = new LayerGroup();
        $layerGroup->unserialize($struct);
        
        $this->assertEquals('toto', $layerGroup->id);
        $this->assertEquals('titi', $layerGroup->label);
        $this->assertEquals('tata', $layerGroup->children['default']->layers[0]);                
        $this->assertEquals('tutu', $layerGroup->children['default']->layers[1]);                
        $this->assertEquals('tete', $layerGroup->children['default']->layers[2]);                
    }        

    public function testLayerUnserialize() {
        
        $struct = new stdclass();
        $struct->id = 'toto';
        $struct->label = 'titi';
        $struct->children = 'tata, tutu, tete';
        $struct->msLayer = 'tyty';
        
        $layer = new Layer();
        $layer->unserialize($struct);

        $this->assertEquals('toto', $layer->id);
        $this->assertEquals('titi', $layer->label);
        $this->assertEquals('tata', $layer->children['default']->layers[0]);                
        $this->assertEquals('tutu', $layer->children['default']->layers[1]);                
        $this->assertEquals('tete', $layer->children['default']->layers[2]);                
        $this->assertEquals('tyty', $layer->msLayer);
    }        

    public function testLayerClassUnserialize() {
    
        $struct = new stdclass();
        $struct->id = 'toto';
        $struct->label = 'titi';
        
        $layerClass = new LayerClass();
        $layerClass->unserialize($struct);
        
        $this->assertEquals('toto', $layerClass->id);
        $this->assertEquals('titi', $layerClass->label);
    }

    public function testLocationUnserialize() {
    
        $struct = new stdclass();
        $struct->bbox = "12, 34, 56, 78";
        
        $location = new Location();
        $location->unserialize($struct);
        
        $this->assertEquals('Bbox', get_class($location->bbox));
        $this->assertEquals(12.0, $location->bbox->minx);
        $this->assertEquals(34.0, $location->bbox->miny);
        $this->assertEquals(56.0, $location->bbox->maxx);
        $this->assertEquals(78.0, $location->bbox->maxy);
    }

    public function testInitialLocationUnserialize() {
    
        $struct = new stdclass();
        $struct->bbox = '12, 34, 56, 78';
        
        $initialLocation = new InitialLocation();
        $initialLocation->unserialize($struct);
        
        $this->assertEquals('Bbox', get_class($initialLocation->bbox));
        $this->assertEquals(12.0, $initialLocation->bbox->minx);
        $this->assertEquals(34.0, $initialLocation->bbox->miny);
        $this->assertEquals(56.0, $initialLocation->bbox->maxx);
        $this->assertEquals(78.0, $initialLocation->bbox->maxy);
    }

    public function testLayerStateUnserialize() {
    
        $struct = new stdclass();
        $struct->id = 'toto';
        $struct->hidden = true;
        $struct->selected = 'false';
        $struct->unfolded = 1;
        
        $layerState = new LayerState();
        $layerState->unserialize($struct);
        
        $this->assertEquals('toto', $layerState->id);
        $this->assertEquals(true, $layerState->hidden);
        $this->assertEquals(false, $layerState->selected);
        $this->assertEquals(true, $layerState->unfolded);
    }

    public function testInitialMapStateUnserialize() {
    
        $structLocation = new stdclass();
        $structLocation->bbox = "12, 34, 56, 78";
        
        $structLayer1 = new stdclass();
        $structLayer1->id = 'titi';
        $structLayer1->hidden = false;
        $structLayer1->selected = 1;
        $structLayer1->unfolded = true;
        
        $structLayer2 = new stdclass();
        $structLayer2->id = 'tutu';
        $structLayer2->hidden = true;
        $structLayer2->selected = false;
        $structLayer2->unfolded = 0;
      
        $struct = new stdclass();
        $struct->id = 'toto';
        $struct->location = $structLocation;
        $struct->layers = array('titi' => $structLayer1, 'tutu' => $structLayer2);
        
        $initMapState = new InitialMapState();
        $initMapState->unserialize($struct);
        
        $this->assertEquals('toto', $initMapState->id);
        $this->assertEquals('InitialLocation', get_class($initMapState->location));
        $this->assertEquals('LayerState', get_class($initMapState->layers['titi']));
        $this->assertEquals('LayerState', get_class($initMapState->layers['tutu']));
        $this->assertEquals('Bbox', get_class($initMapState->location->bbox));
        $this->assertEquals(12.0, $initMapState->location->bbox->minx);
        $this->assertEquals(34.0, $initMapState->location->bbox->miny);
        $this->assertEquals(56.0, $initMapState->location->bbox->maxx);
        $this->assertEquals(78.0, $initMapState->location->bbox->maxy);
        $this->assertEquals('titi', $initMapState->layers['titi']->id);
        $this->assertEquals(false, $initMapState->layers['titi']->hidden);
        $this->assertEquals(true, $initMapState->layers['titi']->selected);
        $this->assertEquals(true, $initMapState->layers['titi']->unfolded);
        $this->assertEquals('tutu', $initMapState->layers['tutu']->id);
        $this->assertEquals(true, $initMapState->layers['tutu']->hidden);
        $this->assertEquals(false, $initMapState->layers['tutu']->selected);
        $this->assertEquals(false, $initMapState->layers['tutu']->unfolded);
    }
    
    public function testMapInfoUnserialize() {
    
        $struct = new stdclass();
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
        
        $structLayer5 = new stdclass();
        $structLayer5->className = 'LayerClass';
        $structLayer5->id = 'layer5';
        $structLayer5->label = 'layer5_label';
        
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
        $structLayer6->unfolded = true;
        
        $structLayer7 = new stdclass();
        $structLayer7->id = 'tutu';
        $structLayer7->hidden = true;
        $structLayer7->selected = false;
        $structLayer7->unfolded = 0;
      
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

        $this->assertEquals('titi', $mapInfo->mapLabel);
                               
        $this->assertEquals('InitialMapState', 
                            get_class($mapInfo->initialMapStates['toto']));                                 
        $this->assertEquals('InitialMapState', 
                            get_class($mapInfo->initialMapStates['tata']));                                 
        $this->assertEquals('InitialLocation', 
                            get_class($mapInfo->initialMapStates['toto']->location));                                 
        $this->assertEquals('InitialLocation', 
                            get_class($mapInfo->initialMapStates['tata']->location));                                 
        $this->assertEquals('Bbox', 
                            get_class($mapInfo->initialMapStates['toto']->location->bbox));                                 
        $this->assertEquals('Bbox', 
                            get_class($mapInfo->initialMapStates['tata']->location->bbox));                                 
        $this->assertEquals('LayerState', 
                            get_class($mapInfo->initialMapStates['toto']->layers['titi']));                                 
        $this->assertEquals('LayerState', 
                            get_class($mapInfo->initialMapStates['toto']->layers['tutu']));                                 
        $this->assertEquals('Bbox', get_class($mapInfo->extent));                                  
        $this->assertEquals('Location', get_class($mapInfo->location));                                  
        $this->assertEquals('Bbox', get_class($mapInfo->location->bbox)); 
    }
    
    /* TODO: move this code to a common test for plugin layers.
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
        
        $layer1 = $mapInfo->layersInit->getLayerById('layer1');
        $layer2 = $mapInfo->layersInit->getLayerById('layer2');
        $layer3 = $mapInfo->layersInit->getLayerById('layer3');
        
        $this->assertEquals('layer1_label', $layer1->label); 
        $this->assertEquals('layer2_label', $layer2->label); 
        $this->assertEquals('layer3_label', $layer3->label); 
    }
    */
}

?>
