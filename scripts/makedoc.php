#!/usr/local/bin/php
<?php
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

// Modified by Yves Bolognini <yves.bolognini@camptocamp.com>

define('CARTOCLIENT_HOME', realpath(dirname(__FILE__) . '/..') . '/');

set_include_path(get_include_path() . PATH_SEPARATOR .
                 CARTOCLIENT_HOME . 'include/pear/' . PATH_SEPARATOR . 
                 CARTOCLIENT_HOME . 'include/pear/PhpDocumentor/');

/** 
 * PHP auto documentor, like javadoc
 * If you get an error uses this as a shell script then its been dosified
 * @author Joshua Eichorn
 * @version 1.3.0
 * @copyright Joshua Eichorn
 */
include("PhpDocumentor/phpDocumentor/phpdoc.inc");
?>
