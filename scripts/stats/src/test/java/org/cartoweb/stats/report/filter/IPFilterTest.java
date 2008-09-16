package org.cartoweb.stats.report.filter;

import org.cartoweb.stats.BaseTestCase;

import java.net.UnknownHostException;

public class IPFilterTest extends BaseTestCase {
    public IPFilterTest(String name) {
        super(name);
    }

    public void testSimple() throws UnknownHostException {
        IPFilter filter=new IPFilter("192.168.1.1");
        assertEquals("192.168.1.1/255.255.255.255", filter.config);
        assertTrue(filter.match("192.168.1.1"));
        assertFalse(filter.match("192.168.1.2"));
    }

    public void testNumMask() throws UnknownHostException {
        IPFilter filter=new IPFilter("192.168.1.1/24");
        assertEquals("192.168.1.0/255.255.255.0", filter.config);
        assertTrue(filter.match("192.168.1.1"));
        assertTrue(filter.match("192.168.1.255"));
        assertFalse(filter.match("192.168.2.255"));

        filter=new IPFilter("192.168.1.1/32");
        assertEquals("192.168.1.1/255.255.255.255", filter.config);
        assertTrue(filter.match("192.168.1.1"));
        assertFalse(filter.match("192.168.1.2"));

        filter=new IPFilter("192.168.1.1/25");
        assertEquals("192.168.1.0/255.255.255.128", filter.config);
        assertTrue(filter.match("192.168.1.1"));
        assertTrue(filter.match("192.168.1.127"));
        assertFalse(filter.match("192.168.1.128"));

        filter=new IPFilter("192.168.1.1/31");
        assertEquals("192.168.1.0/255.255.255.254", filter.config);
        assertTrue(filter.match("192.168.1.1"));
        assertTrue(filter.match("192.168.1.0"));
        assertFalse(filter.match("192.168.1.2"));
    }

    public void testMask() throws UnknownHostException {
        IPFilter filter=new IPFilter("192.168.1.1/255.255.255.0");
        assertEquals("192.168.1.0/255.255.255.0", filter.config);
        assertTrue(filter.match("192.168.1.1"));
        assertTrue(filter.match("192.168.1.255"));
        assertFalse(filter.match("192.168.2.255"));

        filter=new IPFilter("192.168.1.1/255.255.255.255");
        assertEquals("192.168.1.1/255.255.255.255", filter.config);
        assertTrue(filter.match("192.168.1.1"));
        assertFalse(filter.match("192.168.1.2"));
    }

    public void testMulti() throws UnknownHostException {
        IPFilter filter=new IPFilter("192.168.1.10, !192.168.1.0/255.255.255.0, 0.0.0.0/0");
        assertEquals("192.168.1.10/255.255.255.255,!192.168.1.0/255.255.255.0,0.0.0.0/0.0.0.0", filter.config);
        assertFalse(filter.match("192.168.1.1"));
        assertTrue(filter.match("192.168.1.10"));
        assertTrue(filter.match("192.168.2.0"));        
    }

    public void testIPV6() throws UnknownHostException {
        IPFilter filter=new IPFilter("2001:db8:85a3::8a2e:370:7334");
        assertEquals("2001:db8:85a3:0:0:8a2e:370:7334/ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff", filter.config);
        assertTrue(filter.match("2001:db8:85a3:0:0:8a2e:370:7334"));
        assertFalse(filter.match("2001:db8:85a3:0:0:8a2e:370:7335"));        

        filter=new IPFilter("2001:db8:85a3::8a2e:370:7334/127");
        assertEquals("2001:db8:85a3:0:0:8a2e:370:7334/ffff:ffff:ffff:ffff:ffff:ffff:ffff:fffe", filter.config);
        assertTrue(filter.match("2001:db8:85a3:0:0:8a2e:370:7334"));
        assertTrue(filter.match("2001:db8:85a3:0:0:8a2e:370:7335"));
        assertFalse(filter.match("2001:db8:85a3:0:0:8a2e:370:7336"));
    }

    public void testProxy() throws UnknownHostException {
        IPFilter filter=new IPFilter("192.168.1.1");
        assertFalse(filter.match("192.168.1.1, 10.12.0.1"));
        assertTrue(filter.match("10.12.0.1,192.168.1.1"));
    }
}
