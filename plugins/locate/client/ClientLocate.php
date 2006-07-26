<?php
/**
 * Locate web service
 * @package Plugins
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
 * @copyright 2005 Camptocamp SA
 * @package Plugins
 * @version $Id$
 */

/**
 * Locate web service
 * @package Plugins
 */
class ClientLocate extends ClientPlugin
                   implements GuiProvider {

// TODO set the variables for tables and fields

    /**
     * @var Logger
     */
    private $log;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->log =& LoggerManager::getLogger(__CLASS__);
    }

    /**
     * Retrieves list of records
     */
    private function getList($lyrid, $nom) {
        $loc = ConfigParser::parseObjectArray($this->getConfig(),
                                              'locate',
                                              array('id', 'sql'));
                                              
        foreach($loc as $lyr) {
            if ($lyr->id == $lyrid) {
                $sql = $lyr->sql;
                break;
            }
        }
        if (!isset($sql)) return false;
        $sql = sprintf($sql, $nom);
        $this->db = Utils::getDb($this->db, $this->getConfig()->dsn);

        $res = $this->db->getAll($sql);
        
        if (DB::isError($res))
            die($res->getMessage());
        $this->db->setFetchMode(DB_FETCHMODE_ASSOC);
        
        //return false;
        return $res;
    }

    public function handleHttpPostRequest($request) {}

    public function handleHttpGetRequest($request) {
        if (isset ($request['locate_layer_id']) && $request['locate_layer_id']) {
            $formRenderer = $this->getCartoclient()->getFormRenderer();
            $formRenderer->setCustomForm('locateResults.tpl');
            $this->getCartoclient()->setInterruptFlow(true);
            $result = $this->getList($request['locate_layer_id'], $request['locate_'.$request['locate_layer_id']]);
            if ($result) {
                print $this->drawLocateUlLi($result);
            } else {
                die();
            }
        }
    }
    
    
    /**
     * Draws locate specific template
     * @return string
     */ 
    protected function drawLocate() {
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        
        $locateArray = ConfigParser::parseObjectArray($this->getConfig(),
                                              'locate',
                                              array('id', 'label', 'sql'));
        $smarty->assign('locates', $locateArray);                                      
        
        return $smarty->fetch('locate.tpl');
    }

    public function renderForm(Smarty $template) {
        $template->assign('locate_form', $this->drawLocate());
        $template->assign('locate_active', true);
    }

    /**
     *
     */
    public function drawLocateUlLi($result) {
        // This generates HTML code to go in the HTML page from the $result array
        $i = 0;
        print '<ul>';
        foreach ($result as $resultItem) {
            $i++;
            $keys = array_keys($resultItem);
            print "<li id=\"".$resultItem[$keys[0]]."\" title=\"". $resultItem[$keys[1]] . "\">". $resultItem[$keys[1]] . "</li>";
        }
        print '</ul>';
        die;
    }
}
?>