<?php
/**
 * Common part of PDF export plugin.
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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.    *
 * @copyright 2005 Camptocamp SA
 * @package Plugins
 * @version $Id$
 */

/**
 * Abstract serializable
 */
require_once(CARTOWEB_HOME . 'common/CwSerializable.php');

/**
 * ExportPdf initialization information
 * @package Plugins
 */
class ExportPdfInit extends CwSerializable {

    /**
     * @var int
     */
    public $mapServerResolution;

    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->mapServerResolution = self::unserializeValue($struct,
                                        'mapServerResolution', 'int');
    }
}

?>
