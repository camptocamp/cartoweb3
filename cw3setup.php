<?php
/**
 * CartoWeb3 Installer
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * @copyright 2005 Camptocamp SA
 * @package Core
 * @version $Id$
 */

/*
 * It is necessary for the error recovery procedures that the install script 
 * be idempotent. This means that if it is run successfully, and then it is 
 * called again, it doesn't bomb out or cause any harm, but just ensures 
 * that everything is the way it ought to be. If the first call failed, or 
 * aborted half way through for some reason, the second call should merely 
 * do the things that were left undone the first time, if any, and exit with 
 * a success status if everything is OK.
 */

error_reporting(E_ALL);

define('CW3_SETUP_REVISION', '$Revision$');
define('MINIMUM_REVISION', 41);
define('CW3_SETUP_INCLUDED', true);

// URL of required libraries (md5sum: 6224ec2db6815b6d9c21465b7b868a00):
define('CW3_LIBS_URL', 'http://www.cartoweb.org/downloads/cw3.3/cartoweb-includes-3.3.0.tar.gz');
// URL of demo data (md5sum: c1d654245f725ca3fa157f21423c8fc3):
define('CW3_DEMO_URL', 'http://www.cartoweb.org/downloads/cw3.3/cartoweb-demodata-3.3.0.tar.gz');

// Directories to create from cw3 root:
$CW3_DIRS_TO_CREATE = array(
                  'htdocs/generated',
                  'htdocs/generated/images',
                  'htdocs/generated/icons',
                  'htdocs/generated/pdf',

                  'www-data',
                  'www-data/mapinfo_cache',
                  'www-data/mapresult_cache',
                  'www-data/soapxml_cache',
                  'www-data/saved_posts',
                  'www-data/wsdl_cache',
                  'www-data/pdf_cache',
                  'www-data/views',
                  'www-data/accounting',
                  'www-data/wms_cache',
                  'templates_c'
                  );

// Directories which should be writable by the php process
$CW3_WRITABLE_DIRS = array('log',
                      'htdocs/generated',
                      'www-data',
                      'templates_c'
                     );


function usage() {
?>Usage: <?php echo $_SERVER['argv'][0]; ?> ACTION [OPTION_1] ... [OPTION_N]

Possible actions:

 --help, or -h              Display this help and exit.
 --version or -v            Output version information and exit.
 --install                  Install CartoWeb.
 --fetch-demo               Fetch the demo data from cartoweb.org, and extract 
                            it in the demo project if not already there.
 --clean                    Clean generated files and caches.

List of options:

 --debug                    Turn on output debugging.
 
 --writableowner OWNER      The user who should have write permissions for 
                            generated files.
 
 --cvs-root                 CVS Root directory to use when fetching 
                            CartoWeb/project out of CVS.
 --fetch-from-cvs           Fetch CartoWeb from CVS and install it in the 
                            current directory, or in the directory given by 
                            the --install-location parameter.
                            NOTE: You must be located where cartoweb3 directory
                            will be created, not inside like other commands.
 --cartoweb-cvs-option OPTIONS  A string which will be given to the cvs checkout
                            command of CartoWeb (not projects!).
                            For instance, to fetch a specific branch, 
                            use '-r MY_BRANCH'. Or for a specific date, 
                            use '-D "2005-09-05 11:00"'.
 --fetch-from-dir DIRECTORY Copy CartoWeb from the specified directory into the
                            current directory, or in the directory given by the
                            --install-location parameter.
                            NOTE 1: You must be located where cartoweb3 
                            directory will be created, not inside like other 
                            commands.
                            NOTE 2: You may either use a path relative to the 
                            target cartoweb3 directory or an absolute path.
 --install-location         Directory where to install CartoWeb 
                            (when using --fetch-from-cvs/dir options).
 
 --delete-existing          Overwrite existing directories if any.
 --no-symlinks              Do not use symbolic links, even if your operating 
                            system supports them.
 
 --config-from-file FILE    Location of a configuration file for automatic 
                            variable replacement in .in files.
                            NOTE: You may either use a path relative to the 
                            target cartoweb3 directory or an absolute path.
 --config-from-project PROJECT Read the configuration file containing variables
                            to replace in .in files from the specified project. 
 
 --fetch-project-cvs PROJECT Fetch the given project from CVS (see --cvs-root 
                            option). To fetch several projects at a time, 
                            specify this option as many times as necessary.
 --fetch-project-dir DIRECTORY Fetch the given project from a directory. To
                            fetch several projects at a time, specify this
                            option as many times as necessary.
 --project PROJECT          Installation is launched only for given project. To
                            install several projects at a time, specify this
                            option as many times as necessary.
 
 --default-project PROJECT  Default project to use.
 --base-url BASEURL         URL where you can find client.php.
 --profile PROFILENAME      The profile to use (development/production/custom).
                            NOTE: default is 'production'
 --clean-views              Clean views (must be used with --clean).
 --clean-accounting         Clean accounting (must be used with --clean).
 
<?php
    exit();
}

class InstallException extends Exception {
}

/**
 * We need "." in the include path, otherwise parse_ini_file won't work
 */
function checkIncludePath() {
    $includePath = get_include_path();
    if (!in_array('.', explode(PATH_SEPARATOR, $includePath))) {
        set_include_path(get_include_path() . PATH_SEPARATOR . '.' . PATH_SEPARATOR . '..');
    }
}

/**
 * register_argc_argv must be set to "on" in php.ini
 */
function checkRegisterArgcArgv() {
    if (!ini_get('register_argc_argv')) {
        die("Parameter register_argc_argv must be set to On in your php.ini\n");
    }
}

checkIncludePath();
checkRegisterArgcArgv();

function setOption(&$i, $takesArgument = false) {
    global $OPTIONS;
    $option = substr($_SERVER['argv'][$i], 2);
    
    $argument = true;
    if ($takesArgument) {
        $i++;
        if (!isset($_SERVER['argv'][$i]) || 
                substr($_SERVER['argv'][$i], 0, 2) == '--') {
            fail("Missing argument for option $option");
            exit(-1);   
        }
        $argument = $_SERVER['argv'][$i];
    }
    
    $OPTIONS[$option] = $argument;
}


$OPTIONS['debug'] = false;
$OPTIONS['test'] = false;
$OPTIONS['force'] = "";

$OPTIONS['writableowner'] = 'www-data';

define('ACTION_NOP', 0);
define('ACTION_INSTALL', 1);
define('ACTION_CLEAN', 2);
define('ACTION_FETCH_DEMO', 3);
define('ACTION_PREPARE_ARCHIVE', 4);

define('LOG_LEVEL_DEBUG', 0);
define('LOG_LEVEL_INFO', 1);
define('LOG_LEVEL_WARN', 2);
define('LOG_LEVEL_FAIL', 3);

/* default log level is debug so that po2mo and others show messages */
$logLevel = LOG_LEVEL_DEBUG;

function processArgs() {
    
    global $OPTIONS;
    global $logLevel;
    $action = ACTION_NOP;

    // default log level
    $logLevel = LOG_LEVEL_INFO;
    
    //loop through our arguments and see what the user selected
    for ($i = 1; $i < $_SERVER['argc']; $i++) {
        switch ($_SERVER['argv'][$i]) {
            case '-v':
            case '--version':
               info($_SERVER['argv'][0] . ' ' . CW3_SETUP_REVISION);
               exit;
               break;
    
            case '--delete-existing':
            case '--fetch-from-cvs':
            case '--no-symlinks':
            case '--clean-views':
            case '--clean-accounting':
            case '--with-demo':
                setOption($i);
                break;
    
            case '--cvs-root':
            case '--cartoweb-cvs-option':
            case '--config-from-file':
            case '--install-location':
            case '--fetch-from-dir':
            case '--config-from-project':
            case '--default-project':
            case '--base-url':
            case '--profile':
                setOption($i, true);
                break;      
    
            case '--fetch-project-cvs':
            case '--fetch-project-dir':
            case '--project':
                $option = substr($_SERVER['argv'][$i], 2);
                $oldOptions = array();
                if (isset($OPTIONS[$option])) {
                    $oldOptions[$option] = $OPTIONS[$option];
                } else {
                    $oldOptions[$option] = array();
                }
                setOption($i, true);
                array_push($oldOptions[$option], $OPTIONS[$option]);
                $OPTIONS[$option] = $oldOptions[$option];
                break;

            case '--debug':
                $logLevel = LOG_LEVEL_DEBUG;
                break;
                
            case '--clean':
                $action = ACTION_CLEAN;
                break;
    
            case '--fetch-demo':
                $action = ACTION_FETCH_DEMO;
                break;

            case '--prepare-archive':
                $action = ACTION_PREPARE_ARCHIVE;
                break;

            case '--install':
                $action = ACTION_INSTALL;
                break;
                
            case '-h':
            case '--help':
                usage();
                break;
            
            default:
                throw new InstallException('Unknown option ' . 
                    $_SERVER['argv'][$i] . " \nUse --help for usage");
        }
    }
    
    debug('Installer version ' . CW3_SETUP_REVISION . (isWin32() ? ' win32' : ''));
    
    switch ($action) {
        case ACTION_NOP:
            info('No action given, doing nothing. Use --help to see usage');
            exit(0);
            break;
        case ACTION_INSTALL:
            info('installing');
            
            fetchCartoWeb();

            // sanity check
            if (!file_exists('common'))
                throw new InstallException('Looks like we are not inside a cartoweb3 directory');

            removeDevFilesIfProd();
            fetchProjects();
            fetchLibs();
            makeDirs();
            setPermissions();
            createConfig();
            setupLinks();
            replaceDotIn();
            init();
            removeInstallWarning();

            info('Installation finished...');
        
            break;
        case ACTION_CLEAN:
            cleanFiles();
            break;
        case ACTION_FETCH_DEMO:
            info('Fetching demo');
            fetchDemo();
            info('Demo data installed');
            // launch init() for running po2mo
            init();
            break;
        case ACTION_PREPARE_ARCHIVE:
            fetchLibs();
            if (isset($OPTIONS['with-demo']))
                fetchDemo();
            break;
        default:
            fail('Should not happen');
            exit(-1);
    }

}

if (strpos($_SERVER['argv'][0], 'cw3setup.php') !== false) {
    try {        
        processArgs();
    } catch (InstallException $e) {
        showFailure($e);
    }
}

function logMessage($level, $message) {
    global $logLevel;
    if ($level >= $logLevel) {
        print "$message\n";   
    }   
}

function debug($message) {
    logMessage(LOG_LEVEL_DEBUG, $message);   
}

function info($message) {
    logMessage(LOG_LEVEL_INFO, $message);   
}

function warn($message) {
    logMessage(LOG_LEVEL_WARN, $message);   
}

function fail($message) {
    logMessage(LOG_LEVEL_FAIL, $message);   
}

function showFailure(InstallException $exception) {

    fail("\n Error during installation:");
    fail(" ==========================\n");
    fail('The installation process encountered an error and was aborted.');
    fail('See the message below for an explanation of the problem.');
    fail('If you want more information to find out the problem, try again with the --debug parameter.');
    fail('If you think you found a bug in the installer or want support,');
    fail('mail cartoweb-users@lists.maptools.org with the full output');
    fail('of the installer launched with the --debug parameter.');
    
    fail("\n\nError message: {$exception->getMessage()}\n");   
    fail('Installation aborted');   
    exit(-1);
}

/**************************************************************/
/* Utility functions */
/**************************************************************/

/**
 * Recursively remove
 */
function rmdirr($dir) {
    $dh = @opendir($dir);
    if (!$dh)
        return false;
    while ($file = readdir($dh)) {
        if ($file == '.' || $file == '..') 
            continue;
        $fullpath = $dir . '/' . $file;

        if (!is_dir($fullpath) || is_link($fullpath))
            unlink($fullpath);
        else
            rmdirr($fullpath);
    }
    closedir($dh);
    if (@rmdir($dir))
        return true;
    else
        return false;
}

/**
 * rmdirr() if target exists
 */
function rmdirrIfExists($target) {
    if (file_exists($target)) {
        rmdirr($target);
    }
}

/**
 *  Recursive copy
 */
function copyr($source, $dest) {
    //debug("rec copy $source --> $dest");

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
    if (!$dir) 
        return false;
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

/**
 * Throws an exception if copyr() failed
 */
function tryToCopyr($source, $target) {
    if (!copyr($source, $target)) {
        throw new InstallException("Failed copying $source => $target");
    }
}

/**
 * Crawls recursively a directory, and calls a callback function for each files/directories.
 */
function crawl($dir, $function, $context=null) {
    if (!$dir)
        return false;

    $function($dir, $context);

    $dh = @opendir($dir);
    if (!$dh)
        return false;
    while ($file = readdir($dh)) {
        if ($file == '.' || $file == '..')
            continue;
        $fullpath = "$dir/$file";

        //$function($fullpath, $context);

        if (is_dir($fullpath)) {
            crawl($fullpath, $function, $context);
        } else {
            //print "handling file $fullpath \n";
            $function($fullpath, $context);
        }
    }
    closedir($dh);
}

/**************************************************************/
/**************************************************************/


function getCvsRoot() {

    global $OPTIONS;
    
    if (isset($OPTIONS['cvs-root']))
        return $OPTIONS['cvs-root'];
    
    return ':pserver:anonymous@dev.camptocamp.com:/var/lib/cvs/public';
}

function execWrapper($command, $quiet=false) {

    $output = '';
    $status = 0;

    exec("$command 2>&1", $output, $status);
    $output = implode("\n", $output);
    if ($status)
        throw new InstallException("Failure while launching \"$command\" (output is $output)");
    
    if (!$quiet)
        debug($output);
    
}

function removeDirectory($directory) {

    global $OPTIONS;

    if (!file_exists($directory))
        return;
           
    if (!isset($OPTIONS['delete-existing'])) {
        throw new InstallException("Directory $directory already exists and " .
                "should be overwritten. This script will not overwrite it " .
                "unless you provide the --delete-existing option.");   
    }
    info("removing directory $directory recursively");
    rmdirr($directory);
}

/**
 * Compare this script cvs revision, and the one from the just fetched CartoWeb, and tell the user
 *  to update of CartoWeb one is more recent.
 */
function checkCw3setupVersion() {

    if (isset($_ENV['CW3_NO_VERSION_CHECK']))
        return;

    $content = file_get_contents('cartoweb3/cw3setup.php');
    $revision_pattern = '/\$Rev.sion: ([\.\d]+) \$/';
    preg_match($revision_pattern, $content, $matches);
    if (!isset($matches[1]))
        die("Unable to find Revision in cartoweb3/cw3setup.php\n");

    // FIXME: This cvs revision comparison alghorithm is broken with branches

    $cvs_revision = (int)(substr($matches[1], 2));
    preg_match($revision_pattern, CW3_SETUP_REVISION, $matches);
    $this_revision = (int)(substr($matches[1], 2));
    debug("Installer revision of fetched cartoweb: $cvs_revision");
    debug("Revision of this installer: $this_revision");
    if (defined('MINIMUM_REVISION')) {
        if ($cvs_revision < MINIMUM_REVISION) 
            throw new InstallException('The version of CartoWeb you have just ' .
                    'retrieved is not compatible with the installer.');
    }
    
    if ($cvs_revision > $this_revision)
        throw new InstallException('The version of cw3setup.php that has just been ' .
                'installed is more recent that the one you are currently running. ' .
                'You MUST update cw3setup.php and try again.');
}

function fetchCartoWeb() {

    global $OPTIONS;

    if (!isset($OPTIONS['fetch-from-cvs']) && !isset($OPTIONS['fetch-from-dir']))
        return;

    if (isset($OPTIONS['install-location'])) {
        $installLocation = $OPTIONS['install-location'];
        if (!file_exists($installLocation))
            throw new InstallException("install-location \"$installLocation\" is not accessible");
        chdir($installLocation);
    }
    
    removeDirectory('cartoweb3');

    if (isset($OPTIONS['fetch-from-cvs'])) {
    
        info('fetching cartoweb from cvs');

        $coOptions = '';
        if (isset($OPTIONS['cartoweb-cvs-option'])) {
            $coOptions = $OPTIONS['cartoweb-cvs-option'];
        }
        $cvsRoot = getCvsRoot();
        if (!hasCommand('cvs --version'))
            throw new InstallException("You need to install the cvs command " .
                    "to be able to fetch CartoWeb from cvs");

        execWrapper("cvs -d $cvsRoot co $coOptions cartoweb3 2>&1");
       
    } else if (isset($OPTIONS['fetch-from-dir'])) {
    
        $sourcePath = $OPTIONS['fetch-from-dir'];
        if (!file_exists($sourcePath))
            throw new InstallException("Source directory $sourcePath not found. " .
                    "Warning: paths are relative to cartoweb3 target directory");
        info("Copying cartoweb from $sourcePath to cartoweb3");
        copyr($sourcePath, 'cartoweb3');
    } else {
        throw new InstallException('Unhandled fetch type');   
    }

    checkCw3setupVersion();

    // if we were fetching cartoweb, get inside
    if (file_exists('cartoweb3'))
        chdir('cartoweb3');
        
}

function fetchProjects() {

    /* The two types of project CVS layout:
     * type 1) [cvs module]projectname/cartoweb3/projects/projectname/
     * type 2) [cvs module]projectname/
     */

    global $OPTIONS;
    if (isset($OPTIONS['fetch-project-cvs'])) {
    
        foreach($OPTIONS['fetch-project-cvs'] as $project) {
            info("Fetching project $project from CVS");
            removeDirectory("projects/$project");

            // TODO: make this win32 portable
            if (isWin32())
                throw new InstallException('Sorry, project fetching from CVS is not supported on Win32 yet');

            $cvsRoot = getCvsRoot();
            execWrapper("cd projects ; cvs -d $cvsRoot co $project 2>&1");

            if (file_exists("projects/$project/cartoweb3")) {
                // type 1
                execWrapper("mv projects/$project projects/{$project}.tmp");
                execWrapper("mv projects/{$project}.tmp/cartoweb3/projects/$project projects");
                rmdirr("projects/{$project}.tmp");
            }
        }
    }

    if (isset($OPTIONS['fetch-project-dir'])) {
    
        foreach($OPTIONS['fetch-project-dir'] as $directory) {
            info("Fetching project from directory $directory");
            
            $project = basename($directory);
            
            removeDirectory("projects/$project");

            if (!file_exists($directory))
                throw new InstallException("Source directory $directory not found");
            info("Copying project from $directory to cartoweb3/projects");

            if (file_exists("$directory/cartoweb3")) 
                // type 1
                copyr("$directory/cartoweb3/projects/$project", "projects/$project");
            else 
                // type 2
                copyr("$directory", "projects/$project");

        }
    }
    
    // launch project deploy script
    $projects = getRequestedProjects();
    if (empty($projects)) {
        $projects = cw3setupGetProjects('projects');
    }
    foreach ($projects as $project) {
        if (is_file("projects/$project/deployment/install.php")) {
            $_ENV['project'] = $project;
            info("Lanching project $project install script");
            include("projects/$project/deployment/install.php");
        }
    }

    if (isset($OPTIONS['default-project'])) {
        setDefaultProject($OPTIONS['default-project']);
    }
    
    // Broken compatibity warning
    foreach ($projects as $project) {
        if (is_file("projects/$project/deployment/cw3_cvs_pin.txt")) {
            throw new InstallException("Using the cw3_cvs_pin.txt is deprecated, " .
                    "you now have to use the --cartoweb-cvs-option" .
                    " parameter to have the same effect\n. You have to remove" .
                    "this file from the project to avoid this failure.");
        }
    }
}

function hasCommand($command) {

    try {
        execWrapper("$command", true);
    } catch (Exception $e) {
        return false;
    }
    return true;
}

function fetchArchive($archiveUrl, $targetDirectory) {
    
    $versionRegexp = '/-([^-]*).tar.gz$/';
    if (preg_match($versionRegexp, $archiveUrl, $matches) == 1) {
        $version = $matches[1];
        debug("Archive upstream version: $version");
    }
    
    $versionFile = "$targetDirectory/version";
    if ($version && file_exists($versionFile)) {
        $actualVersion = file_get_contents($versionFile);
        $actualVersion = trim($actualVersion);
        debug("actual version: $actualVersion");
        if ($actualVersion == $version) {
            debug("Archive version match, skipping");
            return;
        } else {
            // TODO: remove old archive files
            // rmdirr(archive_output_directory);   
        }
    }
    
    $destFile = dirname($targetDirectory) . "/archive_tmp";

    if (hasCommand('tar --help')) {
        $destFile .= '.tar.gz';
        $extractCmd = "tar xzf " . basename($destFile);
    } else if (hasCommand('unzip')) {
        $archiveUrl = str_replace('.tar.gz', '.zip', $archiveUrl);
        $destFile .= '.zip';
        $extractCmd = "unzip " . basename($destFile);
    } else {
        throw new InstallException("Can't find a program to extract the include files. " .
                "Install tar or unzip, and be sure it is on your path");
    }
    
    debug("Dest file $destFile");
    
    info("Fetching archive file from $archiveUrl, this may take some time...");
    if (extension_loaded('curl')) {
        debug('Fetching archive using curl extension');
        $ch = curl_init($archiveUrl);
        $fp = fopen($destFile, "wb");
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    } else {
        debug('Fetching archive with file_get_contents');
        $cnt = file_get_contents($archiveUrl);
        if ($cnt === false)
            throw new InstallException('Unable to retrieve the archive at ' . $archiveUrl);
        $fd = fopen($destFile, "wb");
        fwrite($fd, $cnt);
        fclose($fd);
    }
    
    if (!file_exists($destFile))
        throw new InstallException('Unable to retrieve the archive at ' . $archiveUrl);
    
    debug('Extracting archive content');
    
    $oldPwd = getcwd();
    chdir(dirname($targetDirectory));
    execWrapper($extractCmd);
    chdir($oldPwd);
    @unlink($destFile);
    file_put_contents($versionFile, $version);
    
}

function fetchLibs() {

    fetchArchive(CW3_LIBS_URL, 'include');
}

function fetchDemo() {

    fetchArchive(CW3_DEMO_URL, 'demodata');

    // Installing demoCW3 data
    $source = 'demodata/demoCW3/data';
    $target = 'projects/demoCW3/server_conf/demoCW3/data';
    rmdirrIfExists($target);
    tryToCopyr($source, $target);

    // Installing demoPlugins data
    $source = 'projects/demoCW3/server_conf/demoCW3';
    $target = 'projects/demoPlugins/server_conf/demoPlugins';
    rmdirrIfExists($target);
    tryToCopyr($source, $target);
    
    rename('projects/demoPlugins/server_conf/demoPlugins/demoCW3.map.in',
           'projects/demoPlugins/server_conf/demoPlugins/demoPlugins.map.in');

    // Installing demoGeostat data
    $source = 'demodata/demoGeostat/data';
    $target = 'projects/demoGeostat/server_conf/demoGeostat/data';
    rmdirrIfExists($target);
    tryToCopyr($source, $target);

    // Removing temporary directory
    rmdirr('demodata');
}

function removeDevFilesIfProd() {
    global $OPTIONS;
    
    if (isset($OPTIONS['profile']) && $OPTIONS['profile'] != 'production') {
        return;
    }
    
    $filesToRemove = array('htdocs/info.php');
    foreach ($filesToRemove as $file) {
        if (!is_file($file) && !is_link($file)) {
            continue;
        }
        
        if (unlink($file)) {
            info("Removing $file");
        } else {
            info("Failed to removed $file");
        }
    }
}

function removeInstallWarning() {
    $indexFile = 'htdocs/index.html';
    $bakFile   = 'htdocs/index.html.bak';

    if (!is_file($bakFile)) {
        return;
    }

    if (!is_file($indexFile) || !unlink($indexFile)) {
        info('Failed to removed installation warning');
    }

    if (!rename($bakFile, $indexFile)) {
        info('Failed to rename standard home file');
    }
}

function makeDirs() {
    global $CW3_DIRS_TO_CREATE;

    info('Creating directories');
    foreach($CW3_DIRS_TO_CREATE as $dir) {
        @mkdir($dir);
        debug("Created $dir");
    }
}


function setPermissions() {
    global $CW3_WRITABLE_DIRS;

    info("Setting permissions");
    // todo, for win32, using cacls, BUT ONLY FOR NTFS
    foreach($CW3_WRITABLE_DIRS as $dir) {
        crawl($dir, 'setPermissionsCallback');
    }
}

function fileIgnored($file) {
    return in_array($file, array('..', '.', 'CVS', '.cvsignore'));
}

function setPermissionsCallback($file, $context) {

    global $OPTIONS;

    if (!is_dir($file) || fileIgnored(basename($file)))
        return;
        
    debug("Handling dir $file");
    $writableOwner = $OPTIONS['writableowner'];

    if (@chown($file, $writableOwner))
        debug("\"$file\" is now owned by $writableOwner");
    else if (@chmod($file, 0777)) 
        debug("\"$file\" is now writable by everybody, including $writableOwner");
    else
        info("WARNING: unable to set permissions on \"$file\"");   
}

function createConfigCallback($file, $context) {
    
    if (is_dir($file) || fileIgnored(basename($file)))
        return;

    if (substr($file, strlen($file) - 5) == '.dist') {
        $target = substr($file, 0, strlen($file) - 5);
        if (file_exists($target)) {
            debug("Target config file $target already exists, skipping");
            return; 
        }
        debug("copying $file to $target");
        copy($file, $target);
    }
}

function createConfig() {
    
    info("Copying .ini.dist files into .ini (if not existing)");
    
    crawl('client_conf', 'createConfigCallback');
    crawl('server_conf', 'createConfigCallback');
    
    // In case script is launched from outside of 
    // cartoweb3 directory (full install):
    crawl('cartoweb3/client_conf', 'createConfigCallback');
    crawl('cartoweb3/server_conf', 'createConfigCallback');
}

/**
 * Get the list of projects in directory $dir
 *
 * FIXME: should use a common utility method
 */
function cw3setupGetProjects($dir) {
    $dh = @opendir($dir);
    if (!$dh)
        return false;
    $projects = array();
    while ($file = readdir($dh)) {
        if (!fileIgnored($file)) {
            $projects[] = $file;
        }
    }
    closedir($dh);
    return $projects;
}

function isWin32() {

    return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}

function useSymlinks() {
    global $OPTIONS;

    if (isWin32())
        return false;
    
    return !isset($OPTIONS['no-symlinks']);
}

/**
 * Link or copy a file or directory
 */
function linkOrCopy($src, $dest) {

    // $src is a relative path from $dest. 
    // This computes the absolute equivalent path of $src
    $destDir = dirname($dest);
    $absSrc = getcwd() . '/' . dirname($dest) . '/' . $src;
    $absSrc = realpath($absSrc);
    if (!$absSrc) {
        debug("Can't find target path from source: $src and dest $dest");
        return;
    }

    if (is_link($dest)) {
        debug("Removing previous symlink on $dest");
        unlink($dest);
    }
    if (file_exists($dest)) {
        debug("target $dest already exists");
        if (is_link($dest)) {
            unlink($dest);
        } else if (is_dir($dest)) {
            debug("Assuming target directory $dest was copied previously, removing!!");
            rmdirr($dest);
        } else if (is_file($dest)) {
            unlink($dest);
        } else {
            throw new InstallException("Target $dest already there and is of type " . filetype($dest));
        }
    } 

    if (useSymlinks()) {

        debug("linking \"$src\" to \"$dest\"");

        if (!symlink($src, $dest))
            throw new InstallException("link failure");

    } else {

        if (copyr($absSrc, $dest))
            $result = "\"$src\" copied to \"$dest\"\n";
    }
}


/**
 * Setup symlinks or copy files for projects, plugins, coreplugins:
 */
function setupLinks() {

    info('Copying/linking resources into htdocs');
    if (!is_dir('htdocs/gfx/icons'))
        mkdir('htdocs/gfx/icons');

    $pList = getRequestedProjects();
    if (empty($pList)) {
        $pList = cw3setupGetProjects('projects');
    }
    foreach($pList as $project) {
        @mkdir("htdocs/gfx/icons/$project");
        $mList = cw3setupGetProjects("projects/$project/server_conf");
        if (!$mList)
            continue;
        foreach($mList as $mapfolder) {
             linkOrCopy("../../../../projects/$project/server_conf/$mapfolder/icons/", 
                "htdocs/gfx/icons/$project/$mapfolder");
        }
    }

    // Create symlinks to po directories
    if (!is_dir('htdocs/po'))
        mkdir('htdocs/po');
    
    foreach($pList as $project) {
        linkOrCopy("../../projects/$project/po/", "htdocs/po/$project");
    }
    
    // special case for main po files
    linkOrCopy('../../po/', 'htdocs/po/default');    

    $projdirs =  array('projects', 'plugins', 'coreplugins');
    foreach($projdirs as $dir) {
        
        if ($dir == 'projects') {
            $pList = getRequestedProjects();
            if (empty($pList)) {
                $pList = cw3setupGetProjects('projects');
            }
        } else {
            $pList = cw3setupGetProjects($dir);
        }

        foreach($pList as $project) {
            if (!is_dir("./htdocs/$project"))
                @mkdir("htdocs/$project");
            $d = @opendir("$dir/$project/htdocs");
            if ($d) {
                while ($file=readdir($d)) {
                        if (!fileIgnored($file)) {
                        // symlink htdocs elements from projects to core
                        linkOrCopy("../../$dir/$project/htdocs/$file", 
                                    "htdocs/$project/$file");
                    }
                }
            }
            $plugdirs = array('plugins', 'coreplugins');
            foreach($plugdirs as $pdir) {
                $pd = @opendir("$dir/$project/$pdir");
                if ($pd)  {
                    while ($pfile = readdir($pd)) {
                        if (!fileIgnored($pfile)) {
                            // symlink plugins and coreplugins htdocs elements from projects
                            linkOrCopy("../../$dir/$project/$pdir/$pfile/htdocs/", 
                                        "htdocs/$project/$pfile");
                        }
                    }
                }
            }
        }
    }
}

function init() {

    $projects = getRequestedProjects();
    $pList = !empty($projects) ? implode(', ', $projects) : 'all';

    info("Launching makemaps script for projects: $pList");
    include('scripts/makemaps.php');

    // FIXME: makemaps needs a soft_clean afterwards (do it, or fix makemaps).
    if (!hasCommand('msgfmt --help') || !hasCommand('msgcat')) {
        warn('Warning: Gettext command msgfmt or msgcat was not found: translations won\'t work');
        warn('If you want to use internationalisation, be sure to have gettext installed');
        warn('See http://www.gnu.org/software/gettext/ (for Windows, ' .
                'see http://gettext.sourceforge.net/)');
    } else {
        info("Launching po2mo script for projects: $pList");
        include('scripts/po2mo.php');
    }
}

function replaceDotInCallback($file, $context) {

    if (is_dir($file) || fileIgnored(basename($file)))
        return;

    if (!(substr($file, strlen($file) - strlen('.in')) == '.in'))
        return;

    debug("Handling $file");
    
    $target_filename = substr($file, 0, strlen($file) - strlen('.in'));
    if (file_exists($target_filename)) {
        debug("Target $target_filename already exists, it is deleted");
        unlink($target_filename);
    }
    
    $content = file_get_contents($file);
    
    $new_content = str_replace($context['search'], $context['replace'], $content);
    file_put_contents($target_filename, $new_content);    
}

/**
 * Try:
 * 1 config_HOST_PATH.properties
 *    where HOST = $(cat /etc/hostname)
 *          PATH = $(pwd | sed s:/:_:g)
 * 2 config_HOST.properties
 * 3 config.properties
 *
 * @return The name of the project config file
 */
function getProjectConfig($basePath) {
 
    // XXX portability!!
 
    $trySuffixes = array();

    // We try /etc/hostname to make distinction with chroots 
    if (file_exists('/etc/hostname')) {
        $hostname = file_get_contents('/etc/hostname');
    } else {
        $hostname = php_uname('n');
    }
    $hostname = str_replace("\n", "", $hostname);

    $path = dirname(__FILE__);
    $path = substr($path, 1);
    $path = str_replace('/', '_', $path);
    $path_bis = str_replace('_cartoweb3', '', $path);

    $trySuffixes[] = "_{$hostname}_{$path}";
    $trySuffixes[] = "_{$hostname}_{$path_bis}";
    $trySuffixes[] = "_{$hostname}";
    $trySuffixes[] = "";
 
    $triedPaths = '';
    foreach($trySuffixes as $suffix) {
        $configFile = "$basePath/config{$suffix}.properties";
        $triedPaths .= "$configFile\n";
        debug("Trying project config file: $configFile");
        if (file_exists($configFile)) {
            return $configFile;
        }
    }
    throw new InstallException(sprintf("Can't find project config file. It " .
                        "should be in one of the path (tried in order):\n\n%s",
                         $triedPaths));
}

function setDefaultProject($project) {

    file_put_contents('current_project.txt', $project);
}

function getSearchReplaceContext() {

    global $OPTIONS;

    $vars = array();

    $configFile = null;
    if (isset($OPTIONS['config-from-file'])) {
        $configFile = $OPTIONS['config-from-file'];
        if (!is_readable($configFile))
            throw new InstallException("Can't access configfile: $configFile." .
                    "Warning: paths are relative to cartoweb3 target directory");

    } else if (isset($OPTIONS['config-from-project'])) {
        $project = $OPTIONS['config-from-project'];
    
        $configFile = getProjectConfig("projects/$project/deployment");
    }
    if ($configFile) {
        $ini = parse_ini_file($configFile);

        $vars = array_merge($vars, $ini);
    }

    $vars['BLURB'] = '!!!Do not edit this file, it is generated. Edit the .in instead!!!';
    // special handling for demo config
    if (!isset($vars['ROUTING_PLUGINS']))
        $vars['ROUTING_PLUGINS'] = '';

    // allow options in environment variable "CW3_VARS"
    if (isset($_ENV['CW3_VARS'])) {
        $envVars = explode(';', $_ENV['CW3_VARS']);
        foreach($envVars as $v) {
            if (strpos($v, '=') === false)
                continue;
            list($key, $value) = explode('=', $v);
            $vars[$key] = $value;
        }
    }

    if (!isset($vars['CARTOCLIENT_BASE_URL'])) {

        if (!isset($OPTIONS['base-url']))
            throw new InstallException('You need to specify the --base-url URL parameter. ' .
                    'It corresponds to the URL where you can find client.php');
        $vars['CARTOCLIENT_BASE_URL'] = $OPTIONS['base-url'];
    }
    
    if (isset($OPTIONS['profile']))
        $vars['PROFILE'] = $OPTIONS['profile'];
    if (!isset($vars['PROFILE']))
        $vars['PROFILE'] = 'production';
    if (!isset($OPTIONS['profile']))
        $OPTIONS['profile'] = $vars['PROFILE'];

    $newVars = array();
    foreach($vars as $key => $value) {
        $newVars["@{$key}@"] = $value;
    }
    $vars = $newVars;

    $context = array();
    $context['search'] = array_keys($vars);
    $context['replace'] = array_values($vars); 
    return $context;
}

function getRequestedProjects() {
    global $OPTIONS;

    if (!empty($OPTIONS['fetch-project-cvs'])) {
        return $OPTIONS['fetch-project-cvs'];
    }

    if (!empty($OPTIONS['fetch-project-dir'])) {
        return $OPTIONS['fetch-project-dir'];
    }

    if (!empty($OPTIONS['config-from-project'])) {
        return array($OPTIONS['config-from-project']);
    }

    if (!empty($OPTIONS['project'])) {
        return $OPTIONS['project'];
    }

    return array();
}

function replaceDotIn() {
 
    $context = getSearchReplaceContext();
    $projects = getRequestedProjects();
    if ($projects) {
        foreach ($projects as $project) {
            crawl("projects/$project", 'replaceDotInCallback', $context);
        }
        $processed = implode(', ', $projects);
    } else {
        crawl('projects', 'replaceDotInCallback', $context);
        $processed = 'all';
    }
    info("Copied <files>.in into <files> for projects: $processed");
}

function deleteFilesCallback($file, $context) {

    if (is_dir($file))
        return;
    debug("Removing $file");
    if (!unlink($file)) {
        throw InstallException("Unable to remove file $file");
    }
}

function cleanFiles() {
    global $CW3_DIRS_TO_CREATE;
    global $OPTIONS;

    info('Removing generated files');

    if (!isset($OPTIONS['clean-views'])) {
        @rmdirr('views');
        if (is_dir('www-data/views'))
            rename('www-data/views', 'views');
    }
    if (!isset($OPTIONS['clean-accounting'])) {
        @rmdirr('accounting');
        if (is_dir('www-data/accounting'))
            rename('www-data/accounting', 'accounting');
    }

    foreach($CW3_DIRS_TO_CREATE as $dir) {
        debug("checking $dir");
        if (is_dir($dir)) {
            debug("removing $dir");
            rmdirr($dir);
            /* FIXME: strange behaviour, uncomment if fixed
            if (!rmdirr($dir)) {
                throw new InstallException("Failed to remove recursively $dir");
            }
            */
        }
    }

    makeDirs();
    setPermissions();

    if (!isset($OPTIONS['clean-views'])) {
        if (is_dir('views'))
            rename('views', 'www-data/views');
    }
    if (!isset($OPTIONS['clean-accounting'])) {
        if (is_dir('accounting'))
            rename('accounting', 'www-data/accounting');
    }
}

?>
