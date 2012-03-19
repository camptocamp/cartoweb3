#!/usr/bin/env sh
#
#	Cartoweb 3.5+ 
#	Compatibility with php >=5.3x
#	Values in .ini need to be quoted
#	but not boolean like true/false
#	Use this script on all of your project configuration client & server part
# 	Remember to patch also top folders like client_conf & server_conf
#

#Default Pattern to look for, noramlly we only have to change ini.in files
PAT="*ini.in"
SCRIPT="`pwd`/`dirname $0`"

if [ -z "$1" ]; then
  echo "usage $0 'path_to_ini_to_be_modified' [optional pattern for ini ('*ini.dist')] "
  exit 1
fi

if [ ! -z "$2" ]; then
  PAT="$2"
fi

if [ ! -d "$1" ];then
  echo "Directory \"$1\" not readable please check !"
  exit 1
fi

cd "$1"
echo " --- Converting ${PAT} in "$1" --- "
for INI in $(ls ${PAT})
do

  dos2unix --keepdate ${INI}
  sed -f ${SCRIPT}/php53_ini.sed ${INI} > ${INI}.tmp

  mv -fv ${INI} ${INI}.old
  mv ${INI}.tmp ${INI}

# small check about the number of lines which have to be equal
NBLA=`wc -l ${INI}.old | awk '{print $1}'`
NBLB=`wc -l ${INI} | awk '{print $1}'`
if [ $NBLA -ne $NBLB ];then
	echo "  -- WARNING -- nb lines differs for ${INI} !"
fi
NBLA=0
NBLB=1
done

echo " --- Converting done --- "
echo " Old ini file are backuped to .old"
echo " You can remove them once a manual review has been done"
echo " "

