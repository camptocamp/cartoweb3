<?php
/**
 * Throttling helper classes
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
 *
 * @package Plugins
 * @version $Id$
 */

/**
 * Utility class containing static methods for various common tasks.
 */
class ThrottlingUtils {

    /**
     * Create the 'file' complete parent directory.
     *
     * @param String file
     */
    public static function mkdirname($file) {
        if (!file_exists($file)) {
            $dir = dirname($file);
            if ($dir != "" && !file_exists($dir)) {
                Utils::makeDirectoryWithPerms($dir, CARTOWEB_HOME . 'www-data/');
            }
        }
    }

    /**
     * Return the current request IP or NULL if the ip can't be found in the
     * request headers.
     *
     * @return mixed
     */
    public static function getRemoteAddress() {
        $headers = array('HTTP_X_FORWARDED_FOR',
                         'HTTP_X_FORWARDED',
                         'HTTP_CLIENT_IP');

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                return $_SERVER[$header];
            }
        }
        // not behind  a proxy
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return null;
    }
}

/**
 * Buffer class, record ip access count.
 */
class Buffer extends ListInFile {

    /**
     * @var Integer Buffer period in second.
     */
    protected $period;

    /**
     * @var Integer Maximum number of request per ip for this period.
     */
    protected $maxRequest;

    /**
     * Buffer constructor.
     *
     * @param String file
     * @param Integer period Buffer period in second.
     * @param Integer maxRequest number of request before an overflow
     */
    public function __construct($file, $period, $maxRequest) {
        $this->period = $period;
        $this->maxRequest = $maxRequest;

        parent::__construct($file);
    }

    /**
     * Remove record if the period has passed.
     *
     * @param Integer timestamp
     */
    public function clear($now) {
        $keys = array_keys($this->list);
        foreach ($keys as $key) {
            if ($this->list[$key]['start'] + $this->period <= $now) {
                unset($this->list[$key]);
            }
        }
    }

    /**
     * Update the buffer and returns whatever the maximum of request for the
     * period has been reached.
     *
     * @param String ip
     * @param Integer now timestamp
     * @return Boolean
     */
    public function update($ip, $now) {
        // Create record if needed
        if (!array_key_exists($ip, $this->list)) {
            $this->list[$ip] = array('start' => $now, 'count' => 0);
        }
        $this->list[$ip]['count']++;

        if ($this->list[$ip]['count'] >= $this->maxRequest) {
            unset($this->list[$ip]);
            return true;
        }
        return false;
    }

    /**
     * Convert a raw line from the file to it's internal representation.
     *
     * @param String raw line
     */
    public function readLine($line) {
        if ($line) {
            list($ip, $start, $count) = explode(':', $line);
            $this->list[$ip] = array('start' => intval($start),
                                     'count' => intval($count));
        }
    }

    /**
     * Return a formated record.
     *
     * @param String key
     * @param String value
     * @return String
     */
    public function writeLine($key, $value) {
        return "{$key}:{$value['start']}:{$value['count']}\n";
    }
}


/**
 * A WhiteList is a list of zero or more IP networks. The list is read from a
 * config file.
 */
class WhiteList extends ListInFile {

    /**
     * Convert a raw line from the file to it's internal representation.
     *
     * @param String raw line
     */
    public function readLine($line) {
        if ($line) {
            // transform CIDR address into addresses range
            list($base, $bits) = explode('/', $line);
            list($a, $b, $c, $d) = explode('.', $base);

            $i = ($a << 24) + ($b << 16) + ($c << 8) + $d;
            $mask = $bits == 0 ? 0 : (~0 << (32 - $bits));

            $low = $i & $mask;
            $high = $i | (~$mask & 0xFFFFFFFF);

            $this->list[] = array('low'  => $low,
                                  'high' => $high);
        }
    }

    public function sync() {
        // never write list to file !!
    }

    /**
     * Return whatever the provided IP is contained into the white list.
     *
     * @param String ip eg. 192.168.12.7
     * @return Boolean
     */
    public function contains($ip) {
        $items = explode('.', $ip);

        if (count($items) == 4) {
            list($a, $b, $c, $d) = $items;
            $ip = ($a << 24) + ($b << 16) + ($c << 8) + $d;
            foreach ($this->list as $range) {
                if ($ip >= $range['low'] && $ip <= $range['high']) {
                    return true;
                }
            }
        }
        return false;
    }
}

/**
 * An IP can't be staticly add in the list.
 */
class BlackList extends ListInFile {

    /**
     * @var integer The BlackList duration in seconds.
     */
    protected $period;


    /**
     * @see ListInFile::__construct()
     */
    public function __construct($file, $period) {
        $this->period = $period;
        parent::__construct($file);
    }

    /**
     * Remove all the IP from list if they have
     */
    public function clearList($now) {
        $removed = array();
        $keys = array_keys($this->list);
        foreach ($keys as $key) {
            if ($this->list[$key] + $this->period <= $now) {
                $removed[] = $key;
                unset($this->list[$key]);
            }
        }
        return $removed;
    }


    /**
     * Add an ip to the black list.
     *
     * @param String ip
     * @param Integer now when the ip have been added
     */
    public function add($ip, $now) {
        $this->list[$ip] = $now;
    }

    /**
     * Returns whatever the passed ip is included in the blacklist.
     *
     * @param String ip
     * @return Boolean
     */
    public function contains($ip) {
        return array_key_exists($ip, $this->list);
    }
}


/**
 * Handle
 */
class ListInFile {

    /*
     * @var String file name
     */
    protected $file;

    /**
     * @var Array internal list
     */
    protected $list;


    /**
     * ListInFile constructor.
     * Reads the file content and update the internal list.
     *
     * @param String file
     */
    public function __construct($file) {
        $this->file = $file;
        $this->list = array();

        if (file_exists($this->file)) {
            if (($fp = fopen($this->file, 'r')) === FALSE) {
                throw new CartoclientException("Couldn't open the file " .
                                               "for reading ({$this->file})");
            }
            if (flock($fp, LOCK_SH)) {
                while (!feof($fp)) {
                    $line = trim(fgets($fp));
                    $first = substr($line, 0, 1);

                    // skip comments
                    if ($first != ';' && $first != '#') {
                        $this->readLine($line);
                    }
                }
                flock($fp, LOCK_UN);
            } else {
                throw new CartoclientException("Couldn't lock the file ".
                                               "({$this->file})");
            }
            fclose($fp);
        }
    }

    /**
     * Write the full list to the file.
     */
    public function sync() {
        $lines = array();
        foreach ($this->list as $key => $value) {
            $lines[] = $this->writeLine($key, $value);
        }

        ThrottlingUtils::mkdirname($this->file);

        $fp = fopen($this->file, 'w');

        if (flock($fp, LOCK_EX)) {
            fwrite($fp, implode("", $lines));
            flock($fp, LOCK_UN);
        } else {
            throw new CartoclientException("Couldn't lock the file ".
                                           "({$this->file})");
        }
        fclose($fp);
    }

    /**
     * Convert a raw line from the file to it's internal representation.
     *
     * @param String raw line
     */
    public function readLine($line) {
        if ($line) {
            list($key, $value) = explode(':', $line);
            $this->list[$key] = intval($value);
        }
    }

    /**
     * Return a formated record.
     *
     * @param String key
     * @param String value
     * @return String
     */
    public function writeLine($key, $value) {
        return "{$key}:{$value}\n";
    }
}

?>
