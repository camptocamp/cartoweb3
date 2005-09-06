#!/usr/local/bin/php
<?php
/**
 * Documentation generator using PhpDocumentor
 *
 * Usage: ./makedoc.php
 *
 * @package Scripts
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 * @version $Id$
 */

//
// PhpDoc, a program for creating javadoc style documentation from php code
// Copyright (C) 2000-2001 Joshua Eichorn
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//

//
// Copyright 2000-2001 Joshua Eichorn
// Email jeichorn@phpdoc.org
// Web      http://phpdoc.org/
// Mirror   http://phpdocu.sourceforge.net/
// Project  http://sourceforge.net/projects/phpdocu/
//

define('CARTOWEB_HOME', realpath(dirname(__FILE__) . '/..') . '/');

require_once(CARTOWEB_HOME . 'common/Common.php');
Common::preInitializeCartoweb(array());

set_include_path(get_include_path() . PATH_SEPARATOR . 
                 CARTOWEB_HOME . 'include/pear/PhpDocumentor/');

// creates a symlink from pear_base --> ../include/pear_base
// FIXME: is there a better way to do it ?
if (!is_link('pear_base')) {
    symlink('../include/pear_base', 'pear_base');
}

/**
 * Directories and files to include
 */
define('INCLUDE_FILES',
       '../client,../server,../common,../plugins,../coreplugins,../tests,' .
       '../scripts,../projects');

/**
 * Directories (ending with '/') and files to ignore
 */
define('IGNORE_FILES',
       'scripts/pear_base/,include/,www-data/,doc/,client_conf/,' .
       'server_conf/,locale/,po/,templates/,templates_c/,log/,*.inc');

/**
 * Documentation title
 */
define('DOC_TITLE', 'CartoWeb 3 Documentation');

/**
 * Target directory
 */
define('DOC_DIR', '../documentation/apidoc');

/**
 * Default package, when @package is not specified 
 */
define('DEFAULT_PACKAGE', 'CartoWeb3');

/**
 * Includes private methods ('on' or 'off')
 */
define('PARSE_PRIVATE', 'on');
 
/**
 * PHP Doc template to use
 */
define('TEMPLATE', 'HTML:Smarty:PHP');
 
$_SERVER['argv'] = array('-d',  INCLUDE_FILES,
                         '-i',  IGNORE_FILES,
                         '-ti', DOC_TITLE,
                         '-t',  DOC_DIR,
                         '-dn', DEFAULT_PACKAGE,
                         '-pp', PARSE_PRIVATE,
                         '-q',
                         '-o',  TEMPLATE);

/** 
 * PHP auto documentor, like javadoc
 * If you get an error uses this as a shell script then its been dosified
 * @author Joshua Eichorn
 * @version 1.3.0
 * @copyright Joshua Eichorn
 */
include("PhpDocumentor/phpDocumentor/phpdoc.inc");

?>
