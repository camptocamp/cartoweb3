#
# Accounting log files management (database import, simple stats generation)
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
#
# Author: Sylvain Pasche
#

import sys
import glob
import os.path
import re
from sets import Set
import copy
from datetime import datetime
import logging
logging.basicConfig()
log = logging.getLogger()
if os.environ.has_key("LOG_DEBUG"):
    log.setLevel(logging.DEBUG)

import psycopg

# TODO:
#
# Parse logrotate rotated log files 
#

FIELDS_PAT = re.compile('([\.\w]+)="([^"]*)"')

fields_client = {
    'general': {
        'client_version': 0,
        # Cartoclient
        'export_plugin': (str,),
        # ClientAccounting
        'browser_info': (str,),
        'mapid': (str,),
        'time': (datetime,),
        'ua': (str,),
        'sessid': (str,),
        'direct_access': (bool,), # Is this useful ?

        # CartoserverService
        'request_id': (str,),
        # Security manager
        'security.user': (str,),
    },
    # Core plugins

    # Plugins
    'exportpdf': {
        'client_version': 1,
        'format': (str,),
        'resolution': (int,),
    },                  
}

fields_server = {
    'general': {
        'server_version': 0,
        # Cartoserver
        'elapsed_time': (float,),
        # server.php
        'request_id': (str,),
       
        # MapResultCache / SoapXMLCache
        'cache_id': (str,),
        'cache_hit': (str,),
    },
    # Core plugins
    'images': {
        'server_version': 0,
        'mainmap.width': (int,),
        'mainmap.height': (int,),
    },
    'layers': {
        'server_version': 0,
        'layers': (str,),
    },
    'location': {
        'server_version': 0,
         'bbox': (str,),
         'scale': (float,),
    },
    'query': {
        'server_version': 1,
        'results_count': (int,),
        'results_table_count': (str,),
    },
    # Plugins
    
}

fields_all = copy.deepcopy(fields_client)
for (field_group, sub_fields) in fields_server.iteritems():
    fields_all.setdefault(field_group, {}).update(sub_fields)

##########################
# Update functions

def update_client_exportpdf_0_to_1(fields):
    raise "Client exportpdf version 0 not supported"

def update_server_query_0_to_1(fields):
    raise "Server query version 0 not supported"

###########################

def update_fields_versioning(fields, fields_descr, kind):
    for (field_group, sub_fields) in fields_descr.iteritems():
        if sub_fields.has_key("%s_version" % kind):
            curr_version = sub_fields["%s_version" % kind]
        else:
            raise Exception("Missing version field in field group %s %s" % (kind, field_group))

        version_field = ("%s_%s" % (field_group, "%s_version" % kind))
        version_field = to_column_name(version_field)
        
        try:
            version = int(fields[version_field])
        except KeyError:
            if len(fields.keys()) == 1: # special case for general_request_id
                continue
            if True in [k.startswith(field_group) for k in fields.keys()]:
                log.warn("Missing version field in field group %s %s" % (kind, field_group))
            continue
        
        log.debug("Found field for %s", field_group)
        if version < curr_version:
            for i in range(version, curr_version):
                update_func_name = "update_%s_%s_%s_to_%s" % (kind, field_group, i, i + 1)
                log.debug("Update func: %s", update_func_name)
                fields = globals()[update_func_name](fields)
        elif version > curr_version:
            raise Exception("Version %s (max %s) not yet supported in field group %s %s" % 
                     (version, curr_version, kind, field_group))
    return fields

def update_fields(fields, table_name):

    log.debug("Updating fields %s", fields)
    if table_name == "stats":
        fields = update_fields_versioning(fields, fields_client, "client")
        fields = update_fields_versioning(fields, fields_server, "server")
    elif table_name == "stats_client":
        fields = update_fields_versioning(fields, fields_client, "client")
    elif table_name == "stats_server":
        fields = update_fields_versioning(fields, fields_server, "server")
    
    # Time formatting
    if fields.has_key('general_time'):
        d = datetime.fromtimestamp(float(fields['general_time']))
        fields['general_time'] = d.isoformat()
    
    return fields

def to_column_name(field_name):
    return field_name.replace(".", "_").lower()

def table_columns(fields_descr):
    columns = {}
    type_map = {str: "text",
                int: "integer",
                float: "real",
                datetime: "timestamp",
                bool: "boolean",
                }
    for (field_group, sub_fields) in fields_descr.iteritems():
        cols = []
        for (sub_field, field_type) in sub_fields.iteritems():
            if sub_field in ("client_version", "server_version"):
                continue
            column_name = ("%s_%s" % (field_group, sub_field))
            column_name = to_column_name(column_name)
            t = field_type[0]
            columns[column_name] = type_map[t]
    return columns
    
def parse_log(log_file, fields_descr, table_name):
    log.info("parsing %s", log_file)
    curs = conn.cursor()

    curs.execute("delete from %s" % table_name)
    log.debug("Deleted %s", curs.rowcount)
    
    for l in open(log_file):
        l = l[:-1]
        fields = dict(FIELDS_PAT.findall(l))
        
        new_fields = {}
        for (k, v) in fields.iteritems():
            new_fields[to_column_name(k)] = v
        fields = new_fields

        fields = update_fields(fields, table_name)
        keys = fields.keys()
        cols = table_columns(fields_descr).keys()
        unmapped = Set(keys) - Set(cols)
        log.info("Unmapped fields: %s", list(unmapped))

        missing = Set(cols) - Set(keys)
        log.info("Non filled fields %s", list(missing))
        
        keys_filtered =  Set(keys)
        keys_filtered.intersection_update(Set(cols))
        
        # XXX escaping !!
        values = ["'%s'" % fields[k] for k in keys_filtered]
        sql = """insert into %s (%s) values (%s)""" % \
            (table_name, ",".join(keys_filtered), ",".join(values))
        log.debug(sql)
        curs.execute(sql)
    conn.commit()

def uncachify():
    log.info("uncachify")
    curs = conn.cursor()
    curs.execute("""select distinct general_cache_hit from stats where general_cache_hit is not null""")
    hits = [f[0] for f in curs.fetchall()]
    log.debug("hits %s", hits)

    cols_server = table_columns(fields_server).keys()
    
    # Fields we don't want to propagate:
    cols_server.remove("general_cache_id")
    cols_server.remove("general_request_id")
    
    set_cmd = ", ".join(["%s = s.%s" % (c, c) for c in cols_server])
    set_cmd += ", general_cache_id = 'cache_hit_replaced' "
    for hit in hits:
        sql = """update stats set %s from stats s where stats.general_cache_hit = '%s' and s.general_cache_id = '%s'"""  % \
                (set_cmd, hit, hit)
        log.debug(sql)
        if True:
            curs.execute(sql)
            log.debug("Affected rows: %s", curs.rowcount)
    conn.commit()
    
def merge_client_server():

    cols = table_columns(fields_all).keys()

    cols_client = table_columns(fields_client).keys()
    cols_server = table_columns(fields_server).keys()
    
    cols_insert = []
    cols_select = []
    for c in cols:
        if c in cols_client:
            cols_insert.append(c)
            cols_select.append("stats_client.%s" % c)
        elif c in cols_server:
            cols_insert.append(c)
            cols_select.append("stats_server.%s" % c)
        else:
            log.warn("Warning, common column %s not found" % c)
    
    sql = ("""insert into stats (%s) select %s from stats_client """ + \
        """left outer join stats_server using (general_request_id);""") % \
        (", ".join(cols_insert), ", ".join(cols_select))
    log.debug("SQL %s", sql)
    curs = conn.cursor()
    curs.execute(sql)
    log.debug("Affected rows: %s", curs.rowcount)
    conn.commit()
    
def parse_accounting_dir(ac_dir):
    
    # XXX always delete ??
    curs = conn.cursor()
    for t in ("stats_client", "stats_server", "stats"):
        curs.execute("delete from %s" % t)
        log.debug("Deleted %s", curs.rowcount)
    
    if os.path.exists(ac_dir + "/server_accounting.log"):
        parse_log(ac_dir + "/client_accounting.log", fields_client, "stats_client")
        parse_log(ac_dir + "/server_accounting.log", fields_server, "stats_server")
        merge_client_server()
    else:
        parse_log(ac_dir + "/client_accounting.log", fields_all, "stats")
    
    uncachify()

def raw_stats():
    print "stats"
    curs = conn.cursor()
    curs.execute("select count(*) from stats")
    print "Number of hits %s" % curs.fetchone()
    
    def aggregate_stat(title, sql):
        print "%s :" % title
        curs.execute(sql)
        for (label, value) in curs.fetchall():
            print "  %010s: %s" % (label, value)

    aggregate_stat("hits per user", " select case when general_security_user = '' then " + 
                  "'anonymous' else general_security_user end, " + \
                  "count(1)  from stats group by general_security_user")
    
    aggregate_stat("hits per hour", \
                   "select extract (hour from general_time) as hour, count(1) from stats group by hour")

    aggregate_stat("hits per ua", \
                   "select general_ua, count(1) from stats group by general_ua")
    
    aggregate_stat("hits per export plugin", \
                   "select general_export_plugin, count(1) from stats group by general_export_plugin")

if __name__ == "__main__":

    # TODO: better argument parsing, allow dsn on command line argument

    try:
        DSN = os.environ['DSN']
    except KeyError:
        log.error("Error: You must provide a DSN environment variable for database connection")
        log.error(""" For instance, use DSN="dbname=stats user=my_user password=my_passord port=5432" """)
        sys.exit()
    conn = psycopg.connect(DSN)

    if "-raw_stats" in sys.argv:
        raw_stats()
    if "-gen-schema" in sys.argv:

        tables = [(fields_server, "stats_server"),
                (fields_client, "stats_client"),
                (fields_all, "stats")]
        for (fields_descr, table_name) in tables:
            # XXX confirm drop ?
            curs = conn.cursor()
            try:
                curs = conn.cursor()
                curs.execute("drop table %s" % table_name)
            except psycopg.ProgrammingError, e:
                conn.rollback()
            conn.commit()
            
            sql = "create table %s (" % table_name
            cols = table_columns(fields_descr)
            sql += ", ".join(["%s %s" % (k, v) for (k, v) in cols.items()])
            sql += " );"
            curs.execute(sql)
            conn.commit()

    if "-import" in sys.argv:
        for p in sys.argv[2:]:
            for ac_dir in glob.glob(p + "/*"):
                if os.path.isdir(ac_dir):
                    parse_accounting_dir(ac_dir)
