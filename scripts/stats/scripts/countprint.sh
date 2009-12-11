#!/bin/bash
# -----------------------------------------------------------------------------
# countprint.sh
#
# This script count the number of generated PDFs out of Haproxy or Varnish
# logs.
# There must be one log file per day named YYYY-MM-DD-haproxy.log(.gz), or 
# YYYY-MM-DD.log(.gz) for Varnish logs. Log lines containing string
# /print/create are considered as print requests.
#
# Examples:
#   countprint.sh
#     All log files
#   countprint.sh -p schweizmobil
#     All log files, hits coming from schweizmobil project
#   countprint.sh -df 2009-11-17
#     Hits starting at Nov 11, 2009
#   countprint.sh -df 2009-11-17 -dt 2009-11-19 -tt 10:00:00
#     Hits from Nov 17 at 0 am to Nov 19 at 10 am
#
# Nov 19, 2009
# -----------------------------------------------------------------------------


showusage() {
    echo Usage: `basename $0` [OPTIONS]
    echo Try `basename $0` -h for more information.
    exit
}

showhelp() {
    echo Usage: `basename $0` [OPTIONS]
    echo "       -df   From date (format YYYY-MM-DD)"
    echo "       -tf   From time (format HH:MM:SS)"
    echo "       -dt   To date (format YYYY-MM-DD)"
    echo "       -tt   To time (format HH:MM:SS)"
    echo "       -p    Project (default all)"
    echo "       -l    Logs directory"
    echo "       -f    Format: haproxy or varnish"
    echo "       -h    Display this help and exit"
    exit
}

datefrom=0000-00-00
timefrom=00:00:00
dateto=9999-99-99
timeto=99:99:99
project=all
logdir=.
format=haproxy
postfix=-haproxy
while [ "$1" != "" ]; do
    case $1 in
        -df )  shift
               datefrom=$1
               ;;
        -tf )  shift
               timefrom=$1
               ;;
        -dt )  shift
               dateto=$1
               ;;
        -tt )  shift
               timeto=$1
               ;;
        -p )   shift
               project=$1
               ;;
        -l )   shift
               logdir=$1
               ;;
        -f )   shift
               format=$1               
               ;;
        -h )   showhelp
               exit
               ;;
        * )    showusage
               exit 1
    esac
    shift
done

if [[ $datefrom > $dateto ]] ||
   ([[ $datefrom == $dateto ]] && [[ $timefrom > $timeto ]]); then
    echo "Error: from date-time > to date-time"
    showusage
fi

if [ ! -d $logdir ]; then
    echo "Error: $logdir is not a directory"
    showusage
fi

if [[ $format != 'haproxy' ]] && [[ $format != 'varnish' ]]; then
    echo "Error: wrong format"
    showusage
fi
if [[ $format == 'varnish' ]]; then
    postfix=''
fi

currentdir=`pwd`
cd $logdir
count=0
for date in `ls *.log *.log.gz |
             awk -F"$postfix.log" '{print $1}'`; do
    if [ ! -e $date$postfix.log.gz -a ! -e $date$postfix.log ]; then
        continue
    fi
    if ([[ $date == $datefrom ]] || [[ $date > $datefrom ]]) &&
       ([[ $date == $dateto ]] || [[ $date < $dateto ]]); then    
        field=0
        filename=$date$postfix.log.gz
        if [ -e $date$postfix.log ]; then
            filename=$date$postfix.log
        fi
        echo "Processing file $logdir/$filename"        
        list=""
        if [[ $format == 'haproxy' ]]; then
            if [ -e $date$postfix.log.gz ]; then
                list=`gunzip -c $filename | grep /print/create | 
                      awk -F" " '{print $3; print $9}' |
                      awk -F"/" '{print $1}'`
            fi
            if [ -e $date$postfix.log ]; then
                list=`grep /print/create $filename |                                                                       
                      awk -F" " '{print $3; print $9}' |
                      awk -F"/" '{print $1}'`
            fi
        fi
        if [[ $format == 'varnish' ]]; then
            if [ -e $date$postfix.log.gz ]; then
                list=`gunzip -c $filename | grep /print/create | 
                      awk -F" " '{print $4; print $11}' |
                      awk -F"/" '{print $3 ":" $6}' |
                      awk -F":" '{print $2 ":" $3 ":" $4; print $5}'`
            fi
            if [ -e $date$postfix.log ]; then
                list=`grep /print/create $filename |                                                                       
                      awk -F" " '{print $4; print $11}' |
                      awk -F"/" '{print $3 ":" $6}' |
                      awk -F":" '{print $2 ":" $3 ":" $4; print $5}'`
            fi
        fi
        for time in $list; do
            if [ $field -eq 0 ]; then
                increment=1
                if [[ $date == $datefrom ]]; then
                    # First date, checking time
                    if [[ $time < $timefrom ]]; then
                        increment=0
                    fi
                fi
                if [[ $date == $dateto ]]; then
                    # Last date, checking time
                    if [[ $time > $timeto ]]; then
                        increment=0
                    fi
                fi
            fi
            if [ $field -eq 1 ] && [ $increment -eq 1 ]; then
                # Checking project (only if time test passed, ie increment == 1)
                if [[ $project == "all" ]] || [[ $time == $project ]]; then
                    let count+=1
                fi
            fi
            let field=1-$field    
        done
    fi
done

echo "PDF count = $count"
cd $currentdir

