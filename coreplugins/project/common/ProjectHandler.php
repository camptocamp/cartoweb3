<?php
/**
 * @package Common
 * @version $Id$
 */

/**
 * Handler for specific project files
 * @package Common
 */
class ProjectHandler {

    const PROJECT_ENV_VAR = 'CW3_PROJECT';
    const PROJECT_DIR = 'projects';

    static function getProjectName () {
        if (array_key_exists(self::PROJECT_ENV_VAR, $_ENV))
            return $_ENV[self::PROJECT_ENV_VAR];
                
        if (array_key_exists('REDIRECT_' . self::PROJECT_ENV_VAR, $_ENV))
            return $_ENV['REDIRECT_' . self::PROJECT_ENV_VAR];
        
        return NULL;
    }

    static function isProjectFile ($rootPath, $filePath, $file = '') {
    
        $projectName = self::getProjectName();
        if (!$projectName)
            return false;
        
        $path = self::PROJECT_DIR . '/' . $projectName . '/' . $filePath;

        if (!file_exists($rootPath . $path . $file))
            return false;
            
        return true;
    }

    static function getPath ($rootPath, $filePath, $file = '') {
        
        if (self::isProjectFile($rootPath, $filePath, $file)) {
            return self::PROJECT_DIR . '/' . self::getProjectName() . '/' . $filePath;
        } else {
            return $filePath;
        }
    } 
    
    static function getWebPath ($rootPath, $filePath, $file = '') {
        
        if (self::isProjectFile($rootPath, 'htdocs/' . $filePath, $file)) {
            return self::getProjectName() . '/' . $filePath;
        } else {
            return $filePath;
        }
    } 

}
?>