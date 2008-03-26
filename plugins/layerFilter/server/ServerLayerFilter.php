<?php
/**
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
 * @copyright 2008 Camptocamp SA
 * @package Plugins
 * @version $Id$
 */

class ServerLayerFilter extends ClientResponderAdapter {

    /**
     * @see ClientResponder::initializeRequest()
     */
    public function initializeRequest($requ) {
        
        if (empty($requ->criteria)) {
            return;
        }

        $iniArray = $this->getConfig()->getIniArray();
        $configStruct = StructHandler::loadFromArray($iniArray);
        $config = $configStruct->criteria;

        $pluginManager = $this->serverContext->getPluginManager();
        $activatedLayers = $pluginManager->layers->getRequestedLayerNames();
        
        // array associating layers to criteria that affect them
        $layersFilters = array();

        foreach ($requ->criteria as $critname => $critopt) {
            // skips criterion if it is not listed in config
            if (empty($config->$critname)) continue;

            // gets values of submitted criterion and retrieves matching
            // filter strings
            $optionsFilters = array();
            if (!empty($critopt)) {
                foreach (Utils::parseArray($critopt) as $option) {
                    $optionsFilters[] = $config->$critname->options->$option->filter;
                }
                $filter = '(' . implode(' OR ', $optionsFilters) . ')';
            
            } else {
                // if criterion is empty, then use negation of all options
                foreach ($config->$critname->options as $optname => $optprop) {
                    $optionsFilters[] = $optprop->filter;
                }
                $filter = 'NOT (' . implode(' OR ', $optionsFilters) . ')';
            }

            // detects layers affected by current criterion
            $layers = Utils::parseArray($config->$critname->layers);
            if (!empty($activatedLayers)) {
                // skips layers that are not activated
                // if activated layers list is available
                $layers = array_intersect($layers, $activatedLayers);
            }

            foreach ($layers as $layer) {
                if (array_key_exists($layer, $layersFilters)) {
                    array_push($layersFilters[$layer], $filter); 
                } else {
                    $layersFilters[$layer] = array($filter);
                }
            }
        }

        foreach ($layersFilters as $layer => &$filter) {
            $filter = implode(' AND ', $filter);
            $filter = str_replace('#', '"', $filter);
        }

        // applies filters to matching layers
        $msMapObj = $this->serverContext->getMapObj();
        for ($i = 0; $i < $msMapObj->numlayers; $i++) {
            $msLayer = $msMapObj->getLayer($i);
            if (!empty($layersFilters[$msLayer->name])) {
                $msLayer->setFilter($layersFilters[$msLayer->name]);
            }
        }
    }
}
?>
