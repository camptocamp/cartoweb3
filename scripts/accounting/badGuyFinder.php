#!/usr/bin/env php
<?php

/**********************************************************************

Utility that analyzes the CartoWeb accounting logs and tries to find
bad guys trying to download the maps.

The algorithm used is:
  - Take into account only non-cached accounting entries
  - Maintain stats records for each IP address:
    - number of requests: "count".
    - maximum number of consecutive requests on the same zoom level,
      without changing zoom level: "maxSameScale".
  - Flag as bad guys all the records that have more than 500 "count"
    or more than 200 "maxSameScale".
  - Every once in a while, purge stats records that had no activity in
    the last hour and that are not flagged as bad guys.

This utility shall be started from the command prompt and takes no
parameters. The accounting data is read from the standard input and
shall be in chronological order. The results are displayed on the
standard output, showing only potential bad guys and sorted by the
number of requests.

The fields in the output are:
  - count: the total number of requests
  - maxSameScale: the maximum number of consecutive requests on the
                  same zoom level, without changing zoom level.
  - badGuyTime: when this record has been flagged as a bad guy.
  - scales: the scales visited and the number of requests in
            parenthesis.
  - IPs: the IP address
  - sessions or nbSessions: if the number of session is small,
            contains the list of sessions and the number of requests
            in parenthesis. Otherwise, contains just the number of
            sessions.

Sample usage (bash shell):
   cat *.log | php badGuyFinder.php > results.txt

*********************************************************************/


// What to use as identifier
//$keyField='general_sessid';
$keyField='general_ip';

// Cleanup frequency (in number of non cache hit)
$cleanupFrequency=10000;

// Inactivity time for the cleanup (in seconds)
$inactiveTime=3600;

// How many requests with the same scale (within the cleanup frequency) to do for being flagged as bad guy.
$badGuySameScale=200;

// How many requests in general (within the cleanup frequency) to do for being flagged as bad guy.
$badGuyCount=500;


$statsMap=array();

# read the stats
$count=0;
while(!feof(STDIN)) {
    $curLine=fgets(STDIN);
    $fields=processLine($curLine);
    if(!array_key_exists('general_cache_hit', $fields)) {
        $key=$fields[$keyField];
        if($key) {
            $stats=$statsMap[$key];
            if(!$stats) {
                $stats=new Stats();
                $statsMap[$key]=$stats;
            }
            $stats->add($fields);

            # clean records that don't look suspicious
            if(++$count>$cleanupFrequency) {
                $old=$fields['general_time']-$inactiveTime;
                $nbDeleted=0;
                foreach($statsMap as $key=>$stats) {
                    if($stats->canDelete($old)) {
                        ++$nbDeleted;
                        unset($statsMap[$key]);
                    }
                }
                $count=0;
            }
        }
    }
}


# print the results sorted by badGuyNess
usort($statsMap, 'cmpStats');
foreach($statsMap as $key=>$stats) {
    if($stats->isBadGuy()) {
        print $stats->__toString()."\n";
    }
}


class Stats {
    public $count=0;
    public $lastScale;
    public $curNbSameScale=0;
    public $maxNbSameScale=0;
    public $lastTime;
    public $sessionIds=array();
    public $ips=array();
    public $scales=array();
    public $badGuyTime=0;
    public $ua;

    public function add(&$fields) {
        $this->count++;
        $this->ips[str_replace('unknown, ', '', $fields['general_ip'])]++;
        $this->lastTime=$fields['general_time'];
        $this->sessionIds[$fields['general_sessid']]++;
        $this->ua=$fields['general_ua'];

        $scale=$fields['location_scale'];
        $this->scales[$scale]++;
        if($scale==$this->lastScale){
            $this->curNbSameScale++;
        } else {
            $this->lastScale=$scale;
            $this->maxNbSameScale=$this->getMaxNbSameScale();
            $this->curNbSameScale=1;
        }

        if(!$this->badGuyTime && $this->isBadGuy()) {
            $this->badGuyTime=$this->lastTime;
        }
    }

    private function getMaxNbSameScale() {
        return max($this->maxNbSameScale, $this->curNbSameScale);
    }

    public function __toString() {
        $result="count=$this->count;maxSameScale=".$this->getMaxNbSameScale().";";
        $result.="badGuyTime=".strftime('%X %x',$this->badGuyTime).";";
        //$result.="ua=\"$this->ua\";";
        $result.="scales=";
        foreach($this->scales as $scale=>$count) {
            $result.="$scale($count)";
        }
        $result.=";";

        if(sizeof($this->ips)>5) {
            $result.="nbIPs=".sizeof($this->ips);
        } else {
            $result.="IPs=";
            foreach($this->ips as $ip=>$count) {
                $result.="$ip($count)";
            }
        }
        $result.=";";

        if(sizeof($this->sessionIds)>5) {
            $result.="nbSessions=".sizeof($this->sessionIds);
        } else {
            $result.="sessions=";
            foreach($this->sessionIds as $id=>$count) {
                $result.="$id($count)";
            }
        }
        return $result;
    }

    public function isBadGuy() {
        global $badGuySameScale, $badGuyCount;
        return $this->getMaxNbSameScale() > $badGuySameScale || $this->count > $badGuyCount;
    }

    public function canDelete($old) {
        return $this->lastTime<$old && !$this->isBadGuy();
    }
}


# Parse a line and return a map {name=>value}
function processLine($line) {
    global $config;

    preg_match_all('/([^=^;]*)="([^"]*)"/', 
               $line, $matches, PREG_SET_ORDER);
    $data = array();
    foreach ($matches as $match) {
        $key = str_replace('.', '_', $match[1]);

        $data[$key] = $match[2];
    }
    return $data;
}

function cmpStats($a, $b) {
    if($a->count != $b->count) {
        return ($a->count < $b->count)?1:-1;
    } else {
        return strcmp($a->ip, $b->ip);
    }
}

