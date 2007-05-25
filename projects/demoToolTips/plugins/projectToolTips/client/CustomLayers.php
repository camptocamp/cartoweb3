<?php

class Radio_ptQueryableLayer extends ByXYQueryableLayer {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Sets the type of ResultLayer returned by ResultLayer::queryLayer()
     * @see QueryableLayer::newLayerResult()
     */
    protected function newLayerResult() {
        return new Radio_ptLayerResult();
    }
    
    /**
     * @see QueryableLayer::filterResults()
     */
    public function filterResults($layerResults) {
        // $layerResults attribs: name | power | service | program | freqchan | the_geom
        if (count($layerResults) < 1) return array();

        $stations = array();
        foreach ($layerResults as $layerResult) {
            $attributes = $layerResult->getAttributes();
            $stations[$attributes['id']]['id'] = $attributes['id'];
            $stations[$attributes['id']]['name'] = $attributes['name'];
            $stations[$attributes['id']]['power'] = $attributes['power'];  
            $stations[$attributes['id']]['layerId'] = $layerResult->getId();
            $stations[$attributes['id']]['layerLabel'] = $layerResult->getLabel();
        }

        foreach ($stations as $stationId => $station) {
            $stations[$stationId]['channels'] = $this->getChannelsForStation($stations[$stationId]['id']);
        }
        
        $mergedLayerResults = array();
        foreach ($stations as $stationId => $station) {
            $mergedLayerResult = $this->newLayerResult();
            $mergedLayerResult->setId($station['layerId']);
            $mergedLayerResult->setLabel($station['layerLabel']);
            $mergedLayerResult->addAttributes($station);
            $mergedLayerResults[] = $mergedLayerResult;
        }
        
        return $mergedLayerResults; 
    }
    
    /**
     * Get channels for the corresponding station
     * @param int station id
     * @return array of channels
     */
    private function getChannelsForStation($station_id) {
        $sql = 'SELECT service, program, freqchan ' .
                'FROM channel ' .
                "WHERE id_radio = $station_id";
                
        $dbResult = $this->db->query($sql);
        
        Utils::checkDbError($dbResult, 'Error');
        
        $channels = array();
        $resultArray = array();
        while ($dbResult->fetchInto($resultArray, DB_FETCHMODE_ASSOC)) {
            $channels[] = $resultArray;
        }
        
        
        return $channels;
    }
}

class Radio_ptLayerResult extends LayerResult {

    /**
     * @see LayerResult::renderResult()
     */
    public function renderResult($smarty) {
        
        $smarty->assign('layerId', $this->getId());
        $smarty->assign('layerLabel', $this->getLabel());
        $smarty->assign('stationName', $this->getAttribute('name'));
        $smarty->assign('stationPower', $this->getAttribute('power'));
        $smarty->assign('channels', $this->getAttribute('channels'));
        
        return $smarty->fetch('layerResult_radioLayer.tpl');
               
    }
}

?>