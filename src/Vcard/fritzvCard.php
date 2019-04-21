<?php

/* class fritzvCard delivers a simple function based on VCard
 * to provide a vcf file whose data is based on the FRITZ!Box
 * phonebook entries
 *
 * Copyright (c) 2019 Volker Püschel
 * @license MIT
 */

namespace Andig\Vcard;

use JeroenDesloovere\VCard\VCard;

class fritzvCard
{

    /**
     * get a new simple vCard according to FRITZ!Box phonebook data
     *
     * @param string $name
     * @param array $numbers
     * @param string $email
     * @param string $vip
     * @return string
     */
    public function getvCard ($name, $numbers, $email = '', $vip = '')
    {
        $newVCard = new VCard();

        $parts = explode(', ', $name);
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
        if (!empty($email)) {
            $newVCard->addEmail($email);
        }
        if ($vip == 1) {
            $newVCard->addNote("This contact was marked as important.\nSuggestion: assign to a VIP category or group.");
        }

        return $newVCard->get();
    }
}
