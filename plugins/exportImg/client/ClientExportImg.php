<?php
/**
 * Client exportImg plugin
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
 * @package CorePlugins
 * @version $Id$
 */
 
/**
 * Client part of exportImg plugin
 * export an image as an img tag in an html template
 * allow easy copy/past between IE and winword
 * @package CorePlugins
 */
class ClientExportImg extends ClientPlugin
                   implements GuiProvider {
    /**
     * @var Logger
     */
    private $log;

    /**
     * @var exportImgEnabled
     */
    private $exportImgEnabled;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {}

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpGetRequest($request) {
        $this->exportImgEnabled = isset($request['exportImg']);
    }
    
    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {

        if ($this->exportImgEnabled) {
            $images = $this->cartoclient->getPluginManager()->getPlugin('images');
            $imgPath = $images->getImagesResult()->mainmap->path;
            $resourceHandler = $this->getCartoclient()->getResourceHandler();
            $fullImgPath = $resourceHandler->getFinalUrl($imgPath, true);
            $smarty = $this->getCartoclient()->getFormRenderer()->getSmarty();
            $smarty->assign('mapPath', $fullImgPath);
            $this->getCartoclient()->getFormRenderer()->setCustomForm('imgoutput.tpl');
        }
    }
}

?>
