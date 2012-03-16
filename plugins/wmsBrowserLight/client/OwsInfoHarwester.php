<?php
/**
 * OwsInfoHarwester
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
 * @copyright 2006 Office International de l'Eau, Camptocamp SA
 * @package Plugins
 * @version $Id$
 *
 */

/**
 * Used to parse Capability file from a WMS services. 
 * Call by WmsBrowserLight plugin using Ajax.
 * @package Plugins
 */
class OwsInfoHarwester {

    /**
     * HTML response to return.
     * @var string
     */
    public $response;
    
    /**
     * WMS Version to use
     * @var string
     */
    const DEFAULT_WMS_VERSION='1.1.1';

    /**
     * getLayers in a xml (capabilities) doc and create an HTML option form element
     *
     * @param array xml which correspond to a layer node from a GetCapabilities document
     * @param string depth could be used to indicate the current depth of the layer node
     */
    protected function getLayers($xml, $depth) {
        foreach ($xml as $src){
            if (!empty($src->Title) && !empty($src->Name)){
                
                // WMS time support
                if (!empty($src->Extent)) {
                    // Default is used if no plan to do movies
                    $time = '#' . $src->Extent['default'] . '#' . $src->Extent['default'] 
                            . "#" . $src->Extent[0]; 
                } else { 
                    $time = null;
                }
                
                // WMS scale support
                if (!empty($src->ScaleHint)) {
                    $scale = '#' . $src->ScaleHint['min'] . '#' . $src->ScaleHint['max']; 
                } else { 
                    $scale = '##';
                }
                $this->response .= '<option value="' . $src->Name . '#' . $src->Title 
                   . '#' . $src->SRS . $scale.$time . '">' . $depth . $src->Title . '</option>';
            }
            $this->getLayers($src->Layer, $depth.'-');
        }
    }


    /**
     * getWmsLayers
     * @param string url is the url of the service to contact
     */
    public function getWmsLayers($url) {
        // Add ? if needed
        if (!strpos ($url, '?')) {
            $url .= '?'; 
        }
        
        // And append GetCapabilities parameters
        $url = $url . '&SERVICE=WMS&REQUEST=GetCapabilities&VERSION=' . self::DEFAULT_WMS_VERSION;

        if (preg_match('/^(http|https):\/\/'.'/',$url)) {
            if (@get_headers($url)) {
                $capa = @simplexml_load_file(urlencode($url));
                $this->response = '<select name="owsLayerList" style="width:200px;" ' .
                                  'id="owsLayerList">';
                foreach ($capa->Capability->Layer as $src) {
                                $this->getLayers ($src, null);
                }      
                $this->response .= '</select><input type="button" onclick="doSubmit();" ' .
                                   'class="form_button" value="Ajouter la couche"/>';
            } else {
                $this->response .= 'Can\'t get server capabilities.';
            }
        } else {
            $this->response .= 'Wrong url format (must be http(s)://mywmsserverurl.com).';
        }
              
        return true;
    }


   /**
     * Get Scale info from capabilities
     * @param service url
     * @param name
     * @return array min and max scale info
     */
    protected function getScale ($url, $name, $service, $version) {
        // Add ? if needed
        if (!strpos ($url, '?')) {
            $url .= '?';
        }
    
        $url .= '&SERVICE='.$service.'&REQUEST=GetCapabilities&VERSION='.$version;
    
        $capa = @simplexml_load_file(urlencode($url));
        $node = $capa->xpath("//Layer[Name='".$name."']");
    
        if (!empty($node[0]->ScaleHint)) {
            $scale = array('minScale'=>$node[0]->ScaleHint['min'], 
                    'maxScale'=>$node[0]->ScaleHint['max']); 
        } else {
            $scale = array('minScale'=>-1, 'maxScale'=>-1); 
        }
        
        return $scale;
   }
}    
