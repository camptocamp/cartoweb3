<?php
/**
 * Encoding classes
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
    
    const DEFAULT_CONTEXT = 'output';
  
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
     * Initializes encoding
     * @param Config
     */
    static function init($config) {
        self::$log =& LoggerManager::getLogger(__CLASS__);

        self::$encoders = array();
        $iniArray = $config->getIniArray();
        foreach ($iniArray as $key => $value) {
            $keyArray = explode('.', $key);
            if ($keyArray[0] == 'EncoderClass') {
                self::$encoders[$keyArray[1]] = new $value;
            }
        }
        if (!array_key_exists(self::DEFAULT_CONTEXT, self::$encoders)) {
            self::$encoders[self::DEFAULT_CONTEXT] = new EncoderUTF();
        }
    }    
    
    static private function getEncoder($context) {
        if (array_key_exists($context, self::$encoders)) {
            return self::$encoders[$context];
        }                
        return self::$encoders[self::DEFAULT_CONTEXT];
    }
    
    /**
     * Calls encoder's encode
     * @param string
     * @return string
     */
    static function encode($text, $context = self::DEFAULT_CONTEXT) {
        return self::getEncoder($context)->encode($text);
    }

    /**
     * Calls encoder's decode
     * @param string
     * @return string
     */
    static function decode($text, $context = self::DEFAULT_CONTEXT) {
        return self::getEncoder($context)->decode($text);
    }
    
    /**
     * Calls encoder's getCharset
     * @return string
     */
    static function getCharset($context = self::DEFAULT_CONTEXT) {
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
    function encode($text);
    
    /**
     * Wrapper for function decode
     * @param string
     * @return string
     */ 
    function decode($text);
    
    /** 
     * Wrapper for function getCharset
     * @return string
     */
    function getCharset();
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
    function encode($text) {
        return $text;
    }

    /**
     * @see EncoderInterface::decode()
     */
    function decode($text) {
        return $text;
    }
    
    /**
     * @see EncoderInterface::getCharset()
     */
    function getCharset() {
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
    function encode($text) {
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
    function decode($text) {
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
    function getCharset() {
        return 'iso-8859-1';
    }
}

?>
