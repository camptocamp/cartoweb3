<?php
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * Server side plugin for handling selection. It may use the HilightPlugin
 * service plugin to render the selection on the map.
 * 
 * @package Plugins
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class ServerSelection extends ClientResponderAdapter {

    private $log;

    function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }
  
    private function queryLayer($layerId, $shape) {
        
        $plugins = $this->serverContext->pluginManager;
        if (empty($plugins->query))
            throw new CartoserverException("query plugin not loaded, and needed " .
                    "for the selection request");
        
        // FIXME: PERFORMANCE add a queryArgs which does not fetch metadata 
        // (but needs an id, retrieved from classItem !!)
        $queryArgs = new stdclass();
        
        $layerResult = $plugins->query->queryLayer($layerId, $shape, $queryArgs);
        $ids = $plugins->query->getIdsFromLayerResult($layerResult);
        return $ids;
    }

    private function array_union($a, $b) {
        return array_unique(array_merge($a, $b));   
    }

    private function array_xor($a, $b) {
        return array_diff($this->array_union($a, $b), array_intersect($a, $b));   
    }  
  
    private function mergeIds($previousIds, $newIds, $policy) {

        $this->log->debug("previous Ids: " . var_export($previousIds, true));
        $this->log->debug("new Ids: " . var_export($newIds, true));

        switch($policy) {
        case SelectionRequest::POLICY_XOR:
            return $this->array_xor($previousIds, $newIds);
            break;
        case SelectionRequest::POLICY_UNION:
            return $this->array_union($previousIds, $newIds);
            break;
        case SelectionRequest::POLICY_INTERSECTION:
            return array_intersect($previousIds, $newIds);
            break;
        default:
            throw new CartoserverException("invalid selection request policy $policy");
        }
    }
    
    private function getLabel($msLayer, $values) {
        
        $labelFixedValue = $msLayer->getMetaData('label_fixed_value');
        if (!empty($labelFixedValue)) {
            return $labelFixedValue;
        }
        
        $labelFieldName = $msLayer->getMetaData('label_field_name');
        if (empty($labelFieldName))
            $labelFieldName = 'label';

        // change here to set that a missing label field is fatal
        $noLabelFieldFatal = false;
        if (!isset($values[$labelFieldName])) {
            if ($noLabelFieldFatal)
                throw new CartoserverException("No label field named " .
                        "\"$labelFieldName\" found in layer $requ->layerId");
            return 'no_name';
        }
        return $values[$labelFieldName];
    }
    
    private function getArea($msLayer, $values) {
        $areaFactor = $msLayer->getMetaData('area_factor');
        if (empty($areaFactor))
            $areaFactor = 1.0;
        else
            $areaFactor = (double)$areaFactor;
        
        $areaFixedValue = $msLayer->getMetaData('area_fixed_value');
        if (!empty($areaFixedValue)) {
            return (double)$areaFixedValue * $areaFactor;
        }
        
        $areaFieldName = $msLayer->getMetaData('area_field_name');
        if (empty($areaFieldName))
            $areaFieldName = 'area';

        // change here to set that a missing area field is fatal
        $noAreaFieldFatal = false;
        if (!isset($values[$areaFieldName])) {
            if ($noAreaFieldFatal)
                throw new CartoserverException("No area field named " .
                        "\"$areaFieldName\" found in layer $requ->layerId");
            return 0.0;
        }
        return (double)$values[$areaFieldName] * $areaFactor;
    }
    
    private function encodingConversion($str) {
        // FIXME: $str is asserted to be iso8851-1 
        return utf8_encode($str);
    }
    
    private function getAttributes($requ) {
    
        $mapInfo = $this->serverContext->getMapInfo();
        $serverLayer = $mapInfo->getLayerById($requ->layerId);
        if (!$serverLayer)
            throw new CartoserverException("can't find serverLayer $requ->layerId");

        $msMapObj = $this->serverContext->getMapObj();
        
        $msLayer = @$msMapObj->getLayerByName($serverLayer->msLayer);
        if (empty($msLayer))
            throw new CartoserverException("can't find mslayer $serverLayer->msLayer");
        
        $layerResult = new LayerResult();
        $layerResult->layerId = $requ->layerId;
        $layerResult->fields = array('label', 'area');
        $layerResult->resultElements = array();

        $results = array();
        if (count($requ->selectedIds) > 0) {
            $pluginManager = $this->serverContext->pluginManager;
            if (!empty($pluginManager->mapquery)) {
                $results = $pluginManager->mapquery->queryByIdSelection($requ);            
            }            
        }
        
        $idAttribute = $this->serverContext->getIdAttribute($requ->layerId);
        foreach ($results as $result) {
            $resultElement = new ResultElement();
            
            if (!is_null($idAttribute))
                $resultElement->id = $this->encodingConversion(
                                                $result->values[$idAttribute]);
            // warning: filling order has to match field order
            $resultElement->values[] = $this->encodingConversion(
                        $this->getLabel($msLayer, $result->values));
            $resultElement->values[] = 
                        $this->getArea($msLayer, $result->values);
            $layerResult->resultElements[] = $resultElement;
        }
        
        return array($layerResult);
    }

    function handlePreDrawing($requ) {

        $layerId = $requ->layerId;     
        
        $mergedIds = $requ->selectedIds;
        if (!is_null($requ->bbox)) {   
            $newIds = $this->queryLayer($layerId, $requ->bbox);
            $mergedIds = $this->mergeIds($requ->selectedIds, $newIds, $requ->policy);
        
            $this->log->debug("merged ids are: " . var_export($mergedIds, true));            
        }

        $pluginManager = $this->serverContext->pluginManager;
        if (!empty($pluginManager->hilight)) {
            $requ->selectedIds = $mergedIds;
            $pluginManager->hilight->hilightLayer($requ);
        }  
        
        if (!$requ->returnResults) {
            return null;
        }
        
        $selectionResult = new SelectionResult();
        $selectionResult->selectedIds = $mergedIds; 

        if (!$requ->retrieveAttributes) {
            return $selectionResult;
        }

        $selectionResult->layerResults = $this->getAttributes($requ);

        return $selectionResult;
    }
}
?>