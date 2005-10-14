<?php
/**
 * @version $Id$
 */

$_ENV['CW3_PROJECT'] = 'demoRouting';

if (!defined('CARTOCLIENT_HOME'))
    define('CARTOCLIENT_HOME', realpath(dirname(__FILE__) .
                                        '/../../../../..') . '/');

if (!defined('CARTOCOMMON_HOME'))
    define('CARTOCOMMON_HOME', CARTOCLIENT_HOME);
    
require_once(CARTOCOMMON_HOME . 'common/Common.php');
Common::preInitializeCartoweb(array('client' => true));

require_once(CARTOCLIENT_HOME . 'client/Cartoclient.php');

$cartoclient = new Cartoclient();
if (!$cartoclient->clientAllowed()) {
    return;
}

$plugin = $cartoclient->getPluginManager()->getCurrentPlugin();
if (isset($_POST['searchpost'])) {
    $plugin->handleHttpPostRequest($_REQUEST);
} else {
    //$plugin->setOutOfCw(true);
    $plugin->handleHttpGetRequest($_REQUEST);
}

/*header('Content-Type: text/html');*/
print $plugin->drawSearchFrame();

?>
