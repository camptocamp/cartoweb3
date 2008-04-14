<?php
/**
 * Throttling plugin client classes
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
 *
 * @package Plugins
 * @version $Id$
 */

require_once "Throttling.php";

/**
 * Client Throttling
 * @package Plugins
 */
class ClientThrottling extends ClientPlugin implements GuiProvider {

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var WhiteList
     */
    protected $white;

    /**
     * @var BlackList
     */
    protected $black;

    /**
     * @var Array Array of Buffer
     */
    protected $buffers = array();

    /**
     * @var Boolean True if the plugin really block the request. If false, the
     * request is not blocked but the blacklist state is filled as well.
     */
    protected $blockAccess = true;


    /**
     * @see ClientPlugin::__construct()
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    /**
     * @see Sessionable::initializeConfig()
     */
    public function initializeConfig($initArgs) {
        parent::initializeConfig($initArgs);

        $project = $this->cartoclient->getProjectHandler()->getProjectName();
        $config = $this->getConfig()->getIniArray();

        if (array_key_exists('dontBlock', $config) &&
            $config['dontBlock'] == '1') {
            $this->blockAccess = false;
        }

        // Construct the whiteListPath complete path
        $whiteListPath = null;
        if (Utils::isRelativePath($config['whiteListPath'])) {
            $file = CARTOWEB_HOME . 
                    "projects/{$project}/{$config['whiteListPath']}";
            if (file_exists($file)) {
                // get the project config file
                $whiteListPath = $file;
            } else {
                // no config file in project, get the default file 
                $whiteListPath = CARTOWEB_HOME . $config['whiteListPath'];
            }
        } else {
            // absolute path
            $whiteListPath = $config['whiteListPath'];
        }

        $this->white = new WhiteList($whiteListPath);
        
        $this->black = new BlackList(CARTOWEB_HOME . 
                                     'www-data/throttling/throttling.blacklist.txt',
                                     $config['blackListPeriod']);

        // pupulate the buffers array
        foreach ($config as $ckey => $cvalue) {
            $exploded = explode('.', $ckey);
            if (count($exploded) == 3 && $exploded[0] == 'buffer' &&
                !array_key_exists($exploded[1], $this->buffers)) {
                $this->buffers[$exploded[1]] = new Buffer(
                    CARTOWEB_HOME . 'www-data/throttling/throttling.buffer.' . 
                    $exploded[1],
                    $config["buffer.{$exploded[1]}.period"],
                    $config["buffer.{$exploded[1]}.maxRequest"]);
            }
        }


        // Set the black list log file complete path
        if (Utils::isRelativePath($config['blackListLog'])) {
            $this->abuseFile = CARTOWEB_HOME . $config['blackListLog'];
        } else {
            $this->abuseFile = $config['blackListLog'];
        }
        
        if (!is_writable($this->abuseFile) && 
            !is_writable(dirname($this->abuseFile))) {
            // The log file can't be created or is not writable, 
            // change the path to a safe location
            $this->abuseFile = CARTOWEB_HOME . 
                               'www-data/throttling/throttling.log';
        }

        $this->abuseMail = $config['blackListMail'];
    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {
        $this->handleRequest($request);
    }

    /**
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {
        $this->handleRequest($request);
    }

    /**
     * Common method to handle both Get or Post request
     * @param array HTTP request
     */
    protected function handleRequest($request) {
        if (array_key_exists('toolTips', $request)) {
            // Never block and record tool tips requests
            return;
        }

        $ip = ThrottlingUtils::getRemoteAddress();
        $now = time();

        // remove entries that have not generated overflows from buffers.
        $this->clearBuffers($now);

        // remove blacklisted entries if they have spend enough time.
        $removed = $this->black->clearList($now);
        foreach ($removed as $r) {
            $this->notify($r, "removed from blacklist", $now);
        }

        $isAllowed = false;
        if ($this->white->contains($ip)) {
            // ip is in the white list: don't block
            $isAllowed = true;
        } else if($this->black->contains($ip)) {
            // ip is already blacklisted: block
            $isAllowed = false;
        } else {
            // not in the white or black list: update all buffers and check 
            // if the request triggered an overflow
            list($newInBlackList, $bufferId) = $this->updateBuffers($ip, $now);

            if ($newInBlackList) {
                // the request have triggered an overflow: notify and add the 
                // ip in the blacklist
                $this->notify($ip, 
                              "added in blacklist ('{$bufferId}' overflow)", 
                              $now);
                $this->black->add($ip, $now);

                $isAllowed = false;
            } else {
                // the request don't triggered an overflow
                $isAllowed = true;
            }
        }
        $this->sync();

        // Notify the user that he is blocked and immediately stop cartoweb
        if (!$isAllowed && $this->blockAccess) {
            header('HTTP/1.1 503 Service Unavailable');

            $formRenderer = $this->getCartoclient()->getFormRenderer();
            $formRenderer->setCustomForm(false);
            $this->getCartoclient()->setInterruptFlow(true);

            $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
            print $smarty->fetch('blacklisted.tpl');

            exit();
        }
    }

    /**
     * Send a notification by sending a mail and/or writing the envent 
     * to the log file.
     *
     * @param String ip
     * @param String what A text describing the event
     * @param Integer when When does the event occurs (timestamp)
     */
    protected function notify($ip, $what, $when) {
        // Create the log file parent directory is needed
        ThrottlingUtils::mkdirname($this->abuseFile);

        $project = $this->cartoclient->getProjectHandler()->getProjectName();
        $line = "[" . date('r', $when) . "] {$project}: {$ip} {$what}";

        // Write the event to the log file
        $fp = fopen($this->abuseFile, 'a');
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, $line . "\n");
            flock($fp, LOCK_UN);
        } else {
            throw new CartoclientException("Couldn't lock the file " .
                                           "('{$this->abuseFile}')");
        }
        fclose($fp);

        // Send the event by mail
        if ($this->abuseMail) {
            mail($this->abuseMail, "[Throttling] {$project}", $line);
        }
    }

    /**
     * Synchronize the blacklist and all buffers.
     */
    public function sync() {
        $this->black->sync();
        foreach ($this->buffers as $buffer) {
            $buffer->sync();
        }
    }

    /**
     * Update all buffers.
     *
     * @param Integer ip
     * @param Integer now timestamp
     * @return array Return whatever the user overflow the buffer and the
     * buffer name.
     */
    protected function updateBuffers($ip, $now) {
        foreach($this->buffers as $id => $buffer) {
            if ($buffer->update($ip, $now)) {
                return array(true, $id);
            }
        }
        return array(false, '');
    }

    /**
     * Clear all buffers.
     * 
     * @param Integer now timestamp
     */
    protected function clearBuffers($now) {
        foreach($this->buffers as $buffer) {
            $buffer->clear($now);
        }
    }

    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {
        // nothing to do
    }
}
?>
