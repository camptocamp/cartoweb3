<head>
<style type="text/css">

#failure {
     background-color:red; 
     color: white; 
     font-size: xx-large; 
     height: 300; 
     text-align: center;
}

#success {
     background-color:green; 
     color: white; 
     font-size: xx-large; 
     height: 300; 
     text-align: center;
}

#warning {
     background-color:yellow; 
     color: black; 
     font-size: xx-large; 
     height: 100; 
     text-align: center;
}

</style>
</head>
<pre>
<?php 
/**
 * Web unit tests runner 
 * 
 * To run tests, you will need to set variable allowTests to true in client.ini
 * or server.ini (when tests are run on a server-only environement).
 * @package Htdocs
 * @version $Id$
 */

/**
 * Dir path to common home
 */
define('CARTOCOMMON_HOME', realpath(dirname(__FILE__) . '/..') . '/');

/**
 * Dir path to Cartoclient home
 */
define('CARTOCLIENT_HOME', realpath(dirname(__FILE__) . '/..') . '/');

/**
 * Dir path to Cartoserver home
 */
define('CARTOSERVER_HOME', realpath(dirname(__FILE__) . '/..') . '/');

$iniFile = CARTOCLIENT_HOME . 'client_conf/client.ini';
if (!file_exists($iniFile)) {
    $iniFile = CARTOSERVER_HOME . 'server_conf/server.ini';
}
$iniArray = parse_ini_file($iniFile);
if (!array_key_exists('allowTests', $iniArray) || !$iniArray['allowTests']) {
    
    echo "<div id='failure'>PERMISSION DENIED</div>";
    exit;
}

require_once (CARTOCOMMON_HOME . 'tests/CartowebTestRunner.php');

// This global tell the cartoclient not to output header or start sesssion. 
//  Otherwise it would cause problems because of already sent output.
$GLOBALS['headless']=true;
 
$testRunner = new CartowebTestRunner();

if (!empty($_REQUEST['testsuite']))
    $testSuite = $_REQUEST['testsuite'];
else
    $testSuite = 'AllTests';

$testRunner->runTests($testSuite);

$success = $testRunner->wasSuccessful();

if ($success)
    echo "<div id='success'>SUCCESS</div>";
else
    echo "<div id='failure'>FAILURE</div>";

$errors = $testRunner->getErrors();
if (!empty($errors)) {
    echo "<div id='warning'>Warning: There were notices:</div>";
    foreach ($errors as $error) {
        echo "$error\n";
    }
}
?>