<?php
/**
 * ClientAccouting tests
 *
 * This program is free software; you can redistribute it and/or modify
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
 * @copyright 2006 Camptocamp SA
 * @package Tests
 * @version $Id$
 */
 
 require_once(CARTOWEB_HOME . 'common/Accounting.php');
 require_once(CARTOWEB_HOME . 'client/ClientAccounting.php');
 
/**
 * Unit tests for ClientAccounting
 * @package Tests
 * @author Sylvain Pasche <sylvain.pasche@camptocamp.com> 
 */
 class client_ClientAccountingTest extends PHPUnit_Framework_TestCase {
 
    public function testFileAccounting() {
        $targetAccountingFile =  CARTOWEB_HOME . 
                    '/www-data/accounting/test_main.test/client_accounting.log';
        $targetServerAccountingFile =  CARTOWEB_HOME . 
                    '/www-data/accounting/test_main.test/server_accounting.log';

        if (file_exists($targetAccountingFile)) {
            unlink($targetAccountingFile);
        }
        if (file_exists($targetServerAccountingFile)) {
            unlink($targetServerAccountingFile);
        }

        $cartoclient = new Cartoclient();
                
        $accounting = Accounting::getInstance();
        $sampleValue = 'Sample accounting value';
        $accounting->account('test.label0', $sampleValue);
        $accounting->save();
        
        $this->assertTrue(file_exists($targetAccountingFile), 
                          'Accounting file not created correctly');
        $content = file_get_contents($targetAccountingFile);
        $this->assertTrue(strpos($content, $sampleValue) !== false, 
                   'accounting file not written correctly');

        unlink($targetAccountingFile);
        if (file_exists($targetServerAccountingFile)) {
            unlink($targetServerAccountingFile);
        }
    }

    public function testDbAccounting() {
        /* TODO */
    }
}
