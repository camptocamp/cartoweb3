<?php
/**
 * Class and function needed to handle projects on client
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * @copyright 2005 Camptocamp SA
 * @package Client
 * @version $Id$
 */

/**
 * Project handler
 */
require_once(CARTOWEB_HOME . 'common/ProjectHandler.php');

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
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
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
    public function getProjectName() {
        if ($this->projectName === false) {
            $projectFileName = CARTOWEB_HOME . 'current_project.txt';
            
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

            else $this->projectName = ProjectHandler::DEFAULT_PROJECT;
            
            $this->log->debug('current project is ' . $this->projectName);
        }
        return $this->projectName;
    }

}

?>
