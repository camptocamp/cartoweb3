<?
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * @package CorePlugins
 */
class ServerProjectLocation extends ServerLocation {

    function useExtendedResult() {
        return true;
    }

    function useExtendedRequest() {
        return true;
    }

    function handleCorePlugin($requ) {

        $projectResult = new ProjectLocationResult();
        if (isset($requ->locationRequest)) {
            $projectResult->locationResult = 
                parent::handleCorePlugin($requ->locationRequest);
        }
        $projectResult->projectResult = str_rot13($requ->projectRequest);
        
        return $projectResult;
    }

    public function replacePlugin() {
        return 'location';
    }
}

?>
