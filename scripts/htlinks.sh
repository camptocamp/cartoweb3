#!/bin/sh

usage () {
    echo >&2 "htlinks - create symbolic links for a project
          usage: htlinks [-h] project"
    exit 1
}

while [ $# -gt 0 ]
do
    case "$1" in
	-h)	usage;;
	*)	break;;
    esac
done

[ $# -lt 1 ] && usage

cd ../htdocs
rm $1
ln -s ../projects/$1/htdocs $1
cd $1
find -type l -exec rm {} \;
cd ../../projects/$1

for i in `ls plugins`; do
	for j in `ls plugins/$i`; do
		if [ "$j" = 'htdocs' ]
		then
			ln -s ../plugins/$i/htdocs htdocs/$i
		fi
	done
done
