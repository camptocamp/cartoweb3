#!/bin/sh

usage () {
        echo >&2 "hticons - create symbolic links for layers icons directories
          usage: hticons [-h] [-a] [mapId [projectName]]
          -h    Returns this help
          -a    CartoWeb is standalone: creates Cartoclient htdocs/gfx/icons link
          If no argument: generate links for every mapId in server_conf/."
        exit 1  
}

addlinks () {
        if [ ! -d www-data ]
        then
                mkdir www-data
        fi
        cd www-data

        if [ ! -d icons ]
        then
                mkdir icons
        fi
        cd icons
        
        if [ ! -d $1 ]
        then
                mkdir $1
        fi

        #[ !  `sudo chown -R www-data $1 > /dev/null 2>&1` ] || [ `chmod -R 777 $1` ]
        chmod -R 777 $1
        
        cd $1
       
        if [ $# -eq 2 ]
        then
                path=../../../../..
        else
                path=../../..
        fi
        
        if [ -d $path/server_conf/$1/icons ]
        then
                for i in `ls $path/server_conf/$1/icons`; do
                        find -name $i -type l -exec rm {} \;
                        ln -s $path/server_conf/$1/icons/$i $i
                done
        fi

        cd ../../../htdocs/gfx
        find -name servicons -type l -exec rm {} \;
        ln -s ../../www-data/icons servicons

        cd ../..
        return
}

addclientlink () {
        cd ../htdocs/gfx
        if [ -d servicons ] || [ -l servicons ]
        then
                ln -s servicons icons 
        fi
}

if [ $# -eq 0 ]
then
        cd ..
        for i in `ls server_conf`; do
                if [ -d server_conf/$i ] && [ ! "$i" = CVS ]
                then
                        addlinks "$i"
                fi
        done
        cd scripts
        addclientlink

elif [ "$1" = -h ]
then
        usage

elif [ $# -eq 2 ] && 
     [ -d ../projects/$2/server_conf/$1 ]
then
        cd ../projects/$2
        addlinks "$1"
        cd htdocs
        addclientlink

elif [ $# -eq 2 ] &&
     [ -d ../projects/$2 ] &&
     [ -d ../server_conf/$1 ]
then
        cd ../projects/$2
        addlinks "$1" "foo"
        cd htdocs
        addclientlink

elif [ -d ../server_conf/$1 ]
then
        cd ..
        addlinks "$1"
        cd scripts
        addclientlink

elif [ "$1" = -a ]
then
        addclientlink

#elif [ "$1" = -clean ] && [ -d ../www-data/icons/$2 ]
#then
#        find ../htdocs/gfx -name *icons -type l -exec rm {} \;
#        find ../www-data/icons -name $2 -type d -exec rm -fr {} \;

else
        usage
fi
