<?php
/**
 * Smarty class override for the cartoclient
 * @package Client
 * @version $Id$
 */

/**
 * Smarty templates
 */
require_once('smarty/Smarty.class.php');

/**
 * Specific Smarty engine for Cartoclient
 * @package Client
 */
class Smarty_Cartoclient extends Smarty {

    /**
     * @var Cartoclient
     */
    private $cartoclient;

    /**
     * @var ClientProjectHandler
     */
    private $projectHandler;

    /** 
     * Constructor
     * 
     * Initializes dirs and cache, ans registers block functions (resources
     * and i18n).
     * @param Cartoclient the current cartoclient
     */
    function __construct(Cartoclient $cartoclient) {
        parent::__construct();
    
        $this->cartoclient = $cartoclient;
        $config = $cartoclient->getConfig();
        $this->template_dir = $config->getBasePath() . 'templates/';
        $this->compile_dir = $config->getBasePath() . 'templates_c/';
        $this->config_dir = $config->getBasePath() . 'configs/';
        $this->cache_dir = $config->getBasePath() . 'cache/';
        
        $this->caching = $config->smartyCaching;
        $this->compile_check = $config->smartyCompileCheck;
        $this->debugging = $config->smartyDebugging;
        
        $this->projectHandler = $cartoclient->getProjectHandler();
        
        // Block function for resources
        $this->register_block('r', array($this, 'smartyResource'));
        
        // Block function for translation
        $this->register_block('t', array($this, 'smartyTranslate'));        
    }

    /**
     * Overrides Smarty's resource compile path
     *
     * Updates template dir to point to the right project and insert a compile
     * id to have one cache file per project and per template.
     * @param string resource name
     * @return string path to resource  
     */    
    function _get_compile_path($resource_name)
    {
        $oldPath = $this->template_dir;
        $oldPath = substr($oldPath, strlen(CARTOCLIENT_HOME) - strlen($oldPath));

        $prjDir = ProjectHandler::PROJECT_DIR . '/';
        $prjStrlen = strlen($prjDir);
        if (substr($oldPath, 0, $prjStrlen) == $prjDir) {
            $oldPath = substr($oldPath,
                strlen($this->projectHandler->getProjectName()) + $prjStrlen + 1
                     - strlen($oldPath));
        }

        $this->template_dir = CARTOCLIENT_HOME 
                              . $this->projectHandler->getPath($oldPath, 
                                                               $resource_name);
        $this->_compile_id = md5($this->template_dir);
        
        return $this->_get_auto_filename($this->compile_dir, $resource_name,
                                         $this->_compile_id) . '.php';
    }
 
    /**
     * Smarty block function for resources
     *
     * Transforms {r type=css plugin=myplugin}toto.css{/r} to 
     * myplugin/css/toto.css or currentproject/myplugin/css/toto.css .
     * @package Client
     * @param array block parameters
     * @param string block text
     * @param Smarty Smarty engine
     * @return string resource path
     */
    function smartyResource($params, $text, &$smarty) {
        
        $resource = stripslashes($text);
        
        if (isset($params['type'])) {
            $resource = $params['type'] . '/' . $resource;
            unset($params['type']);       
        }
        
        $plugin = '';
        if (isset($params['plugin'])) {
            $plugin = $params['plugin'];
            unset($params['plugin']);        
        }

        $project = $this->cartoclient->getProjectHandler()->getProjectName();
        $urlProvider = $this->cartoclient->getResourceHandler()->getUrlProvider();
        return $urlProvider->getHtdocsUrl($plugin, $project, $resource);
    }

    /**
     * Replace arguments in a string with their values
     * 
     * Arguments are represented by % followed by their number.
     * Original code was written by Sagi Bashari <sagi@boom.org.il>
     * @param   string  Source string
     * @param   mixed   Arguments, can be passed in an array or through single variables.
     * @return  string  Modified string
     */
    private function strarg($str)
    {
        $tr = array();
        $p = 0;
    
        for ($i=1; $i < func_num_args(); $i++) {
            $arg = func_get_arg($i);
            
            if (is_array($arg)) {
                foreach ($arg as $aarg) {
                    $tr['%'.++$p] = $aarg;
                }
            } else {
                $tr['%'.++$p] = $arg;
            }
        }
        
        return strtr($str, $tr);
    }

    /**
     * Smarty block function, provides gettext support for Smarty
     *
     * Original code was written by Sagi Bashari <sagi@boom.org.il>
     *
     * The block content is the text that should be translated.
     * Any parameter that is sent to the function will be represented as %n in the translation text, 
     * where n is 1 for the first parameter. The following parameters are reserved:
     *   - escape - sets escape mode:
     *       - 'html' for HTML escaping, this is the default.
     *       - 'js' for javascript escaping.
     *       - 'no'/'off'/0 - turns off escaping
     *   - plural - The plural version of the text (2nd parameter of ngettext())
     *   - count - The item count for plural mode (3rd parameter of ngettext())
     * @param array parameters
     * @param string text to translate
     * @param Smarty Smarty engine
     * @return string translated text
     */
    function smartyTranslate($params, $text, &$smarty)
    {
        $text = stripslashes($text);
        
        // set escape mode
        if (isset($params['escape'])) {
            $escape = $params['escape'];
            unset($params['escape']);
        }
        
        // set plural version
        if (isset($params['plural'])) {
            $plural = $params['plural'];
            unset($params['plural']);
            
            // set count
            if (isset($params['count'])) {
                $count = $params['count'];
                unset($params['count']);
            }
        }
        
        // use plural if required parameters are set
        if (isset($count) && isset($plural)) {
            $text = I18n::ngt($text, $plural, $count);
        } else { // use normal
            $text = I18n::gt($text);
        }
    
        // run strarg if there are parameters
        if (count($params)) {
            $text = $this->strarg($text, $params);
        }
    
        if (!isset($escape) || $escape == 'html') { // html escape, default
            $text = nl2br(htmlspecialchars($text));
        } elseif (isset($escape) && ($escape == 'javascript' || $escape == 'js')) { // javascript escape
            $text = str_replace('\'','\\\'',stripslashes($text));
        }
    
        return Encoder::decode($text);
    }
}

/**
 * Specific Smarty engine for core plugins
 * @package Client
 */
class Smarty_CorePlugin extends Smarty_Cartoclient {

    /**
     * @param ClientConfig
     * @param ClientPlugin
     */
    function __construct(Cartoclient $cartoclient, ClientPlugin $plugin) {
        parent::__construct($cartoclient);
        
        $this->template_dir = $plugin->getBasePath() . 'templates/';

        $this->assignCommonVariables($cartoclient);
    }

    /**
     * Fills some smarty variables common to all core plugins.
     * 
     * @param Cartoclient cartoclient object used to fill common smarty variables.
     */
    private function assignCommonVariables(Cartoclient $cartoclient) {
        // sets the project name, as it is propagated through hidden variables.
        $this->assign('project', $cartoclient->getProjectHandler()->
                      getProjectName());
    }
}

?>
