#!/usr/local/bin/php -dvariables_order="GPS" -dauto_prepend_file= -dauto_append_file=
<?php
/**
 * cwprof.php - Computes execution times for client, server and Mapserver
 *
 * This command line script takes one or two APD trace files and computes
 * execution times for the client, the server and Mapserver.
 *
 * First usage is used with a single trace file (direct, client or server).
 * Second usage looks for most recent trace file(s) in a directory.
 *
 * "-local" option indicates that client and server trace files are located
 * in the same directory. In this case, the script will parse the two files
 * and give a merged result.
 *
 * Usage:
 *    ./cwprof.php <trace_file>
 * or ./cwprof.php [-local] <trace_dir> 
 *
 * Original code was pprofp, a script included in the APD distribution.
 *
 * @package Scripts
 * @author Yves Bolognini <yves.bolognini@camptocamp.com>
 */

error_reporting(0);

/**
 * Main parsing function
 * @param string
 * @return array
 */
function parseFile($fileName) {
    $opt['O'] = 1000000;
    $opt['R'] = '';
    
    if (!file_exists($fileName)) {
        usage("File $fileName not found");
    }
      
    if (($DATA = fopen($fileName, "r")) == FALSE) {
        usage("Cannot open $fileName");
    }
    
    $cfg = array();
    parse_info('HEADER', $DATA, $cfg);
    
    $callstack = array();
    $calls = array();
    $indent_cur = 0;
    $file_hash = array();
    $mem = array();
    $t_rtime = 0;
    $t_stime = 0;
    $t_utime = 0;
    $c_rtimes = array();
    $c_stimes = array();
    $c_utimes = array();
    $rtimes = array();
    $stimes = array();
    $utimes = array();
    $rtotal = 0;
    $stotal = 0;
    $utotal = 0;
    $last_memory = 0;
    
    $symbol_hash = array();
    $symbol_type = array();
    
    while ($line = fgets($DATA)) {
        $line = rtrim($line);
        if (preg_match("/^END_TRACE/", $line)){
            break;
        }
        list($token, $data) = preg_split("/ /",$line, 2);
        if ($token == '!') {
            list ($index, $file) = preg_split("/ /", $data, 2);
            $file_hash[$index] = $file;
            continue;
        }
        if ($token == '&') {
            list ($index, $name, $type) = preg_split("/ /", $data, 3);
            $symbol_hash[$index] = $name;
            $symbol_type[$index] = $type;
            continue;
        }
        if ($token == '+') {
            list($index, $file, $line) = preg_split("/ /",$data, 3);
            if (array_key_exists('i',$opt) && $symbol_type[$index] == 1) {
                continue;
            }
            $index_cur = $index;
            $calls[$index_cur]++;
            array_push($callstack, $index_cur);
            if (array_key_exists('T', $opt)) {
                if (array_key_exists('c', $opt)) {
                    printf("%2.02f ", $rtotal/1000000);
                }
                print str_repeat('  ', $indent_cur) . 
                      $symbol_hash[$index_cur] . "\n";
                if (array_key_exists('m', $opt)) {
                    print str_repeat('  ', $indent_cur) . 
                          "C: $file_hash[$file]:$line M: $memory\n";
                }
            } elseif (array_key_exists('t', $opt)) {
                if ( $indent_last == $indent_cur && $index_last == $index_cur ) {
                    $repcnt++;
                } else {
                    if ( $repcnt ) {
                        $repstr = ' ('.++$repcnt.'x)';
                    }
                    if(array_key_exists('c', $opt)) {
                        printf("%2.02f ", $rtotal/1000000);
                    }
                    print str_repeat('  ', $indent_last) .
                          $symbol_hash[$index_last] . $repstr . "\n";
                    if(array_key_exists('m', $opt)) {
                        print str_repeat('  ', $indent_cur) .
                           "C: $file_hash[$file_last]:$line_last M: $memory\n";
                    }
                    $repstr = '';
                    $repcnt = 0;
                    $index_last = $index_cur;
                    $indent_last = $indent_cur;
                    $file_last = $file;
                    $line_last = $line;
                }
            }
            $indent_cur++;
            continue;
        }
        if ($token == '@') {
            list($file_no, $line_no, $ut, $st, $rt) = preg_split("/ /", $data);
            $top = array_pop($callstack);
            $utimes[$top] += $ut;
            $utotal += $ut;
            $stimes[$top] += $st;
            $stotal += $st;
            $rtimes[$top] += $rt;
            $rtotal += $rt;
            array_push($callstack, $top);
            foreach ($callstack as $stack_element) {
                $c_utimes[$stack_element] += $ut;
                $c_stimes[$stack_element] += $st;
                $c_rtimes[$stack_element] += $rt;
            }
            continue;
        }
        if ($token == '-') {
            list($index, $memory) = preg_split("/ /", $data, 2);
            if (array_key_exists('i',$opt) && $symbol_type[$index] == 1) {
                continue;
            }
            $mem[$index] += ($memory - $last_memory);
            $last_memory = $memory;
            $indent_cur--;
            $tmp = array_pop($callstack);
            continue;
        }
    }
    parse_info('FOOTER', $DATA, $cfg);
    $sort = 'by_time';
    if(array_key_exists('l', $opt)) { $sort = 'by_calls'; }
    if(array_key_exists('m', $opt)) { $sort = 'by_mem'; }
    if(array_key_exists('a', $opt)) { $sort = 'by_name'; }
    if(array_key_exists('v', $opt)) { $sort = 'by_avgcpu'; }
    if(array_key_exists('r', $opt)) { $sort = 'by_rtime'; }
    if(array_key_exists('R', $opt)) { $sort = 'by_c_rtime'; }
    if(array_key_exists('s', $opt)) { $sort = 'by_stime'; }
    if(array_key_exists('S', $opt)) { $sort = 'by_c_stime'; }
    if(array_key_exists('u', $opt)) { $sort = 'by_utime'; }
    if(array_key_exists('U', $opt)) { $sort = 'by_c_utime'; }
    if(array_key_exists('Z', $opt)) { $sort = 'by_c_time'; }
    if( !count($symbol_hash)) {
        continue;
    }
    
    // is caller server ?
    $server = strpos($cfg['caller'], 'server.php');
    
    /*
    printf("
    Trace for %s
    Total Elapsed Time = %4.2f
    Total System Time  = %4.2f
    Total User Time    = %4.2f
    ", $cfg['caller'], $rtotal/1000000, $stotal/1000000, $utotal/1000000);
    print "\n
             Real         User        System             secs/    cumm
    %Time (excl/cumm)  (excl/cumm)  (excl/cumm) Calls    call    s/call  Memory Usage Name
    --------------------------------------------------------------------------------------\n";
    */
    $l = 0;
    $itotal = 0;
    $percall = 0;
    $cpercall = 0;
    
    // time elapsed in Mapserver
    $ms_time = 0;
    $ms_new = 0;
    $getMap = 0;
    
    uksort($symbol_hash, $sort);
    foreach (array_keys($symbol_hash) as $j) {
        if(array_key_exists('i', $opt) && $symbol_type[$j] == 1) {
            continue;
        }
        if ($l++ <  $opt['O']) {
            $pcnt = 100 * ($stimes[$j] + $utimes[$j]) / 
                    ($utotal + $stotal + $itotal);
            $c_pcnt = 100 * ($c_stimes[$j] + $c_utimes[$j]) / 
                      ($utotal + $stotal + $itotal);
            $rsecs = $rtimes[$j]/1000000;
            $ssecs = $stimes[$j]/1000000;
            $usecs = $utimes[$j]/1000000;
            $c_rsecs = $c_rtimes[$j]/1000000;
            $c_ssecs = $c_stimes[$j]/1000000;
            $c_usecs = $c_utimes[$j]/1000000;
            $ncalls = $calls[$j];
            if (array_key_exists('z', $opt)) {
                $percall = ($usecs + $ssecs) / $ncalls;
                $cpercall = ($c_usecs + $c_ssecs) / $ncalls;
                if ($utotal + $stotal) {
                    $pcnt = 100 * ($stimes[$j] + $utimes[$j]) / 
                            ($utotal + $stotal);
                } else {
                    $pcnt = 100;
                }
        }
        if (array_key_exists('Z', $opt)) {
                $percall = ($usecs + $ssecs)/$ncalls;
                $cpercall = ($c_usecs + $c_ssecs)/$ncalls;
                if($utotal + $stotal) {
                    $pcnt = 100 * ($c_stimes[$j] + $c_utimes[$j]) /
                            ($utotal + $stotal);
                } else {
                    $pcnt = 100;
                }
        }
        if (array_key_exists('r', $opt)) {
            $percall = ($rsecs)/$ncalls;
            $cpercall = ($c_rsecs)/$ncalls;
            if($rtotal) {
                $pcnt = 100 * $rtimes[$j] / $rtotal;
            } else {
                $pcnt = 100;
            }
        }
        if (array_key_exists('R', $opt)) {
                $percall = ($rsecs) / $ncalls;
                $cpercall = ($c_rsecs) / $ncalls;
                if($rtotal) {
                    $pcnt = 100 * $c_rtimes[$j] / $rtotal;
                } else {
                    $pcnt = 100;
                }
        }
        if (array_key_exists('u', $opt)) {
            $percall = ($usecs) / $ncalls;
            $cpercall = ($c_usecs) / $ncalls;
            if($utotal) {
                $pcnt = 100 * $utimes[$j] / $utotal;
            } else {
                $pcnt = 100;
            }
        }
        if (array_key_exists('U', $opt)) {
            $percall = ($usecs) / $ncalls;
            $cpercall = ($c_usecs) / $ncalls;
            if($utotal) {
                $pcnt = 100 * $c_utimes[$j] / $utotal;
            } else {
                $pcnt = 100;
            }
        }
        if (array_key_exists('s', $opt)) {
            $percall = ($ssecs) / $ncalls;
            $cpercall = ($c_ssecs) / $ncalls;
            if($stotal) {
                $pcnt = 100 * $stimes[$j] / $stotal;
            } else {
                $pcnt = 100;
            }
        }
        if (array_key_exists('S', $opt)) {
            $percall = ($ssecs) / $ncalls;
            $cpercall = ($c_ssecs) / $ncalls;
            if($stotal) {
                $pcnt = 100 * $c_stimes[$j] / $stotal;
            } else {
                $pcnt = 100;
            }
        }
        //$cpercall = ($c_usecs + $c_ssecs)/$ncalls;
        $mem_usage = $mem[$j];
        $name = $symbol_hash[$j];
        
        if ($name == 'ms_newmapobj') {
            $ms_new += $rsecs;
        } elseif (substr($name, 0, 3) == 'ms_') {
            $ms_time += $rsecs;
        } elseif ($name == 'SoapClient->getMap') {
            $getMap += $rsecs;
        }
        //printf("%3.01f %2.02f %2.02f  %2.02f %2.02f  %2.02f %2.02f  %4d  %2.04f   %2.04f %12d %s\n", $pcnt, $rsecs, $c_rsecs, $usecs, $c_usecs, $ssecs, $c_ssecs, $ncalls, $percall, $cpercall, $mem_usage, $name);
        }
    }
    
    $result = array();
    if ($server) {
        $result['type']    = 'server';
        $result['client']  = NULL;
        $result['server']  = round($rtotal / 1000);
        $result['msobj']   = round($ms_new * 1000);
        $result['msother'] = round($ms_time * 1000);
        $result['total']   = NULL;
    } else {
        if ($ms_time > 0.0) {
            $result['type']    = 'direct';
            $result['client']  = round(($rtotal / 1000) - 
                                 (($ms_new + $ms_time) * 1000));
            $result['server']  = 'n/a (direct mode)';
            $result['msobj']   = round($ms_new * 1000);
            $result['msother'] = round($ms_time * 1000);
            $result['total']   = round($rtotal / 1000);
        } else {
            $result['type']    = 'client';
            $result['client']  = round(($rtotal / 1000) - ($getMap * 1000));
            $result['server']  = NULL;
            $result['msobj']   = NULL;
            $result['msother'] = NULL;
            $result['total']   = round($rtotal / 1000);
        }
    }
    
    return $result;
}

/**
 * Returns the pos-th last file in a directory
 * @param string
 * @param int
 * @param string
 */
function getFile($dir, $pos) {
    $filedirs = scandir($dir);
    $files = array();
    foreach ($filedirs as $filedir) {
        if (!is_dir($dir . $filedir)) {
        
            $files[$filedir] = $filedir;
        }
    }
   
    arsort($files);
    $i = 0;
    foreach ($files as $file => $index) {
        if ($pos == $i) {
            break;
        }
        $i++;
    }
    if ($i < count($files)) {
        return $file;
    } else {
        return NULL;
    }
}

if ($_SERVER['argc'] == 2) {

    $filedir = $_SERVER['argv'][1];
    if (is_dir($filedir)) {
        
        if (substr($filedir, -1) != '/') {
            $filedir .= '/';
        }
        // Argument was a directory, get the last file
        $file = getFile($filedir, 0);

        if ($file) {
            $result = parseFile($filedir . $file);
        } else {
            usage("No files found in $filedir");
        }
    } else {
    
        // Argument was a file
        $result = parseFile($filedir);   
    }
    if ($result['type'] != 'direct') {
        print 'Warning: Results are incomplete (' . $result['type'] . 
              " side only)\n";
    }
} elseif ($_SERVER['argc'] == 3 && $_SERVER['argv'][1] == '-local') {
    
    $dir = $_SERVER['argv'][2];
    if (is_dir($dir)) {

        if (substr($dir, -1) != '/') {
            $dir .= '/';
        }

        // Get server file
        $file2 = getFile($dir, 0);
        if ($file2) {
            $result2 = parseFile($dir . $file2);
            if ($result2['type'] == 'client') {
                usage('Second file was not a server trace file');
            }
        } else {
            usage("Not enough files found in $dir");            
        }
        
        if ($result2['type'] == 'direct') {
        
            // Trace file was in direct mode, no need for a second trace file
            $result = $result2;
        } else {
        
            // Get client file
            $file1 = getFile($dir, 1);
            if ($file1) {
                $result1 = parseFile($dir . $file1);
                if ($result1['type'] != 'client') {
                    usage("First file was not a client trace file");
                }
            } else {
                usage("Not enough files found in $dir");            
            }
                
            $result = $result1;
            $result['server'] = $result2['server'];
            $result['msobj'] = $result2['msobj'];
            $result['msother'] = $result2['msother'];
        }
        
    } else {
        usage("Directory $dir not found");
    }
} else {
    usage();
}
print "Exec client       = " . $result['client'] . "\n";
print "Exec server total = " . $result['server'] . "\n";
print "Exec MS obj       = " . $result['msobj'] . "\n";
print "Exec MS other     = " . $result['msother'] . "\n";
print "Exec total        = " . $result['total'] . "\n";

/**
 * Prints usage with an error message
 * @param string
 */
function usage($message = NULL) {
    if ($message) {
        print "ERROR: $message\n";
    }
    print "Usage: ./cwprof.php <trace_file>\n";
    print "    or ./cwprof.php [-local] <trace_dir>\n";    
    exit(1);
}

/**
 * @param string
 * @param PHP resource handle
 * @param array
 */
function parse_info($tag, $datasource, &$cfg) {
    while($line = fgets($datasource)) {
        $line = rtrim($line);
        if(preg_match("/^END_$tag$/", $line)) {
            break;
        }
        if(preg_match("/(\w+)=(.*)/", $line, $matches)) {
            $cfg[$matches[1]] = $matches[2];
        }
    }
}

/**
 * Compares to numbers.
 *
 * Returns 1 if int($a) > int($b),
 * -1 if int($a) < int($b)
 * else 0
 * @param float
 * @param float
 * @return int
 */
function num_cmp($a, $b) {
    if (intval($a) > intval($b)) {
        return 1;
    }
    
    if(intval($a) < intval($b)) {
        return -1;
    }
    
    return 0;
}

/**
 * @return int
 */
function by_time($a,$b) {
    global $stimes;
    global $utimes;
    return num_cmp(($stimes[$b] + $utimes[$b]),
                   ($stimes[$a] + $utimes[$a]));
}

/**
 * @return int
 */
function by_c_time($a,$b) {
    global $c_stimes;
    global $c_utimes;
    return num_cmp(($c_stimes[$b] + $c_utimes[$b]),
                   ($c_stimes[$a] + $c_utimes[$a]));
}

/**
 * @return int
 */
function by_avgcpu($a,$b) {
    global $stimes;
    global $utimes;
    global $calls;
    return num_cmp(($stimes[$b] + $utimes[$b]) / $calls[$b],
                   ($stimes[$a] + $utimes[$a]) / $calls[$a]);
}

/**
 * @return int
 */
function by_calls($a, $b) {
    global $calls;
    return num_cmp($calls[$b], $calls[$a]);
}

/**
 * @return int
 */
function by_rtime($a,$b) {
    global $rtimes; 
    return num_cmp($rtimes[$b], $rtimes[$a]);
}

/**
 * @return int
 */
function by_c_rtime($a,$b) {
    global $c_rtimes;
    return num_cmp($c_rtimes[$b], $c_rtimes[$a]);
}

/**
 * @return int
 */
function by_stime($a,$b) {
    global $stimes;
    return num_cmp($stimes[$b], $stimes[$a]);
}

/**
 * @return int
 */
function by_c_stime($a,$b) {
    global $c_stimes;
    return num_cmp($c_stimes[$b], $c_stimes[$a]);
}

/**
 * @return int
 */
function by_utime($a,$b) {
    global $utimes;
    return num_cmp($utimes[$b], $utimes[$a]);
}

/**
 * @return int
 */
function by_c_utime($a,$b) {
    global $c_utimes;
    return num_cmp($c_utimes[$b], $c_utimes[$a]);
}

/**
 * @return int
 */
function by_mem($a, $b) {
    global $mem;
    return num_cmp($mem[$b], $mem[$a]);
}
    
?>
