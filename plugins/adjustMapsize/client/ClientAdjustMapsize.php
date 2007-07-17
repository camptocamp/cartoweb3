<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

class ClientAdjustMapsize extends ClientPlugin
                          implements ToolProvider {

    const TOOL_ADJUST_MAPSIZE = 'adjust_mapsize';

    public function getTools() {
        $toolsArray = array();

        $toolsArray[] = new ToolDescription(self::TOOL_ADJUST_MAPSIZE, true, 201);

        return $toolsArray;
    }

    public function handleApplicationTool(ToolDescription $tool) {}

    public function handleKeymapTool(ToolDescription $tool, 
                                     Shape $keymapShape) {}

    public function handleMainmapTool(ToolDescription $tool, 
                                      Shape $mainmapShape) {}

}
?>
