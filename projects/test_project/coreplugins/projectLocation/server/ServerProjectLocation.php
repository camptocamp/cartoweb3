<?
/**
 * @package Tests
 * @version $Id$
 */

/**
 * @package Tests
 */
class ServerProjectLocation extends ServerLocation {

    public function useExtendedResult() {
        return true;
    }

    public function useExtendedRequest() {
        return true;
    }

    /**
     * @see CoreProvider::handleCorePlugin()
     */
    public function handleCorePlugin($requ) {

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
