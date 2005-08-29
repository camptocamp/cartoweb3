DATABASE=geo2
PSQL="psql -U postgres $DATABASE"

TABLES="1 2 3 5 7 10 20 30"

# drop tables enventually
for t in $TABLES; do
    $PSQL -c "DROP TABLE edges_perfs_$t"
done

$PSQL -c "VACUUM ANALYZE"

# create sql files
for t in $TABLES; do
    python gen_sql.py $t
done

# insert sql
for t in $TABLES; do
    $PSQL -f create_edges_perfs_$t.sql
done

do_query() {
    $PSQL -f query_edges_perfs_$1.sql
}

exec > perfs_log_$(date +%s) 2>&1
for t in $TABLES; do
    echo "================================="
    echo "  querying $t"
    echo "================================="

    do_query $t
    do_query $t
    do_query $t
done
