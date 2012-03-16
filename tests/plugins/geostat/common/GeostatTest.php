<?php
/**
 * Tests for Geostat Common plugins
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
 * @copyright 2006 Camptocamp SA
 * 
 * @package Tests
 * @version $Id$
 */

/**
 * Abstract test case
 */
require_once 'PHPUnit/Framework/TestCase.php';

require_once(CARTOWEB_HOME . 'common/Utils.php');
require_once(CARTOWEB_HOME . 'client/Cartoclient.php');
require_once(CARTOWEB_HOME . 'client/ClientPlugin.php');
require_once(CARTOWEB_HOME . 'plugins/geostat/common/Geostat.php');


/**
 * Unit tests for common Geostat plugin
 * @package Tests
 */
class plugins_geostat_client_GeostatTest extends PHPUnit_Framework_TestCase {
    
    public function testConvertColorRgb2TwColorRgb() {
        $colorRgb = new ColorRgb(1,10,100);
        $twColorRgb = TwColorRgbHelper::ColorRgb2TwColorRgb($colorRgb);
        $this->assertEquals(1, $twColorRgb->redLevel);
        $this->assertEquals(10, $twColorRgb->greenLevel);
        $this->assertEquals(100, $twColorRgb->blueLevel);
    }
    
    public function testConvertTwColorRbg2ColorRgb() {
        $twColorRgb = new TwColorRgb();
        $twColorRgb->redLevel = 1;
        $twColorRgb->greenLevel = 10;
        $twColorRgb->blueLevel = 100;
        $color = new ColorRgb(
            $twColorRgb->redLevel,
            $twColorRgb->greenLevel,
            $twColorRgb->blueLevel);
        $this->assertEquals(1, $twColorRgb->redLevel);
        $this->assertEquals(10, $twColorRgb->greenLevel);
        $this->assertEquals(100, $twColorRgb->blueLevel);
    }
    
    public function testConvertTwColorRgbArray() {
        $colorRgb = array();
        $colorRgb[] = new ColorRgb(1,10,100);
        $colorRgb[] = new ColorRgb(2,20,200);
        $twColorRgb = 
            TwColorRgbHelper::ColorRgbArray2TwColorRgbArray($colorRgb);
        $this->assertEquals(1, $twColorRgb[0]->redLevel);
        $this->assertEquals(10, $twColorRgb[0]->greenLevel);
        $this->assertEquals(100, $twColorRgb[0]->blueLevel);
        $this->assertEquals(2, $twColorRgb[1]->redLevel);
        $this->assertEquals(20, $twColorRgb[1]->greenLevel);
        $this->assertEquals(200, $twColorRgb[1]->blueLevel);
        
    }
    
    public function testConvertColorHsv2TwColorRgbFails() {
        $colorHsv = new ColorHsv(100,0.5,0.5);
        try {
            $twColor = TwColorRgbHelper::ColorRgb2TwColorRgb($colorHsv);
        } catch (Exception $e) {
           $this->assertEquals(strcmp($e->getCartowebMessage(),
               'This object is not RGB Color'),0);
        }
    }
    
    public function testConvertBin2TwBin() {
        $bin = new Bin(5, 'lab', 10, 11.1);
        $twBin = new TwBin();
        $twBin->nbVal = $bin->getNbVal();
        $twBin->lowerBound = $bin->getLowerBound();
        $twBin->upperBound = $bin->getUpperBound();
        $twBin->label = $bin->getLabel();
        //By default $bin->isLast == false
        $twBin->isLast = $bin->isLastBin();
        
        $this->assertEquals(5,$twBin->nbVal);
        $this->assertEquals('lab',$twBin->label);
        $this->assertEquals(10,$twBin->lowerBound);
        $this->assertEquals(11.1,$twBin->upperBound);
        $this->assertFalse($twBin->isLast);
    }
    
    public function testConvertTwBin() {
        
        $bin = new Bin(5, 'lab', 10, 11.1);
        $twBin = TwBinHelper::bin2TwBin($bin);
        $this->assertEquals(5,$twBin->nbVal);
        $this->assertEquals('lab',$twBin->label);
        $this->assertEquals(10,$twBin->lowerBound);
        $this->assertEquals(11.1,$twBin->upperBound);
        $this->assertFalse($twBin->isLast);
        
        $twBin = new TwBin();
        $twBin->nbVal = 5;
        $twBin->lowerBound = 10;
        $twBin->upperBound = 11.1;
        $twBin->label = 'lab';
        $twBin->isLast = true;
        $bin = TwBinHelper::TwBin2Bin($twBin);
        $this->assertEquals(5, $bin->getNbVal());
        $this->assertEquals('lab', $bin->getLabel());
        $this->assertEquals(10, $bin->getLowerBound());
        $this->assertEquals(11.1, $bin->getUpperBound());
        $this->assertTrue($bin->isLastBin());
    }
    
    public function testConvertTwClassification() {
        $bin1 = new Bin(5, 'lab1', 10, 11.1);
        $bin2 = new Bin(6, 'lab2', 11.1, 100, true);
        $bins = array($bin1,$bin2);
        $classification = new Classification($bins);
        $twClassification = 
            TwClassificationHelper::Classification2TwClassification($classification);
        $twClassificationBins = $twClassification->bins;
        $this->assertEquals('TwBin', get_class($twClassificationBins[0]));
        $this->assertEquals(5,$twClassificationBins[0]->nbVal);
    }
    
    public function testUnserializeTwBin() {
        $struct = new stdclass();
        $struct->nbVal = 5;
        $struct->lowerBound = 10;
        $struct->upperBound = 11.1;
        $struct->label = 'lab';
        $struct->isLast = true;
        $twBin = new TwBin();
        $twBin->unserialize($struct);
        $this->assertEquals(5,$twBin->nbVal);
        $this->assertEquals('lab',$twBin->label);
        $this->assertEquals(10,$twBin->lowerBound);
        $this->assertEquals(11.1,$twBin->upperBound);
        $this->assertTrue($twBin->isLast);
    }
    
    public function testTwDistributionSummary2DistributionSummary() {
        $values = array(1,2,3);
        $distribution = new Distribution($values);
        $summary = new DistributionSummary($distribution);
        $twSummary = 
            TwDistributionSummaryHelper::DistSummary2TwDistSummary($summary);
        $this->assertEquals(3, $twSummary->nbVal);
        $this->assertEquals(1, $twSummary->minVal);
        $this->assertEquals(3, $twSummary->maxVal);
        $this->assertEquals(2, $twSummary->meanVal);
        $this->assertEquals(1.0, $twSummary->stdDevVal, '', 0.01);
        
        $twSummary = new TwDistributionSummary();
        $twSummary->nbVal = 5;
        $twSummary->minVal = 0;
        $twSummary->maxVal = 5;
        $twSummary->meanVal = 3;
        $twSummary->stdDevVal = 1.1;
        $summary = 
            TwDistributionSummaryHelper::TwDistSummary2DistSummary($twSummary);
        $this->assertEquals(5, $summary->nbVal);
        $this->assertEquals(0, $summary->minVal);
        $this->assertEquals(5, $summary->maxVal);
        $this->assertEquals(3, $summary->meanVal);
        $this->assertEquals(1.1, $summary->stdDevVal, '', 0.01);
    }
    
    public function testUnserializeGeostatLayerParams() {
        $struct = new stdclass();
        $struct->msLayer = 'layer';
        $struct->label = 'lab';
        $struct->choropleth = true;
        $struct->symbols = 1;
        $struct->choropleth_attribs = 'a,b';
        $struct->choropleth_attribs_label = 'A,B';
        $struct->symbols_attribs = 'c,d';
        $struct->symbols_attribs_label = 'C,D';
        $params = new GeostatLayerParams();
        $params->unserialize($struct);
        $this->assertEquals('layer',$params->msLayer);
        $this->assertEquals('lab',$params->label);
        $this->assertTrue($params->choropleth);
        $this->assertTrue($params->symbols);
        $this->assertEquals('a,b',$params->choropleth_attribs);
        $this->assertEquals('A,B',$params->choropleth_attribs_label);
        $this->assertEquals('c,d',$params->symbols_attribs);
        $this->assertEquals('C,D',$params->symbols_attribs_label);
    }
}
