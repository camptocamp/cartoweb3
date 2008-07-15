<?php
/*
*
* This script is free software; you can redistribute it and/or modify
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
* @package Plugins
* @version $Id: ClientExportRtf.php,v 1.0  
*/

include dirname(__FILE__) . '/PageCode.php';
require_once CARTOWEB_HOME . 'client/ExportPlugin.php';

/**
  * Overall class for generating RTF
  */
class ClientExportRtf extends ExportPlugin {
    /**
    * @var string the output RTF
    */
    private $rtf;
    /**
     * @var array activated block in the form.tpl
     */
    protected $optionalInputs = array('title', 'scalebar', 'overview', 'queryResult');
    /**
     * @var array state that comes from posted form 
     */
    protected $exportRtfFormState = array();
    /**
     * @var array optional values set in the config file
     */
    protected $addedValues = array();
    /**
     * @var array received inputs
     */
    protected $addedInputs = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        parent :: __construct();
    }
    
    /*
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {
          $rtfRoles = $this->getConfig()->allowedRoles;
          if (!SecurityManager::getInstance()->hasRole($rtfRoles)) {
              return;
          }
         // we get all the RTF inputs 
        foreach ($request as $inputName => $inputVal) {
              if (substr($inputName, 0, 3) == 'rtf') {
                  $this->exportRtfFormState[$inputName] = $inputVal;
              }
              // we get all the optional RTF inputs
              if (substr($inputName, 0, 6) == 'optRtf') {
                  $this->addedInputs[$inputName] = $inputVal;
              }
          }
        
    }

    /**
     *  @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {
       
    }

    /**
     * 
     * @return String exported rtf string   
     * @see ExportPlugin::getExport
     */
    protected function getExport() {
        try {
                $session = $this->getCartoclient()->getClientSession();
                $this->rtf = $this->render_rtf();
                $output = new ExportOutput();
                $output->setContents($this->rtf);
                return $output;
        } catch (Exception $e) {
            throw new CartoclientException($e->getMessage());
        }
    }
        
    /**
     * @see ExportPlugin::output()
     */
    public function output() {
        try {
            $content = $this->getExport()->getContents();
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private",false);
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"".mktime().".rtf\";");
            header("Content-Length: ".strlen($content));
            print  str_replace('?',"'",$content);//iconv("UTF-8","ISO-8859-1//IGNORE",$content));
            } catch (Exception $e) {
                throw new CartoclientException($e->getMessage());
            }
        }
        
        
        /**
         * Builds export configuration.
         * @return ExportConfiguration
         */
        public function getConfiguration() {
            $config = new ExportConfiguration();
            $config->setRenderMap(true);
            if (isset($this->exportRtfFormState['rtfScalebar'])) {
                $config->setRenderScalebar(true);
            }
            if (isset($this->exportRtfFormState['rtfOverview'])) {
                $config->setRenderKeymap(true);
            }
            
            if ($this->getConfig()->mapDimention == null || 
                $this->getConfig()->mapDimention ==''){
                     throw new CartoclientException('You need to set ExportRtf mapDimention ' .
                                'in exportRtf.ini');
            } else {
                $dimentions = explode('*', $this->getConfig()->mapDimention);
                // this way of doing does not launch a Fatal Error if there is nothing on the table index
                if (intval($dimentions[0]) == 0 ||
                    intval($dimentions[1]) == 0 ) {
                        throw new CartoclientException('Invalid mapDimention ' .
                                    'in exportRtf.ini');
                    }
                $config->setMapWidth(intval($dimentions[0]));  
                $config->setMapHeight(intval($dimentions[1]));  
            }
            return $config;
        }
        

        /**
         * @see GuiProvider::renderForm()
         */
        public function renderForm(Smarty $template) {
            $template->assign(array('exportRtf' => $this->drawUserForm()));
        }
            
        /**
        * Returns a String of the user form that will be injected in the cartoclient.tpl 
        * @return string The user form that will be injected in the cartoclient.tpl 
        */    
        protected function drawUserForm() {
            $rtfRoles = $this->getConfig()->allowedRoles;
            if (!SecurityManager::getInstance()->hasRole($rtfRoles))
                return 'not allowed to export';

            $activatedBlocks = explode(',',$this->getConfig()->activatedBlocks);
            $this->smarty = new Smarty_Plugin($this->getCartoclient(), $this);
            foreach ($this->optionalInputs as $input) {
                $inputName = 'rtf' . ucfirst($input);
                if (in_array($input, $activatedBlocks)) {
                    $this->smarty->assign(
                        array($inputName => true));
                } else {
                    $this->smarty->assign(array($inputName => false));
                }
            } 

            $this->addedValues = explode(',', $this->getConfig()->optionalValues);
            $optionalInputs = '';
            foreach ($this->addedValues as $optional) {
                if (trim($optional) != '') {
                    $optionalInputs .= '<input type="hidden" id="optRtf'.$optional.
                    '" name="optRtf'.$optional.'"/> ';
                }
            }
            $this->smarty->assign(array('rtfOptionalValues' => $optionalInputs));
            return $this->smarty->fetch('form.tpl');
        }
           
        /**
         * Returns an RTF string containing the map and other choosen data
         * @return string  An RTF string containing the map and other choosen data
         */
        private function render_rtf(){
            $RTFsmarty = new Smarty_Plugin($this->getCartoclient(), $this);
            $RTFsmarty->left_delimiter = '[[[';
            $RTFsmarty->right_delimiter = ']]]';
            $mapResult = $this->getExportResult($this->getConfiguration());
            // we assign the optional value
            foreach ($this->addedInputs as $key => $values) {
                $RTFsmarty->assign(array(strtoupper(str_replace('optRtf','',$key)) => $values));
            }
            if (isset($this->exportRtfFormState['rtfQueryResult'])){
                                 $RTFsmarty->assign(array(
                                    'QUERYRESULTS' => $this->fetchRtfQueryResult()
                                    ));
            }
            
            if ($mapResult->imagesResult->mainmap->isDrawn){
                $RTFsmarty->assign(array (
                    'MAP' => $this->fetchRtfImage($mapResult->imagesResult->mainmap->path)
                     ));
            } else {
                $RTFsmarty->assign(array('MAP' => 'NO MAP'));
            }
            
            if (isset($this->exportRtfFormState['rtfScalebar'])){
                 $RTFsmarty->assign(array (
                        'SCALEBAR' => $this->fetchRtfImage($mapResult->imagesResult->scalebar->path)
                         ));
            } 
           
            if (isset($this->exportRtfFormState['rtfOverview'])){
                     $RTFsmarty->assign(array (
                        'KEYMAP' => $this->fetchRtfImage($mapResult->imagesResult->keymap->path)
                         ));
            }
            
            if (isset($this->exportRtfFormState['rtfTitle'])){
                $RTFsmarty->assign(array(
                    'TITLE' => $this->exportRtfFormState['rtfTitle']
                    ));
              }
            return $RTFsmarty->fetch('exportRtf.rtf');
        }

        /**
         * Returns an RTF string containing the query result in form of tables
         * @return string An RTF string containing the query result in form of tables
         */
        protected function fetchRtfQueryResult() {
            $resultSmarty = new Smarty_Plugin($this->getCartoclient(), $this);
            $resultSmarty->left_delimiter = '[[[';
            $resultSmarty->right_delimiter = ']]]';
            $tables = $this->cartoclient->getPluginManager()->tables;
            $tableGroups = $tables->getTableGroups();
            if (empty($tableGroups)){
                $resultSmarty->assign(array('tables' => array()));
            } else {
             foreach ($tableGroups as $tableGroup) {
                if (!empty($tableGroup->groupTitle))
                        $tableGroup->groupTitle = PageCode::encodeUtfToRtf(Encoder::encode($tableGroup->groupTitle));            
                    if (empty($tableGroup->tables)) {
                        continue;
                    }            
                    foreach ($tableGroup->tables as $table) {
                        if (!empty($table->tableTitle))
                            $table->tableTitle = PageCode::encodeUtfToRtf(Encoder::encode($table->tableTitle));
                        foreach ($table->columnTitles as $key => $columnTitle) {
                            $table->columnTitles[$key] = PageCode::encodeUtfToRtf(Encoder::encode($columnTitle));                          
                        }
                        if ($table->numRows == 0) {
                            continue;
                        }    
                        foreach ($table->rows as $row) {        
                            $row->rowId = PageCode::encodeUtfToRtf(Encoder::encode($row->rowId));
                            if (!empty($row->cells[1])) {
                                $row->cells[1] = PageCode::encodeUtfToRtf(Encoder::encode($row->cells[1]));
                            }
                        }
                    }
                }
                $resultSmarty->assign(array('tables' => $tableGroups));
            }
            return $resultSmarty->fetch('results.tpl');
        }
            
         
        /**
         * Returns an RTF string containing an RTF compatible image
         * GIF is not supported for Open Office
         * @return string An RTF string containing the query result in form of tables
         */
        protected function fetchRtfImage($image_path) {
            $imageSmarty = new Smarty_Plugin($this->getCartoclient(), $this);
            $imageSmarty->left_delimiter = '[[[';
            $imageSmarty->right_delimiter = ']]]';
            $lib='';
            $extention = strtolower(substr($image_path, strrpos($image_path, '.')));
            switch ($extention) {
                case '.jpg':
                    $lib = 'jpeg';
                break;
                
                case '.jpeg':
                    $lib = 'jpeg';
                break;
                
                case '.png':
                    $lib = 'png';
                break;
                
                case '.gif':
                // This is the way Word does it : it saves it as a bin and opens it with libpng
                    $lib = 'png';
                break;
                        
                default:
                   throw new CartoclientException("image format not supported");
                break;
            }
            $imageSmarty->assign(array('IMG' => $this->convert_to_rtf_image($image_path,$lib),
                'RAND'=> rand(0, 1000),
                'LIB'=> $lib));

            return $imageSmarty->fetch('img.tpl');
        }
        

        /**
        * Returns the absolute URL of $gfx, using the ResourceHandler
        * @param string
        * @return string
        */
        protected function getGfxPath($gfx) {
            $resourceHandler = $this->cartoclient->getResourceHandler();
            $url = $resourceHandler->getPathOrAbsoluteUrl($gfx, false);
            return ResourceHandler::convertXhtml($url, true);
        }
            
        /**
         * Returns an hexadecimal representation of an image
         * @return string An hexadecimal representation of an image
         */
        public function convert_to_rtf_image($img_path, $imgType) {
            if ($imgType == 'jpeg' || $imgType == 'png'){
                $jpg_file = fopen($img_path,"rb");       
                $jpg = fread($jpg_file,filesize($img_path));
                $jpg = bin2hex($jpg);
                return $jpg;
            }
            //TODO handle other image types
            return '';
        }
        
        /**
        * @return Bbox bbox from last session-saved MapResult.
        */
        protected function getLastBbox() {
            $mapResult = $this->getLastMapResult();

            if (is_null($mapResult)){
            return new Bbox;
        }
            return $mapResult->locationResult->bbox;
        }
        
        /**
         * function kept for future developments
         *
         */
         protected function getLastMapImage() {
             if ($this->getLastMapResult()->imagesResult->mainmap->isDrawn){
                 return $this->getLastMapResult()->imagesResult->mainmap->path;
             }
             return 'NO MAP';
         }
    }
?>