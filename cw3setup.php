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
define('CW3_PHP_MAPSCRIPT', 'php_mapscript');

// Required mapserver version:
define('CW3_PHP_MS_VERSION', '4.4.0');

// Required php version:
define('CW3_PHP_VERSION', '5.0.3');

define ('CW3_CVS_LOGIN', 'daniel'); // for tests: your login
//define ('CW3_CVS_LOGIN', 'anonymous');

// Url of required libraries:
define('CW3_LIBS_URL', 'http://www.camptocamp.com/~sypasche/cartoweb3/cartoweb3_includes.tgz');

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
                  'templates_c'
                  );

$CW3_WRITABLE_DIRS = array('log',
                      'www-data',
                      'templates_c'
                     );

// Directories and files to remove from cw3 root when removeing:
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


/**************************************************************/
/*           DO NOT CHANGE ANYTHING AFTER THAT                */
/**********************************************************dF**/

// Check operating system (Unix-like or windaube)
$isWin = (PHP_SUFFIX_LIB == 'dll');

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
echo "check                   : check configuration\n";
echo "get[=user]              : CVS checkout with user name [anonymous]\n";
echo "get_libs                : get required libraries\n";
echo "dirs                    : create user directories\n";
echo "perms[=www-data]        : set writing perms for web-user [www-data]\n";
echo "                          - set ownership if ran as superuser,\n";
echo "                          - give write permission instead.\n";
echo "create_conf             : create the new configuration\n";
echo "link_or_copy            : link or copy paths for web browser\n";
echo "remove                  : remove cartoweb3\n";
echo "\n";

// Retrieve command:
$cmd_array = @$_REQUEST;
if (count($cmd_array) == 0) {
    $cmd = "FULL INSTALL";
    $cmd_array = array('check' => '',
                       'get' => '',
                       'get_libs' => '',
                       'dirs' => '',
                       'perms' => '',
                       'create_conf' => '',
                       'link_or_copy' => ''
                      );
}
else
    $cmd = implode(array_keys($cmd_array), ', ');

// Check if we proceed with installation:
echo "Command(s): $cmd. Continue ? y/n [y]";
$r = getInput();
if (strlen($r) > 0 && strtolower($r) <> 'y') die ("Aborted\n");

foreach($cmd_array as $cmd=>$params) {
    switch($cmd) {
        case 'check':
            checkConfig();
            break;

        case 'get':
            if ($params) get($params);
            else get(CW3_CVS_LOGIN);
            break;

        case 'get_libs':
            getLibs(CW3_LIBS_URL);
            break;

        case 'dirs':
            mkDirs();
            break;

        case 'perms':
            if ($params) setPerms($params);
            else setPerms('www-data');
            break;

        case 'create_conf':
            echo "\nCreating configuration:\n";
            createConfig(getcwd());
            echo " ... installed from dist.\n";
            break;

        case 'link_or_copy':
            echo "\nIf you're using the Miniproxy, cartoweb3 is now installed.\n";
            echo "If not, you MUST say yes here: [y]";
            $r = getInput();
            if (strlen($r) > 0 && strtolower($r) <> 'y') die ("Finished\n");
            setupProjects();
            break;

        case 'remove':
            remove();
            break;

        default:
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

// Check php version:
    if (version_compare(phpversion(), CW3_PHP_VERSION) < 0)
        die("Your php is too old: please install php ".CW3_PHP_VERSION." or above.\n");
    else
        echo "php ", phpversion(), "       => ok\n";

// Check if soap is enabled:
    if (!extension_loaded('soap'))
        die("Soap not enabled: please re-install php with soap extension.\n");
    else
        echo "Soap            => ok\n";

// Check php mapscript version:
    if (!extension_loaded(CW3_PHP_MAPSCRIPT))
        if (!dl(CW3_PHP_MAPSCRIPT.".".PHP_SHLIB_SUFFIX))
            die ("Unable to load library ".CW3_PHP_MAPSCRIPT.'.'.PHP_SHLIB_SUFFIX.".\n");
    $ms_ver = trim(substr(ms_GetVersion(), 18, 6));
    if(version_compare($ms_ver, CW3_PHP_MS_VERSION) < 0)
        die ("Mapscript too old: please upgrade MapServer. Version must be >= ".CW3_PHP_MS_VERSION.".\n");
    else
        echo "Mapserver ", $ms_ver, " => ok\n";

// The configuration is ok.
    echo "Configuration   => ok\n";
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
// todo, for windaube, using cacls, BUT ONLY FOR NTFS
    foreach($CW3_WRITABLE_DIRS as $dir) {
        setPermsRecursive($dir, $user);
    }
}

function setPermsRecursive($dir, $user) {
   if (@chown($dir, $user)) echo "\"$dir\" is now owned by $user\n";
   elseif (@chmod($dir, 777)) echo "\"$dir\" is now writable by everybody, including $user\n";
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
        if (is_dir($file) && $file != '.' && $file != '..') createConfig($file);
        if(substr($file, strlen($file) - 5) == ".dist") {
            $fullpath = $dir."/".$file;
            $name = substr($fullpath, 0, strlen($fullpath) - 5);
            copy($fullpath, $name);
            echo $name, ",\n";
        }
    }
    closedir($dh);
}

// Create directories
function mkDirs() {
    global $CW3_DIRS_TO_CREATE;

    echo "\nCreating directories:\n";
    foreach($CW3_DIRS_TO_CREATE as $dir) {
        @mkdir($dir);
        echo "Create $dir => ok\n";
    }
}

// Checkout from cvs
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

// Setup projects, plugins, coreplugins:
function setupProjects() {
   $projdirs =  array('projects', 'plugins', 'coreplugins');
   foreach($projdirs as $dir) {
       foreach(getProjects($dir) as $project) {
           if (is_dir($dir.'/'.$project.'/htdocs'))
               link_or_copy('../'.$dir.'/'.$project.'/htdocs', 'htdocs/'.$project);
       }
   }
}

// Get the list of projects in directory $dir
function getProjects($dir) {
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
        passthru("copy $src $dest", &$r);
        $result = "\"$src\" copied to \"$dest\"\n";
    }
    else {
        passthru("ln -s $src $dest", &$r);
        $result =  "\"$src\" linked from \"$dest\"\n";
    }
    if (!$r) echo $result;
}

// Get libraries
function getLibs($url) {
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
            echo "         Please install tar and redo, or untar $inc manually.\n";
            echo "         For window, you can get tar at http://gnuwin32.sourceforge.net/packages/tar.htm\n";
        }
    }
    else
        die("WARNING: Libraries not found.\n");
    @unlink($inc);
}

// Read user input in php shell mode
function getInput($length = 255) {
    $fr = fopen("php://stdin", "r");
    $input = fgets($fr, $length);
    $input = rtrim($input);
    fclose($fr);
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


?>