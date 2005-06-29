#!/usr/local/bin/php
<?php
/**
 * client2pot.php - rips gettext strings from smarty template and client .ini
 *
 * This command line script rips gettext strings from smarty file and .ini, and
 * save one file per project. If file already exists, calls msgmerge.
 *
 * Usage:
 * ./client2pot.php
 *
 * Original code was tsmarty2c.php written by Sagi Bashari <sagi@boom.org.il>
 *
 * @package Scripts
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */

/**
 * Home dirs
 */ 
define('CARTOCLIENT_HOME', realpath(dirname(__FILE__) . '/..') . '/');
define('CARTOCLIENT_PODIR', 'po/');

require_once('./pot_tools.php');

// smarty open tag
$ldq = preg_quote('{');

// smarty close tag
$rdq = preg_quote('}');

// smarty command
$cmd = preg_quote('t');

// extensions of smarty files, used when going through a directory
$extensions = array('tpl');

// treat parameter
if (isset($argv[1])) {
    $projectname = $argv[1];
} else {
    $projectname = false;
}

/**
 * "Fix" string - strip slashes, escape and convert new lines to \n
 * @param string
 * @return string
 */
function fs($str) {
    $str = str_replace('"', '\"', $str);
    $str = str_replace("\n", '\n', $str);
    return $str;
}

/**
 * Rips gettext strings from $file and prints them in POT format
 * @param string
 * @param array map text_to_translate => references
 * @param array map of texts plurals
 */
function do_file($file, &$texts, &$plurals) {
    $content = @file_get_contents($file);
   
    if (empty($content)) {
        return;
    }

    global $ldq, $rdq, $cmd;

    preg_match_all("/{$ldq}\s*({$cmd})\s*([^{$rdq}]*)"
                   . "{$rdq}([^{$ldq}]*){$ldq}\/\\1{$rdq}/",
                   $content, $matches);
    
    for ($i = 0; $i < count($matches[0]); $i++) {
        $text = fs($matches[3][$i]);
        $ref = substr($file, (strlen(CARTOCLIENT_HOME) - strlen($file)));
        if (array_key_exists($text, $texts)) {
            $texts[$text] .= ',' . $ref;
        } else {
            $texts[$text] = $ref;
        }
        if (preg_match('/plural\s*=\s*["\']?\s*(.[^\"\']*)\s*["\']?/', 
                       $matches[2][$i], $match)) {
            $plurals[$text] = fs($match[1]);
        }
    }
}

/**
 * Goes through a directory
 * @param string
 * @param string project name or 'default'
 * @param array map text_to_translate => references
 * @param array map of texts plurals
 */
function do_dir($dir, $project, &$texts, &$plurals) {
    
    // Include directory if:
    // - no projects set and not in projects directory, or
    // - project set and not in projects directory OR in this specific project
    // directory
    if (($project == ProjectHandler::DEFAULT_PROJECT
         && !strstr($dir, ProjectHandler::PROJECT_DIR . '/'))
        || ($project != ProjectHandler::DEFAULT_PROJECT
            && (strstr($dir, ProjectHandler::PROJECT_DIR . '/' . $project)))) {

        $d = dir($dir);
    
        while (false !== ($entry = $d->read())) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }
    
            $entry = $dir.$entry;

            if (is_dir($entry)) { // if a directory, go through it
                do_dir($entry . '/', $project, $texts, $plurals);
            } else { // if file, parse only if extension is matched
                $pi = pathinfo($entry);
                
                if (isset($pi['extension']) && 
                    in_array($pi['extension'], $GLOBALS['extensions'])) {
                    do_file($entry, $texts, $plurals);
                }
            }
        }
    
        $d->close();
    }
}

/**
 * Parses an INI file looking for variable ending with '.label'
 * @param string
 * @param array map text_to_translate => references
 * @return boolean
 */
function parseIni($project, &$texts) {

    $iniPath = CARTOCLIENT_HOME;
    if ($project != ProjectHandler::DEFAULT_PROJECT) {
        $iniPath .= ProjectHandler::PROJECT_DIR . '/' . $project. '/';
    }
    $iniPath .= 'client_conf/';
    
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

$projects = getProjects($projectname);
// Adds default project
$projects[] = ProjectHandler::DEFAULT_PROJECT;

foreach ($projects as $project) {

    $dir = CARTOCLIENT_HOME;
    if ($project != ProjectHandler::DEFAULT_PROJECT) {
        $dir .= ProjectHandler::PROJECT_DIR . '/' . $project . '/';
    }
    if (is_dir($dir)) {
    
        if (!is_dir($dir . CARTOCLIENT_PODIR)) {
            mkdir($dir . CARTOCLIENT_PODIR);
        }
    
        // arrays with all translations found
        $texts = array();
        $plurals = array();

        $fileName = 'client.po';

        print "Creating new template $fileName for project $project ";

        $fh = fopen($dir . CARTOCLIENT_PODIR . $fileName, 'w');
    
        // POT header
        fwrite($fh, '# CartoWeb 3 translation template ' . "\n");
        fwrite($fh, '#' . "\n");
        fwrite($fh, '#, fuzzy' . "\n");
        fwrite($fh, 'msgid ""' . "\n");
        fwrite($fh, 'msgstr ""' . "\n");
        fwrite($fh, '"POT-Creation-Date: ' . date('Y-m-d H:iO') . '\n"' . "\n");
        fwrite($fh, '"MIME-Version: 1.0\n"' . "\n");
        fwrite($fh, '"Content-Type: text/plain; charset=' . 
                                 getCharset('client', $project) . '\n"' . "\n");
        fwrite($fh, '"Content-Transfer-Encoding: 8bit\n"' . "\n");

        parseIni($project, $texts);
        do_dir($dir, $project, $texts, $plurals);

        foreach ($texts as $text => $files) {
            fwrite($fh, "\n");
            foreach (explode(',', $files) as $file) {
                fwrite($fh, "#: $file\n");
            }
            fwrite($fh, 'msgid "' . $text . '"' . "\n");
            if (array_key_exists($text, $plurals)) {
                fwrite($fh, 'msgid_plural "' . $plurals[$text] . '"' . "\n");
                fwrite($fh, 'msgstr[0] ""' . "\n");
                fwrite($fh, 'msgstr[1] ""' . "\n");
            } else {
                fwrite($fh, 'msgstr ""' . "\n");
            }
        }
    
        fclose($fh);
        
        print ".. done.\n";

        print "Adding strings from PHP code for project $project ";
        addPhpStrings('client', CARTOCLIENT_HOME,
                      $dir . CARTOCLIENT_PODIR . $fileName, $project);
        print ".. done.\n";
             
        $poFiles = getTranslatedPo('client', $project);

        foreach ($poFiles as $poFile) {
        
            print "Merging new template into $poFile ";
            exec("msgmerge -o $dir" . CARTOCLIENT_PODIR . "$poFile $dir"
                 . CARTOCLIENT_PODIR . "$poFile $dir" . CARTOCLIENT_PODIR . "$fileName");
        }                
    }
}
    
?>
