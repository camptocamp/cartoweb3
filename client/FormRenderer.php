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

        $smarty->assign("mainmap_path", $cartoclient->pluginManager->images->imagesResult->mainmap->path);

		$smarty->assign('selected_tool', $clientSession->selectedTool);

        $smarty->assign('tools', array(
                            CartoForm::TOOL_ZOOMIN => 'Zoom in',
                            CartoForm::TOOL_ZOOMOUT => 'Zoom out',
                            CartoForm::TOOL_RECENTER => 'Recenter',
                            CartoForm::TOOL_QUERY => 'Query'
                            ));

        $smarty->assign('layers2', 'TOTO');

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