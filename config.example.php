<?php

$config = [
    // phonebook
    'phonebook' => [
        'id'        => 0,                                              // only "0" can store quickdial and vanity numbers
        'name'      => 'Telefonbuch',
        'imagepath' => 'file:///var/InternerSpeicher/[YOURUSBSTICK]/FRITZ/fonpix/', // mandatory if you use the -i option
        'forcedupload' => 1,        // 3 = CardDAV contacts overwrite phonebook on Fritz!Box
    ],                              // 2 = like 3, but newer entries will send as VCF via eMail (-> reply)
                                    // 1 = like 2, but vCards are only downloaded if they are newer than the phonebook

    // or server
    'server' => [
        [
            'url' => 'https://...',
            'user' => '',
            'password' => '',
            'http' => [           // http client options are directly passed to Guzzle http client
                // 'verify' => false, // uncomment to disable certificate check
                // 'auth' => 'digest', // uncomment for digest auth
            ]
        ],
/* add as many as you need
        [
            'url' => 'https://...',
            'user' => '',
            'password' => '',
        ],
*/
    ],

    // or fritzbox
    'fritzbox' => [
        'url' => 'http://fritz.box',
        'user' => '',
        'password' => '',
        'fonpix'   => '/[YOURUSBSTICK]/FRITZ/fonpix',   // the storage on your usb stick for uploading images
        'fritzadr' => '/[YOURUSBSTICK]/FRITZ/mediabox'           // if not empty FRITZadr will be written to this location
        'http' => [           // http client options are directly passed to Guzzle http client
            // 'verify' => false, // uncomment to disable certificate check
        ],
        'plainFTP' => false, // set true to use FTP instead of FTPS e.g. on Windows
    ],

    /*
    'reply' => [                                                    // mandatory if you use "forcedupload" < 3 ! 
        'url'      => 'smtp...',
        'port'     => 587,                                          // alternativ 465
        'secure'   => 'tls',                                        // alternativ 'ssl'
        'user'     => '[USER]',                                     // your sender email adress e.g. account
        'password' => '[PASSWORD]',
        'receiver' => 'BlackSenator@github.com',                    // your email adress to receive the secured contacts  
        'debug'    => 0,                                            // 0 = off (for production use)
                                                                    // 1 = client messages
                                                                    // 2 = client and server messages
    ],
    */

    'filters' => [
        'include' => [
            // if empty include all by default
        ],

        'exclude' => [
            'category' => [
                'a', 'b'
            ],
            'group' => [
                'c', 'd'
            ],
        ],
    ],

    'conversions' => [
        'vip' => [
            'category' => [
                'vip1'
            ],
            'group' => [
                'PERS'
            ],
        ],
        /**
         * 'realName' conversions are processed consecutively. Order decides!
         */
        'realName' => [
            '{lastname}, {prefix} {nickname}',
            '{lastname}, {prefix} {firstname}',
            '{lastname}, {nickname}',
            '{lastname}, {firstname}',
            '{organization}',
            '{fullname}'
        ],
        /**
         * 'phoneTypes':
         * The order of the target values (first occurrence) determines the sorting of the telephone numbers
         */
        'phoneTypes' => [
            'WORK' => 'work',
            'HOME' => 'home',
            'CELL' => 'mobile',
            'FAX' => 'fax_work' // NOTE: actual mapping is ignored but order counts, so fac is put last
        ],
        'emailTypes' => [
            'WORK' => 'work',
            'HOME' => 'home'
        ],
        /**
         * 'phoneReplaceCharacters' conversions are processed consecutively. Order decides!
         */
        'phoneReplaceCharacters' => [
            '+49' => '',  // router is usually operated in 'DE; '0049' could also be part of a phone number
            '('   => '',
            ')'   => '',
            '/'   => '',
            '-'   => ''
        ]
    ]
];
