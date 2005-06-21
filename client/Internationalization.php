<?php
/**
 * Internationalization (I18n) classes
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
    
    /**
     * Translator
     * @var I18nInterface
     */
    static private $i18n;

    /**
     * Restrictive lang list
     * @var string
     */
    static private $authLanglist;

    /**
     * Guess the I18nInterface class to use
     * @return I18nInterface The i18n interface to use.
     */
    static private function guessI18nClass() {
    
        if (function_exists('gettext'))
            return new I18nGettext();
        return new I18nDummy();
    }
    
    /**
     * Initializes locales
     *
     * Default language is set in configuration file (variable defaultLang).
     * @param ClientConfig
     */
    static public function init($config) {
        if ($config->I18nClass)
            self::$i18n = new $config->I18nClass;
        else
            self::$i18n = self::guessI18nClass();

        self::$authLanglist = $config->authLang;
        
        self::setLocale($config->defaultLang);

        self::$i18n->bindtextdomain($config->mapId, CARTOCLIENT_HOME . 'locale/');
        
        self::$i18n->textdomain($config->mapId);
    }
    
    /**
     * Returns available locales
     *
     * Looks for two-characters directories in locale directory.
     * @return array array of locales (two-characters strings)
     */
    static public function getLocales() {

        $authLang = array();

        if (self::$authLanglist != '')
            $authLang = explode(",", trim(self::$authLanglist));       
           
        // Looks in directory locale
        $dir = CARTOCLIENT_HOME . 'locale/';
        $d = dir($dir);
        $locales = array();
        while (false !== ($entry = $d->read())) {
            if ($entry == '.' || $entry == '..' || strlen($entry) != 2) {
                continue;
            }
            if (sizeof($authLang) >= 1 && $authLang[0] != '') {
                if(!in_array($entry, $authLang))
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
     * @param string default language
     */
    static public function setLocale($defaultLang) {
        $log =& LoggerManager::getLogger(__METHOD__); 
         
        $locales = self::getLocales();
        
        // Set language code based on phpLang:
        // http://www.phpheaven.net/projects/phplang/
        // look in LANG->cookies->$HTTP_ACCEPT_LANGUAGE
        // look if LANG has been passed (by url and mod_rewrite)
        // will work with following mod_rewrite rule:
        // RewriteRule   ^(.*)/(fr|de|it|en)/(.*)          $1/$3 [E=LANG:$2]
        
        // looks if the language was set in GET parameter
        if (isset($_REQUEST['lang']) && in_array($_REQUEST['lang'], $locales)) {
            define('LANG', $_REQUEST['lang']);
            $log->debug('LANG: $_REQUEST[lang] = ' . LANG);
        }
        // if not, looks if the language was set in env variable (mod_rewrite)
        elseif (isset($_SERVER['LANG']) && in_array($_SERVER['LANG'], $locales)) {
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
        if (!isset($GLOBALS['headless']))
            header('Content-Language: ' . LANG);
        
        // if cookie doesn't exist, set a cookie expiring in one year for current language
        if (!isset($_COOKIE['LangCookie']) || $_COOKIE['LangCookie'] != LANG) {
            setcookie('LangCookie', LANG, time() + 31536000);
        }       
    }
    
    /**
     * Calls translator's bindtextdomain
     * @param string domain
     * @param string path to tranlsations
     * @return string full path
     */
    static private function bindtextdomain($domain, $path) {
        return self::$i18n->bindtextdomain($domain, $path);
    }

    /**
     * Calls translator's textdomain
     * @param string domain
     * @return string domain
     */
    static private function textdomain($domain) {
        return self::$i18n->textdomain($domain);
    }
    
    /**
     * Calls translator's gettext
     * @param string text to translate
     * @return string tranlated text
     */
    static public function gt($text) {
        $result = self::$i18n->gettext(Encoder::decode($text, 'config'));
        if (Encoder::getCharset() == Encoder::getCharset('config')) {
            return $result;
        }
        return Encoder::decode(Encoder::encode($result, 'config'));
    }
    
    /**
     * Calls translator's ngettext
     * @param string text to translate
     * @param string text to translate (plural)
     * @param int count
     * @return string translated text
     */
    static public function ngt($text, $plural, $count) {
        $result = self::$i18n->ngettext(Encoder::decode($text, 'config'),
                                        Encoder::decode($plural, 'config'), $count);
        if (Encoder::getCharset() == Encoder::getCharset('config')) {
            return $result;
        }
        return Encoder::decode(Encoder::encode($result, 'config'));
    }
}

/**
 * Translator interface
 * @package Client
 */
interface I18nInterface {

    /**
     * Wrapper for function bindtextdomain
     * @param string domain
     * @param string path to tranlsations
     */ 
    public function bindtextdomain($domain, $path);
    
    /**
     * Wrapper for function textdomain
     * @param string domain
     */ 
    public function textdomain($domain);
    
    /**
     * Wrapper for function gettext
     * @param string text to translate
     * @return string tranlated text
     */
    static public function gettext($text);

    /** 
     * Wrapper for function ngettext
     * @param string text to translate
     * @param string text to translate (plural)
     * @param int count
     * @return string translated text
     */  
    static public function ngettext($text, $plural, $count);
}

/**
 * Dummy translator (does nothing)
 * @package Client
 */
class I18nDummy implements I18nInterface {

    /**
     * @see I18nInterface::bindtextdomain()
     */
    public function bindtextdomain($domain, $path) {
    }
    
    /**
     * @see I18nInterface::textdomain()
     */
    public function textdomain($domain) {
    }
    
    /**
     * @see I18nInterface::gettext()
     */
    static public function gettext($text) {
        return $text;
    }
    
    /**
     * @see I18nInterface::ngettext()
     */
    static public function ngettext($text, $plural, $count) {
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

    /**
     * @see I18nInterface::bindtextdomain()
     */
    public function bindtextdomain($domain, $path) {
        bindtextdomain($domain, $path);
        $log =& LoggerManager::getLogger(__METHOD__); 
        $log->debug('LANG: binddomain ' . $domain . ' ' . $path);
    }
    
    /**
     * @see I18nInterface::textdomain()
     */
    public function textdomain($domain) {
        textdomain($domain);
        $log =& LoggerManager::getLogger(__METHOD__); 
        $log->debug('LANG: textdomain ' . $domain);
    }
    
    /**
     * @see I18nInterface::gettext()
     */
    static public function gettext($text) {
        return gettext($text);
    }
    
    /**
     * @see I18nInterface::ngettext()
     */
    static public function ngettext($text, $plural, $count) {
        return ngettext($text, $plural, $count);
    }
}

?>
