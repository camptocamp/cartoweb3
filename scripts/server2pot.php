#!/usr/local/bin/php
<?php
/**
 * server2pot.php - generates PO templates, merge with old PO files
 *
 * Uses .ini (for layer labels) and .map (for class names) 
 *
 * Usage:
 * ./server2pot.php
 *
 * @package Scripts
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */

/**
 * Home dirs
 */
define('CARTOSERVER_HOME', realpath(dirname(__FILE__) . '/..') . '/');
define('CARTOSERVER_PODIR', 'po/');

require_once('./pot_tools.php');

// treat parameter
if (isset($argv[1])) {
    $projectname = $argv[1];
} else {
    $projectname = false;
}

/**
 * Parses an INI file looking for variable ending with '.label'
 * @param string
 * @param string
 * @param array map text_to_translate => references
 * @return boolean
 */
function parseIni($project, $mapId, &$texts) {

    $iniPath = CARTOSERVER_HOME;
    if (!is_null($project)) {
        $iniPath .= ProjectHandler::PROJECT_DIR . '/' . $project. '/';
    }
    $iniPath .= 'server_conf/' . $mapId . '/';
    
    if (!is_dir($iniPath)) {
        return true;
    }
    $d = dir($iniPath);
    while (false !== ($entry = $d->read())) {
        if (!is_dir($entry) && substr($entry, -4) == '.ini') {
            $iniFile = $iniPath . $entry;
            $iniArray = parse_ini_file($iniFile);
            foreach($iniArray as $key => $value) {
                if (substr($key, -6) == '.label') {
                    $info = $entry . ':' . $key;
                    if (array_key_exists($value, $texts)) {
                        $texts[$value] .= ',' . $info;
                    } else {
                        $texts[$value] = $info;
                    }
                }
            }
        }
    }
    return true;
}

/**
 * Adds a reference
 * @param string
 * @param string
 * @param array map text_to_translate => references
 */
function addMapText($text, $mapId, &$texts) {
    $info = $mapId . '.map';
    if (array_key_exists($text, $texts)) {
        $texts[$text] .= ',' . $info;
    } else {
        $texts[$text] = $info;
    }
}

function dummyErrorHandler($errno, $errmsg, $filename, $linenum, $vars) {}

/**
 * Parses a MAP file looking for class names and 'query_returned_attributes'
 * metadata
 * @param string
 * @param string
 * @param array map text_to_translate => references
 * @return boolean
 */
function parseMap($project, $mapId, &$texts) {

    $mapFileDir = CARTOSERVER_HOME;
    if (!is_null($project)) {
        $mapFileDir .= ProjectHandler::PROJECT_DIR . '/' . $project. '/';
    }
    $mapFileDir .= 'server_conf/' . $mapId . '/';
    $mapFile = $mapFileDir . $mapId . '.map';
    
    if (!extension_loaded('mapscript')) {
        $prefix = (PHP_SHLIB_SUFFIX == 'dll') ? '' : 'php_';
        if (!dl($prefix . 'mapscript.' . PHP_SHLIB_SUFFIX))
            print 'Error: Cannot load Mapscript library.';
            return false;
    }
    
    if (!file_exists($mapFile)) {
        // Trying generated mapfile
        $mapFile = $mapFileDir . 'auto.' . $mapId . '.all.map';
    }
    if (!file_exists($mapFile)) {            
        print "\nWarning: Map file $mapFile not found.\n";
        return false;
    }
    
    $old_error_handler = set_error_handler("dummyErrorHandler");
    $map = ms_newMapObj($mapFile);
    restore_error_handler();
    if (empty($map)) {
        print "\nWarning: Error while loading mapfile $mapFile.\n";
        return false;
    }
    for ($i = 0; $i < $map->numlayers; $i++) {
        $layer = $map->getLayer($i);
       
        if ($layer->name != '') {
            addMapText($layer->name, $mapId, $texts);
        }
       
        for ($j = 0; $j < $layer->numclasses; $j++) {
            $class = $layer->getClass($j);
            
            if ($class->name != '') {
                addMapText($class->name, $mapId, $texts);
            }
        }     
        
        $returnedAttr = $layer->getMetaData('query_returned_attributes');
        if (!empty($returnedAttr)) {
            $attrArray = explode(' ', $returnedAttr);
            foreach ($attrArray as $attr) {
                addMapText($attr, $mapId, $texts);
            }
        }
    }
    return true;
}

/**
 * Finds list of already translated PO files for a project and a map ID
 * @param string
 * @return array
 */
function getTranslatedMapIdPo($project, $mapId) {
    
    $files = array();
    $dir = CARTOSERVER_HOME;
    if (!is_null($project)) {
        $dir .= ProjectHandler::PROJECT_DIR . '/' . $project . '/';
    }
    $d = dir($dir . CARTOSERVER_PODIR);

    $pattern = "server\\.$mapId\\.(.*)\\.po";          
 
    while (false !== ($entry = $d->read())) {
        if (!is_dir($dir . CARTOSERVER_PODIR . $entry)) {
            if (ereg($pattern, $entry)) {
                $files[] = $entry;
            };
        }
    }    
 
    return $files;   
}

$projects = getProjects($projectname);
// Adds a null value for extracting the po file from upstream
$projects[] = null;

foreach ($projects as $project) {

    $fileName = 'server.po';
    $dir = CARTOSERVER_HOME;
    if (!is_null($project)) {
        $dir .= ProjectHandler::PROJECT_DIR . '/' . $project . '/';
    }
    if (!is_dir($dir . CARTOSERVER_PODIR)) {
        mkdir($dir . CARTOSERVER_PODIR);
    }

    $file = $dir . CARTOSERVER_PODIR . $fileName;

    print "Creating new template $fileName for project $project ";
        
    $fh = fopen($file, 'w');
    
    // POT header
    fwrite($fh, '# CartoWeb 3 translation template ' . "\n");
    fwrite($fh, '#' . "\n");
    fwrite($fh, '#, fuzzy' . "\n");
    fwrite($fh, 'msgid ""' . "\n");
    fwrite($fh, 'msgstr ""' . "\n");
    fwrite($fh, '"POT-Creation-Date: ' . date('Y-m-d H:iO') . '\n"' . "\n");
    fwrite($fh, '"MIME-Version: 1.0\n"' . "\n");
    fwrite($fh, '"Content-Type: text/plain; charset=' . 
                                   getCharset('server', $project) . '\n"' . "\n");
    fwrite($fh, '"Content-Transfer-Encoding: 8bit\n"' . "\n");

    fclose($fh);    
        
    print ".. done.\n";

    print "Adding strings from PHP code for project $project ";
    addPhpStrings('server', CARTOSERVER_HOME,
                  $dir . CARTOSERVER_PODIR . $fileName, $project);
    print ".. done.\n";        

    $poFiles = getTranslatedPo('server', $project);

    foreach ($poFiles as $poFile) {
        
        print "Merging new template into $poFile ";
        exec("msgmerge -o $dir" . CARTOSERVER_PODIR . "$poFile $dir"
             . CARTOSERVER_PODIR . "$poFile $dir" . CARTOSERVER_PODIR . "$fileName");
    }

    $mapIds = getMapIds($project);
    
    foreach ($mapIds as $mapId) {
    
        $texts = array();
        $fileName = 'server.' . $mapId . '.po';
        $file = $dir . CARTOSERVER_PODIR . $fileName;

        print "Creating new template $fileName for project $project ";
        
        parseIni($project, $mapId, $texts);
        if (!parseMap($project, $mapId, $texts)) continue;

        $fh = fopen($file, 'w');
    
        // POT header
        fwrite($fh, '# CartoWeb 3 translation template ' . "\n");
        fwrite($fh, '#' . "\n");
        fwrite($fh, '#, fuzzy' . "\n");
        fwrite($fh, 'msgid ""' . "\n");
        fwrite($fh, 'msgstr ""' . "\n");
        fwrite($fh, '"POT-Creation-Date: ' . date('Y-m-d H:iO') . '\n"' . "\n");
        fwrite($fh, '"MIME-Version: 1.0\n"' . "\n");
        fwrite($fh, '"Content-Type: text/plain; charset=' . 
                                        getCharset('server', $project) . '\n"' . "\n");
        fwrite($fh, '"Content-Transfer-Encoding: 8bit\n"' . "\n");

        foreach ($texts as $text => $files) {
            fwrite($fh, "\n");
            foreach (explode(',', $files) as $file) {
                fwrite($fh, "#: $file\n");
            }
            fwrite($fh, 'msgid "' . $text . '"' . "\n");
            fwrite($fh, 'msgstr ""' . "\n");
        }
    
        fclose($fh);    
        
        print ".. done.\n";
        
        $poFiles = getTranslatedMapIdPo($project, $mapId);

        foreach ($poFiles as $poFile) {
        
            print "Merging new template into $poFile ";
            exec("msgmerge -o $dir" . CARTOSERVER_PODIR . "$poFile $dir"
                 . CARTOSERVER_PODIR . "$poFile $dir" . CARTOSERVER_PODIR . "$fileName");
        }
    }
}

?>
