<?php
/**
 * StatsReports plugin request and result
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
 * @copyright 2007 Camptocamp SA
 * @package Plugins
 * @version $Id$
 */

/**
 * Request for plugin StatsReports
 * @package Plugins
 */
class StatsReportsRequest extends CwSerializable {

    /**
     * Image file name
     * @var string
     */
    public $imageFile;
    
    /**
     * Key to invalidate cache
     * @var string
     */
    public $cacheKey;
    
    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {
        $this->imageFile = self::unserializeValue($struct, 'imageFile', 'string');
        $this->cacheKey = self::unserializeValue($struct, 'cacheKey', 'string');
    }
}

/**
 * Result for plugin StatsReports
 * @package Plugins
 */
class StatsReportsResult extends CwSerializable { 

    /**
     * @see CwSerializable::unserialize()
     */
    public function unserialize($struct) {}
}

?>