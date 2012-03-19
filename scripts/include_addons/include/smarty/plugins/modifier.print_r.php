<?php
/*
 * $RCSfile$
 *
 * modified from the modifier included in Gallery
 * a web based photo album viewer and editor
 * Copyright (C) 2000-2006 Bharat Mediratta
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     print_r
 * Purpose:  Dump out the object, reasonably formatted
 * Use: in template : {$some_smarty_variable|@print_r}
 * @ is needed if the variable is an array
 * see http://smarty.php.net/manual/en/language.modifiers.php
 * -------------------------------------------------------------
 */
function smarty_modifier_print_r($object) {

    $content = "<pre>";
    $content .= print_r($object, true);
    $content .= "</pre>";

    return $content;
}
