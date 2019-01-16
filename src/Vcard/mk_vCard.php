<?php

namespace Andig\Vcard;


use JeroenDesloovere\VCard\VCard;


class mk_vCard

{

    public function createVCard ($name, $numbers, $email = '', $vip = '')
    {
        $newVCard = new VCard();

        $parts = explode (', ', $name);
        count($parts) !== 2 ? $newVCard->addCompany($name) : $newVCard->addName($parts[0], $parts[1]);
        foreach ($numbers as $number) {
            switch ($number[0]) {
                case 'fax_work' :
                    $newVCard->addPhoneNumber($number[1], 'FAX');
                    break;
                case 'mobile' :
                    $newVCard->addPhoneNumber($number[1], 'CELL');
                    break;
                default :                                   // home & work
                    $newVCard->addPhoneNumber($number[1], strtoupper($number[0]));
                    break;
            }
        }
        if (isset($email)) {
            $newVCard->addEmail($email);
        }
        if ($vip == 1) {
            $newVCard->addNote("This contact was marked as important.\nSuggestion: assign to a VIP category or group.");  
        }
        return $newVCard->get();
    }
}
?>