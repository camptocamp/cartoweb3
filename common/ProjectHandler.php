<?php
/**
 * Tools for projects management
 * @package Common
 * @version $Id$
 */

/**
 * Handler for specific project files
 * @package Common
 */
abstract class ProjectHandler {

    const PROJECT_DIR = 'projects';

    /**
     * Map name without the project prefix
     * @var string
     */
    public $mapName;

    /**
     * Returns cartoserver or cartoclient root path.
     * @return string
     */
    abstract function getRootPath();
    
    /**
     * Returns the project name
     * @return string
     */
    abstract function getProjectName();
 
    /**
     * @return string
     */
    function getMapName() {
        return $this->mapName;
    }   
 
    /**
     * Finds out if a file exists in project
     * @param string path to file (without project specific path)
     * @param string optional file name
     * @return boolean
     */
    private function isProjectFile($filePath, $file = '') {
    
        $projectName = $this->getProjectName();
        if (!$projectName)
            return false;
        
        $path = self::PROJECT_DIR . '/' . $projectName . '/' . $filePath;

        if (!file_exists($this->getRootPath() . $path . $file))
            return false;
            
        return true;
    }

    /**
     * Returns path for a file, depending on projects
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
     * Returns relative Web path for a file, depending on projects
     * 
     * If file exists in current project, path to project file name is returned.
     * Otherwise, default file is returned.
     * @param string path to file (without project specific path)
     * @param string optional file name
     * @return string     
     */
    public function getWebPath($filePath, $file = '') {
        
        if (self::isProjectFile('htdocs/' . $filePath, $file)) {
            return $this->getProjectName() . '/' . $filePath;
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

        // it simply looks for directory name. Maybe a smarter approach could be used
        $projects = array('default');
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