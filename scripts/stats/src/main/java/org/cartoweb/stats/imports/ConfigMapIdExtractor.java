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

import org.ini4j.Ini;

import java.io.FileInputStream;
import java.io.IOException;
import java.util.Map;
import java.util.Set;

public class ConfigMapIdExtractor implements MapIdExtractor {
    private final Map.Entry<String, String>[] entries;
    private Map.Entry<String, String> prevMatch = null;

    public ConfigMapIdExtractor(String mapIdConfig) throws IOException {
        FileInputStream file = new FileInputStream(mapIdConfig);
        try {
            Ini ini = new Ini(file);
            final Ini.Section mappings = ini.get("mapIDs");
            final Set<Map.Entry<String, String>> values = mappings.entrySet();
            entries = (Map.Entry<String, String>[]) values.toArray(new Map.Entry[values.size()]);
        } finally {
            file.close();
        }
    }

    public String extract(String line) {
        if (prevMatch != null && line.contains(prevMatch.getKey())) {
            return prevMatch.getValue();
        }
        for (int i = 0; i < entries.length; i++) {
            Map.Entry<String, String> entry = entries[i];
            if (line.contains(entry.getKey())) {
                prevMatch = entry;
                return entry.getValue();
            }
        }
        return null;
    }
}
