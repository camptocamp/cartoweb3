<?php
/**
 * Class and function needed to handle projects on client
 * @package Client
 * @version $Id$
 */

/**
 * Project handler
 */
require_once(CARTOCLIENT_HOME . 'common/ProjectHandler.php');

/**
 * Project handler for the client
 * @package Client
 */
class ClientProjectHandler extends ProjectHandler {

    /**
     * Environment variable which contains project name
     */
    const PROJECT_ENV_VAR = 'CW3_PROJECT';

    /**
     * @see ProjectHandler::getRootPath()
     */
    function getRootPath() {
        return CARTOCLIENT_HOME;
    }
    
    /**
     * Returns project name
     *
     * Tries to find project name in:
     * - Root directory, file current_project.txt
     * - $_ENV, variable CW3_PROJECT
     * - $_SERVER, variable CW3_PROJECT
     * - $_SERVER, variable REDIRECT_CW3_PROJECT (CGI redirect)
     * @return string project name
     */
    function getProjectName () {
        $projectFileName = CARTOCLIENT_HOME . 'current_project.txt';
        if (is_readable($projectFileName))
            return rtrim(file_get_contents($projectFileName));
        
        if (array_key_exists(self::PROJECT_ENV_VAR, $_ENV))
            return $_ENV[self::PROJECT_ENV_VAR];

        if (array_key_exists(self::PROJECT_ENV_VAR, $_SERVER))
            return $_SERVER[self::PROJECT_ENV_VAR];
                
        if (array_key_exists('REDIRECT_' . self::PROJECT_ENV_VAR, $_SERVER))
            return $_SERVER['REDIRECT_' . self::PROJECT_ENV_VAR];
        
        return NULL;
    }

}

/**
 * Smarty block function for resources
 *
 * Transforms {r type=css plugin=myplugin}toto.css{/r} to 
 * myplugin/css/toto.css or currentproject/myplugin/css/toto.css .
 * @package Client
 * @param array block parameters
 * @param string block text
 * @param Smarty Smarty engine
 * @return string resource path
 */
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

    // FIXME: performance hit: a new object is created on every instanciation
    //  do another way !! 
    $projectHandler = new ClientProjectHandler();
    $text = $projectHandler->getWebPath($text);

    return $text;
}

?>
