<?php
/**
 * Tools for projects management
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
 * @package Common
 * @version $Id$
 */

/**
 * Handler for specific project files
 * @package Common
 */
abstract class ProjectHandler {

    const PROJECT_DIR = 'projects';
    const DEFAULT_PROJECT = 'test_main';

    /**
     * Map name without the project prefix
     * @var string
     */
    public $mapName;

    /**
     * Returns cartoserver or cartoclient root path.
     * @return string
     */
    abstract public function getRootPath();
    
    /**
     * Returns the project name
     * @return string
     */
    abstract public function getProjectName();
 
    /**
     * @return string
     */
    public function getMapName() {
        return $this->mapName;
    }   
 
    /**
     * Finds out if a file or directory exists in project
     * @param string path to file of directory (without project specific path)
     * @param string optional file name (if none, the test will be done on the
     * direcory only)
     * @return boolean true if the file or directory belongs to a project.
     */
    public function isProjectFile($filePath, $file = '') {
    
        $projectName = $this->getProjectName();
        if (!$projectName)
            return false;
        
        $path = self::PROJECT_DIR . '/' . $projectName . '/' . $filePath;

        if ($file == '' && is_dir($this->getRootPath() . $path))
            return true;
        if (file_exists($this->getRootPath() . $path . $file))
            return true;
            
        return false;
    }

    /**
     * Returns path for a file, depending on projects
     * 
     * FIXME: it should be simplier to pass only a filename or directory, and
     * let the caller do a dirname() or basename() from the result.
     * 
     * If file exists in current project, path to project file name is returned.
     * Otherwise, default file is returned.
     * @param string path to file (without project specific path)
     * @param string optional file name
     * @return string     
     */
    public function getPath($filePath, $file = '') {
        
        if (self::isProjectFile($filePath, $file)) {
            return self::PROJECT_DIR . '/' . $this->getProjectName() . '/' . $filePath;
        } else {
            return $filePath;
        }
    } 

    /**
     * Returns a list of available projects.
     * 
     * @return array a string array of project names (identifiers)
     */
    public function getAvailableProjects() {

        // It simply looks for directory name. 
        // Maybe a smarter approach could be used
        $projects = array();
        $directory = $this->getRootPath() . self::PROJECT_DIR . '/';
        $d = dir($directory);
        while (false !== ($entry = $d->read())) {
            if (is_dir($directory . $entry) && $entry != '.'
                && $entry != '..' && $entry != 'CVS') {
                $projects[] = $entry;
            }
        }
        return $projects;
    }
    
}

?>
