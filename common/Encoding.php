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
    
    /**
     * Encoder
     * @var EncoderInterface
     */
    static public $encoder;
    
    /**
     * Initializes encoding
     * @param ClientConfig
     */
    static function init($config) {
        if ($config->EncoderClass) {
            self::$encoder = new $config->EncoderClass;
        } else {
            self::$encoder = new EncoderUTF();
        }
    }    
    
    /**
     * Calls encoder's encode
     * @param string
     * @return string
     */
    static function encode($text) {
        return self::$encoder->encode($text);
    }

    /**
     * Calls encoder's decode
     * @param string
     * @return string
     */
    static function decode($text) {
        return self::$encoder->decode($text);
    }
    
    /**
     * Calls encoder's getCharset
     * @return string
     */
    static function getCharset() {
        return self::$encoder->getCharset();
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
