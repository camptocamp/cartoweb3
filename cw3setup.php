<?php
/**************************************************************/
/*                   Cartoweb 3 Installer                     */
/*                                                            */
/* This program will setup cartoweb3 in the current directory */
/*                                                            */
/**********************************************************dF**/

error_reporting(E_ALL);

/**************************************************************/
/*                      Parameters                            */
/**********************************************************dF**/

define('CW3_SETUP_REVISION', '$Revision$');

// If you had renamed your php mapscript library,
// you need to change it here too:
define('CW3_PHP_MAPSCRIPT', 'php_mapscript'); // linux
//define('CW3_PHP_MAPSCRIPT', 'php_mapscript_45'); //win

// Required mapserver version:
define('CW3_PHP_MS_VERSION', '4.4.0');

// Required php version:
define('CW3_PHP_VERSION', '5.0.3');

define ('CW3_CVS_LOGIN', 'daniel'); // for tests: your login
//define ('CW3_CVS_LOGIN', 'anonymous');

// Url of required libraries:
define('CW3_LIBS_URL', 'http://www.cartoweb.org/downloads/cartoweb-includes-3.0.0.tar.gz');

// Directories to create from cw3 root:
$CW3_DIRS_TO_CREATE = array('www-data',
                  'www-data/mapinfo_cache',
                  'www-data/mapresult_cache',
                  'www-data/soapxml_cache',
                  'www-data/images',
                  'www-data/saved_posts',
                  'www-data/wsdl_cache',
                  'www-data/icons',
                  'www-data/pdf',
                  'www-data/pdf_cache',
                  'www-data/views',
                  'templates_c'
                  );

$CW3_WRITABLE_DIRS = array('log',
                      'www-data',
                      'templates_c'
                     );

// Directories and files to remove from cw3 root when removing:
$CW3_TO_REMOVE = array(
                  'CVS',
                  'ChangeLog',
                  'HACKING',
                  'Makefile',
                  'client',
                  'client_conf',
                  'common',
                  'coreplugins',
                  'documentation',
                  'htdocs',
                  'include',
                  'locale',
                  'log',
                  'plugins',
                  'po',
                  'projects',
                  'scripts',
                  'server',
                  'server_conf',
                  'templates',
                  'templates_c',
                  'tests',
                  'www-data'
                  );

$commands = array('check', 'get', 'getLibs', 'mkDirs', 'rmDirs', 'perms', 
                  'createConfig', 'setupLinks', 'removeLinks', 'setup', 
                  'init', 'deployProject', 'remove', 'cw3setup.php');

/**************************************************************/
/*           DO NOT CHANGE ANYTHING AFTER THAT                */
/**********************************************************dF**/

// Check operating system (Unix-like or win32)
$isWin = (PHP_OS == 'Window');

// Retrieve command:
$cmd_array = $_SERVER['argv'];

$batchMode = in_array('-batch', $cmd_array);
if ($batchMode) {
    unset($cmd_array[array_search('-batch', $cmd_array)]);
}

if (!$batchMode) {

    // Clear the screen:
    if ($isWin)
        echo `cls`;
    else
        echo `clear`;

    echo "\n";
    echo "/**************************************************************/\n";
    echo "/*                   Cartoweb 3 Installer                     */\n";
    echo "/*                                                            */\n";
    echo "/* This program will setup cartoweb3 in the current directory */\n";
    echo "/**************************************************************/\n\n";
    echo "To setup cartoweb3, just type: php -f cw3setup.php\n\n";
    echo "Available commands:\n";
    echo "Syntax: php -f cw3setup.php [command] [command=parameters]\n";
    echo "Sample: php -f cw3setup.php check get=anonymous\n\n";
    echo "(no command, by default): install cartoweb3\n";
    echo "cvs                     : install cartoweb3 from cvs\n";
    echo "check                   : check configuration\n";
    echo "get                     : CVS checkout with user name [anonymous]\n";
    echo "getLibs                 : get required libraries\n";
    echo "perms [www-data]        : set writing perms for web-user [www-data]\n";
    echo "                          - set ownership if ran as superuser,\n";
    echo "                          - give write permission instead.\n";
    echo "createConfig            : create the new configuration (install .dist files)\n";
    echo "setupLinks              : link or copy paths for web browser\n";
    echo "removeLinks             : remove all links created by setupLinks.\n";
    echo "                          - super user rights required to remove dynamic content\n";
    echo "setup [path]            : setup a new project in existing installation\n";
    echo "mkDirs                  : create all cache directories\n";
    echo "rmDirs                  : remove all temporary files AND directories\n";
    echo "init                    : performs CartoWeb intialization (locale compiling, makemaps, ...)\n";
    echo "deployProject project   : install a cartoweb3 from scratch including the specified project\n";
    echo "remove                  : remove cartoweb3\n";
    echo "\n";
}

if (count($cmd_array) <= 1) {
    $cmd = "FULL INSTALL";
    $cmd_array = array('check',
                       'getLibs',
                       'mkDirs',
                       'perms',
                       'createConfig',
                       'setupLinks',
                       'init'
                      );
}
else {
    array_shift($cmd_array);
    $cmd = implode($cmd_array, ', ');
}

if (in_array('cvs', $cmd_array)) {
    $cmd = "FULL INSTALL from CVS";
    $cmd_array = array('check',
                       'get',
                       'getLibs',
                       'mkDirs',
                       'perms',
                       'createConfig',
                       'setupLinks',
                       'init'
                      );
}

// Check if we proceed with installation:
echo "Command(s): $cmd. Continue ? y/n [y]";
$r = getInput();
if (strlen($r) > 0 && strtolower($r) <> 'y') die ("Aborted\n");

foreach($cmd_array as $keycmd=>$cmd) {
    switch($cmd) {
        case 'check':
            $cnf = checkConfig();
            if ($cnf) {
                echo $cnf;
                echo "Right configuration NOT detected.";
                echo " Continue ? y/n [n]";
                $r = getInput();
                if (strlen($r) > 0 && strtolower($r) <> 'y') die ("Aborted\n");
            }
            else {
                // The configuration is ok.
                echo "Configuration   => ok\n";
            }
            break;

        case 'get':
            get(CW3_CVS_LOGIN);
            break;

        case 'getLibs':
            getLibs(CW3_LIBS_URL);
            break;

        case 'mkDirs':
            mkDirs();
            break;

        case 'rmDirs':
            rmDirs();
            break;

        case 'perms':
           if (isset($cmd_array[$keycmd + 1]) && (!in_array($commands[$keycmd + 1], $cmd_array))) {
                $params = $cmd_array[$keycmd + 1];
                setPerms($params);
                $not_a_command_flag = true;
            }
            else setPerms('www-data');
            break;

        case 'createConfig':
            echo "\nCreating configuration:\n";
            
            createConfig(dirname(__FILE__));
            echo " ... installed from dist.\n";
            break;

        case 'setupLinks':
            echo "\nIf you're using the Miniproxy, cartoweb3 is now installed.\n";
            echo "If not, or don't know, you MUST say yes here: [y]";
            $r = getInput();
            if (strlen($r) > 0 && strtolower($r) <> 'y') die ("Finished\n");
            setupLinks();
            break;

        case 'removeLinks':
            removeLinks();
            break;

        case 'setup':
            if (isset($cmd_array[$keycmd + 1]) && (!in_array($commands[$keycmd + 1], $cmd_array))) {
                $params = $cmd_array[$keycmd + 1];
                $dest = ltrim(substr($params, strrpos($params, '/')), '/');
                link_or_copy($params, 'projects/'.$dest);
                setupLinks();
                $not_a_command_flag = true;
            }
            break;

        case 'init':

            include('scripts/makemaps.php');
            // FIXME: makemaps needs a soft_clean afterwards (do it, or fix makemaps).
            include('scripts/po2mo.php');

            break;

        case 'deployProject':

            $not_a_command_flag = true;
            if (!isset($cmd_array[$keycmd + 1]))
                die("Missing project name\n");
            $project = $cmd_array[$keycmd + 1];

            deployProject($project);

            break;
        case 'remove':
            remove();
            break;

        default:
            if (@$not_a_command_flag) {
                $not_a_command_flag = false;
                break;
            }
            die("Unknown command: $cmd \n");
    }
}
echo "\n";

/**************************************************************/
/*                  Library for cw3 installer                 */
/**********************************************************dF**/


// Check if configuration is ok for cw3:
function checkConfig() {
    echo "\nChecking configuration:\n";
    $badConfig = false;

// Check php version:
    if (version_compare(phpversion(), CW3_PHP_VERSION) < 0) {
        $r = "Your php is too old: please install php ".CW3_PHP_VERSION." or above.\n";
        $badConfig = true;
    }
    else
        $r = "php ".phpversion()."       => ok\n";

// Check if soap is enabled:
    if (!extension_loaded('soap')) {
        $r .= "Soap extension not detected, but installer is not able to detect if there's a problem or not: please re-install php with soap extension if not done AND if you want to set cartoweb in a client/server configuration.\n";
    }
    else
        $r .= "Soap            => ok\n";

    if ($badConfig) return $r;
    else return false;
}

/* Library */

function remove() {
    global $CW3_TO_REMOVE;

    foreach($CW3_TO_REMOVE as $dir) {
        if (is_dir($dir)) {
            if (@rmdirr($dir)) echo "$dir REMOVED => ok\n";
            else echo "WARNING: Unable to remove \"$dir\"\n";
        }
        elseif(file_exists($dir)) {
            if(@unlink($dir)) echo "$dir REMOVED => ok\n";
            else echo "WARNING: Unable to remove \"$dir\"\n";
        }
    }
}

// Set permissions
function setPerms($user) {
    global $CW3_WRITABLE_DIRS;

    echo "\nSetting permissions:\n";
    // todo, for win32, using cacls, BUT ONLY FOR NTFS
    foreach($CW3_WRITABLE_DIRS as $dir) {
        setPermsRecursive($dir, $user);
    }
}

function setPermsRecursive($dir, $user) {
   if (@chown($dir, $user)) echo "\"$dir\" is now owned by $user\n";
   elseif (@chmod($dir, 0777)) echo "\"$dir\" is now writable by everybody, including $user\n";
   else echo "WARNING: unable to set permissions on \"$dir\"\n";
   $dh = @opendir($dir);
   if (!$dh) return false;
   while ($file = readdir($dh)) {
       if($file != "." && $file != ".." && $file != 'CVS' && $file != '.cvsignore') {
           setPermsRecursive($dir.'/'.$file, $user);
       }
   }
   closedir($dh);
}

function createConfig($dir) {
    if (!$dir) return false;
    $dh = @opendir($dir);
    if (!$dh) return false;
    while ($file = readdir($dh)) {
        $fullpath = "$dir/$file";
        if (is_dir($fullpath) && $file != '.' && $file != '..' && !is_link($fullpath))
            createConfig($fullpath);
        if(substr($file, strlen($file) - 5) == ".dist") {
            $target = substr($fullpath, 0, strlen($fullpath) - 5);
            if (file_exists($target)) {
                print "Target config file $target already exists, skipping\n";
                continue; 
            }
            copy($fullpath, $target);
            echo $target, ",\n";
        }
    }
    closedir($dh);
}

/**
 * Create directories
 */
function mkDirs() {
    global $CW3_DIRS_TO_CREATE;

    echo "\nCreating directories:\n";
    foreach($CW3_DIRS_TO_CREATE as $dir) {
        @mkdir($dir);
        echo "Create $dir => ok\n";
    }
}

/**
 * Create directories
 */
function rmDirs() {
    global $CW3_DIRS_TO_CREATE;

    echo "\nRemove directories:\n";
    foreach($CW3_DIRS_TO_CREATE as $dir) {
        if (@rmdirr($dir))
            echo "Remove $dir => ok\n";
    }
}

/**
 * Checkout from cvs
 */
function get($user) {
    global $isWin;

    echo "\nCheckout from CVS repository:\n";
    $r = passthru("cvs -q -d :pserver:$user@c2cpc2.camptocamp.com:2401/var/cvs/mapserver co cartoweb3");
    if ($r) {
        echo $r."\n";
        echo "FATAL: Cartoweb3 not copied from repository.\n";
    }
    if ($isWin)
        $r = passthru('xcopy cartoweb3/* . /e/i');
    else
        $r = passthru('mv cartoweb3/* ./');
    if ($r) echo "$r\n";
    rmdirr('cartoweb3');
}

/**
 * Setup symlinks or copy files for projects, plugins, coreplugins:
 */
function setupLinks() {
    // Create symlinks to www-data icons, images, and pdf sub-directories
    if (link_or_copy('../www-data/icons', './htdocs/icons'))
        echo "\"../www-data/icons\" linked from \"./htdocs/icons\"\n";
    if (link_or_copy('../www-data/images', './htdocs/images'))
        echo "\"../www-data/images\" linked from \"./htdocs/images\"\n";
    if (link_or_copy('../www-data/pdf', './htdocs/pdf'))
        echo "\"../www-data/pdf\" linked from \"./htdocs/pdf\"\n";

    if (!is_dir('htdocs/gfx/icons')) mkdir('htdocs/gfx/icons');

    $pList = cw3setupGetProjects('projects');
    foreach($pList as $project) {
        @mkdir('htdocs/gfx/icons/'.$project);
        $mList = cw3setupGetProjects('projects/'.$project.'/server_conf');
        if (!$mList)
            continue;
        foreach($mList as $mapfolder) {
             link_or_copy('../../../../projects/'.$project.'/server_conf/'.$mapfolder.'/icons/', 'htdocs/gfx/icons/'.$project.'/'.$mapfolder);
        }
    }
    // special case for default project
    @mkdir('htdocs/gfx/icons/default');
    $mList = cw3setupGetProjects('server_conf');
    foreach($mList as $mapfolder) {
        link_or_copy('../../../../server_conf/'.$mapfolder.'/icons/', 'htdocs/gfx/icons/default/'.$mapfolder);
    }

    // Create symlinks to po directories
    if (!is_dir('htdocs/po')) mkdir('htdocs/po');
    
    $pList = cw3setupGetProjects('projects');
    foreach($pList as $project) {
        link_or_copy('../../projects/'.$project.'/po/', 'htdocs/po/'.$project);
    }
    // special case for default project
    link_or_copy('../../po/', 'htdocs/po/default');    

    $projdirs =  array('projects', 'plugins', 'coreplugins');
    foreach($projdirs as $dir) {
        $pList = cw3setupGetProjects($dir);
        foreach($pList as $project) {
            if (!is_dir('./htdocs/'.$project)) @mkdir('htdocs/'.$project);
            $d = @opendir($dir.'/'.$project.'/htdocs');
            if ($d) {
                while ($file=readdir($d)) {
                    if($file!="." && $file!=".." && $file != 'CVS') {
                        // symlink htdocs elements from projects to core
                        link_or_copy('../../'.$dir.'/'.$project.'/htdocs/'.$file, 'htdocs/'.$project.'/'.$file);
                    }
                }
            }
            $plugdirs = array('plugins', 'coreplugins');
            foreach($plugdirs as $pdir) {
                $pd = @opendir($dir.'/'.$project.'/'.$pdir);
                if ($pd)  {
                    while ($pfile=readdir($pd)) {
                        if($pfile!="." && $pfile!=".." && $pfile != 'CVS') {
                            // symlink plugins and coreplugins htdocs elements from projects
                            link_or_copy('../../'.$dir.'/'.$project.'/'.$pdir.'/'.$pfile.'/htdocs/', 'htdocs/'.$project.'/'.$pfile);
                        }
                    }
                }
            }
        }
    }
}

/**
 * Remove all links made by setupLinks
 */
function removeLinks() {
    @unlink('./htdocs/icons');
    @unlink('./htdocs/images');
    @unlink('./htdocs/pdf');
    @unlink('./htdocs/gfx/icons');
    $projdirs =  array('projects', 'plugins', 'coreplugins');
    foreach($projdirs as $dir) {
        $pList = cw3setupGetProjects($dir);
        foreach($pList as $project) {
            $d = @opendir($dir.'/'.$project.'/htdocs');
            if ($d) {
                while ($file=readdir($d)) {
                    if($file!="." && $file!=".." && $file != 'CVS') {
                        if (is_link('htdocs/'.$project.'/'.$file))
                            if (unlink('htdocs/'.$project.'/'.$file))
                                echo 'htdocs/'.$project.'/'.$file." unlinked\n";
                    }
                }
            }
            $plugdirs = array('plugins', 'coreplugins');
            foreach($plugdirs as $pdir) {
                $pd = @opendir($dir.'/'.$project.'/'.$pdir);
                if ($pd)  {
                    while ($pfile=readdir($pd)) {
                        if($pfile!="." && $pfile!=".." && $pfile != 'CVS') {
                            if (is_link('htdocs/'.$project.'/'.$pfile))
                                if (unlink('htdocs/'.$project.'/'.$pfile))
                                    echo 'htdocs/'.$project.'/'.$pfile." unlinked\n";
                        }
                    }
                }
            }
        }
    }
}

// Get the list of projects in directory $dir
// FIXME: should use a common utility method
function cw3setupGetProjects($dir) {
   $dh = @opendir($dir);
   if (!$dh) return false;
   while ($file=readdir($dh)) {
       if($file!="." && $file!=".." && $file != 'CVS') {
           $projects[] = $file;
       }
   }
   closedir($dh);
   return $projects;
}

// Link or copy a file or directory
function link_or_copy($src, $dest) {
    global $isWin;

    if ($isWin) {
        if (copyr($src, $dest))
            $result = "\"$src\" copied to \"$dest\"\n";
    }
    else {
        if (@symlink($src, $dest))
            $result =  "\"$src\" linked from \"$dest\"\n";
    }
    if (isset($result)) echo $result;
}

// Get libraries
function getLibs($url) {
    global $isWin;

    echo "Loading libraries. Please wait. It may take a while.\n";

    if ($isWin)
            $inc = 'cartoweb3_includes.zip';
    else
            $inc = 'cartoweb3_includes.tgz';
    if (extension_loaded('curl')) {
        $ch = curl_init($url);
        $fp = fopen($inc, "wb");
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }
    else {
        $cnt = file_get_contents($url);
        $fd = fopen($inc, "wb");
        fwrite($fd, $cnt);
        fclose($fd);
    }
    if (file_exists($inc)) {

        $r = passthru("tar xzf $inc");
        if ($r) {
            echo $r."\n";
            echo "WARNING: Libraries not uncompressed.\n";
            echo "         Please install tar (or zip for win) and redo, or uncompress file $inc manually.\n";
        }
    }
    else
        die("WARNING: Libraries not found.\n");
    @unlink($inc);
}

// Read user input in php shell mode
function getInput() {
    global $batchMode;
    if ($batchMode)
        return 'y';
    $input = false;
    $fr = fopen("php://stdin", "r");
    $input = fgets($fr, 255);
    $input = rtrim($input);
    fclose($fr);
    if ($input)
        return $input;
}

// Recursively remove:
function rmdirr($dir) {
    $dh=@opendir($dir);
    if (!$dh) return false;
    while ($file=readdir($dh)) {
        if ($file == '.' || $file == '..') 
            continue;
        $fullpath = $dir . '/' . $file;

        if(!is_dir($fullpath) || is_link($fullpath))
            unlink($fullpath);
        else
            rmdirr($fullpath);
    }
    closedir($dh);
    if(@rmdir($dir))
        return true;
    else
        return false;
}

// Recursive copy
function copyr($source, $dest) {
    // Simple copy for a file
    if (is_file($source)) {
        return copy($source, $dest);
    }

    // Make destination directory
    if (!is_dir($dest)) {
        mkdir($dest);
    }

    // Loop through the folder
    $dir = @dir($source);
    if (!$dir) return false;
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        // Deep copy directories
        if ($dest !== "$source/$entry") {
            copyr("$source/$entry", "$dest/$entry");
        }
    }

    // Clean up
    $dir->close();
    return true;
}

/**************************************************************/
/*                      Project deployment                    */
/**************************************************************/

// FIXME: all this part is heavily non portable (lots of unix assumptions)

function debug($msg) {
    //print "$msg\n";
}

function parse_host_config_file($host_config_file) {
    $ini = parse_ini_file($host_config_file);

    $keys = array_keys($ini);
    
    $new_keys = array();
    foreach($keys as $k) {
        $new_keys[] = "@{$k}@";
    }
    $keys = $new_keys;
    $values = array_values($ini);
    $keys[] = '@BLURB@';
    $values[] = '!!! Do not edit this file, it is generated. Edit the .in instead !!!';
    return array($keys, $values);
}

/**
 * Try:
 * 1 config_HOST_PATH.properties
 *    where HOST = $(cat /etc/hostname)
 *          PATH = $(pwd | sed s:/:_:g)
 * 2 config_HOST.properties
 * 3 config.properties
 *
 * Returns an array of (array of keys, array of values)
 */
function get_host_config($base_path) {
 
    if (isset($_ENV['HOST_CONFIG']))
        return parse_host_config_file($_ENV['HOST_CONFIG']);

    $try_suffixes = array();

    if (file_exists('/etc/hostname')) {
        $hostname = file_get_contents('/etc/hostname');
    } else {
        $hostname = php_uname('n');
    }
    $hostname = str_replace("\n", "", $hostname);

    $path = dirname(__FILE__);
    $path = substr($path, 1);
    $path = str_replace('/', '_', $path);

    $try_suffixes[] = "_{$hostname}_{$path}";
    $try_suffixes[] = "_{$hostname}";
    $try_suffixes[] = "";
 
    foreach($try_suffixes as $suffix) {
        $host_config_file = "$base_path/config{$suffix}.properties";
        print "Trying host config file: $host_config_file\n";
        if (file_exists($host_config_file)) {
            return parse_host_config_file($host_config_file);
        }
    }
    die("Can't find host config file\n");
}

/**
 * Crawls recursively a directory, and calls a callback function for each files.
 */
function crawl($dir, $function, $context) {
    if (!$dir) return false;
    $dh = @opendir($dir);
    if (!$dh) return false;
    while ($file = readdir($dh)) {
        if ($file == '.' || $file == '..')
            continue;
        $fullpath = "$dir/$file";
        if (is_dir($fullpath)) {
            crawl($fullpath, $function, $context);
        } else {
            //print "handling file $fullpath \n";
            $function($fullpath, $context);
        }
    }
    closedir($dh);
}

$host_config = null;

function replace_dot_in($filename, $context) {

    global $host_config;

    if (!(substr($filename, strlen($filename) - strlen('.in')) == '.in'))
        return;

    if (is_null($host_config))
        $host_config = get_host_config($context['host_config_base_path']);

    print "Replacing $filename \n";

    $target_filename = substr($filename, 0, strlen($filename) - strlen('.in'));
    if (file_exists($target_filename)) {
        print "Warning: target $target_filename already exists, not overwritting\n";
        return;
    }
    
    $content = file_get_contents($filename);
    $new_content = str_replace($host_config[0], $host_config[1], $content);
    file_put_contents($target_filename, $new_content);
}

/**
 * Compare this script cvs revision, and the one from the just fetched CartoWeb, and tell the user
 *  to update of CartoWeb one is more recent.
 */
function check_cw3setup_version() {

    $content = file_get_contents('cartoweb3/cw3setup.php');
    $revision_pattern = '/\$Rev.sion: ([\.\d]+) \$/';
    preg_match($revision_pattern, $content, $matches);
    if (!isset($matches[1]))
        die("Unable to find Revision in cartoweb3/cw3setup.php\n");

    // FIXME: This cvs revision comparison alghorithm is broken with branches

    $cvs_revision = (int)(substr($matches[1], 2));
    preg_match($revision_pattern, CW3_SETUP_REVISION, $matches);
    $this_revision = (int)(substr($matches[1], 2));

    if ($cvs_revision > $this_revision)
        die("\nThe CVS version of cw3setup.php is more recent. " .
            "You MUST update cw3setup.php and try again. Deployment aborted unfinished!\n");
}

// FIXME: refactor this with the cvs functions used elsewhere
function get_cvs_repository() {
    $cvs_user = isset($_ENV['CVS_USER']) ? $_ENV['CVS_USER'] : get_current_user();
    $cvs_host = isset($_ENV['CVS_HOST']) ? $_ENV['CVS_HOST'] : 'source.c2c:';
    $cvs_path = isset($_ENV['CVS_PATH']) ? $_ENV['CVS_PATH'] : '/var/lib/cvs/projects/cw3';
    return ":pserver:{$cvs_user}@{$cvs_host}{$cvs_path}";
}

function fetch_project($project, $target_directory) {

    if (isset($_ENV['PROJECT_LOCATION'])) {
        passthru("cp -rl {$_ENV['PROJECT_LOCATION']} $target_directory");
    } else {
        $quiet = isset($_ENV['QUIET']) ? ' >/dev/null 2>&1' : '';
        $repository = get_cvs_repository();
        passthru("cd $target_directory && cvs -d $repository co $project $quiet");
    }
}

function fetch_cartoweb($co_options='') {

    if (isset($_ENV['CARTOWEB_LOCATION'])) {
        passthru("rm -rf cartoweb3; cp -rl {$_ENV['CARTOWEB_LOCATION']} cartoweb3");
    } else {
        $quiet = isset($_ENV['QUIET']) ? ' >/dev/null 2>&1' : '';
        $repository = get_cvs_repository();
        passthru("cvs -d $repository co $co_options cartoweb3 $quiet");
    }
}

function deployProject($project) {

    print "\n";

    $temp_deploy_directory = dirname(__FILE__) . "/.tmp_deploy_" . rand();
    mkdir($temp_deploy_directory);

    echo "Fetching project ...\n";
    fetch_project($project, $temp_deploy_directory);

    // TODO: This is for the $project/cartoweb3/projects/$project naming convention
    //  we should support the $project naming convention too
    passthru("mv $temp_deploy_directory/$project $temp_deploy_directory/{$project}.tmp");
    passthru("mv $temp_deploy_directory/{$project}.tmp/cartoweb3/projects/$project $temp_deploy_directory/$project");
    
    $sleep_sec = 2;
    print "\nWARNING WARNING WARNING\n";
    print "WARNING: will remove cartoweb3 directory in $sleep_sec seconds. Press <Ctrl-c> to abort \n";
    print "WARNING WARNING WARNING\n";
    sleep($sleep_sec);
    
    rmdirr('cartoweb3');

    $cw3_cvs_pin = '';
    $cw3_cvs_pin_file = "$temp_deploy_directory/$project/deployment/cw3_cvs_pin.txt";
    if (file_exists($cw3_cvs_pin_file)) {
        $cw3_cvs_pin = file_get_contents($cw3_cvs_pin_file);
        $cw3_cvs_pin = str_replace("\n", "", $cw3_cvs_pin);
    }

    fetch_cartoweb($cw3_cvs_pin);

    check_cw3setup_version();

    passthru("mv $temp_deploy_directory/$project cartoweb3/projects");
    rmdirr($temp_deploy_directory);

    $context = array();
    $context['host_config_base_path'] = "cartoweb3/projects/$project/deployment/";
    crawl('cartoweb3', 'replace_dot_in', $context);

    // launch project deploy script
    if (is_file("cartoweb3/projects/$project/deployment/install.php")) {
        $_ENV['project'] = $project;
        include("cartoweb3/projects/$project/deployment/install.php");
    }

    // init cartoweb

    // FIXME: This is ugly
    $interpreter = $_SERVER['_'];
    
    passthru("cd cartoweb3 && $interpreter cw3setup.php -batch getLibs mkDirs perms createConfig setupLinks init");

    file_put_contents('cartoweb3/current_project.txt', $project);
}

/**************************************************************/
/**************************************************************/

?>
