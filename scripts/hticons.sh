#!/bin/sh

usage () {
        echo >&2 "hticons - create symbolic links for layers icons directories
          usage: hticons [-h] [[-a] [mapId]]
          -a    CartoWeb is standalone: creates Cartoclient htdocs/gfx/icons link"
        exit 1  
}

addlinks () {
        cd ../www-data/icons
        if [ ! -d $@ ]
        then
                mkdir $@
        fi

        [ !  `sudo chown -R www-data $@ > /dev/null 2>&1` ] || [ `chmod -R 777 $@` ]

        cd $@
       
        if [ -d ../../../server_conf/$@/icons ]
        then
                for i in `ls ../../../server_conf/$@/icons`; do
                        find -name $i -type l -exec rm {} \;
                        ln -s ../../../server_conf/$@/icons/$i $i
                done
        fi

        cd ../../../htdocs/gfx
        find -name servicons -type l -exec rm {} \;
        ln -s ../../www-data/icons servicons

        cd ../../scripts
        return
}

addclientlink () {
        cd ../htdocs/gfx
        if [ -d servicons ] || [ -l servicons ]
        then
                ln -s servicons icons 
        fi
        cd ../../scripts
}

if [ "$1" = -h ]
then
        usage

elif [ "$1" = -a ] && [ -d ../server_conf/$2 ]
then
        addlinks "$2"
        addclientlink

elif [ -d ../server_conf/$1 ]
then
        addlinks "$1"

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
