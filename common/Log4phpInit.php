<?php
/**
 * Initializes the log4php library, uses a LoggerConfiguration which changes
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
 * the file path, to that logs are written to CARTOWEB_HOME/log
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
        define('LOG4PHP_CONFIGURATION', CARTOWEB_HOME . 
            'client_conf/cartoclientLogger.properties');
    } else {
        define('LOG4PHP_CONFIGURATION', CARTOWEB_HOME . 
            'server_conf/cartoserverLogger.properties');
    }

    define('LOG4PHP_CONFIGURATOR_CLASS', 'LoggerPropertyOverriderConfigurator');
    define('LOG4PHP_DEFAULT_INIT_OVERRIDE', true);

    require_once ('log4php/LoggerManager.php');
    require_once ('log4php/LoggerPropertyConfigurator.php');

    class LoggerPropertyOverriderConfigurator extends LoggerPropertyConfigurator {

        public function configure($url = '') {
            $configurator = new LoggerPropertyOverriderConfigurator();
            $repository = & LoggerManager :: getLoggerRepository();
            return $configurator->doConfigure($url, $repository);
        }

        public function doConfigureProperties($properties, & $hierarchy) {

            // TODO: should search for file appenders instead
            define('FILE_APPENDER', 'log4php.appender.A1.file');

            if (isset ($properties[FILE_APPENDER])) {
                $logFilename = $properties[FILE_APPENDER];

                $logFilename = str_replace('LOG_HOME', CARTOWEB_HOME.'log', $logFilename);
                $properties[FILE_APPENDER] = $logFilename;
            }
            parent :: doConfigureProperties($properties, $hierarchy);
        }
    }

    LoggerManagerDefaultInit();
}

?>
