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

        // 'weight' => 'toolname'
        $this->availableTools = array('80' => self::TOOL_DISTANCE, 
                                      '81' => self::TOOL_SURFACE);
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
        $tools = array();
        $activatedTools = $this->getConfig()->staticTools;

        if (!$activatedTools) return $tools;
        
        $activatedTools = explode(',', $activatedTools);
        $activatedTools = array_map('trim', $activatedTools);

        foreach ($this->availableTools as $w => $tool) {
            if (in_array($tool, $activatedTools)) {
                $weightname = 'weight' . ucfirst($tool);
                $weight = $this->getConfig()->$weightname;
                if (!$weight) $weight = $w;
                
                // FIXME: use a real translated string for 3rd argument
                $tools[] = new ToolDescription($tool, $tool, $tool,
                                               ToolDescription::MAINMAP,
                                               $weight, 'statictools');
            }
        }
        return $tools;
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
