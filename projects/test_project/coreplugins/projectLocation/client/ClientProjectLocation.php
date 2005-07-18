<?php
/**
 * @package Tests
 * @version $Id$
 */

/**
 * Client part of Location plugin, project test_project
 * @package Tests
 */
class ClientProjectLocation extends ClientLocation {

    private $projectResult;

    public function buildRequest() {

        $locationRequest = parent::buildRequest();
        
        $projectRequest = new ProjectLocationRequest();
        $projectRequest->locationRequest = $locationRequest;
        $projectRequest->projectRequest = "my message";
        return $projectRequest;
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
