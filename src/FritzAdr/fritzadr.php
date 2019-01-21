<?php

/*
 * The class provides functions to manipulate the address database
 * FRITZ!Adr from AVM. FRITZ!Adr is an address and phonebook for the
 * more or less legacy programs FRITZ!Fon, FRITZ!Data and FRITZ!Com.
 * But still in use for FRITZ!fax (https://ftp.avm.de/archive/fritz.box/tools/fax4box) 
 *
 * the following functions are wrappers around the generic dBase functions:
 * - create a new database:               create
 * - open an existing database:           open
 * - attach a record:                     addRecord
 * - delete a record:                     delRecord
 * - a record as an associative array:    getRecord
 * - number of records:                   countRecord
 * - reorganize database (delete final):  pack
 * - overwrite record:                    replaceRecord
 * - close the database:                  close
 *
 * The DB analysis of several FritzAdr.dbf files has surprisingly
 * shown two variants. Ultimately the 21er works for me.  
 *
 * Author: black senator
 */

namespace Andig\FritzAdr;

class fritzadr
{

    const FRITZADRDEFINITION_19 = [
            ['BEZCHNG',   'C',  40],    //  1
            ['FIRMA',     'C',  40],    //  2
            ['NAME',      'C',  40],    //  3
            ['VORNAME',   'C',  40],    //  4
            ['ABTEILUNG', 'C',  40],
            ['STRASSE',   'C',  40],
            ['PLZ',       'C',  10],
            ['ORT',       'C',  40],
            ['KOMMENT',   'C',  80],
            ['TELEFON',   'C',  64],
            ['MOBILFON',  'C',  64],
            ['TELEFAX',   'C',  64],    // 12
            ['TRANSFER',  'C',  64],
            ['BENUTZER',  'C', 128],
            ['PASSWORT',  'C', 128],
            ['TRANSPROT', 'C',   1],
            ['NOTIZEN',   'C', 254],
            ['EMAIL',     'C', 254],
            ['HOMEPAGE',  'C', 254],     // 19
        ];
    const FRITZADRDEFINITION_21 = [
            ['BEZCHNG',   'C',  40],   // Feld 1
            ['FIRMA',     'C',  40],   // Feld 2
            ['NAME',      'C',  40],   // Feld 3
            ['VORNAME',   'C',  40],   // Feld 4
            ['ABTEILUNG', 'C',  40],
            ['STRASSE',   'C',  40],
            ['PLZ',       'C',  10],
            ['ORT',       'C',  40],
            ['KOMMENT',   'C',  80],
            ['TELEFON',   'C',  64],
            ['TELEFAX',   'C',  64],   // Feld 11
            ['TRANSFER',  'C',  64],
            ['TERMINAL',  'C',  64],
            ['BENUTZER',  'C', 128],
            ['PASSWORT',  'C', 128],
            ['TRANSPROT', 'C',   1],
            ['TERMMODE',  'C',  40],
            ['NOTIZEN',   'C', 254],
            ['MOBILFON',  'C',  64],
            ['EMAIL',     'C', 254],
            ['HOMEPAGE',  'C', 254]   // Feld 21
        ];

    const FRITZADRDEFINITION = self::FRITZADRDEFINITION_21;    // if necessary choose self::FRITZADRDEFINITION_19;

    private $dataBasePath = '',
            $dataBaseID,
            $dataBaseHeader;

    public $numAttributes = 0;


    public function __construct () {
        $this->numAttributes = count(self::FRITZADRDEFINITION);
    }

    /**
     * Creates an adress database with the given definition
     * @return  returns a database link identifier or FALSE
     */
    public function create ($dbPath = '') {
        if (!empty($dbPath)) {
            if (dbase_create($dbPath, self::FRITZADRDEFINITION)) {
                $this->dataBasePath = $dbPath;
                return true;
            }
            else {
                echo 'Error: Can´t create dBase file '.$dbPath;
                return false;
            }
        }
        else {
            echo 'Error: Can´t create dBase file without a location!';
            return false;
        }
    }

    /**
     * Opens the adress database with the given access mode
     * @return  returns a database link identifier or FALSE
     */
    public function open ($dbPath = '') {
        if (!empty($dbPath)) {
            $this->dataBasePath = $dbPath;
        }
        $this->dataBaseID = dbase_open($this->dataBasePath, 2);
        return $this->dataBaseID;
    }

    /**
     * Returns information on the column structure of the given database link identifier
     * @return  array  https://secure.php.net/manual/en/function.dbase-get-header-info.php
     */
    public function getHeader () {
        $this->dataBaseHeader = dbase_get_header_info($this->dataBaseID);
        return $this->dataBaseHeader;
    }

    /**
     * Adds the given data to the database
     * @return  boolean
     */
    public function addRecord ($dbData) {
        return dbase_add_record($this->dataBaseID, $dbData);
    }

    /**
     * Marks the given record to be deleted from the database -> pack
     * @return  boolean
     */
    public function delRecord ($recordNum) {
        return dbase_delete_record($this->dataBaseID, $recordNum);
    }

    /**
     * Gets a record from an adress database as an indexed array
     * @return  an indexed array with the record
     */
    public function getRecord ($recordNum) {
        return dbase_get_record_with_names($this->dataBaseID, $recordNum);
    }

    /**
     * Gets the number of records (rows) in the specified database
     * @return  the number of records in the database or FALSE
     */    
    public function countRecord () {
        return dbase_numrecords($this->dataBaseID);
    }

    /**
     * Packs the specified database by permanently deleting all records marked for deletion
     * @return  boolean
     */
    public function pack () {
        return dbase_pack($this->dataBaseID);
    }

    /**
     * Replaces the given record in the adress database with the given data
     * @return  boolean
     */
    public function replaceRecord ($dbData, $recordNum) {
        return dbase_replace_record($this->dataBaseID, $dbData, $recordNum);
    }

    /**
     * Closes the given database link identifier
     * @return  boolean
     */
    public function close () {
        dbase_close($this->dataBaseID);
    }
}

?>