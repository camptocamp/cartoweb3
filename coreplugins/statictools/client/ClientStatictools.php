<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * @package CorePlugins
 */

class ClientStatictools extends ClientCorePlugin implements ToolProvider {
    private $log;

    private $statictoolsState;
    private $availableTools;

    const TOOL_DISTANCE = 'distance';
    const TOOL_SURFACE = 'surface';

    function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();

        $this->availableTools = array(
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

    function loadSession($sessionObject) {
        $this->log->debug('loading session:');
        $this->log->debug($sessionObject);

        $this->statictoolsState = $sessionObject;
    }

    function createSession(MapInfo $mapInfo, InitialMapState $initialMapState) {
        $this->log->debug('creating session:');
        
        return; 
    }

    function handleMainmapTool(ToolDescription $tool,
                               Shape $mainmapShape) {}

    function handleKeymapTool(ToolDescription $tool,
                              Shape $keymapShape) {}

    function getTools() {
        
        return $this->availableTools;
    }

    function handleHttpRequest($request) {}

    function buildMapRequest($mapRequest) {}

    function handleResult($queryResult) {}

    function renderForm($template) {}

    function saveSession() {
        $this->log->debug('saving session:');
        $this->log->debug($this->statictoolsState);

        return $this->statictoolsState;
    }
}
?>
