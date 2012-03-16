#!/bin/sh

# 
# Little C program compiled on the fly to test 
# if gettext is correctly installed
# 
# This script needs gettext, gcc, libc6-dev and run, 
# at this time, only on Linux.
#

MAIN="sample"
LOCALE="fr"
LOCALE_PATH="/tmp"

cat > $LOCALE_PATH/$MAIN.c <<EOF
/*
 * Sample program for testing the gettext
 * File  : sample.c
 */

#include<stdio.h>    /* Standard Header File */

/* Header Files required for gettext support */
#include<locale.h>   /* Definition of Locale Variables LC_*  */
#include<libintl.h>  /* Function definitions for NLS */
#include <stdlib.h>

/*
 * Macros for reducing the typing
 * Macros are understandable by the gettext preprocessor
 */
#define _(String) gettext(String)
#define gettext_noop(String) (String)

main(){

    char message[] = gettext_noop("Gettext is not installed correctly !");

    /* Reset the locale variables LC_* */
    setlocale(LC_ALL, "");
    setlocale(LC_TIME, "" );
    setlocale(LC_MESSAGES, "");

    /* Bind with MO File */
    bindtextdomain( "$MAIN", "$LOCALE_PATH");
    textdomain( "$MAIN") ;

    printf("%s\n", _(message));

    return 0;
}
EOF

xgettext -ao $LOCALE_PATH/$MAIN-tmp.po $LOCALE_PATH/$MAIN.c > /dev/null 2>&1
head -n 23 $LOCALE_PATH/$MAIN-tmp.po |sed -e 's/""/"Gettext is OK, well done !"/g' -e 's/CHARSET/UTF-8/g' > $LOCALE_PATH/$MAIN.po

mkdir -p $LOCALE_PATH/$LOCALE/LC_MESSAGES
msgfmt -o $LOCALE_PATH/$LOCALE/LC_MESSAGES/$MAIN.mo $LOCALE_PATH/$MAIN.po

#Compile the program
gcc -o $LOCALE_PATH/$MAIN $LOCALE_PATH/$MAIN.c
    
# Now set [fr] as default language
export LANG=$LOCALE
export LANGUAGE=$LOCALE

# Run the program
$LOCALE_PATH/sample

# Clean all
#rm -f $LOCALE_PATH/sample*
#rm -fR $LOCALE_PATH/$LOCALE

exit 0
