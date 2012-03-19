<?php
/**
 * bbox history plugin
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
 * @package Plugins
 * @version $Id$
 */

/**
 * @package Plugins
 */
class ClientBboxHistoryState {

    /**
     * @var array of Bbox
     */
    public $history = array();

    /**
     * Current position in $history
     * @var integer
     */
    public $position = null;
}

class ClientBboxHistory extends ClientPlugin
                        implements Sessionable, Ajaxable, GuiProvider,
                                   FilterProvider, ServerCaller {


    /**
     * @var Logger
     */
    private $log;

    /**
     * @var ClientBboxHistoryState
     */
    private $state;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->log = LoggerManager::getLogger(__CLASS__);
    }


    /**
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo,
                                  InitialMapState $initialMapState) {
        $this->state = new ClientBboxHistoryState();
    }


    /**
     * @see Sessionable::loadSession()
     */
    public function loadSession($sessionObject) {
        $this->state = $sessionObject;
    }


    /**
     * @see Sessionable::saveSession()
     */
    public function saveSession() {
        return $this->state;
    }

    
    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {}
    

    /**
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {}


    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {
        $template->assign('bboxHistoryForm', $this->drawForm());
    }

    protected function drawForm() {
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);

        $smarty->assign('has_prev', is_null($this->state->position) &&
            count($this->state->history) > 1 || $this->state->position > 0);

        $smarty->assign('has_next', !is_null($this->state->position) &&
            $this->state->position < count($this->state->history) - 2);

        return $smarty->fetch('bbox_history_form.tpl');
    }

    /**
     * @see FilterProvider::filterPostRequest()
     */
    public function filterPostRequest(FilterRequestModifier $request) {
        if ($request->getValue('ajaxActionRequest') == 'BboxHistory.moveTo') {
            $steps = (int)$request->getValue('steps');

            if (is_null($this->state->position)) {
                // first call
                $this->state->position = count($this->state->history) - 1;
            } else {
                // Don't record new position while moving through history.
                array_pop($this->state->history);
            }

            $index = $this->state->position + $steps;

            if (isset($this->state->history[$index])) {
                $request->setValue('recenter_bbox',
                    $this->state->history[$index]->toRemoteString(','));

                $this->state->position = $index;
            }
        } else {
            $this->state->position = null;
        }
    }

    /**
     * @see FilterProvider::filterGetRequest()
     */
    public function filterGetRequest(FilterRequestModifier $request) {
        $this->filterPostRequest($request);
    }

    /**
     * @see Ajaxable::ajaxGetPluginResponse()
     */
    public function ajaxGetPluginResponse(AjaxPluginResponse $ajaxPluginResponse) {
        $ajaxPluginResponse->addHtmlCode('bboxHistoryForm', $this->drawForm());
    }

    /**
     * @see Ajaxable::ajaxHandleAction()
     */
    public function ajaxHandleAction($actionName, PluginEnabler $pluginEnabler) {
        $pluginEnabler->enablePlugin('bboxHistory');
    }

    /**
     * @see ServerCaller::buildRequest()
     */
    public function buildRequest() {
        /* we have no server part ... */
    }

    /**
     * @see ServerCaller::initializeResult()
     */
    public function initializeResult($result) {
        $location = $this->cartoclient->getPluginManager()->getPlugin('location');

        $bbox = $location->getLocation();

        // don't duplicate last entries
        if (count($this->state->history) > 0) {
            $last = $this->state->history[count($this->state->history) - 1];
            if ($last->minx != $bbox->minx || $last->miny != $bbox->miny ||
                $last->maxx != $bbox->maxx || $last->maxy != $bbox->maxy) {
                $this->state->history[] = $bbox;
            }
        } else {
            $this->state->history[] = $bbox;
        }
    }

    /**
     * @see ServerCaller::handleResult()
     */
    public function handleResult($result) {
        /* we have no server part ... */
    }
}

