<?php
/**
 * @package Plugins
 * @version $Id$
 */

/**
 * Service side plugin for handling selection. It may use the HilightPlugin
 * to render the selection on the map.
 * 
 * @package Plugins
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class ServerSelection extends ServerPluginAdapter {

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
  
    // dependency: has to be called before hilight plugin
    function handleInit($requ) {

$this->log->debug($this->serverContext->mapRequest);
        // TODO: mechanism to fetch request from other plugins
        $hilightRequest = @Serializable::unserializeObject($this->serverContext->
                mapRequest, 'hilightRequest', 'HilightRequest');
        
        if (empty($hilightRequest))
            throw new CartoserverException("selectionRequest needs a hilightRequest");
        
        $layerId = $hilightRequest->layerId;
        
        $newIds = $this->queryLayer($layerId, $requ->bbox);
        $mergedIds = $this->mergeIds($hilightRequest->selectedIds,
                                        $newIds, $requ->policy);
        
        $this->serverContext->mapRequest->hilightRequest->selectedIds = $mergedIds;
                                        
        $this->log->debug("merged ids are: " . var_export($mergedIds, true));
        $hilightRequest->selectedIds = $mergedIds; 
        
        $selectionResult = new SelectionResult();
        
        $selectionResult->selectedIds = $mergedIds; 

        return $selectionResult;
    }
}
?>