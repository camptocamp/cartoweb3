<?php
/**
 * Plugin extension and request filters example.
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
 * @copyright 2006 Camptocamp SA
 * @package CorePlugins
 * @version $Id$
 */

/**
 * @package CorePlugins
 */
class ClientTestMainLocation extends ClientLocation
                             implements FilterProvider {

    /**
     * Tells that this plugin should be used instead of plugin 'location'.
     * @see PluginManager::replacePlugin()
     */
    public function replacePlugin() {
        return 'location';
    }
    
    /**
     * @see FilterProvider::filterPostRequest()
     */
    public function filterPostRequest(FilterRequestModifier $request) {}

    /**
     * @see FilterProvider::filterGetRequest()
     */
    public function filterGetRequest(FilterRequestModifier $request) {
        $x = $request->getValue('x');
        if (!is_null($x)) {
            $request->setValue('recenter_x', $x);
        }
        $y = $request->getValue('y');
        if (!is_null($y)) {
            $request->setValue('recenter_y', $y);
        }
    }
}
?>
