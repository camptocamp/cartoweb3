#!/bin/sh

usage () {
    echo >&2 "htlinks - create symbolic links for a project or for default plugins
          usage: htlinks [-h] [project]"
    exit 1
}

addlinks () {
	for i in `ls $@`; do
		for j in `ls $@/$i`; do
			if [ "$j" = 'htdocs' ]
			then
				cd htdocs
				find -name $i -type l -exec rm {} \;
				cd ..
				ln -s ../$@/$i/htdocs htdocs/$i
			fi
		done
	done
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
	for j in `ls ../projects`; do
		if [ "$j" = "$1" ]
		then
			cd ../htdocs
			find -name $1 -type l -exec rm {} \;
			ln -s ../projects/$1/htdocs $1
			cd $1
			cd ../../projects/$1

			addlinks "plugins"
			exit 1
		fi
	done
	
	usage
fi
