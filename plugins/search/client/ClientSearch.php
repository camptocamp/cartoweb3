<?php
/**
 * Client search
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
 * @copyright 2007 Camptocamp SA
 * 
 * @package Plugins
 * @version $Id$
 */ 

/**
 * Formats a response in order to send it back to browser.
 * Allows to use several formats (i.e. Smarty, JSON, etc.) for one application.  
 */
abstract class ResponseFormatter {
    
    /**
     * @var ClientSearch plugin
     */
    protected $plugin;
    
    /**
     * @param ClientSearch plugin
     */
    public function __construct(ClientSearch $plugin) {
        $this->plugin = $plugin;
    }
    
    /**
     * Generates Ajax response from a search result
     * @param SearchResult
     * @return string
     */
    abstract public function getResponse(SearchResult $result);
    
    /**
     * Creates a ResponseFormatter from a config structure
     * @param config structure
     * @return ResponseFormatter
     */
    static public function getFormatterFromConfig($config, $defaultValues,
                                                  ClientSearch $plugin) {
                
        return SearchUtils::getFromConfig('ResponseFormatter',
                                          $config, $defaultValues,
                                          $plugin);
    }    
}

/**
 * Formats a response using a Smarty template
 * @see ResponseFormatter
 */
class SmartyResponseFormatter extends ResponseFormatter {
    
    /**
     * @var Smarty template
     */
    public $template;
    
    /**
     * @see ResponseFormatter::getResponse()
     */
    public function getResponse(SearchResult $result) {
        
        $smarty = new Smarty_Plugin($this->plugin->getCartoclient(),
                                    $this->plugin);
                                    
        $smarty->assign('table', $result->table);
        return $smarty->fetch($this->template . '.tpl');
    }
}

/**
 * Formats a response in JSON
 * @see ResponseFormatter
 */
class JsonResponseFormatter extends ResponseFormatter {
    
    /**
     * JSON formatter not yet implemented
     * @see ResponseFormatter::getResponse()
     */
    public function getResponse(SearchResult $result) {
        
        return '**not implemented**';
    }
}

class SearchConfig {
    
    /**
     * @var ResultProvider NULL if search is performed on Cartoserver
     */
    public $provider;
    
    /**
     * @var ResponseFormatter
     */
    public $formatter;
    
    /**
     * True is search is performed on Cartoserver
     * @return boolean
     */
    public function isServer() {
        return is_null($this->provider);
    }
}

/**
 * Client search plugin
 */
class ClientSearch extends ClientPlugin
                   implements GuiProvider,
                              ServerCaller,
                              Ajaxable {
    
    /**
     * @var SearchConfig[] Search configurations
     */
    protected $configs; 

    /**
     * @var SearchRequest the request
     */
    protected $searchRequest;
    
    /**
     * @var SearchResult the result
     */
    protected $searchResult;
    
    /**
     * @see PluginBase::initialize()
     */
    public function initialize() {
        
        $configStruct =
            StructHandler::loadFromArray($this->getConfig()->getIniArray());
        
        $defaultValues = array();
        foreach ($configStruct as $var => $val) {
            
            if ($var != 'config') {
                $defaultValues[$var] = SearchUtils::getValue($var, $val);
            }
        }
        
        $this->configs = array();
        if (isset($configStruct->config)) {
            
            foreach($configStruct->config as $name => $config) {
                
                $newConfig = new SearchConfig();
                
                if (!isset($config->provider)) {
                    throw new CartoclientException("Search config $name has no provider");
                } 
                $newConfig->provider =
                    ResultProvider::getProviderFromConfig($config->provider,
                                                          $defaultValues, $this);
                
                if (!isset($config->formatter)) {
                    throw new CartoclientException("Search config $name has no formatter");
                }
                $newConfig->formatter =
                    ResponseFormatter::getFormatterFromConfig($config->formatter,
                                                              $defaultValues, $this);
                
                $this->configs[$name] = $newConfig;                
            }
        }
    }
    
    /**
     * Converts HTTP request to Search request
     * @param array HTTP request
     * @return SearchRequest
     */
    public function buildSearchRequest($request) {
             
        $searchRequest = new SearchRequest();
        
        foreach($request as $var => $val) {
            if (substr($var, 0, 7) == 'search_') {
                $name = substr($var, 7, strlen($var) - 7);
                
                if ($name == 'config') {
                    $searchRequest->$name = $val;
                } else {
                    $searchRequest->setParameter($name, $val);
                }
            }
        }
        return $searchRequest;
    }
    
    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {
        
        $action = $this->getHttpValue($request, 'ajaxActionRequest');
        if ($action == 'Search.DoIt') {
            $this->searchRequest = $this->buildSearchRequest($request);

            if (!isset($this->searchRequest->config) ||
                !array_key_exists($this->searchRequest->config, $this->configs)) {
                throw new CartoclientException("Empty config or config not found");
            }
        }
    }

    /**
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) { }

    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {
        
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);

        $template->assign('search_active', true);
        $template->assign('search', $smarty->fetch('search.tpl'));
    }   
    
    /**
     * @see ServerCaller::buildRequest()
     */
    public function buildRequest() {

        if (is_null($this->searchRequest)) {
            return NULL;
        }
        if ($this->configs[$this->searchRequest->config]->isServer()) {
            return $this->searchRequest;
        }
        return NULL;
    }

    /**
     * @see ServerCaller::initializeResult()
     */ 
    public function initializeResult($result) {

        if (is_null($this->searchRequest)) {
            return NULL;
        }
        $config = $this->configs[$this->searchRequest->config];
        if (!$config->isServer()) {
            $this->searchResult = $config->provider->getResult($this->searchRequest);
            return;
        }
        $this->searchResult = $result;
    }

    /**
     * @see ServerCaller::handleResult()
     */ 
    public function handleResult($outlineResult) { }

    /**
     * @see Ajaxable::ajaxGetPluginResponse()
     */ 
    public function ajaxGetPluginResponse(AjaxPluginResponse $ajaxPluginResponse) {

        if (is_null($this->searchRequest)) {
            return NULL;
        }
        $config = $this->configs[$this->searchRequest->config];
        $text = $config->formatter->getResponse($this->searchResult);
        $ajaxPluginResponse->addHtmlCode($this->searchRequest->config, $text);
    }

    /**
     * @see Ajaxable::ajaxHandleAction()
     */
    public function ajaxHandleAction($actionName, PluginEnabler $pluginEnabler) {

        if ($actionName == 'Search.DoIt') {
            $pluginEnabler->disableCoreplugins();
            $pluginEnabler->enablePlugin('search');
        }
    }
}

?>
