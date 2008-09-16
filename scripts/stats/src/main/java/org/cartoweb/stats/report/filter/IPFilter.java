package org.cartoweb.stats.report.filter;

import org.pvalsecc.misc.StringUtils;

import java.sql.SQLException;
import java.sql.ResultSet;
import java.net.InetAddress;
import java.net.UnknownHostException;
import java.util.regex.Pattern;
import java.util.regex.Matcher;
import java.util.Arrays;

public class IPFilter extends SoftOnlyFilter {
    private final AddressMatcher[] addresses;
    protected final String config;

    public IPFilter(String value) {
        String[] addresses = value.split(",\\s*");
        this.addresses = new AddressMatcher[addresses.length];
        for (int i = 0; i < addresses.length; i++) {
            try {
                this.addresses[i] = new AddressMatcher(addresses[i]);
            } catch (UnknownHostException e) {
                throw new RuntimeException(e);
            }
        }
        config = StringUtils.join(this.addresses, ",");
    }

    public String getSQLFields() {
        return "general_ip";
    }

    public int updateFromResultSet(ResultSet rs, int pos) throws SQLException {
        final String ipTxt = rs.getString(++pos);
        if (rs.wasNull()) {
            ok = false;
        } else {
            try {
                ok = match(ipTxt);
            } catch (UnknownHostException e) {
                ok = false;
            }
        }
        return pos;
    }

    protected boolean match(String ipTxt) throws UnknownHostException {
        if(ipTxt.contains(",")) {
            //went through proxies=>take the address of the last proxy
            final String[] ips = ipTxt.split(",\\s*");
            ipTxt= ips[ips.length-1];
        }
        InetAddress ip = InetAddress.getByName(ipTxt);
        for (int i = 0; i < addresses.length; i++) {
            AddressMatcher address = addresses[i];
            if (address.match(ip)) {
                return address.accept;
            }
        }
        return false;
    }

    public void getIniFile(StringBuilder result) {
        result.append("filters.ip=").append(config).append("\n");
    }

    private static class AddressMatcher {
        private static final Pattern SIMPLE_REGEXP = Pattern.compile("^([!]?)([^/]*)$");
        private static final Pattern NUM_MASK_REGEXP = Pattern.compile("^([!]?)([^/]*)/(\\d{1,3})$");
        private static final Pattern MASK_REGEXP = Pattern.compile("^([!]?)(.*)/([^/]*)$");

        private final boolean accept;
        private final byte[] address;
        private final byte[] mask;

        public AddressMatcher(String text) throws UnknownHostException {
            Matcher matcher = SIMPLE_REGEXP.matcher(text);
            if (matcher.matches()) {
                accept = !matcher.group(1).equals("!");
                address = InetAddress.getByName(matcher.group(2)).getAddress();
                mask = maskFromInt(8 * address.length, address.length);
                check(text);
                return;
            }
            matcher = NUM_MASK_REGEXP.matcher(text);
            if (matcher.matches()) {
                accept = !matcher.group(1).equals("!");
                address = InetAddress.getByName(matcher.group(2)).getAddress();
                mask = maskFromInt(Integer.parseInt(matcher.group(3)), address.length);
                check(text);
                return;
            }
            matcher = MASK_REGEXP.matcher(text);
            if (matcher.matches()) {
                accept = !matcher.group(1).equals("!");
                address = InetAddress.getByName(matcher.group(2)).getAddress();
                mask = InetAddress.getByName(matcher.group(3)).getAddress();
                check(text);
                return;
            }
            throw new RuntimeException("Cannot parse the address: " + text);
        }

        /**
         * Check the filter is consistent and normalize the address.
         */
        private void check(String text) {
            if (address.length != mask.length) {
                throw new RuntimeException("Mask and address incompatible in: " + text);
            }
            for (int i = 0; i < address.length; i++) {
                address[i] &= mask[i];
            }
        }

        /**
         * Create a mask address from an integer (number of MSB to match)
         */
        private static byte[] maskFromInt(int value, int length) {
            byte[] result = new byte[length];
            Arrays.fill(result, (byte) 0);
            int limit = value / 8;
            for (int i = 0; i < limit; ++i) {
                result[i] = (byte) 0xFF;
            }
            if (value % 8 > 0) {
                result[limit] = (byte) ~(0xFF >>> value % 8);
            }
            return result;
        }

        public String toString() {
            StringBuilder result = new StringBuilder();
            if (!accept) {
                result.append('!');
            }
            try {
                result.append(InetAddress.getByAddress(address).getHostAddress());
                result.append('/');
                result.append(InetAddress.getByAddress(mask).getHostAddress());
            } catch (UnknownHostException e) {
                throw new RuntimeException(e);
            }
            return result.toString();
        }

        public boolean match(InetAddress ip) {
            byte[] ipAddress = ip.getAddress();
            if (ipAddress.length != address.length) {
                return false;
            }
            for (int i = 0; i < ipAddress.length; i++) {
                if ((ipAddress[i] & mask[i]) != address[i]) {
                    return false;
                }
            }
            return true;
        }
    }
}
