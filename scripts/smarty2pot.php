#!/usr/local/bin/php -qn
<?php
/**
 * smarty2pot.php - rips gettext strings from smarty template
 *
 * This command line script rips gettext strings from smarty file, and prints them to stdout in pot format, 
 * that can later be used with the standard gettext tools.
 *
 * Usage:
 * ./smarty2pot.php [<project_name>]
 *
 * Original code was tsmarty2c.php written by Sagi Bashari <sagi@boom.org.il>
 *
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */

// smarty open tag
$ldq = preg_quote('{');

// smarty close tag
$rdq = preg_quote('}');

// smarty command
$cmd = preg_quote('t');

// extensions of smarty files, used when going through a directory
$extensions = array('tpl');

// arrays with all translations found
$texts = array();
$plurals = array();

// "fix" string - strip slashes, escape and convert new lines to \n
function fs($str)
{
	$str = stripslashes($str);
	$str = str_replace('"', '\"', $str);
	$str = str_replace("\n", '\n', $str);
	return $str;
}

// rips gettext strings from $file and prints them in POT format
function do_file($file)
{
	$content = @file_get_contents($file);

	if (empty($content)) {
		return;
	}

	global $ldq, $rdq, $cmd, $texts, $plurals;

	preg_match_all("/{$ldq}\s*({$cmd})\s*([^{$rdq}]*){$rdq}([^{$ldq}]*){$ldq}\/\\1{$rdq}/", $content, $matches);
	
	for ($i=0; $i < count($matches[0]); $i++) {
        $text = fs($matches[3][$i]);
        $ref = substr($file, (strlen(CARTOCLIENT_HOME) - strlen($file)));
        if (array_key_exists($text, $texts)) {
            $texts[$text] .= ',' . $ref;
        } else {
            $texts[$text] = $ref;
        }
		if (preg_match('/plural\s*=\s*["\']?\s*(.[^\"\']*)\s*["\']?/', $matches[2][$i], $match)) {
            $plurals[$text] = fs($match[1]);
		}
	}
}

// go through a directory
function do_dir($dir)
{
    global $projectName;
    
    // Include directory if:
    // - no projects set and not in projects directory, or
    // - project set and not in projects directory OR in this specific project directory
    if (($projectName == '' && !strstr($dir, 'projects/'))
        || ($projectName != ''
            && (!strstr($dir, 'projects/')
                || strstr($dir, 'projects/' . $projectName)))) {
    
    	$d = dir($dir);
    
    	while (false !== ($entry = $d->read())) {
    		if ($entry == '.' || $entry == '..') {
    			continue;
    		}
    
    		$entry = $dir.'/'.$entry;
    
    		if (is_dir($entry)) { // if a directory, go through it
    			do_dir($entry);
    		} else { // if file, parse only if extension is matched
    			$pi = pathinfo($entry);
    			
    			if (isset($pi['extension']) && in_array($pi['extension'], $GLOBALS['extensions'])) {
    				do_file($entry);
    			}
    		}
    	}
    
    	$d->close();
    }
}

define('CARTOCLIENT_HOME', realpath(dirname(__FILE__) . '/..') . '/');

$projectName = '';
if ($_SERVER['argc'] > 1) {
    // Project name was given in argument 
    $projectName = $_SERVER['argv'][1];
}

$dir = CARTOCLIENT_HOME;
if ($projectName != '') {
    $dir .= 'projects/' . $projectName . '/';
}
if (is_dir($dir)) {
    
    $fileName = 'default.po';
    if ($projectName != '') {
        $fileName = $projectName . '.po';
    }
    $fh = fopen($fileName, 'w');
    
    // POT header
    fwrite($fh, '# CartoWeb 3 Smarty templates ' . "\n");
    fwrite($fh, '#' . "\n");
    fwrite($fh, '#, fuzzy' . "\n");
    fwrite($fh, 'msgid ""' . "\n");
    fwrite($fh, 'msgstr ""' . "\n");
    fwrite($fh, '"POT-Creation-Date: ' . date('Y-m-d H:iO') . '\n"' . "\n");
    fwrite($fh, '"MIME-Version: 1.0\n"' . "\n");
    fwrite($fh, '"Content-Type: text/plain; charset=ISO-8859-1\n"' . "\n");
    fwrite($fh, '"Content-Transfer-Encoding: 8bit\n"' . "\n");

    do_dir(CARTOCLIENT_HOME);

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
    
} else {
    print "Usage: ./smarty2pot.php [<project_name>]\n";
}

?>
