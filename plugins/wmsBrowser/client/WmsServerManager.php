<?php
/* Modifs by CartoWeb dev team on WmsServerManager class:
 *  - simplify buildServersList, addServer, updateServer, setServerStatus, 
 *    removeServer, flushServer, refreshServer, getCapabilities, testServer 
 *    and normalizeString methods.
 *  - keep only, in parseCapabilities method, use of php embed parser
 *  - give up mapscript layer creation, in getLayerObj method, to create a
 *    layerOverlay object (the dynamic insertion of the layer in the mapfile 
 *    is delegated to the CartoWeb mapOverlay plugin). 
 *    This method is renamed to createWmsLayer and the fetch layer properties
 *    act is moved to the new fetchLayerMetadatas, fetchLayerStyle, 
 *    fetchLayerSrs, fetchLayerAbstract and fetchLayerBbox methods
 *  - delete remaining methods
 *  - create new methods getServerByUrl and validateUrl
 * 
 * @version $Id$  
 */
////////////////////////////////////////////////////////////////////////////////
// MapBrowser application                                                     //
//                                                                            //
// @project     MapLab                                                        //
// @purpose     This is the dbase database management utility page.           //
// @author      William A. Bronsema, C.E.T. (bronsema@dmsolutions.ca)         //
// @copyright                                                                 //
// <b>Copyright (c) 2002, DM Solutions Group Inc.</b>                         //
// Permission is hereby granted, free of charge, to any person obtaining a    //
// copy of this software and associated documentation files(the "Software"),  //
// to deal in the Software without restriction, including without limitation  //
// the rights to use, copy, modify, merge, publish, distribute, sublicense,   //
// and/or sell copies of the Software, and to permit persons to whom the      //
// Software is furnished to do so, subject to the following conditions:       //
//                                                                            //
// The above copyright notice and this permission notice shall be included    //
// in all copies or substantial portions of the Software.                     //
//                                                                            //
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR //
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,   //
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.  IN NO EVENT SHALL   //
// THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER //
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING    //
// FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER        //
// DEALINGS IN THE SOFTWARE.                                                  //
////////////////////////////////////////////////////////////////////////////////

class WmsServerManager {

    /**                    
     * Logger
     * @var string
     */
    private $log;
    
    /**
     * Execution time (seconds)
     * @var int
     */
    protected $maxExecutionTime;
    
    /**
     * Constructor
     */
    public function __construct($maxExecutionTime=300) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->maxExecutionTime = $maxExecutionTime;
    }
    
    /**
     * Returns the server record in the server database whose url is passed.
     * @param integer identifier for the server database
     * @param string server url
     * @param boolean if true (default), throws exception if server isn't ever 
     * registred in database
     * @return array server database record
     */
    public function getServerByUrl($dbServer, $serverUrl, $strict = true) {
        if (!$serverRec = WmsDatabase::getRecordBy($dbServer, 'capab_url', 
                                                   $serverUrl)) {
            if ($strict)
                throw new CartoclientException(sprintf('server [%s] was not ' .
                    'found in server database', $serverUrl));
            else
                return false;
        }

        return $serverRec;
    }

    /**
     * Opens the server database and returns all records as an array.
     * @param boolean if true, push in array capabilities file modification time
     * @return array server record as an associative array
     **/
    public function buildServersList($fileStatus = false) {
        $dbServer = WmsDatabase::getDb(WmsDatabase::DB_SERVER);
        $serversList = array();
        $nDbServer = dbase_numrecords($dbServer);
        for ($i = 1; $i <= $nDbServer; $i++) {
            $serversList[$i] = WmsDatabase::getRecordById(
                $dbServer, $i);
            if ($fileStatus) {
                // get the capabilities file date/status
                $capabilitiesFile = WMS_CACHE_DIR . $this->normalizeString(
                    $serversList[$i]['capab_url']) . '.xml';
                if (file_exists($capabilitiesFile))
                    $serversList[$i]['file_status'] = 
                        date("l F j, Y, g:i a", filemtime($capabilitiesFile));
                else
                    $serversList[$i]['file_status'] = 'Never';
            }
        }
        WmsDatabase::dbaseClose($dbServer, false);
        
        return $serversList;
    }

    /**
     * Adds a server to the server database.
     * @param string name of the server.
     * @param string url of the server.
     * @param string user comment.
     * @return boolean true if successful, false else
     **/
    public function addServer($serverName, $serverUrl, $serverComment) {
        $dbServer = WmsDatabase::getDb(WmsDatabase::DB_SERVER);
        // verify this server isn't already registred
        if (WmsDatabase::findRecord($dbServer, 'capab_url', $serverUrl))
            return false;
        // fetch the current maximum server ID in use.
        $maxDbServerId = WmsDatabase::findMaxValue($dbServer, 'server_id');    
        // build record
        $serverRec['server_id'] = ++$maxDbServerId;
        $serverRec['capab_url'] = $serverUrl;
        $serverRec['map_url'] = $serverUrl;
        $serverRec['version'] = '';
        $serverRec['formats'] = '';
        $serverRec['name'] = $serverName;
        $serverRec['title'] = '';
        $serverRec['comment'] = $serverComment;
        $serverRec['con_status'] = 1;
        WmsDatabase::dbaseAddRecord($dbServer, array_values($serverRec));
        WmsDatabase::dbaseClose($dbServer);

        return true;
    }
    
    /**
     * Updates given server with the specified information.
     * @param string server url
     * @param string new name
     * @param string new url
     * @param string new user comment
     **/
    public function updateServer($serverUrl, $newServerName, 
                                 $newServerUrl, $userComment) {
        $dbServer = WmsDatabase::getDb(WmsDatabase::DB_SERVER);
        $serverRec = $this->getServerByUrl($dbServer, $serverUrl);
        // remove deleted field
        array_pop($serverRec);
        $serverRec['capab_url'] = $newServerUrl;
        if (trim($newServerName) != '')
            $serverRec['name'] = $newServerName;
        else
            $serverRec['name'] = $serverRec['title'];
        $serverRec['comment'] = $userComment;
        
        WmsDatabase::dbaseReplaceRecord($dbServer, array_values($serverRec),
                                        $serverRec['server_id']);
        WmsDatabase::dbaseClose($dbServer);
    }

    /**
     * Sets the given server's status to the given value
     * @param string url of the server to update.
     * @param integer status value to update to.
     *        - 0: disconnected
     *        - 1: connected
     **/
    public function setServerStatus($serverUrl, $status) {
        $dbServer = WmsDatabase::getDb(WmsDatabase::DB_SERVER);
        $serverRec = $this->getServerByUrl($dbServer, $serverUrl);
        WmsDatabase::setValue($dbServer, $serverRec['server_id'], 
                              'con_status', $status);
        WmsDatabase::dbaseClose($dbServer);
    }

    /**
     * Removes a server from the server database and all references to it.
     * @param string server url
     **/
    public function removeServer($serverUrl) {
        $dbServer = WmsDatabase::getDb(WmsDatabase::DB_SERVER);
        $this->flushServer($dbServer, $serverUrl);

        if (!$serverId = WmsDatabase::findRecord($dbServer, 'capab_url',
                                                 $serverUrl)) {
            WmsDatabase::dbaseClose($dbServer, false);
            throw new CartoclientException(sprintf('server [%s] was not ' .
                'found in server database', $serverUrl));
        }
        WmsDatabase::dbaseDeleteRecord($dbServer, $serverId);
        WmsDatabase::dbaseClose($dbServer);
    }

    /**
     * Clears out data related to a server record.
     * @param integer the identifier for the server database (must be open)
     * @param string server url
     **/
    protected function flushServer($dbServer, $serverUrl) {
        $serverRec = $this->getServerByUrl($dbServer, $serverUrl);
        
        $capabilitiesFile = WMS_CACHE_DIR . 
            $this->normalizeString($serverRec['capab_url']) . '.xml';
        $serverId = $serverRec['server_id'];
        $srsFile = WMS_CACHE_DIR . 'srs_' . $serverId . '.txt';
        $abstractFile = WMS_CACHE_DIR . 'abstract_' . $serverId . '.txt';

        // remove xml file & srs file & abstract file
        WmsDatabase::deleteFile($capabilitiesFile);
        WmsDatabase::deleteFile($srsFile);
        WmsDatabase::deleteFile($abstractFile);

        // remove entries from the capabilities, bbox and style dbf files
        $dbCapab = WmsDatabase::getDb(WmsDatabase::DB_CAPABILITIES);
        $dbBbox  = WmsDatabase::getDb(WmsDatabase::DB_BBOX);
        $dbStyle = WmsDatabase::getDb(WmsDatabase::DB_STYLE);
        
        $nDbCapab = dbase_numrecords($dbCapab);
        for ($i=1; $i<=$nDbCapab; $i++) {
            $capabRec = WmsDatabase::getRecordById($dbCapab, $i);
            
            if ($capabRec['deleted'] == 1) {continue;}
            
            if ($capabRec['server_id'] == $serverId) {
                // delete all bboxIds associated with the layer
                $bboxId = $capabRec['bbox_id'];
                while ($bboxId != -1) {
                    $dbBboxId = WmsDatabase::findRecord($dbBbox, 'bbox_id', 
                                                        $bboxId);
                    if ($dbBboxId) {
                        $bboxRec = WmsDatabase::getRecordById(
                            $dbBbox, $dbBboxId);
                        $bboxId = $bboxRec['next_id'];
                        WmsDatabase::dbaseDeleteRecord($dbBbox, $dbBboxId);
                    } else {
                        $bboxId = -1;
                    }
                }
                // delete all style_ids associated with the layer
                if (isset($capabRec['style_id'])) {
                    $styleId = $capabRec['style_id'];
                    while ($styleId != -1) {
                        $dbStyleId = WmsDatabase::findRecord(
                            $dbStyle, 'style_id', $styleId);
                        if ($dbStyleId) {
                            $styleRec = WmsDatabase::getRecordById(
                                $dbStyle, $dbStyleId);
                            $styleId = $styleRec['next_id'];
                            WmsDatabase::dbaseDeleteRecord($dbStyle, $dbStyleId);
                        } else {
                            $styleId = -1;
                        }
                    }
                }
                // delete the layer
                WmsDatabase::dbaseDeleteRecord($dbCapab, $i);
            }
        }
        WmsDatabase::dbaseClose($dbCapab);
        WmsDatabase::dbaseClose($dbBbox);
        WmsDatabase::dbaseClose($dbStyle);
    }

    /**
     * Refreshs a server entry by removing all related records and 
     * re-downloading/parsing the capabilities file. If something went wrong 
     * removes server from the server database and all references to it.
     * @param string server url
     * @return boolean true if successful, false if not.
     **/
    public function refreshServer($serverUrl) {
        $dbServer = WmsDatabase::getDb(WmsDatabase::DB_SERVER);
        $this->flushServer($dbServer, $serverUrl);
        
        $serverRec = $this->getServerByUrl($dbServer, $serverUrl);
        WmsDatabase::dbaseClose($dbServer, false);
        $onlineResource = $serverRec['capab_url'];
        $capabilitiesFile = WMS_CACHE_DIR .
                            $this->normalizeString($onlineResource) . '.xml';
        $this->getCapabilities($onlineResource, $capabilitiesFile);
        $parse = $this->parseCapabilities($capabilitiesFile,
                                          $serverRec['server_id']);
            
        $dbServer = WmsDatabase::getDb(WmsDatabase::DB_SERVER);
        if (!$serverRec = 
            WmsDatabase::getRecordBy($dbServer, 'server_id', $serverRec['server_id'])) {
            WmsDatabase::dbaseClose($dbServer, false);
            throw new CartoclientException(sprintf('server [%s] was not ' .
                'found in server database', $serverUrl));
        }
        // verify parse
        if (!$parse) {
            WmsDatabase::dbaseClose($dbServer, false);
            $this->removeServer($serverRec['capab_url']);
            return false;
        }
        
        // update the server record if necessary.
        if ($serverRec['name'] == '') {
            $serverRec['name'] = $serverRec['title'];
            // remove 'deleted' item
            array_pop($serverRec);
            WmsDatabase::dbaseReplaceRecord($dbServer, array_values($serverRec),
                                            $serverRec['server_id']);
            WmsDatabase::dbaseClose($dbServer);
            return true;
        }
        
        WmsDatabase::dbaseClose($dbServer, false);
        return true;
    }
    
    /**
     * Validates url
     * @param string server url
     * @param boolean if true, add connection parameters for getCapabilities 
     * request
     * @return string validated server url
     */
     public function validateUrl($url, $capabilities = false) {
        if (strstr($url, '?') !== false) {
            if (substr($url, -1) == '&' || substr($url, -1) == '?')
                $questionMark = '';
            else
                $questionMark = '&';
        } else {
            $questionMark = '?';
        }
        $url .= $questionMark;

        if ($capabilities && !stristr($url, 'getcapabilities'))
            $url .= 'REQUEST=GetCapabilities&VERSION=1.1.1&SERVICE=WMS';

        return $url;
     }
    
    /**
     * Executes a getcapabilities call against the server at the url and 
     * the results are saved an xml file.
     * @param string url to fetch from.
     * @param string xml file to write to.
     **/
    protected function getCapabilities($onlineResource, $capabilitiesFile) {
        // validate url
        $onlineResource = $this->validateUrl($onlineResource, true);
        // fetch capabilities
        set_time_limit($this->maxExecutionTime);
        $fpIn = WmsDatabase::openFile($onlineResource, 'r');
        $fpOut = WmsDatabase::openFile($capabilitiesFile, 'w');
        if (!stream_copy_to_stream($fpIn, $fpOut))
            throw new CartoclientException(sprintf('An error occured ' .
                'while writing %s', $capabilitiesfile));
        fclose($fpIn);
        fclose($fpOut);
    }

    /**
     * Tests the availablility of a server.
     * @param string url to fetch from.
     * @return boolean true if available, false if not.
     **/
    public function testServer($onlineResource) {        
        $onlineResource = $this->validateUrl($onlineResource, true);
        set_time_limit($this->maxExecutionTime);
        if (!$fpIn = WmsDatabase::openFile($onlineResource, 'r', false)) {
            return false;
        } else {
            fclose($fpIn);
            return true;
        }
    }
    
    /**
     * Parses an xml capabilities file and populate dbase files from it.  
     * @param string capabilities file to use as input.
     * @param integer server id.
     * @return boolean true if successful, false if not.
     **/
    protected function parseCapabilities($capabilitiesFile, $serverId) {
        set_time_limit($this->maxExecutionTime);
        if (extension_loaded('chameleon') || 
            @dl('chameleon.' . PHP_SHLIB_SUFFIX)) {
            $return = wmsparse_add_server(
                          $capabilitiesFile, 
                          WMS_CACHE_DIR . WmsDatabase::DB_SERVER, 
                          WMS_CACHE_DIR . WmsDatabase::DB_CAPABILITIES, 
                          WMS_CACHE_DIR . WmsDatabase::DB_BBOX, 
                          WMS_CACHE_DIR . WmsDatabase::DB_STYLE,
                          WMS_CACHE_DIR . 'srs_' . $serverId . '.txt',
                          WMS_CACHE_DIR . 'abstract_' . $serverId . '.txt',
                          $serverId);
            if ($return > 0)
                return true;
            return false;
        } else {
            throw new CartoclientException('Chameleon extension required. ' .
                'Please load it in the php.ini file. ' .
                'See the wmsBrowser plugin installation section on ' .
                'CartoWeb Wiki : http://www.cartoweb.org/cwiki/WmsBrowser');
        }
    }
    
    /**
     * Replaces all special characters in the given string.
     * e.g.    "http://my.host.com/cgi-bin/mywms?"
     * becomes "http___my_host_com_cgi_bin_mywms_"
     * @param string string to convert.
     * @return string converted
     */
    protected function normalizeString($string) {
        return preg_replace("/(\W)/", "_", $string);
    }
    
    /**
     * Opens bbox database and fetch the bbox(es) for the layer
     * @param integer first bbox id in bbox database
     * @return string list of bbox(es)
     */ 
    protected function fetchLayerBbox($bboxId) {
        $layerBbox = '';
        $dbBbox = WmsDatabase::getDb(WmsDatabase::DB_BBOX);
        while ($bboxId >= 0) {
            $bboxRec = WmsDatabase::getRecordBy($dbBbox, 'bbox_id', $bboxId);
            if (!$bboxRec) {
                WmsDatabase::dbaseClose($dbBbox, false);
                throw new CartoclientException(
                    sprintf('bbox id [%s] was not found in bbox file [%s]', 
                            $bboxId, WmsDatabase::DB_BBOX));
            }
            
            $bbox = new Bbox();
            $bbox->setFromBbox($bboxRec['minx'], $bboxRec['miny'], 
                               $bboxRec['maxx'], $bboxRec['maxy']);
            $bboxString = $bbox->toRemoteString();
            $layerBbox .= $bboxRec['SRS'] . ' ' . $bboxString . ' ';
            
            $bboxId = $bboxRec['next_id'];
        }
        
        WmsDatabase::dbaseClose($dbBbox, false);
        return $layerBbox;
    }
    
    /**
     * Opens abstract file and fetch the abstract for the layer
     * @param integer server id
     * @param integer abstract id
     * @return string layer abstract if exist else empty string
     */
    protected function fetchLayerAbstract($serverId, $abstractId) {
        $abstract = '';
        $abstractFile = WMS_CACHE_DIR . 'abstract_' . $serverId . '.txt';
        $abstractContent = WmsDatabase::getFileContent($abstractFile);
        for ($i = 0 ; $i < count($abstractContent) ; $i++) {
            if ($i == $abstractId) {
                $abstract .= trim($abstractContent[$i]);
                break;
            }
        }

        return str_replace('\n', ' ', $abstract);
    }
    
    /**
     * Opens srs file and fetch the srs for the layer
     * @param integer server id
     * @param string comma-delimited list of srs record ids
     * @return string one space delimited list of SRS or empty string if 
     * no found
     */
    protected function fetchLayerSrs($serverId, $srsIds) {
        $srs = array();
        $srsIds = array_unique(Utils::parseArray($srsIds));
        $srsFile = WMS_CACHE_DIR . 'srs_' . $serverId . '.txt'; 
        
        $srsContent = WmsDatabase::getFileContent($srsFile);
        foreach ($srsIds as $srsId) {
            $srs[] = $srsContent[$srsId];
        }
        
        return implode(' ', $srs);
    }
    
    /**
     * Opens style database and fetch the styles for the layer
     * @param integer style id in style database
     * @return array array of parameters :
     *         - first style name
     *         - styles names list
     *         - array of sld url for each style
     */
    protected function fetchLayerStyle($styleId) {
        $firstStyle = '';
        $first = true;
        $stylesList = array();
        $stylesData = array();
        $dbStyle = WmsDatabase::getDb(WmsDatabase::DB_STYLE);
        while ($styleId != -1) {
            $styleRec = WmsDatabase::getRecordBy($dbStyle, 'style_id', $styleId);
            if (!$styleRec) {
                WmsDatabase::dbaseClose($dbServer, false);
                throw new CartoclientException(sprintf('server [%s] was not ' .
                    'found in server database', $serverUrl));
            }
            // validate name & title (mapserver doesn't allow quotes in metadata)
            if (strchr($styleRec['name'], '"') !== false ||
                strchr($styleRec['title'], '"') !== false)
                throw new CartoclientException("NAME or TITLE can't " .
                                               'have quote in it');
            $stylesList[] = $styleRec['name'];
            if ($first) {
                $firstStyle = $styleRec['name'];
                $first = false;
            }
            
            $legendUrl = $styleRec['legendurl'];
            $legendUrl .=  sprintf('&Width=%s&Height=%s&Style=%s', 
                $styleRec['leg_width'], $styleRec['leg_height'], 
                $styleRec['name']);
            $stylesData[$styleRec['name']] = $legendUrl;
            
            $styleId = $styleRec['next_id'];
        }
        WmsDatabase::dbaseClose($dbStyle, false);

        return array($firstStyle, implode(',', $stylesList), $stylesData);
    }
    
    /**
     * Fetches layer's metadatas from a server database record and a 
     * capabilities database record
     * @param array server database record
     * @param array capabilities database record
     * @param boolean if true, fetch abstract metadata
     * @param boolean if true, fetch boundingbox metadata
     * @param boolean if true, fetch styles metadatas
     * @param boolean if true, fetch metadataURL metadatas
     * @return array array of layer's metadatas properties
     */
    public function fetchLayerMetadatas($serverRec, $capabRec,
                                        $boundingbox = false, $style = false, 
                                        $abstract = false, $metadata = false) {
        $layerMetadatas['name'] =  $capabRec['name'];
        $layerMetadatas['title'] = str_replace("\n", ' ', $capabRec['title']);
        $layerMetadatas['title'] = str_replace("'", "\'", $layerMetadatas['title']);
        $bbox = new Bbox();
        $bbox->setFromBbox($capabRec['ll_minx'], $capabRec['ll_miny'],
                           $capabRec['ll_maxx'], $capabRec['ll_maxy']);   
        $layerMetadatas['latlonboundingbox'] = $bbox->toRemoteString();
        $layerMetadatas['server_version'] = $serverRec['version'];
        $layerMetadatas['onlineresource'] = urlencode($serverRec['map_url']);
        $layerMetadatas['formatlist'] = $serverRec['formats'];
        $formats = Utils::parseArray($serverRec['formats']);
        $layerMetadatas['format'] = $formats[0];
        $capabRec['queryable'] ? $queryable = 1 : $queryable = 0;
        $layerMetadatas['queryable'] = $queryable;
        // srs
        if ($capabRec['srs_ids'] != '') 
            $layerMetadatas['srs'] = $this->fetchLayerSrs(
                $capabRec['server_id'], $capabRec['srs_ids']);
        
        // bbox(es)
        if ($boundingbox)
            $layerMetadatas['boundingbox'] =  
                $this->fetchLayerBbox($capabRec['bbox_id']);
        // style(s)
        if (isset($capabRec['style_id']) && $style) {
            list($firstStyle, $stylesList, $stylesData) = 
                $this->fetchLayerStyle($capabRec['style_id']);    
            $layerMetadatas['stylelist'] = $stylesList;
            $layerMetadatas['style'] = $firstStyle;
            if (!empty($stylesData)) {
                foreach($stylesData as $key => $value) {
                    $layerMetadatas[$key . '_sld'] = $value;
                }
            }
        }
        $layerMetadatas['legend_graphic'] = '1';
        // abstract
        if ($abstract){
            $layerAbstract = '';
            if (isset($capabRec['abstractid']) && $abstract) {
                $abstractId = $capabRec['abstractid'];
                if ($abstractId != -1 && $abstractId != '')
                $layerAbstract = $this->fetchLayerAbstract(
                    $capabRec['server_id'], $abstractId);
            }
            $layerMetadatas['abstract'] = htmlentities($layerAbstract);
        }
        // metadatas
        if ($metadata) {
            $metadataurl_href = '';
            $metadataurl_type = '';
            $metadataurl_format = '';
            if (isset($capabRec['mdu_url'])) 
                $metadataurl_href = urlencode($capabRec['mdu_url']);
            if (isset($capabRec['mdu_type']))
                $metadataurl_type = $capabRec['mdu_type'];
            if (isset($capabRec['mdu_format']))
                $metadataurl_format = $capabRec['mdu_format'];
            $layerMetadatas['metadataurl_href'] = $metadataurl_href;
            $layerMetadatas['metadataurl_type'] = $metadataurl_type;
            $layerMetadatas['metadataurl_format'] = $metadataurl_format;
        }
        
        return $layerMetadatas;
    }
    
    /**
     * Fetches layer data for the given server url and layer name and 
     * returns a new layerOverlay object based on this data.
     * @param string server url
     * @param string wms layer name
     * @return object the new layerOverlay object.
     **/
    public function createWmsLayer($serverUrl, $layerName) {
        // get server record
        $dbServer = WmsDatabase::getDb(WmsDatabase::DB_SERVER);
        $serverRec = $this->getServerByUrl($dbServer, $serverUrl);
        WmsDatabase::dbaseClose($dbServer, false);
        // get capabilities record
        $dbCapab = WmsDatabase::getDb(WmsDatabase::DB_CAPABILITIES);
        $capabIds = WmsDatabase::listRecords($dbCapab, 'name',
            $layerName, 'layer_id', 'server_id', $serverRec['server_id']);
        if (!$capabIds) {
            WmsDatabase::dbaseClose($dbCapab, false);
            throw new CartoclientException(sprintf(
                'Failed to find layer named %s', $layerName));
        }
        $capabRec = WmsDatabase::getRecordBy($dbCapab, 'layer_id', $capabIds[0]);
        WmsDatabase::dbaseClose($dbCapab, false);
        
        // create LayerOverlay obj
        $layerOverlay = new LayerOverlay();
        $layerOverlay->action = BasicOverlay::ACTION_INSERT;
        $layerOverlay->connection = $this->validateUrl($serverRec['map_url']);
        $layerOverlay->connectionType = 7; // MS_WMS
        isset($capabRec['maxscale']) ? 
            $maxScale = $capabRec['maxscale'] : $maxScale = -1;
        $layerOverlay->maxscale = $maxScale;
        isset($capabRec['minscale']) ? 
            $minScale = $capabRec['minscale'] : $minScale = -1;
        $layerOverlay->minscale = $minScale;
        $layerOverlay->name =ereg_replace(' ', '_', 
            $serverRec['name'] . '-' . $capabRec['name']);
        $layerOverlay->type = 3; // MS_LAYER_RASTER
        $layerProperties['metadatas'] = 
            $this->fetchLayerMetadatas($serverRec, $capabRec, false, true);
        foreach ($layerProperties['metadatas'] as $key => $value) {
            if (!empty($value)) {
                $metadataOverlay = new MetadataOverlay();
                $metadataOverlay->name = 'wms_' . $key;
                $metadataOverlay->value = $value;
                $metadataOverlay->action = BasicOverlay::ACTION_INSERT;
                $layerOverlay->metadatas[] = $metadataOverlay;
            }
        }

        return $layerOverlay; 
    }
}
?>
