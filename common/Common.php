<?php
/**
 * General code and objects used by the client and the server 
 * @package Common
 * @version $Id$
 */

/**
 * Base exception for cartoweb
 * @package Common
 */
class CartowebException extends Exception {
    
    /**
     * Transforms a backtrace structure into a readable html string
     * 
     * Adapted from diz at ysagoon dot com
     */
    function backtrace() {
        $output = "<div style='text-align: left; font-family:monospace;'>\n";
        $output .= "<b>Backtrace:</b><br />";
        $backtrace = $this->getTrace();

        foreach ($backtrace as $bt) {
            $args = '';
            if (isset($bt['args']))
            foreach ($bt['args'] as $a) {
                if (!empty($args)) {
                        $args .= ', ';
                }
                switch (gettype($a)) {
                case 'integer':
                case 'double':
                    $args .= $a;
                    break;
                case 'string':
                    $a = htmlspecialchars(substr($a, 0, 64)).((strlen($a) > 64) ? '...' : '');
                    $args .= "\"$a\"";
                    break;
                case 'array':
                    $args .= 'Array('.count($a).')';
                    break;
                case 'object':
                    $args .= 'Object('.get_class($a).')';
                    break;
                case 'resource':
                    $args .= 'Resource('.strstr($a, '#').')';
                    break;
                case 'boolean':
                    $args .= $a ? 'True' : 'False';
                    break;
                case 'NULL':
                    $args .= 'Null';
                    break;
                default:
                    $args .= 'Unknown';
                }
            }
            $output .= "<br />";
            $output .= "<b>file:</b> {$bt['line']} - {$bt['file']}<br/>";
            $bt['class'] = isset($bt['class']) ?  $bt['class'] : '';
            $bt['type'] = isset($bt['type']) ?  $bt['type'] : '';
            $output .= "<b>call:</b>{$bt['class']}{$bt['type']}{$bt['function']}($args)<br />";
        }
        $output .= "</div>\n";
        return $output;
    }

    function __construct($message) {
        $message .= $this->backtrace();
        parent::__construct($message);
    }
}

/**
 * Exception for common classes
 * @package Common
 */
class CartocommonException extends CartowebException {
}

/**
 * Sets ini directives useful during development
 */
function setDeveloperIniConfig() {
    ini_set('assert.bail', '1');
    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', '1');
}

function cartowebErrorHandler($errno, $errstr, $errfile, $errline)
{
    $log =& LoggerManager::getLogger(__METHOD__);

    // ignore mapserver errors
    if (strpos($errstr, '[MapServer Error]') === 0 ||
        strpos($errstr, 'getLayerByName') === 0)
        return;
    // ignore log4php notices
    if (strpos($errfile, 'include/log4php/' ) !== false  && $errno | E_NOTICE)
        return;
    // ignore smarty notices
    if (strpos($errfile, '/templates_c/') !== false && $errno | E_NOTICE)
        return;
    
    $log->warn(sprintf("Error in php: errno: %i\n errstr: %s\n errfile: %s (line %i)", 
                       $errno, $errstr, $errfile, $errline));
    throw new CartocommonException("Error [$errno, $errstr, $errfile, $errline]");
}

/**
 * Perform various cartoweb initializations.
 * @param Config
 */
function initializeCartoweb($config) {
    
    if ($config->developerIniConfig) {  
        setDeveloperIniConfig();
    }
    set_error_handler('cartowebErrorHandler', E_ALL);
}

?>
