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
     * @var Logger
     */
    private $log;

    /**
     * Used for caching the project name.
     * @var string
     */
    private $projectName = false;

    /**
     * Environment variable which contains project name
     */
    const PROJECT_ENV_VAR = 'CW3_PROJECT';

    /**
     * Request name which contains the project name
     */
    const PROJECT_REQUEST = 'project';
    
    /**
     * Constructor
     */
    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

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
     * - GET variable 'project'
     * - Root directory, file current_project.txt
     * - $_ENV, variable CW3_PROJECT
     * - $_SERVER, variable CW3_PROJECT
     * - $_SERVER, variable REDIRECT_CW3_PROJECT (CGI redirect)
     * @return string project name
     */
    function getProjectName() {
        if ($this->projectName === false) {
            $projectFileName = CARTOCLIENT_HOME . 'current_project.txt';
            
            if (array_key_exists(self::PROJECT_REQUEST, $_REQUEST))
                $this->projectName = $_REQUEST[self::PROJECT_REQUEST];

            else if (is_readable($projectFileName))
                $this->projectName = rtrim(file_get_contents($projectFileName));

            else if (array_key_exists(self::PROJECT_ENV_VAR, $_ENV))
                $this->projectName = $_ENV[self::PROJECT_ENV_VAR];

            else if (array_key_exists(self::PROJECT_ENV_VAR, $_SERVER))
                $this->projectName = $_SERVER[self::PROJECT_ENV_VAR];

            else if (array_key_exists('REDIRECT_' . self::PROJECT_ENV_VAR, $_SERVER))
                $this->projectName = $_SERVER['REDIRECT_' . self::PROJECT_ENV_VAR];

            else $this->projectName = NULL;
            
            $this->log->debug("current project is " . $this->projectName);
        }
        return $this->projectName;
    }

}

?>
