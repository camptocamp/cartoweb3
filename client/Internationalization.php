<?php
/**
 * @package Client
 * @version $Id$
 */

class I18n {
    
    const DEFAULT_PROJECT_DOMAIN = 'default';
    
    /**
     * Initializes locales
     */
    static function init($config) {
        self::setLocale();
        
        bindtextdomain(I18n::DEFAULT_PROJECT_DOMAIN, CARTOCLIENT_HOME . 'locale/');

        bindtextdomain($config->mapId, CARTOCLIENT_HOME . 'locale/');
    }

    /**
     * Sets the locale depending on URL, browser or config
     */
    static function setLocale() {
        // TODO
        setlocale(LC_MESSAGES, 'fr_CH');
    }
    
    /**
     * Wrapper for function gettext
     */
    static function gt($text) {
        return gettext($text);
    }
    
    /** 
     * Wrapper for function ngettext
     */  
    static function ngt($text, $plural, $count) {
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