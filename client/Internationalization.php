<?php
/**
 * Internationalization (I18n) classes
 * @package Client
 * @version $Id$
 */

/**
 * Translator selection
 *
 * The translator is selected using client.ini's I18nClass.
 * @package Client
 */
class I18n {
    const DEFAULT_PROJECT_DOMAIN = 'default';
    
    static private $i18n;
    
    /**
     * Initializes locales
     *
     * Default language is set in configuration file (variable defaultLang).
     */
    static function init($config) {
        self::$i18n = new $config->I18nClass;
       
        self::setLocale($config->defaultLang);
        
        self::$i18n->bindtextdomain($config->mapId, CARTOCLIENT_HOME . 'locale/');
        
        self::$i18n->textdomain($config->mapId);
    }
    
    /**
     * Returns available locales
     *
     * Looks for two-characters directories in locale directory.
     */
    static function getLocales() {
    
        // Looks in directory locale
        $dir = CARTOCLIENT_HOME . 'locale/';
        $d = dir($dir);
        $locales = array();
        while (false !== ($entry = $d->read())) {
            if ($entry == '.' || $entry == '..' || strlen($entry) != 2) {
                continue;
            }
            $locales[] = $entry;
        }
        return $locales;    
    }
    
    /**
     * Sets the locale depending on URL, browser or config
     *
     * Looks for language in:
     * - $_SERVER['LANG']
     * - Cookie
     * - $_SERVER['HTTP_ACCEPT_LANGUAGE']
     *
     * If no language is found, default language is set.
     */
    static function setLocale($defaultLang) {
        $log =& LoggerManager::getLogger(__METHOD__); 
         
        $locales = self::getLocales();
        
        // Set language code based on phpLang:
        // http://www.phpheaven.net/projects/phplang/
        // look in LANG->cookies->$HTTP_ACCEPT_LANGUAGE
        // look if LANG has been passed (by url and mod_rewrite)
        // will work with following mod_rewrite rule:
        // RewriteRule   ^(.*)/(fr|de|it|en)/(.*)          $1/$3 [E=LANG:$2]
        if (isset($_SERVER['LANG']) && in_array($_SERVER['LANG'], $locales)) {
            define('LANG', $_SERVER['LANG']);
            $log->debug('LANG: $_SERVER[LANG] = ' . LANG);
        }
        // if not, looks if the language has been previously set in a cookie
        elseif (isset($_COOKIE['LangCookie'])
                    && in_array($_COOKIE['LangCookie'], $locales)) {
            define('LANG', $_COOKIE['LangCookie']);
            $log->debug('LANG: $_COOKIE[langCookie] = ' . LANG);
        }
        // if not in cookies, looks if valid language is set in $HTTP_ACCEPT_LANGUAGE
        elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $already_tested = array();
            $accepted_lang = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            
            while (!defined('LANG') && list($key, $name) = each($accepted_lang)) {
                $code = explode(';', $name);
                $lang_ext = substr($code[0], 0, 2);
                
                if (in_array($lang_ext, $already_tested)) continue;
                $already_tested[] = $lang_ext;
                
                if (in_array($lang_ext, $locales)) {
                    define('LANG', $lang_ext);
                    $log->debug('LANG: $_SERVER[HTTP_ACCEPT_LANGUAGE] = ' . LANG);
                }
            }
        }
        
        unset ($accepted_lang, $key, $name, $code, $lang_ext, $already_tested);

        // if language not yet set, set to default language)
        if (!defined('LANG')) {
            define('LANG', $defaultLang);
            $log->debug('LANG: default = ' . LANG);
        }

        putenv('LANG=' . LANG);
        // Set locale to LANG, for strftime()
        setlocale(LC_ALL, '');
        setlocale(LC_NUMERIC, 'C');
        header('Content-Language: ' . LANG);
        
        // if cookie doesn't exist, set a cookie expiring in one year for current language
        if (!isset($_COOKIE['LangCookie']) || $_COOKIE['LangCookie'] != LANG) {
            setcookie('LangCookie', LANG, time() + 31536000);
        }       
    }
    
    /**
     * Calls translator's bindtextdomain
     */
    static function bindtextdomain($domain, $path) {
        return self::$i18n->bindtextdomain($domain, $path);
    }

    /**
     * Calls translator's textdomain
     */
    static function textdomain($domain) {
        return self::$i18n->textdomain($domain);
    }
    
    /**
     * Calls translator's gettext
     */
    static function gt($text) {
        return self::$i18n->gettext($text);
    }
    
    /**
     * Calls translator's ngettext
     */
    static function ngt($text, $plural, $count) {
        return self::$i18n->ngettext($text, $plural, $count);
    }
}

/**
 * Translator interface
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
 * Needs gettext installed in PHP.
 * @package Client
 */
class I18nGettext implements I18nInterface {

    function bindtextdomain($domain, $path) {
        bindtextdomain($domain, $path);
        $log =& LoggerManager::getLogger(__METHOD__); 
        $log->debug('LANG: binddomain ' . $domain . ' ' . $path);
    }
    
    function textdomain($domain) {
        textdomain($domain);
        $log =& LoggerManager::getLogger(__METHOD__); 
        $log->debug('LANG: textdomain ' . $domain);
    }
    
    static function gettext($text) {
        return gettext($text);
    }
    
    static function ngettext($text, $plural, $count) {
        return ngettext($text, $plural, $count);
    }
}

/**
 * Replace arguments in a string with their values.
 * 
 * Arguments are represented by % followed by their number.
 * Original code was written by Sagi Bashari <sagi@boom.org.il>
 *
 * @param   string  Source string
 * @param   mixed   Arguments, can be passed in an array or through single variables.
 * @return  string  Modified string
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
