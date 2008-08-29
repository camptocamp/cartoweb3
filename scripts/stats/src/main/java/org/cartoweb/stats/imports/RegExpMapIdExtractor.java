/*
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * @copyright 2008 Camptocamp SA
 */

package org.cartoweb.stats.imports;

import java.util.regex.Pattern;
import java.util.regex.Matcher;

public class RegExpMapIdExtractor implements MapIdExtractor {
    private Pattern mapIdRegExp;

    public RegExpMapIdExtractor(String mapIdRegExp) {
        this.mapIdRegExp = Pattern.compile(mapIdRegExp);
    }

    public String extract(String line) {
        Matcher mapIdMatcher = mapIdRegExp.matcher(line);
        if (!mapIdMatcher.find() || mapIdMatcher.groupCount() != 1) {
            return null;
        } else {
            return mapIdMatcher.group(1);
        }
    }
}
