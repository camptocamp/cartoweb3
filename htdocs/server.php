<?php
/**
 * @package Htdocs
 * @version $Id$
 */

/**
 * Root directory for server scripts
 */
define('CARTOSERVER_HOME', realpath(dirname(__FILE__) . '/..') . '/');

set_include_path(get_include_path() . PATH_SEPARATOR . 
                 CARTOSERVER_HOME . 'include/');

require_once(CARTOSERVER_HOME . 'server/Cartoserver.php');
require_once(CARTOSERVER_HOME . 'server/SoapXMLCache.php');

// APD trace
if (file_exists(CARTOSERVER_HOME . 'server/trace.apd')) {
    apd_set_pprof_trace();
}

function getSavedPostDir() {
    return CARTOSERVER_HOME . 'www-data/saved_posts/';
}

function savePostData($postData) {
    $log =& LoggerManager::getLogger(__METHOD__);

    $post_id = substr(md5($postData), 0, 5);
    
    $post_file = getSavedPostDir() . $post_id;

    $fp = @fopen($post_file,'w');
    if ($fp) {
        fwrite($fp, $postData);
        fclose($fp);
    } 

    $log->info("post Data id saved is " . $post_id);
    return $post_id;
}

function getPostData($postId) {
    $post_file = getSavedPostDir() . $postId;

    $fd = fopen($post_file, "r");
    if ($fd) 
        return fread($fd, filesize($post_file));
    return NULL;
}

if (array_key_exists('save_posts', $_GET)) {
    if (empty($HTTP_RAW_POST_DATA))
        return;

    $post_id = savePostData($HTTP_RAW_POST_DATA);
    $GLOBALS['saved_post_id'] = $post_id;
}

if (array_key_exists('restore', $_GET)) {
    $HTTP_RAW_POST_DATA = getPostData($_GET['restore']);
}
if (array_key_exists('RESTORE_POST', $_ENV)) {
    $HTTP_RAW_POST_DATA = getPostData($_ENV['RESTORE_POST']);
}

if (empty($HTTP_RAW_POST_DATA))
    return;
$cache = new SoapXMLCache();
$cache->printSoapXML($HTTP_RAW_POST_DATA);

?>