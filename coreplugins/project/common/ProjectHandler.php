<?php
/**
 * @package Common
 * @version $Id$
 */

/**
 * Handler for specific project files
 * @package Common
 */
abstract class ProjectHandler {

    const PROJECT_DIR = 'projects';

    abstract function getProjectName ();

    function isProjectFile ($rootPath, $filePath, $file = '') {
    
        $projectName = $this->getProjectName();
        if (!$projectName)
            return false;
        
        $path = self::PROJECT_DIR . '/' . $projectName . '/' . $filePath;

        if (!file_exists($rootPath . $path . $file))
            return false;
            
        return true;
    }

    function getPath ($rootPath, $filePath, $file = '') {
        
        if (self::isProjectFile($rootPath, $filePath, $file)) {
            return self::PROJECT_DIR . '/' . $this->getProjectName() . '/' . $filePath;
        } else {
            return $filePath;
        }
    } 
    
    function getWebPath ($rootPath, $filePath, $file = '') {
        
        if (self::isProjectFile($rootPath, 'htdocs/' . $filePath, $file)) {
            return $this->getProjectName() . '/' . $filePath;
        } else {
            return $filePath;
        }
    } 

}
?>