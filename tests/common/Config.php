<?php
/**
 *
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
 * @package Tests
 * @version $Id$
 */

/**
 * Configuration for test cases
 *
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
class common_Config {

    private static $instance;

    private $ini_array;

    /** 
     * Constructor
     */
    function __construct() {
     
        $ini_file = CARTOCOMMON_HOME . '/tests/test.ini';
        if (!is_readable($ini_file)) {
            $this->ini_array = array();
            return;   
        }
            
        $this->ini_array = parse_ini_file($ini_file);
    }

    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new common_Config();
        }
        return self::$instance;
    }

    function __get($nm) {
        if (isset($this->ini_array[$nm])) {
            $r = $this->ini_array[$nm];
            return $r;
        } else {
            return NULL;
        }
    }
}
?>