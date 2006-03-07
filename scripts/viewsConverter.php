#!/usr/local/bin/php
<?php
/**
 * viewsConverter.php - converts views from old plain format to new XML one.
 *
 * For help, type "php viewsConverter.php" in CLI.
 * @package Scripts
 * @version $Id$
 */

/**
 * Common home dir
 */
define('CARTOWEB_HOME', realpath(dirname(__FILE__) . '/..') . '/');

require_once(CARTOWEB_HOME . 'common/Common.php');
Common::preInitializeCartoweb(array());

require_once(CARTOWEB_HOME . 'client/Cartoclient.php');

if ($_SERVER['argc'] != 3) {
    usage();
} else {
    $type   = $_SERVER['argv'][1];
    $source = $_SERVER['argv'][2];
}

class ViewConverter extends ViewFilter {
    public function getRecorderVersion($pluginName) {
        return 1;
    }
}

$cartoclient = new Cartoclient();
$wf = new ViewConverter($cartoclient);

if ($type == 'file') {
    // views are stored in files

    if (!strpos($source, '.')) {
        terminate('Source is not valid.');
    }

    define('VIEWS_HOME', sprintf('%swww-data/views/%s/', CARTOWEB_HOME, $source));

    if (!$files = @scandir(VIEWS_HOME)) {
        terminate(printf('Directory %s does not exist or is not readable.',
                         VIEWS_HOME));
    }

    foreach ($files as $file) {
        if (!preg_match('/^([0-9]+)\.txt$/', $file)) {
            continue;
        }

        $viewContent = simplexml_load_file(VIEWS_HOME . $file);
        $sessionData = (string)$viewContent->sessionData;
        $viewData = unserialize(html_entity_decode($sessionData));
        
        $newViewData = $wf->encapsulate($viewData);
        
        $newViewData = htmlspecialchars($newViewData);
        $viewContent->sessionData = $newViewData;
        
        file_put_contents(VIEWS_HOME . $file, $viewContent->asXML());
        print "File $file updated.\n";
    }
    print "Conversion complete.\n";

} elseif ($type == 'db') {
    // views are stored in DB

    require_once 'DB.php';
    $db =& DB::connect($source);
    if (DB::isError($db)) {
        terminate('Failed opening DB connection: ' . $db->getMessage());
    }

    $res = $db->query('SELECT views_id FROM views');

    if (DB::isError($res)) {
        terminate('Error while querying views table: ' . $res->getMessage());
    }

    // WARNING: numRows() is buggy with Oracle!
    if (!$res->numRows()) {
        terminate('No view to update.');
    }

    while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $id = $row['views_id'];

        $sql = sprintf('SELECT sessiondata FROM views where views_id = %d', $id);
        $res2 = $db->query($sql);

        if (DB::isError($res2)) {
            print "Failed to get sessiondata from view #$id: "
                  . $res2->getMessage() . ".\n";
            continue;
        }

        $row2 =& $res2->fetchRow(DB_FETCHMODE_ASSOC);

        $viewData = unserialize($row2['sessiondata']);

        $res2->free();
        
        if (empty($viewData)) {
            print "Failed retrieving data from view #$id\n";
            continue;
        }

        $newViewData = $wf->encapsulate($viewData);

        $sql = sprintf("UPDATE views set sessiondata = '%s' " .
                       'WHERE views_id = %d',
                       addslashes($newViewData), $id);
        $res3 = $db->query($sql);

        if (DB::isError($res3)) {
            print "Failed to update view #$id: " . $res3->getMessage() . ".\n";
        } else {
            print "View #$id updated.\n";
        }
    }
    print "Conversion complete.\n";

} else {
    usage();
}

/**
 * Prints usage.
 */
function usage() {
    print "Usage: ./viewsConverter.php <type> <source>\n";
    print "- type (file|db): whereas views are stored in files or database\n";
    print "- source: full mapId (project.mapId) if type=file\n";
    print "          Data Source Name if type=db\n";
    print "WARNING: backup your views files/database before starting conversion!\n";
    exit(1);
}

/**
 * Prints error messages.
 */
function terminate($msg = '') {
    if ($msg) {
        print "$msg\n";
    }
    die("Conversion aborted.\n");
}
?>
