<?php
/**
 * CardDAV to FritzBox! XML (automatic upload)
 * inspired by http://www.wehavemorefun.de/fritzbox/Hochladen_eines_MySQL-Telefonbuchs
 * 
 * Requirements: 
 *   php5, php-curl (Debian/Ubuntu install shortcut: sudo apt-get install php5-cli php5-curl)
 * 
 * used libraries: 
 *  *  vCard-parser <https://github.com/nuovo/vCard-parser> (LICNECE: unknown)
 *  *  CardDAV-PHP <https://github.com/graviox/CardDAV-PHP>(LICENCE: AGPLv3)
 *  *  fritzbox_api_php <https://github.com/carlos22/fritzbox_api_php> (LICENCE: CC-by-SA 3.0)
 * 
 * LICENCE (of this file): MIT
 * 
 * autor: Karl Glatz
 */
error_reporting(E_ALL);
setlocale(LC_ALL, 'de_DE.UTF8');

require_once('lib/CardDAV-PHP/carddav.php');
require_once('lib/vCard-parser/vCard.php');
require_once('lib/fritzbox_api_php/lib/fritzbox_api.class.php');

if(is_file('config.php')) {
	require('config.php');
} else {
	print 'ERROR: No config.php found, please take a look at config.example.php and create a config.php file!'.PHP_EOL;
	exit(1);
}

// ---------------------------------------------

// MAIN

$client = new CardDAV2FB($config);


// read vcards from webdav
print 'Get all entires from CardDAV server(s)... ';
$client->get_carddav_entries();
print 'Done.'.PHP_EOL;

flush(); // in case this script runs by php-cgi

// transform them to a fritzbox compatible xml file
print 'Transform to FritzBox XML File... ';
$client->build_fb_xml();
print 'Done.'.PHP_EOL;

flush(); // in case this script runs by php-cgi

// upload the xml file to the fritz box (CAUTION: this will overwrite all current entries in the phonebook!!)
print 'Upload to fritzbox at '.$config['fritzbox_url'].'...';
$ul_msg = $client->upload_to_fb();
print 'Done.'.PHP_EOL;
print 'FritzBox: '.$ul_msg.PHP_EOL;

flush(); // in case this script runs by php-cgi


class CardDAV2FB {
	
	protected $entries = array();
	protected $fbxml = "";
	protected $config = null;
	
	public function __construct($config) {
		$this->config = $config;
	}
		

	public function get_carddav_entries() {
		$entries = array();

		foreach($this->config['carddav'] as $conf) {
			$carddav = new carddav_backend($conf['url']);
			$carddav->set_auth($conf['user'], $conf['pw']);
			$xmldata =  $carddav->get();
			
			// read raw_vcard data from xml response
			$raw_vcards = array();
			$xmlvcard = new SimpleXMLElement($xmldata);

			foreach($xmlvcard->element as $vcard_element)
			{
				$id = $vcard_element->id->__toString();
				$value = (string)$vcard_element->vcard->__toString();
				$raw_vcards[$id] = $value;
			}

			// parse raw_vcards
			$result = array();
			foreach($raw_vcards as $v) {
				$vcard_obj = new vCard(false, $v);
				
				// name
				$name_arr = $vcard_obj->n[0];
				$name = $name_arr['FirstName']." ".$name_arr['LastName'];
				
				$phone_no = array();
				$c = 0;
				foreach($vcard_obj->tel as $t) {
					
					// only 3 numbers supported by fritz.box
					if($c > 2) break;
					
					//TODO: improve this (add prio and vcard allows types like: TYPE=home,work,cell - how to handle this?)
					$typearr_lower = unserialize(strtolower(serialize($t['Type'])));
					if (in_array("work", $typearr_lower)) {
						$type = "work";
					}
					if (in_array("cell", $typearr_lower)) {
						$type = "mobile";
					}
					if (in_array("home", $typearr_lower)) {
						$type = "home";
					}
					
					$phone_no[] =  array("type"=>$type, "value" => $this->_clear_phone_number($t['Value']));
					$c++;
				}
				$entries[] = array("realName" => $name, "telephony" => $phone_no);
			}
					
			
			
		}
		
		$this->entries = $entries;
	}

	private function _clear_phone_number($number) {
		return preg_replace("/[^0-9]/", "", $number);
	}

	public function build_fb_xml() {
		
		if(empty($this->entries)) {
			throw new Exception('No entries available! Call get_carddav_entries or set $this->entries manually!');
		}
		
		// create FB XML
		$root = new SimpleXMLElement('<?xml version="1.0" encoding="iso-8859-1"?><phonebooks><phonebook></phonebook></phonebooks>');
		$pb = $root->phonebook;
		
		foreach($this->entries as $entry) {
				
				$contact = $pb->addChild("contact");
				$contact->addChild("category");
				$person = $contact->addChild("person");
				$person->addChild("realName", $this->_convert_text($entry['realName']));
				$telephony = $contact->addChild("telephony");
				
				foreach($entry['telephony'] as $tel) {
					$num = $telephony->addChild("number", $tel['value']);
					$num->addAttribute("type", $tel['type']);
					$num->addAttribute("vanity","");
					//TODO: fill prio 
					$num->addAttribute("prio", "");
				}
				
				$contact->addChild("services");
				$contact->addChild("setup");
				$contact->addChild("mod_time", (string)time());
		}
			
		$this->fbxml = $root->asXML();
		
	}

	public function _convert_text($text) {
		
		$text = htmlspecialchars($text);
		//$text = iconv("UTF-8", "ISO-8859-1//IGNORE", $text);
		
		return $text;
	}
	
	public function _parse_fb_result($text) {
			preg_match("/\<h2\>([^\<]+)\<\/h2\>/", $text, $matches);
			
			if($matches)
				return $matches[1];
			else
				return "Error while uploading xml to fritzbox";
	}

	public function upload_to_fb() {
		$msg = "";
		try
		{
		  $fritz = new fritzbox_api($this->config['fritzbox_pw'], $this->config['fritzbox_url']);
		  $formfields = array(
			'PhonebookId' => '0',
		  );
		  
		  $filefileds = array('PhonebookImportFile' => array(
			 'type' => 'text/xml',
			 'filename' => 'updatepb.xml',
			 'content' => $this->fbxml,
			 )
			);

		  $raw_result =  $fritz->doPostFile($formfields, $filefileds);   // send the command
		  $msg = $this->_parse_fb_result($raw_result);
		  $fritz = null;                     					// destroy the object to log out
		}
		catch (Exception $e)
		{
		  print $e->getMessage();     // show the error message in anything failed
		  print PHP_EOL;
		}
		return $msg;
	}
}

?>