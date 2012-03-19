<?php

/** 
 * Little script to test if PHP-Gettext 
 * is correctly installed.
 *
 * This script run, at this time, only on Linux
 *
 */

$MAIN = "sample";
$LOCALE = "fr";
$LOCALE_PATH = "/tmp";

system("mkdir -p $LOCALE_PATH/$LOCALE/LC_MESSAGES", $error);
if ($error) {
    echo "error: create $LOCALE_PATH/$LOCALE/LC_MESSAGES folder failed !\n";
    die();
}

$po = "msgid \"\"\n" .
      "msgstr \"\"\n" .
      "\"Project-Id-Version: \\n\"\n" .
      "\"POT-Creation-Date: 2005-07-21 10:05+0000\\n\"\n" .
      "\"PO-Revision-Date: 2005-06-15 15:58+0100\\n\"\n" .
      "\"Last-Translator: Mathieu Bornoz <mathieu.bornoz@camptocamp.com>\\n\"\n" .
      "\"Language-Team: \\n\"\n" .
      "\"MIME-Version: 1.0\\n\"\n" .
      "\"Content-Type: text/plain; charset=ISO-8859-1\\n\"\n" .
      "\"Content-Transfer-Encoding: 8bit\\n\"\n" .
      "msgid \"PHP-Gettext is not installed correctly !\"\n" .
      "msgstr \"PHP-Gettext is OK, well done !\"";

$file = "$LOCALE_PATH/$MAIN.po";   
if (!$file_handle = fopen($file,"w")) { 
    echo "error : cannot create file $file"; 
    die();
}  
if (!fwrite($file_handle, $po)) { 
    echo "error: cannot write to file $file"; 
    die();
}  
fclose($file_handle);   

system("msgfmt -o $LOCALE_PATH/$LOCALE/LC_MESSAGES/$MAIN.mo $file", $error);
if ($error) {
    echo "error: create sample.mo failed with command msgfmt !\n";
    die();
}

if (setlocale(LC_ALL, $LOCALE) != $LOCALE) {
    echo "error: setlocale() failed !\n";
} else {
    putenv("LANG=$LOCALE"); 
    putenv("LANGUAGE=$LOCALE");
    bindtextdomain($MAIN, $LOCALE_PATH); 
    textdomain($MAIN);
    printf("<pre><b>%s</b></pre>\n", gettext("PHP-Gettext is not installed correctly !"));
}
system("rm -fR $LOCALE_PATH/$LOCALE $file", $error);
if ($error) {
    echo "error: can't delete temp files !\n";
    die();
}
