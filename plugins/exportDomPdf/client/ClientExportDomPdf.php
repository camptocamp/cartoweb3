<?php
/**
 * PDF Export using dompdf lib (see http://www.digitaljunkies.ca/dompdf/index.php)
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
 * @copyright 2009 Camptocamp SA
 * @package Plugins
 * @version $Id$
 */

/**
 * Export super class
 */
require_once(CARTOWEB_HOME . 'client/ExportPlugin.php');
require_once(realpath(dirname(__FILE__) . '/../lib/dompdf/dompdf_config.inc.php'));

class DPdfImage {
    public $width;
    public $height;
}

/**
 * HTML export
 * @package Plugins
 */
class ClientExportDomPdf extends ExportPlugin {


    private $log;

    protected $orientations;
    protected $sizes;
    protected $resolutions;
    protected $filename;

    protected $orientation;
    protected $size;
    protected $resolution;
    protected $title = '';

    protected $mapScale;

    protected $mainmap;
    protected $keymap;
    protected $scalebar;

    /** 
     * Constructor
     */
    public function __construct() {
        $this->log =& LoggerManager::getLogger(__CLASS__);
        parent::__construct();
    }

    public function initialize() {
        $this->orientations = $this->getConfigFromList('orientations');
        $this->sizes = $this->getConfigFromList('sizes');
        $this->resolutions = $this->getConfigFromList('resolutions');
        $this->filename = $this->getConfig()->filename;

        $this->orientation = $this->orientations[0];
        $this->resolution = $this->resolutions[0];
        $this->size = $this->sizes[0];
    }

    protected function getConfigFromList($name) {
        $list = $this->getConfig()->$name;
        if (empty($list)) return $list;
        return array_map('trim', explode(',', $list));
    }

    public function handleHttpPostRequest($request) {
        $this->updateFromRequest($request, 'size');
        $this->updateFromRequest($request, 'orientation');
        $this->updateFromRequest($request, 'resolution');

        if (!empty($request['pdfTitle'])) {
            $this->title = $request['pdfTitle'];
        }
    }

    protected function updateFromRequest($request, $name) {
        switch ($name) {
            case 'size':
                $rname = 'pdfSize';
                $names = 'sizes';
                break;
            case 'orientation':
                $rname = 'pdfOrientation';
                $names = 'orientations';
                break;
            case 'resolution':
                $rname = 'pdfResolution';
                $names = 'resolutions';
                break;
            default: return;
        }
        if (array_key_exists($rname, $request) &&
            in_array($request[$rname], $this->$names)) {
            $this->$name = $request[$rname];
        }
        return;
    }

    public function handleHttpGetRequest($request) {}

    public function renderForm(Smarty $template) {
        $template->assign(array('exportPdf' => $this->drawUserForm()));
    }

    protected function drawUserForm() {
        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $smarty->assign(array('exportScriptPath' => $this->getExportUrl(),
                              'orientations' => $this->orientations,
                              'resolutions' => $this->resolutions,
                              'sizes' => $this->sizes));
        return $smarty->fetch('form.tpl');
    }

    // TODO: factorize with exportPdf?
    protected function getLastScale() {
        if (!isset($this->mapScale)) {
            $mapResult = $this->getLastMapResult();

            if (is_null($mapResult))
                return 0;
    
            $this->mapScale = $mapResult->locationResult->scale;
        }
        return $this->mapScale;
    }

    protected function getConfiguration() {

        $config = new ExportConfiguration();

        $mapServerResolution = $this->getConfig()->mapServerResolution;

        $format = $this->size . '.' . $this->orientation;
        $mapWidth = $this->getConfig()->{$format . '.width'};
        $mapHeight = $this->getConfig()->{$format . '.height'};
        $this->mainmap = new DPdfImage;
        $this->mainmap->width = $mapWidth;
        $this->mainmap->height = $mapHeight;
        $mapWidth *= $this->resolution / $mapServerResolution;
        $mapHeight *= $this->resolution / $mapServerResolution;

        $config->setMapWidth($mapWidth);
        $config->setMapHeight($mapHeight);

        $scale = $this->getLastScale();
        $scale *= $mapServerResolution;
        $scale /= $this->resolution;
        $config->setScale($scale);

        $config->setResolution($this->resolution);

        return $config;
    }

    protected function getExport() {
        $mapResult = $this->getExportResult($this->getConfiguration());

        $mainmap = $mapResult->imagesResult->mainmap;
        $keymap = $mapResult->imagesResult->keymap;
        $scalebar = $mapResult->imagesResult->scalebar;

        $resourceHandler = $this->cartoclient->getResourceHandler();
        $mainmapUrl = $resourceHandler->getFinalUrl($mainmap->path, false, true);
        $keymapUrl = $resourceHandler->getFinalUrl($keymap->path, false, true);
        $scalebarUrl = $resourceHandler->getFinalUrl($scalebar->path, false, true);

        $queryResult = isset($mapResult->queryResult) ? 
                       $mapResult->queryResult->tableGroup->tables
                       : null;

        if ( !function_exists('__autoload')) { 
            function __autoload($class) {
                DOMPDF_autoload($class);
            }
        }

        $dompdf = new DOMPDF();
        $dompdf->set_paper($this->size, $this->orientation);

        $smarty = new Smarty_Plugin($this->getCartoclient(), $this);
        $smarty->assign(array('mainmapUrl' => $mainmapUrl,
                              'mainmapWidth' => $this->mainmap->width,
                              'mainmapHeight' => $this->mainmap->height,
                              'keymapUrl' => $keymapUrl,
                              'keymapWidth' => $keymap->width,
                              'keymapHeight' => $keymap->height,
                              'scalebarUrl' => $scalebarUrl,
                              'scalebarWidth' => $scalebar->width,
                              'scalebarHeight' => $scalebar->height,
                              'queryResult' => $queryResult,
                              'title' => $this->title
                              ));
         
        $dompdf->load_html($smarty->fetch('pdf.tpl'));
        $dompdf->render();
        return $dompdf;

    }

    public function output() {
        $this->getExport()->stream($this->generateFilename(),
                                   array('Attachment' => 0));
    }

    protected function generateFilename() {
        if (preg_match("/^(.*)\[date,(.*)\](.*)$/",
                       $this->filename, $regs)) {
            $this->filename = $regs[1] . date($regs[2]) . $regs[3];
        }
        return $this->filename;
    }
}
