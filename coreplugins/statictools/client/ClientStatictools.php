<?php
/**
 * Client statictools plugin
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
 * A client plugin class for displaying static tools, like tools for measrurement.
 *
 * @package CorePlugins
 */
class ClientStatictools extends ClientPlugin
                        implements GuiProvider, ToolProvider {
    /**                 
     * @var Logger
     */
    private $log;

    /**
     * Tools constants
     */
    const TOOL_DISTANCE = 'distance';
    const TOOL_SURFACE = 'surface';

    /**
     * Constructor
     */
    public function __construct() {
        $this->log = LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {}

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpGetRequest($request) {}
    
    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {
       
       $nbMaxSegments = $this->getConfig()->nbMaxSegments;
       $template->assign(array(
            'dhtml_nb_max_segments' => $nbMaxSegments
                                ));
    }

    /**
     * @see ToolProvider::handleMainmapTool()
     */
    public function handleMainmapTool(ToolDescription $tool,
                               Shape $mainmapShape) {}
    
    /**
     * @see ToolProvider::handleKeymapTool()
     */
    public function handleKeymapTool(ToolDescription $tool,
                              Shape $keymapShape) {}

    /**
     * @see ToolProvider::handleApplicationTool()
     */
    public function handleApplicationTool(ToolDescription $tool) {}

    /**
     * @see ToolProvider::getTools()
     */
    public function getTools() {
        return array(
            new ToolDescription(self::TOOL_DISTANCE, true, 80),
            new ToolDescription(self::TOOL_SURFACE, true, 81),
        );
    }
}
