<?php

namespace Andig\FritzAdr;

/**
 * This class provides a functionality to extract fax numbers
 * and provide them in a simple array with 19 or 21 fields
 * - to pass to FritzAdr.
 * The DB analysis of several FritzAdr.dbf files has surprisingly
 * shown both variants. Ultimately, the 21er works for me.
 * 
 * Author: Volker Püschel
 */

class convert2fa

{
    
    /**
     * delivers an structured adress array of fax numbers from a designated phone book
     *
     * @param   xml    $fbphonebook    phone book in FRITZ!Box format
     * @param   int    $numDataFields  amount of FRITZ!Adr dBase fields
     * @return  array                  fax numbers, names
     */
    public function convert($xml, $numDataFields = 21) : array {
        
        $i = -1;
        $adrRecords = [];
        $xml->asXML('telefonbuch_up.xml');
        foreach ($xml->phonebook->contact as $contact) {
            foreach ($contact->telephony->number as $number) {
                if ((string)$number['type'] == "fax_work") {
                    $i++;
                    $name = $contact->person->realName;
                    $faxnumber = (string)$number;
                    // dBase uses the DOS charset (Codepage 850); htmlspecialchars makes a '&amp;' from '&' must be reset here 
                    $name = str_replace( '&amp;', '&', iconv('UTF-8', 'CP850//TRANSLIT', $name));
                    // create a new empty FRITZadr record
                    $adrRecords[$i] = array_fill (0, $numDataFields, '');  
                    $adrRecords[$i][0] = $name;             // FullName in field 1 ('BEZCHNG')
                    $parts = explode (', ', $name);
                    if (count($parts) !== 2) {              // if the name was not separated by a comma (no first and last name) 
                        $adrRecords[$i][1] = $name;         // fullName in field 2 ('FIRMA')
                    }
                    else {
                        $adrRecords[$i][2] = $parts[0];     // lastname in field 3 ('NAME')
                        $adrRecords[$i][3] = $parts[1];     // firstnme in field 4 ('VORNAME')
                    }
                    if ($numDataFields == 21) {
                        $adrRecords[$i][10] = $faxnumber;   // FAX number in field 11 ('TELEFAX')
                        continue;
                    }
                    if ($numDataFields == 19) {
                        $adrRecords[$i][11] = $faxnumber;   // FAX number in field 12 ('TELEFAX')
                    }
                }
            }
        }
        return $adrRecords;
    }
}
?>