<?php
/**
 * @package Common
 * @version $Id$
 */

/**
 * Project handler
 */
require_once(CARTOCLIENT_HOME . 'common/ProjectHandler.php');

/**
 * Project handler for the client
 *
 * Project name is know by environment variable CW3_PROJECT.
 * @package Common
 */
class ClientProjectHandler extends ProjectHandler {

    const PROJECT_ENV_VAR = 'CW3_PROJECT';

    function getProjectName () {
        if (array_key_exists(self::PROJECT_ENV_VAR, $_ENV))
            return $_ENV[self::PROJECT_ENV_VAR];
                
        if (array_key_exists('REDIRECT_' . self::PROJECT_ENV_VAR, $_ENV))
            return $_ENV['REDIRECT_' . self::PROJECT_ENV_VAR];
        
        return NULL;
    }

}

function smartyResource ($params, $text, &$smarty) {
    
    $text = stripslashes($text);
    
    if (isset($params['type'])) {
        $type = $params['type'];
        unset($params['type']);       
    }
    
    if (isset($params['plugin'])) {
        $plugin = $params['plugin'];
        unset($params['plugin']);        
    }

    if (isset($type)) {
        $text = $type . '/' . $text;
    }   
    if (isset($plugin)) {
        $text = $plugin . '/' . $text;
    }

    $projectHandler = new ClientProjectHandler();
    $projectName = $projectHandler->getProjectName(); 
    if ($projectName) {
        $text = $projectName . '/' . $text;
    }

    return $text;
}

?>