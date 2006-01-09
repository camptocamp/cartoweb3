<?php
/**
 * makemaps.php - generates map files when auto layers mode is on
 *
 * Usage:
 * ./makemaps.php
 *
 * @package Scripts
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */

define('CARTOWEB_HOME', realpath(dirname(__FILE__) . '/..') . '/');

require_once(CARTOWEB_HOME . 'scripts/pot_tools.php');
if (!defined('CW3_SETUP_INCLUDED'))
    require_once(CARTOWEB_HOME . 'cw3setup.php');

/**
 * Project handler class for constants
 */
require_once(CARTOWEB_HOME . 'common/ProjectHandler.php');

$autoIndexes = array();
$switchLayers = array();
$allLayers = array();
$globalIndex = '';
$globalName = '';
$globalSwitch = '';
$rootDir = '';

if (empty($projects)) {
    $projects = getProjects();
}

foreach ($projects as $project) {
    makeProjectMaps($project);
}

/**
 * Make maps for a project
 * @param string
 */
function makeProjectMaps($project) {

    $mapIds = getMapIds($project);
    foreach ($mapIds as $mapId) {
        makeMapIdMaps($project, $mapId);
    }
}

/**
 * Finds which layers must be in map file for which switch
 * @param string
 * @return array 
 */
function findSwitchLayers($rootDir) {
    global $switchLayers, $autoIndexes;

    $layersIni = parse_ini_file($rootDir . 'layers.ini');
    if (empty($layersIni)) {
        print "File layers.ini not found for project $project, mapId $mapId.\n";
        return;
    }
    
    if (!array_key_exists('autoLayersIndexes', $layersIni)) {
        return;
    }
    $autoIndexes = array_map('trim', explode(',', $layersIni['autoLayersIndexes']));

    $switches = array();
    $switches[] = 'default';
    $layerGroups = array();
    $switchLayers = array();
    $switchLayers['default'] = array();

    foreach ($layersIni as $key => $value) {
        $elements = explode('.', $key);
        if ($elements[0] == 'switches') {
            $switches[] = $elements[1];
            $switchLayers[$elements[1]] = array();
        }
        if (in_array('children', $elements)) {
            $layerGroups = array_unique(array_merge($layerGroups, array($elements[1])));
        }
    }

    foreach ($layerGroups as $layerId) {
        $defaultLayers = NULL;
        if (array_key_exists("layers.$layerId.children", $layersIni)) {
            $defaultLayers = $layersIni["layers.$layerId.children"];        
        }
        foreach ($switches as $switch) {
            $layerIds = '';
            if (!is_null($defaultLayers)) {
                $layerIds = $defaultLayers;
            } else if (array_key_exists("layers.$layerId.children.$switch", $layersIni)) {
                $layerIds = $layersIni["layers.$layerId.children.$switch"];
            } else if (array_key_exists("layers.$layerId.children.default", $layersIni)) {
                $layerIds = $layersIni["layers.$layerId.children.default"];
            }
            if (!empty($layerIds)) {
                $layerIds = array_map('trim', explode(',', $layerIds));
                $layers = array();
                foreach ($layerIds as $childId) {
                    if (array_key_exists('layers.' . $childId . '.msLayer', $layersIni)) {
                        $layers[] = $layersIni['layers.' . $childId . '.msLayer'];
                    } else {
                        $layers[] = $childId;
                    }
                }
                $switchLayers[$switch] = array_unique(array_merge($switchLayers[$switch], $layers));  
                sort($switchLayers[$switch]);                                                  
            }
        }
    }
} 

/**
 * Make maps for a map ID
 * @param string
 * @param string
 */
function makeMapIdMaps($project, $mapId) {

    global $globalSwitch, $switchLayers, $allLayers, $autoIndexes, $rootDir;

    $rootDir = CARTOWEB_HOME . ProjectHandler::PROJECT_DIR . '/' . 
                    $project . '/' . 'server_conf/' . $mapId . '/';
    
    if (!file_exists($rootDir . $mapId . '.map.php')) {
        // Map file is not a PHP file
        return;
    }
    
    findSwitchLayers($rootDir);
    if (is_null($switchLayers) || count($switchLayers) == 0) {
        // No layers found in layers.ini
        return;
    }
    
    $allLayers = array();
    foreach ($switchLayers as $switch => $layers) {
        $allLayers = array_merge($allLayers, $layers);
    }
    $allLayers = array_unique($allLayers);
    sort($allLayers);
  
    $phpMap = file_get_contents($rootDir . $mapId . '.map.php');
    foreach ($switchLayers as $switch => $layers) {
        $switchMap = '';
        $globalSwitch = $switch;
        ob_start();
        eval('?> ' . $phpMap . ' <?');
        file_put_contents($rootDir . 'auto.' . $mapId . '.' . $switch . '.map',
                          ob_get_contents());
        ob_end_clean();
    }
    $globalSwitch = 'all';
    ob_start();
    eval('?> ' . $phpMap . ' <?');
    file_put_contents($rootDir . 'auto.' . $mapId . '.all.map',
                      ob_get_contents());
    ob_end_clean();
        
}

// Functions that can be used in PHP map files

function getIndex() {
    global $globalIndex;
    return $globalIndex;
}

function printIndex() {
    global $globalIndex;
    print $globalIndex;
}

function printName() {
    global $globalName, $globalIndex;
    print $globalName . $globalIndex;
}

function printSwitchLayers($layers, $content) {
    global $globalName, $globalIndex;
    global $autoIndexes;

    foreach($layers as $layer) {
        if (strlen($layer) > strlen($globalName)
            && substr($layer, 0, strlen($globalName)) == $globalName
            && in_array(substr($layer, strlen($globalName)), $autoIndexes)) {
            // There is a layer to generate
            $globalIndex = substr($layer, strlen($globalName));
            eval('?>' . $content . '<?'); 
        }
    }
}

function printLayer($name, $content) {
    global $globalName, $globalSwitch, $globalIndex;
    global $switchLayers, $allLayers, $autoIndexes;

    $globalName = $name;
    if ($globalSwitch == 'all') {
        printSwitchLayers($allLayers, $content);
    } else {
        printSwitchLayers($switchLayers[$globalSwitch], $content);
    }
}

function includeFile($file) {
    global $rootDir;
    
    if (file_exists($rootDir . $file)) {
        $fileMap = file_get_contents($rootDir . $file);
        eval('?>' . $fileMap . '<?');
    } else {
        print "File $file not found.\n";
    } 
}

?>
