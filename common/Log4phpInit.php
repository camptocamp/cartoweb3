<?php 
/**
 * Initializes the log4php library, uses a LoggerConfiguration which changes
 * the file path, to that logs are written to CARTOCLIENT_HOME/log
 * @package Common
 * @version $Id$
 */

/**
 * Initializes the log4php library
 * @param boolean true if initializing the cartoclient (false for the cartoserver)
 */
function initializeLog4php($isClient) {

    if (defined('LOG4PHP_CONFIGURATION'))
        return;

    if ($isClient) {
        define('LOG4PHP_CONFIGURATION', CARTOCLIENT_HOME . 
            'client_conf/cartoclientLogger.properties');
    } else {
        define('LOG4PHP_CONFIGURATION', CARTOSERVER_HOME . 
            'server_conf/cartoserverLogger.properties');
    }

    define('LOG4PHP_CONFIGURATOR_CLASS', 'LoggerPropertyOverriderConfigurator');
    define('LOG4PHP_DEFAULT_INIT_OVERRIDE', true);

    require_once ('log4php/LoggerManager.php');
    require_once ('log4php/LoggerPropertyConfigurator.php');

    class LoggerPropertyOverriderConfigurator extends LoggerPropertyConfigurator {

        function configure($url = '') {
            $configurator = new LoggerPropertyOverriderConfigurator();
            $repository = & LoggerManager :: getLoggerRepository();
            return $configurator->doConfigure($url, $repository);
        }

        function doConfigureProperties($properties, & $hierarchy) {

            // TODO: should search for file appenders instead
            define('FILE_APPENDER', 'log4php.appender.A1.file');

            if (isset ($properties[FILE_APPENDER])) {
                $logFilename = $properties[FILE_APPENDER];

                $logFilename = str_replace('LOG_HOME', CARTOCOMMON_HOME.'log', $logFilename);
                $properties[FILE_APPENDER] = $logFilename;
            }
            parent :: doConfigureProperties($properties, $hierarchy);
        }
    }

    LoggerManagerDefaultInit();
}

?>