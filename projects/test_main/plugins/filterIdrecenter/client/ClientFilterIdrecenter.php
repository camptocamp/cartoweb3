<?php
/**
 * Example of request filtering in brand new plugin.
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
 * @package Plugins
 * @version $Id$
 */
 
/**
 * @package Plugins
 */
class ClientFilterIdrecenter extends ClientPlugin
                             implements FilterProvider {
                       
    /**
     * @see FilterProvider::filterPostRequest()
     */
    public function filterPostRequest(FilterRequestModifier $request) {}
    
    /**
     * @see FilterProvider::filterGetRequest()
     */
    public function filterGetRequest(FilterRequestModifier $request) {
        
        $id = $request->getValue('id');
        if (!is_null($id)) {
            $layer = 'grid_classhilight';
            $request->setValue('query_layer', $layer);
            $request->setValue('query_maskmode', '1');
            $request->setValue('id_recenter_layer', $layer);
        
            $request->setValue('query_select', $id);
            $request->setValue('id_recenter_ids', $id);
        }
    }
}

?>
