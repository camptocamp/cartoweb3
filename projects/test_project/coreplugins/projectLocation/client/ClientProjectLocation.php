<?php
/**
 * @package Tests
 * @version $Id$
 */

/**
 * Client part of Location plugin, project testproject
 * @package Tests
 */
class ClientProjectLocation extends ClientLocation {

    private $projectResult;

    public function buildMapRequest($mapRequest) {

        parent::buildMapRequest($mapRequest);
        
        $projectRequest = new ProjectLocationRequest();
        $projectRequest->locationRequest = $mapRequest->locationRequest;
        $projectRequest->projectRequest = "my message";
        $mapRequest->locationRequest = $projectRequest;
    }

    public function initializeResult($locationResult) {
        if (isset($locationResult->locationResult)) {
            parent::initializeResult($locationResult->locationResult);
        }
        
        $this->projectResult = $locationResult->projectResult;
    }

    public function renderForm(Smarty $template) {
    
        $template->assign('projectmessage', $this->projectResult);
        parent::renderForm($template);
    }

    public function replacePlugin() {
        return 'location';
    }
}

?>
