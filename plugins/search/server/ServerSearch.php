<?php
/**
 * Server search
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
 * 
 * @package Plugins
 * @version $Id$
 */ 

/**
 * Server search plugin
 */
class ServerSearch extends ClientResponderAdapter {

    /**
     * @var ResultProvider[] Search providers
     */
    protected $providers; 

    /**
     * @see PluginBase::initialize()
     */
    public function initialize() {
        
        $configStruct =
            StructHandler::loadFromArray($this->getConfig()->getIniArray());
        
        $defaultValues = array();
        foreach ($configStruct as $var => $val) {
            
            if ($var != 'config') {
                $defaultValues[$var] = SearchUtils::getValue($var, $val);
            }
        }

        $this->providers = array();
        if (isset($configStruct->config)) {
            
            foreach($configStruct->config as $name => $config) {

                if (!isset($config->provider)) {
                    throw new CartoclientException("Search config $name has no provider");
                }
                $newProvider =
                    ResultProvider::getProviderFromConfig($config->provider,
                                                          $defaultValues, $this);

                if (is_null($newProvider)) {
                    throw new CartoclientException("Server provider cannot be of type server");
                }
                
                $this->providers[$name] = $newProvider;                
            }            
        }
    }

    /**
     * @see ClientResponder::initializeRequest()
     */
    public function initializeRequest($requ) { }

    /**
     * @see ClientResponder::handlePreDrawing()
     */    
    public function handlePreDrawing($requ) {

        return $this->providers[$requ->config]->getResult($requ);        
    }  
}

?>
