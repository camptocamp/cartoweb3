#!/bin/sh

usage () {
    echo >&2 "htlinks - create symbolic links for a project or for default plugins
          usage: htlinks [-h] [project]"
    exit 1
}

addlinks () {
	if [ -d $@ ]
	then
		for i in `ls $@`; do
			if [ -d $@/$i/htdocs ]
			then
				cd htdocs
				find -name $i -type l -exec rm {} \;
				cd ..
				ln -s ../$@/$i/htdocs htdocs/$i
			fi
		done
	fi
}

if [ "$1" = -h ]
then 
	usage
fi

if [ $# -lt 1 ]
then
	cd ..
	
	addlinks "plugins"
	addlinks "coreplugins"
else
	if [ -d ../projects/$1 ]
	then
		cd ../htdocs
		find -name $1 -type l -exec rm {} \;
		
		if [ -d ../projects/$1/htdocs ]
		then
			ln -s ../projects/$1/htdocs $1
			cd $1
			cd ../../projects/$1
		
			addlinks "plugins"
		fi
	else	
		usage
	fi
fi
