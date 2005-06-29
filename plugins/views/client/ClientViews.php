<?php
/**
 * View Interface plugin.
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
 * View metas session-saving class
 * @package Plugins
 */
class ViewsState {

    /**
     * @var int
     */
    public $viewId;
    
    /**
     * @var array
     */
    public $metas;
}

/**
 * Handles the views creation/edition/deletion interface.
 * @package Plugins
 */
class ClientViews extends ClientPlugin
                  implements Sessionable, GuiProvider {

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var ViewsState
     */
    private $viewsState;

    /**
     * @var array
     */
    public $metasList;

    /**
     * @var boolean
     */
    private $viewActive = false;

    /**
     * @var string
     */
    private $action;
    
    /**
     * @var ViewManager
     */
    private $viewManager;

    /**
     * @var array
     */
    private $viewsList;

    /** 
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * @return ViewManager
     */
    private function getViewManager() {
        if (!isset($this->viewManager)) {
            $this->viewManager = $this->getCartoclient()->getViewManager();
        }
        return $this->viewManager;
    }

    /**
     * Returns ClientViews::metasList, list of metadata fields.
     * @return array
     */
    private function getMetasList() {
        if (!isset($this->metasList)) {
            $this->metasList = $this->getViewManager()->getMetasList();
        }
        return $this->metasList;
    }

    /**
     * @see Sessionable::loadSession()
     */
    public function loadSession($sessionObject) {
        $this->viewsState = $sessionObject;
    }

    /**
     * @see Sessionable::createSession()
     */
    public function createSession(MapInfo $mapInfo, 
                                  InitialMapState $initialMapState) {
        $this->viewsState = new ViewsState;
    }

    /**
     * @see Sessionable::saveSession()
     */
    public function saveSession() {
        return $this->viewsState;
    }

    /**
     * Sets metadata using GET/POST values.
     * @param array HTTP request
     */
    private function setFromForm($request) {
        foreach ($this->getMetasList() as $metaName) {
            $this->viewsState->metas[$metaName] = 
                strip_tags($this->getHttpValue($request, $metaName));
        }
    }

    /**
     * Common processing for {@see ClientViews::handleHttpPostRequest} and
     * {@see ClientViews::handleHttpGetRequest}.
     * @param array HTTP request
     */
    protected function handleHttpRequest($request) {

        if (!empty($request['viewLoad']) || !empty($request['viewBrowse'])) {
            $this->action = 'load';
        } elseif (!empty($request['viewSave'])) {
            $this->action = 'save';
        } elseif(!empty($request['viewUpdate'])) {
            $this->action = 'update';
        } elseif(!empty($request['viewDelete'])) {
            $this->action = 'delete';
        }

        switch ($this->action) {
            case 'save':
            case 'update':
            // saving view
                if ($this->action == 'update') { 
                    $this->viewsState->viewId = $this->getHttpValue($request,
                                                        'viewUpdateId');
                }
                $this->setFromForm($request);
                $this->viewActive = false;
                break;
            
            case 'load':
            // loading a view
            // priority order: general views dropdown > edition form views
            // dropdown > views id input
                if (!empty($request['viewBrowse']) && 
                    !empty($request['viewBrowseId'])) {
                    $this->viewsState->viewId = $request['viewBrowseId'];  
                } elseif (!empty($request['viewLoadTitleId'])) {
                    $this->viewsState->viewId = $request['viewLoadTitleId'];
                } elseif (!empty($request['viewLoadId'])) {
                    $this->viewsState->viewId = $request['viewLoadId'];
                }
                $this->viewActive = false;
                break;

            default:
                if ($this->action == 'delete') {
                    $this->viewActive = false;
                } else {
                    $this->viewActive = $this->getHttpValue($request,
                                                            'viewActive');
                }
                
                if (!$this->viewActive) {
                    $this->viewsState = new ViewsState;
                } else {
                    $this->setFromForm($request);
                }
        }
        
        if (!$this->getViewManager()->checkViewId($this->viewsState->viewId,
                                                  true)) {
            $this->viewsState->viewId = '';
        }
    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     * @param array HTTP request
     */
    public function handleHttpPostRequest($request) {
        $this->handleHttpRequest($request);
    }

    /**
     * @see GuiProvider::handleHttpGetRequest()
     * @param array HTTP request
     */
    public function handleHttpGetRequest($request) {
        $this->handleHttpRequest($request);
    }

    /**
     * Populates and returns views list.
     * @return array
     */
    private function getViewsList($showAll = false) {
        $viewsList = $this->getViewManager()->getCatalog();
        $this->viewsList = array();
        if ($viewsList) {
            $weights = array();
            foreach ($viewsList as $viewId => $viewInfo) {
                if ($showAll || !empty($viewInfo['viewShow'])) {
                    $weights[$viewId] = $viewInfo['weight'];
                    $this->viewsList[$viewId] = 
                                      stripslashes($viewInfo['viewTitle']);
                }
            }
            if ($this->viewsList) {
                natsort($weights);
                $viewsList = array('0' => '');
                foreach ($weights as $viewId => $weight) {
                    $viewsList[$viewId] = $this->viewsList[$viewId];
                }
                $this->viewsList = $viewsList;
            }
        }
        return $this->viewsList;
    }

    /**
     * @see GuiProvider::renderForm()
     * @param Smarty
     */
    public function renderForm(Smarty $template) {
        
        $template->assign(array('viewsForm'    => $this->drawUserForm(),
                                'viewsList'    => $this->getViewsList(), 
                                'selectedView' => $this->viewsState->viewId,
                                'views'        => $this->getViewManager()
                                                       ->hasRole(true),
                                ));
    }

    /**
     * Builds views edition interface
     * @return string Smarty fetch result
     */
    private function drawUserForm() {
       
        $viewMsg = $this->cartoclient->areViewsEnable()
                   ? $this->getViewManager()->getMessage()
                   : I18n::gt('Views main controller is OFF!!');
        
        $memorizing = ($this->viewActive && !$viewMsg);
        if ($memorizing) {
            $viewMsg = I18n::gt('Memorizing view to update');
        }
        
        if ($this->getViewManager()->getActionSuccess() || $memorizing) {
            switch ($this->action) {
                case 'load':
                    $this->viewsState->metas = $this->getViewManager()
                                                    ->getMetas();
                    break;
                    
                case 'save':
                    $this->viewsState->viewId = $this->getViewManager()
                                                     ->getViewId();
                    break;
            }
        } else {
            $this->viewsState = new ViewsState;
        }

        $viewId = $this->viewsState->viewId;

        $viewOptions = $this->getViewsList(true);
        
        $viewLocationOptions = $viewOptions;
        if ($viewLocationOptions) {
            unset($viewLocationOptions[0]);
            unset($viewLocationOptions[$viewId]);
            if (count($viewLocationOptions) > 0) {
                $viewLocationOptions += 
                    array('0' => I18n::gt('(placed at the end)'));
            }
        }

        $this->smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $this->smarty->assign(array('viewOptions' => $viewOptions,
                                    'viewLocationOptions' => $viewLocationOptions,
                                    'viewId'      => $viewId,
                                    'viewMsg'     => $viewMsg,
                                    'viewActive'  => $this->viewActive,
                                    ));
        
        if (!isset($this->viewsState->metas['viewLocationId'])) {
            $this->viewsState->metas['viewLocationId'] = 0;
        }
        
        foreach ($this->getMetasList() as $metaName) {
            $metaValue = isset($this->viewsState->metas[$metaName]) ?
                         $this->viewsState->metas[$metaName] : '';
           
            $metaValue = strip_tags($metaValue);
            $metaValue = str_replace('"', '&quot;', $metaValue);
            $this->smarty->assign($metaName, $metaValue);
        }
        
        return $this->smarty->fetch('views.tpl');
    }
}
?>
