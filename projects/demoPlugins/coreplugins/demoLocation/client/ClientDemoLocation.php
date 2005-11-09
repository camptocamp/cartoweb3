<?php
/**
 * Client location plugin extension
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
 * @package CorePlugins
 * @version $Id$ 
 */
  
require_once ('DB.php');

/**
 * Client part of Entension of Location Coreplugin
 * @package CorePlugins
 */
class ClientDemoLocation extends ClientLocation {
          
    /**
     * @ var Logger
     */
    private $log;
    
    /**
     * Input text
     * @var string
     */
    protected $inputNameRecenter = '';
    
    /**
     * @ var boolean
     */
    protected $idRecenterActive = false;
    
    /**
     * List of names
     * @var array
     */
    protected $idRecenterIdsList;
    
    /**
     * @var integer
     */
    protected $nbResults = 0;
        
    /**
     * @var string
     */    
    protected $idRecenterLayer = '';
    
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }
    
    /**
     * @see PluginManager::replacePlugin()
     */
    public function replacePlugin(){
        return 'location';
    }
    
    /**
     * Database connection
     * @param boolean
     */
    protected function getDb() {
        if (!isset($this->db)) {
            $dsn = $this->getConfig()->dsn;
            $this->db = DB::connect($dsn);

            Utils::checkDbError($this->db, 
                                'Error connecting search on names database');
        }
        return $this->db;
    }
    
    /**
    * Retrieves id and name attributes of desired layer from metadata
    * @param array array of layers
    * @return array
    */
   protected function getLayerMeta($idRecenterLayer) {
       // TODO: set in the map file for each layer that can be requested by 
       // the recenterid() function the metadata id_attribute_string, 
       // recenter_name_string and exported_values.
       $idAttribute = $nameAttribute = '';
       $layersInit = $this->cartoclient->getMapInfo()->layersInit;
       foreach($layersInit->getLayers() as $msLayer) {
           if ($msLayer->id == $idRecenterLayer) {
               $id_att = 'id_attribute_string';
               $name_att = 'recenter_name_string';
               $idAttribute = $msLayer->getMetadata($id_att);
               $nameAttribute = $msLayer->getMetadata($name_att);
           }
       }
       $idAttribute = substr($idAttribute, 0, strpos($idAttribute, '|'));
       $Attributes = array($idAttribute, $nameAttribute);
       return $Attributes;
   }
   
    /**
     * Retrieves list of names from database
     * @return array
     */
    protected function namesList($idRecenterLayer, $inputNameRecenter) {
        $idRecenterLayerAttributes = $this->getLayerMeta($idRecenterLayer);
       
        $sql = sprintf("SELECT %s, %s FROM %s WHERE %s LIKE UPPER('%s%%') AND "
                       . "%s != 'UNK' ORDER BY %s",
                       $idRecenterLayerAttributes[1],
                       $idRecenterLayerAttributes[0],
                       $idRecenterLayer,
                       $idRecenterLayerAttributes[1],
                       $inputNameRecenter,
                       $idRecenterLayerAttributes[1],
                       $idRecenterLayerAttributes[1]);
                       
        $this->getDb();
        $res = $this->db->query($sql);
        
        Utils::checkDbError($res, 'Error quering search on names database');
        
        $list = array();
        while ($res->fetchInto($row)) {
            $list[$row[1]] = $row[0];
        }
        
        return $list;
    }
        
    /**
     * @see ClientLocation::handleIdRecenter()
     */
    protected function handleIdRecenter($request, $check = false) {
        $center = $this->locationState->bbox->getCenter();
        $point = clone($center);
        
        $this->idRecenterLayer = $this->getHttpValue($request, 'id_recenter_layer');
        $idRecenterIds   = $this->getHttpValue($request, 'id_recenter_ids');
        $this->inputNameRecenter = 
            $this->getHttpValue($request, 'input_name_recenter');
        
        /*If the number of names possibilities according to the input of the user is
        one, the search by ids is directly launched. Instead, the list of possibilities
        is shown to the user*/
        if ($this->inputNameRecenter != ''){   
            $this->idRecenterIdsList = $this->namesList($this->idRecenterLayer,
                                                        $this->inputNameRecenter);
            $this->nbResults = count($this->idRecenterIdsList);
            if ($this->nbResults == 1) {
                $idRecenterIds = array_search(strtoupper($this->inputNameRecenter),
                                                  $this->idRecenterIdsList);
            }
            $this->idRecenterActive = true;
        }
                
        if ($this->idRecenterLayer && $idRecenterIds) {
            
            $ids = explode(',', $idRecenterIds);
            
            if ($check) {
                $found = false;
                $layersInit = $this->cartoclient->getMapInfo()->layersInit;
                foreach($layersInit->getLayers() as $layer) {
                    if (! $layer instanceof Layer) {
                        continue;
                    }
                    if ($this->idRecenterLayer == $layer->id) {
                        $found = true;
                    }
                }
                if (!$found) {
                    $this->cartoclient->addMessage('ID recenter layer not found');
                    return NULL;
                }
            }
                
            $recenterRequest = new RecenterLocationRequest();
                
            $lastMapResult = $this->cartoclient->getClientSession()->lastMapResult;
            if (!is_null($lastMapResult)) {
                $recenterRequest->fallbackBbox = $lastMapResult->locationResult
                                                               ->bbox;
            } else {
                $recenterRequest->fallbackBbox = $this->locationState->bbox;
            }
                
            $idSelection = new IdSelection();
            $idSelection->layerId = $this->idRecenterLayer;
            $this->locationState->idRecenterSelected = $idSelection->layerId;
            $idSelection->selectedIds = $ids;
                
            $recenterRequest->idSelections = array($idSelection);
             
            $locationRequest = new LocationRequest();              
            $locationType = $recenterRequest->type;
            $locationRequest->locationType = $locationType;
            $locationRequest->$locationType = $recenterRequest;
            
            return $locationRequest;
        }
    }
    
    /**
     * @see ClientLocation::drawIdRecenter()
     */
    protected function drawIdRecenter() {
        
        $this->smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        
        $layersInit = $this->cartoclient->getMapInfo()->layersInit;
        $layersId = array();
        $layersLabel = array();
        $idRecenterLayersStr = $this->getConfig()->idRecenterLayers;
        if (!empty($idRecenterLayersStr)) {
            $idRecenterLayers = Utils::parseArray($idRecenterLayersStr);
        }
        foreach($layersInit->getLayers() as $layer) {
            if (!$layer instanceof Layer) {
                continue;
            }
            if (!empty($idRecenterLayers) && 
                !in_array($layer->id, $idRecenterLayers)) {
                continue;
            }
            $layersId[] = $layer->id; 
            $layersLabel[] = I18n::gt($layer->label); 
        }
        if (empty($this->idRecenterLayer)) {
            $idRecenterSelected = $layersId[0];
        }else{
            $idRecenterSelected = $this->idRecenterLayer;
        }
        
        $this->smarty->assign(
            array('id_recenter_layers_id'    => $layersId,
                  'id_recenter_layers_label' => $layersLabel,
                  'id_recenter_selected'     => $idRecenterSelected,
                  'input_name_recenter'      => $this->inputNameRecenter,
                  'id_recenter_active'       => $this->idRecenterActive,
                  'id_recenter_ids_list'     => $this->idRecenterIdsList,
                  'nb_results'               => $this->nbResults));
        return $this->smarty->fetch('id_recenter.tpl');
    }
}
?>
