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
                  'init', 'remove', 'cw3setup.php');

/**************************************************************/
/*           DO NOT CHANGE ANYTHING AFTER THAT                */
/**********************************************************dF**/

// Check operating system (Unix-like or win32)
$isWin = (PHP_OS == 'Window');

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
echo "remove                  : remove cartoweb3\n";
echo "\n";

// Retrieve command:
$cmd_array = $_SERVER['argv'];
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
            include('scripts/po2mo.php');

            break;

        case 'remove':
            remove();
            break;

        default:
            if (@$not_a_command_flag) {
                $not_a_command_flag = false;
                break;
            }
            echo "Unknown command: $cmd \n";
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
        $r .= "Soap extension not detected, but installer is not able to detect if there's a problem or not: please re-install php with soap extension if not done.\n";
        $badConfig = true;
    }
    else
        $r .= "Soap            => ok\n";

// Check php mapscript version:
    if (!extension_loaded(CW3_PHP_MAPSCRIPT))
        if (!dl(CW3_PHP_MAPSCRIPT.".".PHP_SHLIB_SUFFIX)) {
            $badConfig = true;
            $r .= "Unable to load library ".CW3_PHP_MAPSCRIPT.'.'.PHP_SHLIB_SUFFIX.".\n";
        }
    $ms_ver = trim(substr(@ms_GetVersion(), 18, 6));
    if(version_compare($ms_ver, CW3_PHP_MS_VERSION) < 0) {
        $r .= "Mapscript too old: please upgrade MapServer. Version must be equal or greater than ".CW3_PHP_MS_VERSION.".\n";
        $badConfig = true;
    }
    else
        $r .= "Mapserver ".$ms_ver." => ok\n";

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
        if (is_dir($fullpath) && $file != '.' && $file != '..')
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
        if($file!="." && $file!="..") {
            $fullpath=$dir."/".$file;
            if(!is_dir($fullpath))
                unlink($fullpath);
            else
                rmdirr($fullpath);
        }
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

?>
