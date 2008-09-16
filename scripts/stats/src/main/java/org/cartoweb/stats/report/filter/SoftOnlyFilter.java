package org.cartoweb.stats.report.filter;

import java.sql.PreparedStatement;
import java.sql.SQLException;

public abstract class SoftOnlyFilter implements Filter {
    protected boolean ok = false;

    public String getSelectWhereClause() {
        return null;
    }

    public int setSelectWhereParams(PreparedStatement stmt, int pos) throws SQLException {
        return pos;
    }

    public boolean softCheck() {
        return ok;
    }
}
