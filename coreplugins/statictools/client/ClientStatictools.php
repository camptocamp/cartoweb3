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
class ClientStatictools extends ClientPlugin
                        implements ToolProvider {
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
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
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
     * @see ToolProvider::getTools()
     */
    public function getTools() {
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
}
?>