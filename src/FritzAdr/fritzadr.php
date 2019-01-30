<?php

/*
 * The class provides functions to manipulate the address database
 * FRITZ!Adr from AVM.
 * FRITZ!Adr is an address and phonebook for the more or less legacy
 * programs FRITZ!Fon, FRITZ!Data and FRITZ!Com. But still in use for
 * FRITZ!fax: https://ftp.avm.de/archive/fritz.box/tools/fax4box
 * The database is a dBASE III file, the default name is 'FritzAdr.dbf'. 
 *
 * There are three reasons for using this class:
 * 1. because of the difficulty of implementing the outdated extension
 * for dBase (PECL) for current PHP releases and platforms.
 * 2. due to the fact that for the purposes that only just one file with a
 * defined structure has to be written (no reading or manipulating data
 * in records or whatever else is conceivable)
 * 3. lastly, because it allows to write the data to memory instead
 * of a local stored file. So it is possible to create the file via ftp
 * directly in the target directory.
 *
 * The DB analysis of a few FritzAdr.dbf files has surprisingly
 * shown two variants with 19 e.g. 21 fields.
 * Ultimately the 21er version works for me.
 * 
 * Usage:
 * setting a new instance with the number of fields (default: 21):
 *      $fritzAdr = new fritzadr();    // number of fields
 * appending a record:
 *      $fritzAdr->addRecord (['NAME' => 'John', 'VORNAME' => 'Doe']);
 * receiving the data
 *      file_put_contents('FritzAdr.dbf', $fritzAdr->getDatabase());          
 *
 * Author: Black Senator
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
        ],
        FRITZADRDEFINITION_21 = [
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

    private $dbDefinition,
            $numAttributes = 0,
            $headerLength  = 0,
            $recordLength  = 0,
            $table  = '',   
            $numRecords    = 0;

    /**
     * Initialize the class with basic settings
     */
    public function __construct (int $fields = 21)
    {
        switch ($fields) {
            case 19:
                $this->dbDefinition = self::FRITZADRDEFINITION_19;
                $this->recordLength = 1646;
                break;
            case 21:
                $this->dbDefinition = self::FRITZADRDEFINITION_21;
                $this->recordLength = 1750;
                break;
            default:
                $errorMsg = sprintf('FRITZ!Adr expects a database definition with 19 or 21 entities. You have specified %c!', $fields);
                throw new \Exception($errorMsg);
        }
        $this->numAttributes = $fields;
    }

    /**
     * Assambles the 32 byte header describing the kind of file according to:
     * https://guru-home.dyndns.org/dBase.html
     * Example:
     * 03 67 06 0d 03 20 20 20 c1 02 d6 06 20 20 20 20 20 20 20 20 20 20 20 20 20 20 20 20 01 20 20 20  
     */
    private function setHeader()
    {
        $lastUpdate = getdate(time());
        $this->headerLength = 33 + $this->numAttributes*32;

        $header =
            pack('C', 0x03) .                          // 1       dBase version
            pack('C', $lastUpdate['year'] % 1000) .    // 2       date of last update (3 Bytes)
            pack('C', $lastUpdate['mon']) .            // 3
            pack('C', $lastUpdate['mday']) .           // 4
            pack('V', $this->numRecords) .             // 5 - 8   number of records in the table
            pack('v', $this->headerLength) .           // 9 - 10  number of bytes in the header
            pack('v', $this->recordLength) .           // 11 - 12 number of bytes in the record (1646 or 1750)
            str_pad('', 16, chr(0)) .                  /* 13 - 14 reserved; filled with zeros
                                                        * 15      dBase IV filed; filled with zero
                                                        * 16      dBase IV filed; filled with zero
                                                        * 17 - 20 reserved for multi-user processing
                                                        * 21 - 28 reserved for multi-user processing */
            pack('C', 0x01) .                          // 29      mdx file exist   
            str_pad('', 3, chr(0));                    /* 30      language code
                                                        * 31 - 32 reserved; filled with zeros */
        return $header;
    }
        
    /**
     * Assambles the 32 byte descriptor describing each field (entity) according to:
     * https://guru-home.dyndns.org/dBase.html
     * Example (1 field):
     * 42 45 5a 43 48 4e 47 20 20 20 20 43 20 20 20 20 28 20 20 20 20 20 20 20 20 20 20 20 20 20 20 01
     */
    private function setFieldDescriptor()
    {   
        $entities = null;
        foreach ($this->dbDefinition as $attribute) {
            $entity =
                str_pad($attribute[0], 10, chr(0)) .   // field name filled up with zeros
                pack('C', '0') .                       // separator
                substr($attribute[1],0,1) .            // field type
                str_pad('', 4, chr(0)) .               // reserved; filled with zeros
                pack('C', $attribute[2]) .             // filed length
                pack('C', '0') .                       // field decimal count
                str_pad('', 14, chr(0));               // reserved; filled with zeros
            $entities = $entities . $entity;
        }
        return $entities;
    }

    /**
     * sets the byte separating the complete file header from the records
     */
    private function setHeaderEnd ()
    {
        return pack('C', 0x0d);
    }

    /**
     * Assambles an assoziativ array according to the dbdefinition:
     * Each field name to one entry filled up with blanks to the given
     * field length
     */
    private function getBlankRecord ()
    {
        $record = [];
        foreach ($this->dbDefinition as $field) {                 
            $record[$field[0]] = str_pad('', $field[2], ' ');      // fill every field with the designated amont of space
        }
        return $record;
    }

    /**
     * Set a value to a designated field
     * @param    array    $record  assoziative array of fields (e.g. ['NAME' => '', 'VORNAME' => ''])
     * @param    string   $field  e.g. 'NAME'
     * @param    string   $value  e.g. 'Doe'
     * @return   int      $record
     */
    private function setFieldValue (array $record, $field, $value)
    {
        $fieldLength = strlen($record[$field]);                    // count length of field
        $value = substr($value, 0, $fieldLength);                  // truncates the value to the field length
        $record[$field] = str_pad($value, $fieldLength, ' ');      // fills up with spaces
        return $record;
    }

    /**
     * Add a new record to the database
     * @param   array   $record  assoziative array of fields (e.g. ['NAME' => 'Doe', 'VORNAME' => 'John'])
     */
    public function addRecord (array $record)
    {
        $newRecord = $this->getBlankRecord();          // get an new (empty) record
        foreach ($record as $field => $value) {
            if (isset($value)) {
                // transfer the given values into the new record
                $newRecord = $this->setFieldValue($newRecord, $field, $value);
            }
        }  
        $dataset = pack('C', 0x20);                // start byte (0x2a if record is marked for deletion) 
        foreach ($newRecord as $field) {
            $dataset = $dataset . $field;          // assamble the array into a dataset (ASCII)
        }
        $this->table = $this->table . $dataset;    // append the dataset to the global var table    
        $this->numRecords++;                       // increment the record counter; needed in setHeader()
    }

    /**
     * Return the eof byte
     */
    private function setEndOfFile()
    {
        return pack('C', 0x1a);
    }

    /**
     * Return the dBASE data well formated
     */
    public function getDatabase ()
    {
        $dataBase = $this->setHeader() . $this->setFieldDescriptor() . $this->setHeaderEnd() . $this->table . $this->setEndOfFile();

        return $dataBase;
    }

}
?>