<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * A client plugin class for displaying static tools, like tools for measrurement.
 *
 * @package CorePlugins
 */
class ClientStatictools extends ClientCorePlugin
                        implements ToolProvider {
    private $log;

    const TOOL_DISTANCE = 'distance';
    const TOOL_SURFACE = 'surface';

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    function handleMainmapTool(ToolDescription $tool,
                               Shape $mainmapShape) {}

    function handleKeymapTool(ToolDescription $tool,
                              Shape $keymapShape) {}

    function getTools() {
        return array(
            new ToolDescription(self::TOOL_DISTANCE, true,
                     new JsToolAttributes(JsToolAttributes::SHAPE_LINE,
                                         JsToolAttributes::CURSOR_CROSSHAIR,
                                         JsToolAttributes::ACTION_MEASURE), 80),
            new ToolDescription(self::TOOL_SURFACE, true,
                     new JsToolAttributes(JsToolAttributes::SHAPE_POLYGON,
                                         JsToolAttributes::CURSOR_CROSSHAIR,
                                         JsToolAttributes::ACTION_MEASURE), 81),
        );
    }

    function handleHttpRequest($request) {}

    function renderForm($template) {}
}
?>