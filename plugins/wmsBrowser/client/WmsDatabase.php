<?php
/* Modifs by CartoWeb dev team on WMSDatabase class:
 * - move DB_SERVER, DB_CAPABILITIES, DB_BBOX, DB_STYLE constants to class 
 *   constants and delete remainings
 * - Simplify and add CartoWeb errors management to getDb, getDBStructure, 
 *   find_record, list_records, setInfo, findMaxValue, dropTable methods, and 
 *   renames them respectively to getDb, getDbStructure, findRecord, 
 *   listRecords, setValue, findMaxValue, dropTable.
 * - Delete remaining methods
 * - Add other methods to :
 *      - check user rights on wms cache directory (checkDirRights method),
 *      - manage errors on ordinary files handling (deleteFile, openFile, 
 *        getFileContent)
 *      - manage errors on dbf files handling (dbaseOpen, dbaseAddRecord, 
 *        dbaseDeleteRecord, dbaseReplaceRecord, dbaseClose)
 *        
 * @version $Id$ 
 */
////////////////////////////////////////////////////////////////////////////////
// MapBrowser application                                                     //
//                                                                            //
// @project     MapLab                                                        //
// @purpose     This is the dbase database management utility page.           //
// @author      William A. Bronsema, C.E.T. (bronsema@dmsolutions.ca)         //
// @copyright                                                                 //
// <b>Copyright (c) 2002, DM Solutions Group Inc.</b>                         //
// Permission is hereby granted, free of charge, to any person obtaining a    //
// copy of this software and associated documentation files(the "Software"),  //
// to deal in the Software without restriction, including without limitation  //
// the rights to use, copy, modify, merge, publish, distribute, sublicense,   //
// and/or sell copies of the Software, and to permit persons to whom the      //
// Software is furnished to do so, subject to the following conditions:       //
//                                                                            //
// The above copyright notice and this permission notice shall be included    //
// in all copies or substantial portions of the Software.                     //
//                                                                            //
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR //
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,   //
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.  IN NO EVENT SHALL   //
// THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER //
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING    //
// FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER        //
// DEALINGS IN THE SOFTWARE.                                                  //
////////////////////////////////////////////////////////////////////////////////

class WmsDatabase {
    
    const DB_SERVER       = 'server.dbf';
    const DB_CAPABILITIES = 'capab.dbf';
    const DB_BBOX         = 'bbox.dbf';
    const DB_STYLE        = 'style.dbf';
    
    /**
     * Deletes a file
     * @param string file path
     */
    static public function deleteFile($filePath) {
        if (file_exists($filePath) && !@unlink($filePath))
                throw new CartoclientException(sprintf('An error occured ' .
                    'while deleting %s', $filePath));
    }
        
    /**
     * Opens file or url
     * @param string file or url path
     * @param string type of access you require to the stream
     * @param boolean if true (default), throws exception if open failed
     * @return resource a named ressource specified by filename to a stream
     */
    static public function openFile($filePath, $mode, $strict = true) {
        try {
            $resource = @fopen($filePath, $mode);
        } catch (Exception $e) {   
            if ($strict)    
                throw new CartoclientException(sprintf('An error occured ' .
                    'while opening %s', $filePath));
            else
                return false;
        }
        
        return $resource;                                   
    }
    
    /**
     * Reads entire file into an array 
     * @param string file path
     * @return array or string
     */
    static public function getFileContent($filePath) {
        if (!file_exists($filePath) || !$result = file($filePath))
            throw new CartoclientException(sprintf('An error occured while' .
                                                   'reading %s', $filePath));
               
        return $result;
    }
    
    /**
     * Opens a dBase database with the given access mode.
     * @param string name of the dbf file
     * @param integer access mode
     *      0 means read-only 
     *      1 means write-only
     *      2 means read and write
     * @return database link identifier if the database is successfully opened, 
     * or false if an error occurred
     */
    static public function dbaseOpen($dbfFileName, $mode) {
        if (!$db = dbase_open(WMS_CACHE_DIR . $dbfFileName, $mode))
            throw new CartoclientException(sprintf('An error occured while ' .
                                           'opening %s', $dbfFileName));
        return $db;
    }
    
    /**
     * Adds the given data to the database
     * @param integer identifier for the database (must be open)
     * @param array indexed array of data. The number of items must be 
     * equal to the number of fields in the database
     */
    static public function dbaseAddRecord($db, $record) {
        if (!dbase_add_record($db, $record))
            throw  new CartoclientException('An error occured while ' .
                'adding record in database');
    }
   
    /**
     * Marks the given record to be deleted from the database.
     * @param integer identifier for the database (must be open)
     * @param integer record number
     */
    static public function dbaseDeleteRecord($db, $dbRecId) {
        if (!dbase_delete_record($db, $dbRecId))
            throw new CartoclientException(sprintf('An error occured while ' .
                'deleting record %s', $dbRecId));
    }
    
    /**
     * Replaces the given record in the database with the given data.
     * @param integer identifier for the database (must be open)
     * @param array indexed array of data. The number of items 
     * must be equal to the number of fields in the database
     * @param integer record number
     */
    static public function dbaseReplaceRecord($db, $record, $dbRecId) {
        if (!dbase_replace_record($db, $record, $dbRecId))
            throw new CartoclientException(sprintf('An error occured while ' .
                'replacing record %s', $dbRecId));
    }

    /**
     * Closes database
     * @param integer identifier for the database (must be open)
     * @param boolean if true(default), pack database
     */
    static public function dbaseClose($db, $pack = true) {
        if ($pack && !dbase_pack($db))
                throw new CartoclientException ('An error occured while' .
                                                'packing database');
        @dbase_close($db);
    }
    
    /**
     * Open a database by filename and return a reference to it
     * @param string name of the database to open
     * @return link id for the database or false if the open failed.
     */
    static public function getDb($dbfFile) {
        if (file_exists(WMS_CACHE_DIR . $dbfFile)) {
            $db = self::dbaseOpen($dbfFile, 2);
        } else {
            $struct = self::getDbStructure($dbfFile);
            if ($struct != false) {
                if (!file_put_contents(WMS_CACHE_DIR . $dbfFile . '.struct', 
                                       serialize($struct)))
                    throw new CartoclientException(sprintf('An error occured ' .
                        'while writing content to %s file', $dbfFile));    
                // create the database using the appropriate field definitions
                if (!($db = dbase_create(WMS_CACHE_DIR . $dbfFile, $struct)))
                    throw new CartoclientException(sprintf('Invalid ' .
                        'database name: %s'), $dbFile);
            }
        }
        return $db;
    }

    /**
     * Retrieve table structure
     * @param string the name of the database to retrieve structure
     * @return array of table structure.
     */
    static protected function getDbStructure($dbfFile) {
        $struct = array();
        if ($dbfFile == self::DB_SERVER) {
            $struct[] = array('server_id',  'N', 16, 0);
            $struct[] = array('capab_url',  'C', 255);
            $struct[] = array('map_url',    'C', 255);
            $struct[] = array('version',    'C', 64);
            $struct[] = array('formats',    'C', 128);
            $struct[] = array('name',       'C', 64);
            $struct[] = array('title',      'C', 64);
            $struct[] = array('comment',    'C', 255);
            $struct[] = array('con_status', 'N', 1, 0);
        } elseif ($dbfFile == self::DB_CAPABILITIES) {
            $struct[] = array('layer_id',   'N', 16, 0);
            $struct[] = array('server_id',  'N', 16, 0);
            $struct[] = array('name',       'C', 100);
            $struct[] = array('title',      'C', 100);
            $struct[] = array('srs_ids',    'C', 50);
            $struct[] = array('mdu_type',   'C', 50);
            $struct[] = array('mdu_fmt',    'C', 50);
            $struct[] = array('mdu_url',    'C', 255);
            $struct[] = array('abstractid', 'N', 16, 0);
            $struct[] = array('ll_minx',    'N', 16, 5);
            $struct[] = array('ll_miny',    'N', 16, 5);
            $struct[] = array('ll_maxx',    'N', 16, 5);
            $struct[] = array('ll_maxy',    'N', 16, 5);
            $struct[] = array('minscale',   'N', 16, 5);
            $struct[] = array('maxscale',   'N', 16, 5);
            $struct[] = array('bbox_id',    'N', 16, 0);
            $struct[] = array('style_id',   'N', 16, 0);
            $struct[] = array('depth',      'C', 12);
            $struct[] = array('queryable',  'N', 1, 0);
        } elseif ($dbfFile == self::DB_BBOX) {
            $struct[] = array('bbox_id',    'N', 16, 0);
            $struct[] = array('SRS',        'C', 64);
            $struct[] = array('minx',       'N', 16, 5);
            $struct[] = array('miny',       'N', 16, 5);
            $struct[] = array('maxx',       'N', 16, 5);
            $struct[] = array('maxy',       'N', 16, 5);
            $struct[] = array('next_id',    'N', 16, 0);
        } elseif ($dbfFile == self::DB_STYLE) {
            $struct[] = array('style_id',   'N', 16, 0);
            $struct[] = array('name',       'C', 100);
            $struct[] = array('title',      'C', 100);
            $struct[] = array('legendurl',  'C', 255);
            $struct[] = array('leg_height', 'N', 16, 0);
            $struct[] = array('leg_width',  'N', 16, 0);
            $struct[] = array('leg_format', 'C', 255);
            $struct[] = array('stylesheet', 'C', 255);
            $struct[] = array('styleurl',   'C', 255);
            $struct[] = array('next_id',    'N', 16, 0);
        } else {
            throw new CartoclientException(sprintf('Unrecognized'.
                                           'database name (%s)', $dbfFile));
        }
        return $struct;
    }
    
    
    /**
     * Gets a record from a database as a trimmed associtive array
     * @param integer identifier for the database (must be open)
     * @param integer record index
     * @return array database record
      */
    static public function getRecordById($db, $id) {
        if (!$dbRec = @dbase_get_record_with_names($db, $id))
            return new CartoclientException(sprintf('Invalid record index: %s',
                                            $id));
        
        $dbRec = array_map(create_function('$value', 
            'return Encoder::encode($value, \'config\');'), $dbRec);
        return array_map('trim', $dbRec);
    }
    
    /**
     * Search the database for a field containing a value
     * @param integer identifier for the database (must be open)
     * @param string field name to look it
     * @param mixed value to look for in the field
     * @return integer the record number or false if not found
     */
    static public function findRecord($db, $field, $value) {
        $nDb = dbase_numrecords($db);
        if ($nDb == 0)
            return false;
        for ($i = 1; $i <= $nDb; $i++) {
            $rec = self::getRecordById($db, $i);
            if ($rec[$field] == trim($value))
                return $i;
        }

        return false;
    }
    
    /**
     * Searchs a database for a particular value in a particular field and 
     * returns an array of values from another field in matched records
     * @param integer database identifier (must be open)
     * @param string name of the field to match
     * @param mixed value to look for in the match field
     * @param string name of the field to return values from
     * @param string name of the field to additionally match
     * @param mixed value to look for in the additional match field
     * @return array of values
     */
    static public function listRecords($db, $matchField, $matchValue, 
                                       $extractField,$extraFilterField = '', 
                                       $extraFilterValue = '') {
        $values = array();
        $nDb = dbase_numrecords($db);
        for ($i = 1; $i <= $nDb; $i++) {
            $dbRec = self::getRecordById($db, $i);
            if ($extraFilterField != '' && $extraFilterValue != '') {
                // use extra filter criteria
                if ($dbRec[$matchField] == $matchValue &&
                    $dbRec[$extraFilterField] == $extraFilterValue) {
                    if (isset($dbRec['position']))
                        $position = $dbRec['position'];
                    else
                        $position = count($values);
                    $values[$position] = $dbRec[$extractField];
                }
            } else {
                // no extra filter necessary
                if ($dbRec[$matchField] == $matchValue) {
                    if (isset($dbRec['position']))
                        $position = $dbRec['position'];
                    else
                        $position = count($values);
                    $values[$position] = $dbRec[$extractField];
                }
            }
        }
        
        return $values;
    }
    
    /**
     * Searchs the database for a field containing a value and return record
     * @param integer identifier for the database (must be open)
     * @param string name of the field to match
     * @param mixed value to look for in the match field
     * @return array matching record in the database
     */
    static public function getRecordBy($db, $matchField, $matchValue) {
        if (!$dbRecId = self::findRecord($db, $matchField, $matchValue))
            return false;
        return self::getRecordById($db, $dbRecId);
    }
    
    /**
     * Sets a key = value pair for the given record number in the given db.
     * @param integer identifier for the database (must be open)
     * @param integer row number to set the key = value pair on
     * @param string field name to set
     * @param mixed value to set
     * @return boolean true if the update succeeded, false otherwise
     */
    static public function setValue($db, $dbRecId, $key, $value) {
        $dbRec = self::getRecordById($db, $dbRecId);
        // remove 'deleted' item
        array_pop($dbRec);
        if (!in_array($key, array_keys($dbRec)))
            throw new CartoclientException(sprintf('Invalid field: %s', 
                                                   $key));
        $dbRec[$key] = $value;
        self::dbaseReplaceRecord($db, array_values($dbRec), $dbRecId);
    }

    /**
     * Assumes a numeric field, will find the maximum value in that field of
     * all records in the dbf file by scanning every line. Intended
     * primarily for scanning ID fields.
     * @param integer identifier for the database (must be open)
     * @param string name of the field to scan.
     * @return integer maximum value or false if it failed.
     */
    static public function findMaxValue($db, $field) {
        $nDb = dbase_numrecords($db);
        $result = 0;
        if ($nDb == 0) return $result;
        
        for ($current = 1; $current <= $nDb; $current++) {
            $dbRec = self::getRecordById($db, $current);
            $result = max($result, $dbRec[$field]);
            if (!isset($dbRec[$field]))
                return new CartoclientException(sprintf('%s is not a ' .
                                                'valid field', $field));
        }

        return $result;
    }

    /**
     * Delete all dbf tables if no parameter is specified,
     * else delete only the table which name is specified.
     * @param string name of the dbf table to delete
     */
    static protected function dropTable($tableName = '') {
        if ($tableName == '' || $tableName == self::DB_SERVER)
            self::deleteFile(WMS_CACHE_DIR . self::DB_SERVER);
        if ($tableName == '' || $tableName == self::DB_CAPABILITIES)
            self::deleteFile(WMS_CACHE_DIR . self::DB_CAPABILITIES);
        if ($tableName == '' || $tableName == self::DB_BBOX)
            self::deleteFile(WMS_CACHE_DIR . self::DB_BBOX);
        if ($tableName == '' || $tableName == self::DB_STYLE)
            self::deleteFile(WMS_CACHE_DIR . self::DB_STYLE);
    }
}
?>
