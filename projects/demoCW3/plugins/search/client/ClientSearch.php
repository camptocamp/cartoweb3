<?php
/**
 * Search web service
 * @package Plugins
 * @version $Id$
 */

require_once 'DB.php';
//require_once("cartoweb/include/pear/Auth/Container/DB.php");

/**
 * Search web service result
 * @package Plugins
 */
class SearchServiceResult {

    /**
     * @var string
     */
    public $msg = '';

    /**
     * @var array
     */
    public $data = array();
}

/**
 * Session container for search results
 * Not available in this version of search plugin
 * @package Plugins
 */
class SearchState {
    /**
     * @var SearchServiceResult
     */
    public $searchResults;

    /**
     * Constructor
     */
    public function __construct() {
        $this->searchResults = new SearchServiceResult;
    }
}

/**
 * Search web service
 * @package Plugins
 */
class ClientSearch extends ClientPlugin
                   implements GuiProvider, Sessionable {

// TODO set the variables for tables and fields

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var SoapClient
     */
    private $client;
    
     /**
     * @var boolean
     */
     private $input = '';
     
     /**
     * @var boolean
     */
    private $inputActive = false;
    
    /**
     * @var boolean
     */
    private $isOutOfCw = false;
    
    /**
     * @var string
     */
    private $layerSelected = '';
    
     /**
     * @var integer
     */
    private $nbResults;
    
    /**
     * @var boolean
     */
    private $searchInputActive = false;
       
    /**
     * @var SearchState
     */
    private $searchState;
    
    /**
     * @var string
     */
    private $searchInputNom = ''; 
    
    /**
     * @var boolean
     */
    private $simulateQuery = true;
    
    /**
     * @var string
     */
    private $value_alone = '';
    
    
    
    

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }
    
    /**
     * Database connection
     * @param boolean
     */
    private function getDb() {
        if (!isset($this->db)) {
            //TODO set the dsn in the search.ini
            $dsn = $this->getConfig()->dsn;
            $this->db = DB::connect($dsn);
            if (PEAR::isError($this->db)) {
                die($this->db->getMessage());
            }
        }
        return $this->db;
    }

    public function loadSession($sessionObject) {
        $this->log->debug('loading session:');
        $this->log->debug($sessionObject);
        
        $this->searchState = $sessionObject;
    }

    public function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->log->debug('creating session:');

        $this->searchState = new SearchState;
    }

    public function saveSession() {
        $this->log->debug('saving session:');
        $this->log->debug($this->searchState);
        
        return $this->searchState; 
    }

    /**
     * Indicates if current code is run in the viewer frame or 
     * in an external htdocs file.
     * @param boolean
     */
    public function setOutofCw($isOutOfCw) {
        $this->isOutOfCw = $isOutOfCw;
    }

    /**
     * Returns an empty set of results.
     * @return SearchServiceResult
     */
    private function returnEmptyResult() {
        $this->searchState = new SearchState;
        return $this->searchState->searchResults;
    }
   
    
    /**
     * Retrieves list of possibilities for the input text
     * @return array
     */
    private function listNoms($layerSelected, $input) {
        // TODO get the list from session
	$nom_colonne='';$id='';
	switch($layerSelected){
	case 'agglo':
	  $nom_colonne ="NAM";
	  $id = "OGC_FID";
	  break;
	case 'district':
	  $nom_colonne ="NAM";
	  $id = "OGC_FID";
	  break;
	case 'town':
	  $nom_colonne ="TXT";
	  $id = "OGC_FID";
	  break;
	case 'airport':
	  $nom_colonne ="NAM";
	  $id = "OGC_FID";
	  break;
	case 'mountain':
	  $nom_colonne ="ZV2";
	  $id = "OGC_FID";
	  break;
	case 'iceland':
	  $nom_colonne ="AREA";
	  $id = "OGC_FID";
	  break;
	case 'lake':
	  $nom_colonne ="AREA";
	  $id = "OGC_FID";
	  break;
	}
	
        $sql = "SELECT $nom_colonne, $id FROM $layerSelected " .
                "WHERE $nom_colonne LIKE upper('$input%') ORDER BY $nom_colonne;"; 
        //        "GROUP BY $nom_colonne, $id order by $nom_colonne;";
        $this->getDb();
        $res = $this->db->query($sql);
        
        if (DB::isError($res))
            die($res->getMessage());

        while ($res->fetchInto($row)) {
            $list[$row[1]] = $row[0];
        }
        
        return $list;
    }

    /**
     * Calls service methods.
     * @return SearchServiceResult
     */
    private function makeSearch() {
        
        $this->layerList = array('district' => 'region', 'agglo' => 'agglomeration', 'town' => 'ville', 'airport' => 'aeroport', 'mountain' => 'sommet', 'iceland' => 'glacier', 'lake' => 'lac');
        
        if ($this->searchLayer) {
          $this->layerSelected = $this->searchLayer;
        }
	
	$this->inputActive = true;
	
	if (!$this->input || $this->input == 'Saisissez un nom')
          return false;
        else {
          $this->inputList = $this->listNoms($this->layerSelected,$this->input);
	  $this->nbResults = count($this->inputList);
	  if($this->nbResults == 1){$this->value_alone = array_search(strtoupper($this->input), $this->inputList);}
	  $this->searchInputActive = true;
        }      
    }

    /**
     * Sets correct encoding for result data and processes result message.
     * @param SearchServiceResult
     */
    private function filterResults(SearchServiceResult $res) {
        if (strlen($res->msg)) {
            if (is_numeric($res->msg))
                $res->msg = sprintf(I18n::gt('%s results'), $res->msg);
            else
                $res->msg = I18n::gt($res->msg);
        }

        if ($res->data) {
            foreach ($res->data as &$data) {
                foreach ($data as &$info)
                    $info = Encoder::decode($info);
            }
        }
    }

    public function handleHttpPostRequest($request) {
        if (!isset($request['searchLayer']) || !$request['searchLayer'])
            return;
            
        $this->searchLayer   = stripslashes($request['searchLayer']);
        $this->input   = stripslashes($request['input']);
    }

    public function handleHttpGetRequest($request) {
    }

    public function renderForm(Smarty $template) {
        
        if ($this->simulateQuery) {
            $project = $this->cartoclient->getProjectHandler()->
                       getProjectName();
            $iframeSrc = sprintf('%s/search/index.php?project=%s',
                                 $project, 
				 $project); 
        } else {
            $iframeSrc = '';
        }
        
        $template->assign('iframeSrc', $iframeSrc);
    }

    /**
     * Draws search service form and results.
     * @return string Smarty result
     */
    public function drawSearchFrame() {
        $this->makeSearch();
        
//        $this->filterResults($res);
        $mainPage = $this->getConfig()->parentFileName;
        if (!$mainPage)
            $mainPage = 'client.php';
            
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $smarty->assign(array('searchLayer' => $this->layerList,
			      'layerSelected' => $this->layerSelected,
                              'inputActive' => $this->inputActive,
                              'input' => $this->input,
                              'searchInputActive' => $this->searchInputActive,
			      'inputList' => $this->inputList,
			      'nbResults' => $this->nbResults,
			      'value_alone' => $this->value_alone,
                              'mainPage'      => $mainPage, 
			      'iframeSrc' => $iframeSrc
                              ));

        return $smarty->fetch('search.tpl');
    }
}
?>
