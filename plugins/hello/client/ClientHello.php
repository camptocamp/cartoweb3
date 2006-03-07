<?php
/**
 * Demo plugin.
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
 * @package Plugins
 * @version $Id$
 */

/**
 * Demo plugin, shows how to output messages and use sessions.
 * @package Plugins
 */
class ClientHello extends ClientPlugin
                  implements Sessionable, GuiProvider, Ajaxable {

    const HELLO_INPUT = 'hello_input';

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var int
     */
    protected $count;

    /** 
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * Retrieves count number from session.
     * @see Sessionable::loadSession()
     */
    public function loadSession($sessionObject) {
        $this->count = $sessionObject;
    }

    /**
     * Initializes session-saved var "count" to 0.
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->count = 0;
    }

    /**
     * Saves count number in session.
     * @see Sessionable::saveSession()
     */
    public function saveSession() {
        return $this->count;
    }
    
    /**
     * Increments count number and retrieves POST'ed message.
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {
        $this->count = $this->count + 1;
        $this->message = '';
        $this->message = isset($request[self::HELLO_INPUT])
                         ? $request[self::HELLO_INPUT] : '';
    }

    /**
     * Not used.
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {}

    /**
     * Draws plugins interface.
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {

        $message = sprintf(I18n::gt('message: %s count: %d'),
                           $this->message, $this->count);

        $template->assign(array('hello_active'  => true,
                                'hello_message' => $message));
    }

    public function ajaxGetPluginResponse(AjaxPluginResponse $ajaxPluginResponse) {
        $message = sprintf(I18n::gt('message: %s count: %d'),
                           $this->message, $this->count);

		$ajaxPluginResponse->addHtmlCode('hello_message', $message);
    }

	public function ajaxHandleAction($actionName, PluginEnabler $pluginsDirectives) {
		switch ($actionName) {
			case 'Hello.Change':
				$pluginsDirectives->disableCoreplugins();
				$pluginsDirectives->enablePlugin('hello');				
			break;
			case 'Location.Pan':
				// Hello plugin will run on Location.mapPanByKeymap action
				$pluginsDirectives->enablePlugin('hello');								
			break;
		}
	}
}
?>
