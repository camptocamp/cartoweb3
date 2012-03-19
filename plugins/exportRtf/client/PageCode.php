<?php
/*
*
* This script is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*
* @copyright 2005 Camptocamp SA
* @package Plugins
* @version $Id: ClientExportRtf.php,v 1.0  
*/

/**
  * Utils class for handling RTF encoding
  * Only page code Windows 1252 is implemented yet
  */
class PageCode {
    
    /**
    * @var Array The utf caracters list to reencode. This should be a constant 
    * but PHP does not support non scalar constant.
    */
    private static $WIN_1252_CHAR = array(
        '!','"','#','$','%','&',"'",'(',')','*','+',',','-','.','/',':',';',
        '<','=','>','?','@','[','\\',']','^','_','`','{','|','}','~','€','‚','ƒ',
        '„','…','†','‡','ˆ','‰','Š','‹','Œ','Ž','‘','’','“','”','•','–','—','˜',
        '™','š','›','œ','ž','Ÿ',' ','¡','¢','£','¤','¥','¦','§','¨','©','ª','«',
        '¬','' ,'®','¯','°','±','²','³','´','µ','¶','·','¸','¹','º','»','¼','½',
        '¾','¿','À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï',
        'Ð','Ñ','Ò','Ó','Ô','Õ','Ö','×','Ø','Ù','Ú','Û','Ü','Ý','Þ','ß','à','á',
        'â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï','ð','ñ','ò','ó',
        'ô','õ','ö','÷','ø','ù','ú','û','ü','ý','þ','ÿ'
    );
    
    /**
    * @var Array the utf caracters coorespondances used for reencoding. This should be a constant 
    * but PHP does not support non scalar constant.
    */
    private static $WIN_1252_RTF = array(
        "\\u33\\'21","\\u34\\'22","\\u35\\'23","\\u36\\'24","\\u37\\'25",
        "\\u38\\'26","\\u39\\'27","\\u40\\'28","\\u41\\'29","\\u42\\'2a","\\u43\\'2b",
        "\\u44\\'2c","\\u45\\'2d","\\u46\\'2e","\\u47\\'2f","\\u58\\'3a","\\u59\\'3b",
        "\\u60\\'3c","\\u61\\'3d","\\u62\\'3e","\\u63\\'3f","\\u64\\'40","\\u91\\'5b",
        "\\u92\\'5c","\\u93\\'5d","\\u94\\'5e","\\u95\\'5f","\\u96\\'60","\\u123\\'7b",
        "\\u124\\'7c","\\u125\\'7d","\\u126\\'7e","\\u8364\\'80","\\u8218\\'82",
        "\\u402\\'83","\\u8222\\'84","\\u8230\\'85","\\u8224\\'86","\\u8225\\'87",
        "\\u710\\'88","\\u8240\\'89","\\u352\\'8a","\\u8249\\'8b","\\u338\\'8c",
        "\\u381\\'8e","\\u8216\\'91","\\u8217\\'92","\\u8220\\'93","\\u8221\\'94",
        "\\u8226\\'95","\\u8211\\'96","\\u8212\\'97","\\u732\\'98","\\u8482\\'99",
        "\\u353\\'9a","\\u8250\\'9b","\\u339\\'9c","\\u382\\'9e","\\u376\\'9f",
        "\\u160\\'a0","\\u161\\'a1","\\u162\\'a2","\\u163\\'a3","\\u164\\'a4",
        "\\u165\\'a5","\\u166\\'a6","\\u167\\'a7","\\u168\\'a8","\\u169\\'a9",
        "\\u170\\'aa","\\u171\\'ab","\\u172\\'ac","\\u173\\'ad","\\u174\\'ae",
        "\\u175\\'af","\\u176\\'b0","\\u177\\'b1","\\u178\\'b2","\\u179\\'b3",
        "\\u180\\'b4","\\u181\\'b5","\\u182\\'b6","\\u183\\'b7","\\u184\\'b8",
        "\\u185\\'b9","\\u186\\'ba","\\u187\\'bb","\\u188\\'bc","\\u189\\'bd",
        "\\u190\\'be","\\u191\\'bf","\\u192\\'c0","\\u193\\'c1","\\u194\\'c2",
        "\\u195\\'c3","\\u196\\'c4","\\u197\\'c5","\\u198\\'c6","\\u199\\'c7",
        "\\u200\\'c8","\\u201\\'c9","\\u202\\'ca","\\u203\\'cb","\\u204\\'cc",
        "\\u205\\'cd","\\u206\\'ce","\\u207\\'cf","\\u208\\'d0","\\u209\\'d1",
        "\\u210\\'d2","\\u211\\'d3","\\u212\\'d4","\\u213\\'d5","\\u214\\'d6",
        "\\u215\\'d7","\\u216\\'d8","\\u217\\'d9","\\u218\\'da","\\u219\\'db",
        "\\u220\\'dc","\\u221\\'dd","\\u222\\'de","\\u223\\'df","\\u224\\'e0",
        "\\u225\\'e1","\\u226\\'e2","\\u227\\'e3","\\u228\\'e4","\\u229\\'e5",
        "\\u230\\'e6","\\u231\\'e7","\\u232\\'e8","\\u233\\'e9","\\u234\\'ea",
        "\\u235\\'eb","\\u236\\'ec","\\u237\\'ed","\\u238\\'ee","\\u239\\'ef",
        "\\u240\\'f0","\\u241\\'f1","\\u242\\'f2","\\u243\\'f3","\\u244\\'f4",
        "\\u245\\'f5","\\u246\\'f6","\\u247\\'f7","\\u248\\'f8","\\u249\\'f9",
        "\\u250\\'fa","\\u251\\'fb","\\u252\\'fc","\\u253\\'fd","\\u254\\'fe",
        "\\u255\\'ff"
    );
    
    /**
     * function that encodes a UTF string to an RTF compliant string
     * @param text An UTF string
     * @return An RTF compliant string
     */
    static function encodeUtfToRtf($text) {
        return str_replace(self::$WIN_1252_CHAR, self::$WIN_1252_RTF,$text);
    }
}
