<?php
/**
 * Client accounting plugin
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
 * @package CorePlugins
 * @version $Id$
 */
 
/**
 * Client part of Accounting plugin
 * @package CorePlugins
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com> 
 */
class ClientAccounting extends ClientPlugin implements Sessionable, GuiProvider {

    /**
     * @var Logger
     */
    private $log;

    /**
     * True if the user session is just created
     * @var boolean
     */
    private $firstTime;

    /** 
     * Constructor
     */
    public function __construct() {

        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * @see PluginBase::initialize()
     */
    public function initialize() {
        
        $accounting = Accounting::getInstance();
        $accounting->pluginLoaded();        
        $accounting->account('general.client_version', 1);
        $tod = gettimeofday();
        $accounting->account('general.mapid', $this->getCartoclient()->
                             getConfig()->mapId);
        $accounting->account('general.time', $tod['sec']);
        if (isset($_SERVER['HTTP_USER_AGENT']))
            $accounting->account('general.ua', $_SERVER['HTTP_USER_AGENT']);

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $accounting->account('general.ip', $_SERVER['HTTP_X_FORWARDED_FOR']);
        else if (isset($_SERVER['REMOTE_ADDR']))
            $accounting->account('general.ip', $_SERVER['REMOTE_ADDR']);

        
        $accounting->account('general.sessid', session_id());

        $directAccess = $this->getCartoclient()->getConfig()->
                               cartoserverDirectAccess ? '1' : '0';
        $accounting->account('general.direct_access', $directAccess);
    }
    
    /**
     * @see Sessionable::loadSession()
     */
    public function loadSession($sessionObject) {
        $this->firstTime = false;
    }

    /**
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo, 
                                  InitialMapState $initialMapState) {
        $this->firstTime = true;
    }

    /**
     * @see Sessionable::saveSession()
     */
    public function saveSession() {
        return false;
    }
    
    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {
        
        if (isset($request['js_accounting'])) {
            $accounting = Accounting::getInstance();
            $accounting->account('general.browser_info', 
                                 $request['js_accounting']);
        }
    }

    /**
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {}

    /**
     * Draws plugins interface.
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {

        $template->assign(array('jsAccounting' => $this->firstTime));
    }
}

?>
