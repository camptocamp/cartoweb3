<?php
/**
 * General code and objects used by the client and the server 
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
 * Base exception for cartoweb
 * @package Common
 */
class CartowebException extends Exception {
    
    /**
     * Transforms a backtrace structure into a readable html string
     * 
     * Adapted from diz at ysagoon dot com
     * @return string
     */
    private function backtrace() {
        $output = "Backtrace:\n";
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
                    $a = substr($a, 0, 64).((strlen($a) > 64) ? '...' : '');
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
            $output .= "\nfile: {$bt['line']} - {$bt['file']}\n";
            $bt['class'] = isset($bt['class']) ?  $bt['class'] : '';
            $bt['type'] = isset($bt['type']) ?  $bt['type'] : '';
            $output .= "call: {$bt['class']}{$bt['type']}{$bt['function']}($args)\n";
        }
        $output .= "\n";
        return $output;
    }

    /**
     * Constructor
     *
     * Adds backtrace data to current exception message.
     * @param string exception message
     */
    public function __construct($message) {
        $message .= "\n" . $this->backtrace();
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
 * Class containing general common code shared by client and server.
 * For example, it handles common initialization.
 * 
 * @package Common
 */
class Common {
 
    /**
     * Sets the include path, to contain include directory.
     */
    private static function setIncludePath() {
        set_include_path(get_include_path() . PATH_SEPARATOR . 
                 CARTOCOMMON_HOME . 'include/'. PATH_SEPARATOR .
                 CARTOCOMMON_HOME . 'include/pear');
    }

    /**
     * Initialization of the "advanced php debugger" stuff.
     * 
     * @param boolean true if called from client context.
     */
    private static function initializeApd($client) {
        $kind = $client ? 'client' : 'server';
        
        if (file_exists(CARTOCOMMON_HOME . "$kind/trace.apd")) {
            apd_set_pprof_trace();
        }
    }
 
    /**
     * This function initializes cartoweb in the very beginning. It sets the
     * include path, for instance.
     * 
     * @param array array of argument values: 'client' true if client, 'apd' set
     * if apd has to be initialized.
     */
    public static function preInitializeCartoweb($args) {
    
        $client = isset($args['client']) && $args['client']; 
        self::setIncludePath($client);
        if (isset($args['apd']))
            self::initializeApd($client);
    }
    
    /**
     * Sets ini directives useful during development
     */
    private static function setDeveloperIniConfig() {
        ini_set('assert.bail', '1');
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', '1');
    }
    
    /**
     * Ananlyzes errors, and eventually ignores some.
     * 
     * @return boolean true if the given error is to be ignored
     */
    private static function isErrorIgnored($errno, $errstr, $errfile, 
                                           $errline) {
        $errfile = Utils::pathToUnix($errfile);

        // ignore mapserver errors
        if (strpos($errstr, '[MapServer Error]') === 0 ||
            strpos($errstr, 'getLayerByName') === 0 ||
            // mapfile open error are not fatal, so that we stop on 
            //  the more descriptive mapserver message
            strpos($errstr, 'Failed to open map file') === 0)
            return true;
        // ignore log4php notices
        if (strpos($errfile, 'include/log4php/' ) !== false  && 
            $errno | E_NOTICE)
            return true;
        // ignore smarty notices
        if (strpos($errfile, '/templates_c/') !== false && 
            $errno | E_NOTICE)
            return true;
        // ignores the session started error in Pear Auth
        if (strpos($errfile, 'Auth.php') !== false && $errline == 247)
            return true;
        return false;
    }
    
    /**
     * Error handler for cartoweb.
     */
    public static function cartowebErrorHandler($errno, $errstr, $errfile, 
                                                $errline) {
        $log =& LoggerManager::getLogger(__METHOD__);

        if (self::isErrorIgnored($errno, $errstr, $errfile, $errline))
            return;
    
        $log->warn(sprintf("Error in php: errno: %i\n errstr: %s\n" .
                           " errfile: %s (line %i)", 
                           $errno, $errstr, $errfile, $errline));
        throw new CartocommonException(
            "Error [$errno, $errstr, $errfile, $errline]");
    }

    /**
     * Perform various cartoweb initializations.
     * @param Config
     */
    public static function initializeCartoweb($config) {
    
        if ($config->developerIniConfig) {  
            self::setDeveloperIniConfig();
        }

        set_error_handler(array('Common', 'cartowebErrorHandler'), E_ALL);
    }

    /**
     * Restores the php context to what it was before calling
     * InitializeCartoweb()
     */
    public function shutdownCartoweb($config) {

        if ($config->developerIniConfig) {  
            // TODO
            //unsetDeveloperIniConfig();
        }
        restore_error_handler();
    }
}

/**
 * Internationalization methods for automatic strings retrieving
 *
 * Using these methods only tells to gettext's strings retriever (xgettext)
 * that the string must be added to PO template. It does nothing in runtime.
 * @package Common
 */
class I18nNoop {
    
    /**
     * @param string
     * @return string
     */
    static public function gt($text) {
        return $text;
    }
    
    /**
     * @param string
     * @param string
     * @param int
     * @return string
     */
    static public function ngt($text, $plural, $count) {
        return $text;
    }
}
?>
