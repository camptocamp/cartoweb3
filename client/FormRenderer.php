<?php

require_once('smarty/Smarty.class.php');

class Smarty_Cartoclient extends Smarty {

    function __construct($config) {
        parent::__construct();

        $this->template_dir = $config->basePath . 'templates/';
        $this->compile_dir = $config->basePath . 'templates_c/';
        $this->config_dir = $config->basePath . 'configs/';
        $this->cache_dir = $config->basePath . 'cache/';
        
        $this->caching = $config->smartyCaching;
        $this->compile_check = $config->smartyCompileCheck;
        $this->debugging = $config->smartyDebugging;
    }
}

class Smarty_CorePlugin extends Smarty_Cartoclient {

    function __construct(ClientConfig $config, ClientPlugin $plugin) {
        parent::__construct($config);
        
        $this->template_dir = $plugin->getBasePath() . 'templates/';
    }
}

// TODO: eventually create a class SmartyFormRender, and add a plugin mechanism in configuration
// file to choose the templating sytem to use, as a class which extends the abstract class
//  FormRenderer

class FormRenderer {
    private $log;
    private $cartoclient;

    function __construct($cartoclient) {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        $this->cartoclient = $cartoclient;

        $this->smarty = $this->getSmarty();
    }

    private function getSmarty() {
        $smarty = new Smarty_Cartoclient($this->cartoclient->getConfig());
        return $smarty;
    }

    /**
     * Taken from php manual. By anonymous.
     */
    private function glue_url($parsed) {
  
        if (! is_array($parsed)) return false;

        if (isset($parsed['scheme'])) {
            $sep = (strtolower($parsed['scheme']) == 'mailto' ? ':' : '://');
            $uri = $parsed['scheme'] . $sep;
        } else {
            $uri = '';
        }
 
        if (isset($parsed['pass'])) {
            $uri .= "$parsed[user]:$parsed[pass]@";
        } elseif (isset($parsed['user'])) {
            $uri .= "$parsed[user]@";
        }
 
        if (isset($parsed['host']))    $uri .= $parsed['host'];
        if (isset($parsed['port']))    $uri .= ":$parsed[port]";
        if (isset($parsed['path']))    $uri .= $parsed['path'];
        if (isset($parsed['query']))    $uri .= "?$parsed[query]";
        if (isset($parsed['fragment'])) $uri .= "#$parsed[fragment]";
 
        return $uri;
    }

    private function getImagePath($path) {
    
        $config = $this->cartoclient->getConfig();

        if (!@$config->cartoserverUrl)
            return $path;
        
        $parsedUrl = parse_url($config->cartoserverUrl);
        
        $parsedUrl['path'] = $path;

        if ($config->useReverseProxy) {
            x('todo_reverse_proxy');
            
            $proxyParsedUrl = parse_url($config->reverseProxy);
            // TODO: in some cases, we don't want to prefix images with host
            //   if the reverse proxy is on the same machine.
            $parsedUrl['host'] = $proxyParsedUrl['host']; 
            $parsedUrl['port'] = $proxyParsedUrl['port'];
            $parsedUrl['path'] = $proxyParsedUrl['path'] . $parsedUrl['path']; 
        }
    
        return $this->glue_url($parsedUrl);
    }

    function showForm($cartoclient) {

        $cartoForm = $cartoclient->getCartoForm();
        $clientSession = $cartoclient->getClientSession();
        $imagesRes = $cartoclient->getPluginManager()->images->imagesResult;

        $smarty = $this->smarty;

        $smarty->assign("mainmap_path", 
                    $this->getImagePath($imagesRes->mainmap->path));

        $plugins = $cartoclient->getPluginManager()->getPlugins();

        $tools = array();
        foreach ($plugins as $plugin) {
            if ($plugin instanceof ToolProvider) {
                $toolsDescription = $plugin->getTools();
                foreach($toolsDescription as $toolDescription) {
                    $tools[$toolDescription->id] = $toolDescription->label;
                }
            }
        }
        
        // FIXME: initial selected tool might be set in configuration
        
        if (empty($clientSession->selectedTool)) {
            $toolsIds = array_keys($tools);
            if (!empty($toolsIds))
                $clientSession->selectedTool = $toolsIds[0];
        }       
        $smarty->assign('selected_tool', $clientSession->selectedTool);
                
        $smarty->assign('tools', $tools);

        // ------------- string translations

        $smarty->assign('cartoclient_title', 'Cartoclient');

        // ------------- debug

        $smarty->assign('debug_request', var_export($_REQUEST, true));

        // Print problems : recursive structure
        //$smarty->assign('debug_cartoclient', var_export($cartoclient, true));

        // handle plugins

        $cartoclient->callPlugins('renderForm', $smarty);

        // TODO: plugins should be able to change the flow

        $smarty->display('cartoclient.tpl');
    }

    function showFailure($exception) {

        if ($exception instanceof SoapFault) {
            $message = $exception->faultstring;
        } else {
            $message = $exception->getMessage();
        }
        $smarty = $this->smarty;

        $smarty->assign('exception_class', get_class($exception));
        $smarty->assign('failure_message', $message);
        $smarty->display('failure.tpl');
    }
}
?>