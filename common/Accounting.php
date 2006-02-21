<?php
/**
 * Accounting management
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
 * @copyright 2006 Camptocamp SA
 * @package Common
 * @version $Id$
 */

/**
 * Abstract base class for accounting management.
 * @package Common
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com>
 */
abstract class Accounting {

    /**
     * Hash of accounting messages. Indexed by labels
     * @var array
     */
    private $accountings = array();

    /**
     * Used to track if accounting plugin is loaded
     * @var boolean
     */
    private $pluginLoaded;

    /**
     * Singleton
     * @var Accounting
     */
    private static $instance;

    /**
     * True when a cache hit on server occured, to prevent error message of 
     *  accounting plugin not loaeded
     * @var boolean
     */
    private $cacheHit = false;
    
    /**
     * True when accounting is active. Used to shut down accounting temporarily
     * @var boolean
     */
    private $active = true;

    /**
     * Constructor. Can't be called directly, use getInstance() instead.
     */
    private function __construct() {
        self::$instance = $this;
    }

    /**
     * Returns the instance of this class.
     */
    public static function getInstance() {
        if (is_null(self::$instance)) {
            if (class_exists('ClientAccountingImpl')) {
                self::$instance = new ClientAccountingImpl();
            } else if (class_exists('ServerAccountingImpl')) {
                self::$instance = new ServerAccountingImpl();
            } else {
                throw new CartocommonException('invalid accounting class');
            }
        }
        return self::$instance;
    }

    /**
     * Records an accounting message
     * 
     * @param string the label to identify the accounting information
     * @param string accounting data
     */
    public function account($label, $value) {

        if (!$this->isActive()) {
            return;
        }

        if (isset($this->accountings[$label]) && 
            // XXX strange behaviour with this label
            $label != 'general.request_id') {
            throw new CartocommonException("Duplicate accounting label $label");
        }
        
        $this->accountings[$label] = $value;
    }

    /**
     * Needs to be called by accounting plugin, to detect case when the
     * accounting plugin was not enabled and accouting is turned on.
     */
    public function pluginLoaded() {
        $this->pluginLoaded = true;
    }
    
    /**
     * Returns type of Accoungint ('client' or 'server')
     * @return string
     */
    abstract protected function getKind();

    /**
     * Returns the mapId of the current map.
     * @return string mapId
     */
    abstract protected function getMapId();
    
    /**
     * Returns the client or server configuration object
     * @return Config
     */
    abstract protected function getConfig();

    /**
     * Saves an accounting packet (merge of all accounting messages) to file
     *  storage
     * @param accoutingPacket string
     */
    private function saveFile($accountingPacket) {

        $accountingPath = CARTOWEB_HOME . 'www-data/accounting/';
        $accountingPath .= $this->getMapId() . '/';
       
        if (!is_dir($accountingPath)) {
            Utils::makeDirectoryWithPerms($accountingPath, 
                $this->getConfig()->webWritablePath);
        }
       
        $accountingFile = $accountingPath . $this->getKind() . '_accounting.log';
        $fp = fopen($accountingFile, 'a');
        if (!flock($fp, LOCK_EX)) {
            throw new CartocommonException('Failed to lock accounting file');
        }
        fwrite($fp, $accountingPacket . "\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    /**
     * Saves an accounting packet (merge of all accounting messages) to database
     *  storage
     * @param accoutingPacket string
     */
    private function saveDb($accountingPacket) {

        require_once 'DB.php';
        $dsn = $this->getConfig()->accountingDsn;
        $options = array();
        $db =& DB::connect($dsn, $options);
        Utils::checkDbError($db);        

        $accountingPacket = addslashes($accountingPacket);
        
        // Table schema:
        // CREATE TABLE cw_accounting (date timestamp, info text);
        
        $sql = "INSERT INTO cw_accounting (date, info) VALUES " . 
               "(now(), '$accountingPacket')";
        $res = $db->query($sql);
        Utils::checkDbError($res);
    }

    /**
     * Sets whether accounting is active or not. Can be used to disable 
     * accounting temporarily
     * @param active boolean
     */
    public function setActive($active) {

        $this->active = $active;
    }

    /**
     * Returns true if accounting is active and enabled
     * @return boolean
     */
    public function isActive() {

        return $this->getConfig()->accountingOn && $this->active;
    }
    
    /**
     * Tells accounting that a cache hit occured. This is used to prevent
     * false error message in some situations.
     */
    public function setCacheHit() {

        $this->cacheHit = true;
    }
    
    /**
     * Saves all accounting messages to persistent storage. This should be called
     * only once per request (client or server).
     */
    public function save() {
        
        if (!$this->isActive()) {
            return;
        }

        if (!$this->pluginLoaded && !$this->cacheHit) {
            throw new CartocommonException(sprintf('Accounting is turned on, ' .
                    'but Accounting plugin is not loaded on %s. You must load ' .
                    'accounting plugin to enable accounting.', 
                    $this->getKind()));
        }
        
        $accountings = array();
        foreach($this->accountings as $label => $value) {
            $value = str_replace('"', '_', $value);
            $accountings[] = sprintf('%s="%s"', $label, $value);
        }
        $accountingPacket = implode(';', $accountings);

        if ($this->getConfig()->accountingStorage == 'db')
            $this->saveDb($accountingPacket);
        else
            $this->saveFile($accountingPacket);
    }
}

?>
