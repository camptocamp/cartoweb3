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

    function showForm($cartoclient) {

        $cartoForm = $cartoclient->getCartoForm();
        $clientSession = $cartoclient->getClientSession();
        $smarty = $this->smarty;
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