<?php
/**
 * @package Client
 * @version $Id$
 */

/**
 * Translator selection
 *
 * The translator is selected using client.ini's I18nClass 
 * 
 * @package Client
 */
class I18n {
    
    const DEFAULT_PROJECT_DOMAIN = 'default';
    
    static private $i18n;
    
    /**
     * Initializes locales
     */
    static function init($config) {
    
        self::$i18n = new $config->I18nClass;
        
        self::setLocale();
        
        self::$i18n->bindtextdomain(I18n::DEFAULT_PROJECT_DOMAIN, CARTOCLIENT_HOME . 'locale/');
        self::$i18n->bindtextdomain($config->mapId, CARTOCLIENT_HOME . 'locale/');
    }

    /**
     * Sets the locale depending on URL, browser or config
     */
    static function setLocale() {
        // TODO
        setlocale(LC_MESSAGES, 'fr_CH');
    }
    
    static function bindtextdomain($domain, $path) {
        return self::$i18n->bindtextdomain($domain, $path);
    }

    static function textdomain($domain) {
        return self::$i18n->textdomain($domain);
    }
    
    static function gt($text) {
        return self::$i18n->gettext($text);
    }
    
    static function ngt($text, $plural, $count) {
        return self::$i18n->ngettext($text, $plural, $count);
    }
}

/**
 * Translator interface
 *
 * @package Client
 */
interface I18nInterface {

    /**
     * Wrapper for function bindtextdomain
     */ 
    function bindtextdomain($domain, $path);
    
    /**
     * Wrapper for function textdomain
     */ 
    function textdomain($domain);
    
    /**
     * Wrapper for function gettext
     */
    static function gettext($text);

    /** 
     * Wrapper for function ngettext
     */  
    static function ngettext($text, $plural, $count);
}

/**
 * Dummy translator (does nothing)
 *
 * @package Client
 */
class I18nDummy implements I18nInterface {

    function bindtextdomain($domain, $path) {
    }
    
    function textdomain($domain) {
    }
    
    static function gettext($text) {
        return $text;
    }
    
    static function ngettext($text, $plural, $count) {
        return $text;
    }
}

/**
 * Gettext translator
 *
 * Needs gettext installed in PHP
 *
 * @package Client
 */
class I18nGettext implements I18nInterface {

    function bindtextdomain($domain, $path) {
        bindtextdomain($domain, $path);
    }
    
    function textdomain($domain) {
        textdomain($domain);
    }
    
    static function gettext($text) {
        return gettext($text);
    }
    
    static function ngettext($text, $plural, $count) {
        return ngettext($text, $plural, $count);
    }
}

/**
 * Replace arguments in a string with their values. Arguments are represented by % followed by their number.
 *
 * Original code was written by Sagi Bashari <sagi@boom.org.il>
 *
 * @param   string  Source string
 * @param   mixed   Arguments, can be passed in an array or through single variables.
 * @returns string  Modified string
 */
function strarg($str)
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
 * Smarty block function, provides gettext support for smarty.
 *
 * Original code was written by Sagi Bashari <sagi@boom.org.il>
 *
 * The block content is the text that should be translated.
 *
 * Any parameter that is sent to the function will be represented as %n in the translation text, 
 * where n is 1 for the first parameter. The following parameters are reserved:
 *   - escape - sets escape mode:
 *       - 'html' for HTML escaping, this is the default.
 *       - 'js' for javascript escaping.
 *       - 'no'/'off'/0 - turns off escaping
 *   - plural - The plural version of the text (2nd parameter of ngettext())
 *   - count - The item count for plural mode (3rd parameter of ngettext())
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
        $text = strarg($text, $params);
    }

    if (!isset($escape) || $escape == 'html') { // html escape, default
        $text = nl2br(htmlspecialchars($text));
    } elseif (isset($escape) && ($escape == 'javascript' || $escape == 'js')) { // javascript escape
        $text = str_replace('\'','\\\'',stripslashes($text));
    }

    return $text;
}

?>