<?php
/**
 * Exportation of outline shapes in DXF format.
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
 * @author Alexandre Saunier
 * @version $Id$
 */

require_once CARTOWEB_HOME . 'client/ExportPlugin.php';

/**
 * @package Plugins
 */
class DxfShape {
    
    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $label;

    /**
     * @var array of Point
     */
    public $points;

    /**
     * Constructor
     */
    public function __construct($type, $points, $label = '') {
        $this->type   = $type;
        $this->points = $points;
        $this->label  = $label;
    }
}

/**
 * @package Plugins
 */
class ClientExportDxf extends ExportPlugin 
                        implements Ajaxable {

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var ClientOutline
     */
    protected $outline;

    /**
     * @var integer
     */
    protected $roundLevel;

    /**
     * Constructor
     */
    public function __construct() {
        $this->log = LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    /**
     * @see PluginBase::initialize()
     */
    public function initialize() {
        $this->outline = $this->getCartoclient()->getPluginManager()
                              ->getPlugin('outline');
        if (is_null($this->outline)) {
            throw new CartoclientException(
                'exportDxf requires plugin outline to be loaded');
        }
    }

    /**
     * @see GuiProvider::handleHttpPostRequest()
     */
    public function handleHttpPostRequest($request) {}

    /**
     * @see GuiProvider::handleHttpGetRequest()
     */
    public function handleHttpGetRequest($request) {}

    /**
     * This method factors the plugin output for both GuiProvider::renderForm()
     * and Ajaxable::ajaxGetPluginResponse().
     * @return array array of variables and html code to be assigned
     */
    protected function renderFormPrepare() {
        // Export button is displayed only if there are shapes to export.
        if ($this->outline->hasShapes()) {
            $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
            $smarty->assign(array('exportDxfUrl' => $this->getExportUrl()));
            $exportDxf = $smarty->fetch('form.tpl');
        } else {
            $exportDxf = '';
        }
        return $exportDxf;
    }

    /**
     * @see GuiProvider::renderForm()
     */
    public function renderForm(Smarty $template) {
        $template->assign('exportDxf', $this->renderFormPrepare());
    }

    /**
     * @see Ajaxable::ajaxGetPluginResponse()
     */
    public function ajaxGetPluginResponse(AjaxPluginResponse $ajaxPluginResponse) {
        $ajaxPluginResponse->addVariable('exportDxf', $this->renderFormPrepare());
        $ajaxPluginResponse->addVariable('exportDxfContainerName', 
                                       $this->getConfig()->exportDxfContainerName);
    }

    /**
     * @see Ajaxable::ajaxHandleAction()
     */
    public function ajaxHandleAction($actionName, PluginEnabler $pluginEnabler) {
        if ($actionName == 'Outline.AddFeature' || $actionName == 'Outline.Clear') {
            $pluginEnabler->enablePlugin('exportDxf');
        }
    }

    /**
     * Returns the number of digits displayed after the comma 
     * in points coordinates.
     * @return integer
     */
    protected function getRoundLevel() {
        if (!isset($this->roundLevel)) {
            $this->roundLevel = (int)$this->getConfig()->roundLevel;
        }
        return $this->roundLevel;
    }

    /**
     * Returns a Point object with rounded coordinates.
     * @return Point
     */
    protected function getDxfPoint(Point $point) {
        $roundLevel =& $this->getRoundLevel();
        $x = round($point->x, $roundLevel);
        $y = round($point->y, $roundLevel);
        return new Point($x, $y);
    }

    /**
     * Replaces accentuated letters by non accentuated ones.
     * @param string
     * @return string
     */
    protected function removeAccents($str) {
        // FIXME: encoding problem?
        $from = 'ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ';
        $to   = 'AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn';
        $str  = Encoder::decode($str);
        $str  = strtr($str, $from, $to);
        return $str;
    } 

    /**
     * @see ExportPlugin::getExport
     */
    protected function getExport() {
        $shapes = array();
        $mapRequest = $this->getLastMapRequest();

        if (empty($mapRequest->outlineRequest) || 
            empty($mapRequest->outlineRequest->shapes)) {
            return new ExportOutput();
        }
        
        foreach ($mapRequest->outlineRequest->shapes as $shape) {
            $type = get_class($shape->shape);
            if (!in_array($type, array('Point', 'Line', 'Polygon'))) {
                throw new CartoclientException(
                    "exportDxf does not handle shape type '$type'");
            }
            $label = $this->removeAccents($shape->label);
            $points = array();
            if ($type == 'Point') {
                $points[] = $this->getDxfPoint($shape->shape);
            } else {
                foreach ($shape->shape->points as $point) {
                    $points[] = $this->getDxfPoint($point);
                }
            }     
            $shapes[] = new DxfShape($type, $points, $label);
        }

        $output = new ExportOutput();
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $smarty->assign('shapes', $shapes);
        $output->setContents($smarty->fetch('export.dxf.tpl'));
        return $output;
    }

    /**
     * @see ExportPlugin::output()
     */
    public function output() {
        $dxfBuffer = $this->getExport()->getContents();
        header('Content-Type: application/dxf');
        header('Content-Length: ' . strlen($dxfBuffer));
        header('Content-Disposition: attachment; filename=' .
               $this->getFilename());
        print $dxfBuffer;
        return '';
    }

    /**
     * Builds exported file name.
     * @return string
     */
     protected function getFilename() {
         $filename = $this->getConfig()->fileName;
         if ($filename && preg_match('/^(.*)\[date,(.*)\](.*)$/',
                                     $filename, $regs)) {
             $filename = $regs[1] . date($regs[2]) . $regs[3];
         } 

         if (empty($filename)) $filename = 'cartoweb_outline.dxf';

         return $filename;
     }
}
