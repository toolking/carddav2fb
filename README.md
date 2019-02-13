# CardDAV contacts import for AVM FRITZ!Box
[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=BB3W3WH7GVSNW)

Purpose of the software is the (automatic) uploading of contact data from CardDAV servers as a phone book into an AVM Fritz!Box.

This is an extendeded version of https://github.com/andig/carddav2fb which is an entirely simplified version of https://github.com/jens-maus/carddav2fb with much more features and stability.

## Features

  * download from any number of CardDAV servers
  * selection (include/exclude) by categories or groups (e.g. iCloud)
  * upload of contact pictures to display them on the FRITZ!Fon (handling see below)
  * transfer of quick dial and vanity numbers (see wiki for handling details)
  * if more than nine phone numbers are included, the contact will be divided into a corresponding number of phonebook entries (any existing email addresses are assigned to the first set [there is no quantity limit!])
  * phone numbers are sorted by type. The order of the conversion values ('phoneTypes') determines the order in the phone book entry
  * the contact's UID of the CardDAV server is added to the phonebook entry (not visible in the FRITZ! Box GUI)

  Additonal with this version (fork):
  * specify with `forcedupload` whether the phone book should be overwritten, or if phone numbers that are not included in the upload are to be saved as vcf by e-mail (see wiki for handling details). Last but not least: whether a download from the CardDAV server should be made if there are no new changes or not.
  * specify with `fritzadr` if fax numbers should be extracted from the phonebook and stored as FRITZ!Fax (fax4box) adressbook (FritzAdr.dbf)

**Have a look in the [wiki](https://github.com/BlackSenator/carddav2fb/wiki) for further information!**

## Requirements

  * PHP 7.0 (`apt-get install php7.0 php7.0-cli php7.0-curl php7.0-mbstring php7.0-soap php7.0-xml`)
  * Composer (follow the installation guide at https://getcomposer.org/download/)

## Installation

Install carddav2fb:

    git clone https://github.com/BlackSenator/carddav2fb.git
    cd carddav2fb
    composer install

Install composer (see https://getcomposer.org/download/ for newer instructions):

    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php -r "if (hash_file('SHA384', 'composer-setup.php') === 544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    php composer-setup.php
    php -r "unlink('composer-setup.php');"
    mv composer.phar /usr/local/bin/composer
    composer install

Edit `config.example.php` and save as `config.php` or use an other name of your choice (but than keep in mind to use the -c option to define your renamed file)

## Usage

List all commands:

    php carddav2fb.php list

Complete processing:

    php carddav2fb.php run

Or, if you want to upload profil images:

    php carddav2fb.php run -i

If you want to use a different name for the configuration file instead of config.php:

    php carddav2fb.php run -c different_conf.php

Get help for a command:

    php carddav2fb.php run -h

### Precondition for using image upload (command -i)

  * your memory (USB stick) is indexed [Heimnetz -> Speicher (NAS) -> Speicher an der FRITZ!Box]
  * ftp access is activ [Heimnetz -> Speicher (NAS) -> Heimnetzfreigabe]
  * you use an standalone user (NOT `dslf-config`!) which has explicit permissions for FRITZ!Box settings, access to NAS content and read/write permission to all available memory [System -> FRITZ!Box-Benutzer -> [user] -> Berechtigungen]

### Precondition for using this version

  * In addition composer.json includes two additional libraries - so if your upgrading ´composer.lock´ must be deleted and reinstalled
  * the config.example.php contains additional settings - so if your upgrading be aware to include them to your config.php

## License
This script is released under Public Domain, some parts under GNU AGPL or MIT license. Make sure you understand which parts are which.

## Authors
Copyright (c) 2012-2019 Karl Glatz, Christian Putzke, Martin Rost, Jens Maus, Johannes Freiburger, Andreas Götz, Volker Püschel