<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * Client part of Location plugin, project testproject
 * @package CorePlugins
 */
class ClientProjectLocation extends ClientLocation {

    private $projectResult;

    function buildMapRequest($mapRequest) {

        parent::buildMapRequest($mapRequest);
        
        $projectRequest = new ProjectLocationRequest();
        $projectRequest->locationRequest = $mapRequest->locationRequest;
        $projectRequest->projectRequest = "my message";
        $mapRequest->locationRequest = $projectRequest;
    }

    function initializeResult($locationResult) {
        if (isset($locationResult->locationResult)) {
            parent::initializeResult($locationResult->locationResult);
        }
        
        $this->projectResult = $locationResult->projectResult;
    }

    function renderForm($template) {
    
        $template->assign('projectmessage', $this->projectResult);
        parent::renderForm($template);
    }

    public function replacePlugin() {
        return 'location';
    }
}

?>
