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

define('CARTOCOMMON_HOME', realpath(dirname(__FILE__) . '/..') . '/');

require_once (CARTOCOMMON_HOME . 'tests/CartowebTestRunner.php');

$testRunner = new CartowebTestRunner();

if (!empty($_REQUEST['testsuite']))
    $testSuite = $_REQUEST['testsuite'];
else
    $testSuite = 'AllTests';

$testRunner->runTests($testSuite);

$success = $testRunner->wasSuccessful();

if ($success)
    echo "<div id='success'>SUCCESS:</div>";
else
    echo "<div id='failure'>FAILURE:</div>";

$errors = $testRunner->getErrors();
if (!empty($errors)) {
    echo "<div id='warning'>Warning: There were notices:</div>";
    foreach ($errors as $error) {
        echo "$error\n";
    }
}
?>