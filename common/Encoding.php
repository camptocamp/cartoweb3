<?php
/**
 * Encoding classes
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
 * @package Server
 * @version $Id$
 */

/**
 * Encoder selection
 *
 * The Encoder is selected using client.ini's EncoderClass.
 * @package Server
 */
class Encoder {
  
    /**
     * @var Logger
     */
    static private $log;
      
    /**
     * List of Encoder
     * @var array array of EncoderInterface
     */
    static public $encoders;

    /**
     * Sets default encoding object (UTF8).
     * @param string context
     */
    static private function setDefault($context) {
        if (!array_key_exists($context, self::$encoders)) {
            if ($context == 'data' && array_key_exists('config', self::$encoders)) {
                self::$encoders[$context] = self::$encoders['config'];
            } else {
                self::$encoders[$context] = new EncoderUTF();
            }
        }
    }
    
    /**
     * Initializes encoding
     * @param Config
     */
    static public function init(Config $config) {
        self::$log =& LoggerManager::getLogger(__CLASS__);

        self::$encoders = array();
        $iniArray = $config->getIniArray();
        foreach ($iniArray as $key => $value) {
            $keyArray = explode('.', $key);
            if ($keyArray[0] == 'EncoderClass') {
                if (class_exists($value)) {
                    self::$encoders[$keyArray[1]] = new $value;
                }
            }            
        }
        self::setDefault('config');
        self::setDefault('output');
        self::setDefault('data');

    }
   
    /**
     * @param string context
     * @return EncoderInterface
     */
    static private function getEncoder($context) {
        return self::$encoders[$context];
    }
    
    /**
     * Calls encoder encode
     * @param mixed
     * @return mixed
     */
    static public function encode($text, $context = 'output') {
        //$array = debug_backtrace();
        //self::$log->debug("ENCODE($text,$context), " . $array[2]['class'] . "." . $array[2]['function']);     

        if (is_array($text) || is_object($text)) {
            foreach ($text as &$content) {
                $content = self::encode($content, $context);
            }
            return $text;
        }
        
        return self::getEncoder($context)->encode($text);
    }

    /**
     * Calls encoder decode
     * @param mixed
     * @return mixed
     */
    static public function decode($text, $context = 'output') {
        //$array = debug_backtrace();
        //self::$log->debug("DECODE($text,$context), " . $array[2]['class'] . "." . $array[2]['function']);     

        if (is_array($text) || is_object($text)) {
            foreach ($text as &$content) {
                $content = self::decode($content, $context);
            }
            return $text;
        }
        
        return self::getEncoder($context)->decode($text);
    }
    
    /**
     * Calls encoder's getCharset
     * @param string context
     * @return string
     */
    static public function getCharset($context = 'output') {
        return self::getEncoder($context)->getCharset();
    }
}

/**
 * Encoder interface
 * @package Client
 */
interface EncoderInterface {

    /**
     * Wrapper for function encode
     * @param string
     * @return string
     */ 
    public function encode($text);
    
    /**
     * Wrapper for function decode
     * @param string
     * @return string
     */ 
    public function decode($text);
    
    /** 
     * Wrapper for function getCharset
     * @return string
     */
    public function getCharset();
}

/**
 * UTF8 en/decoder
 *
 * Does nothing, as everything (server files, SOAP XML) is UTF8-encoded.
 * @package Client
 */
class EncoderUTF implements EncoderInterface {

    /**
     * @see EncoderInterface::encode()
     */
    public function encode($text) {
        return $text;
    }

    /**
     * @see EncoderInterface::decode()
     */
    public function decode($text) {
        return $text;
    }
    
    /**
     * @see EncoderInterface::getCharset()
     */
    public function getCharset() {
        return 'utf-8';
    }
}

/**
 * ISO-8859-1 en/decoder
 * @package Client
 */
class EncoderISO implements EncoderInterface {

    /**
     * @see EncoderInterface::encode()
     */
    public function encode($text) {
        if (is_array($text)) {
            $result = array();
            foreach ($text as $key => $value) {
                $result[utf8_encode($key)] = utf8_encode($value);
            }
            return $result;
        }
        return utf8_encode($text);
    }

    /**
     * @see EncoderInterface::decode()
     */
    public function decode($text) {
        if (is_array($text)) {
            $result = array();
            foreach ($text as $key => $value) {
                $result[utf8_decode($key)] = utf8_decode($value);
            }
            return $result;
        }
        return utf8_decode($text);
    }
    
    /**
     * @see EncoderInterface::getCharset()
     */
    public function getCharset() {
        return 'iso-8859-1';
    }
}

?>
