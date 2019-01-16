<?php

namespace Andig\FritzBox;


class TR064

{
    private $ip;
    private $user;
    private $password;
    private $client = null;
    private $errorCode;
    private $errorText;


    public function __construct($ip, $user, $password)
    {
        $this->ip       = $ip;
        $this->user     = $user;
        $this->password = $password;
    }

    /**
     * delivers a new SOAP client
     *
     * @param   string $url       Fritz!Box IP
     * @param   string $location  TR-064 area (https://avm.de/service/schnittstellen/)
     * @param   string $service   TR-064 service (https://avm.de/service/schnittstellen/)
     * @param   string $user      Fritz!Box user
     * @param   string $password  Fritz!Box password
     * @return                    SOAP client
     */
    public function getClient($location, $service)
    {    
        if (!preg_match("/https/", $this->ip)) {
           $port = '49000';
        }
        else {
           $port = '49443';
        }
        $this->client = new \SoapClient(
                            null,
                            array(
                                'location'   => $this->ip.":".$port."/upnp/control/".$location,
                                'uri'        => "urn:dslforum-org:service:".$service,
                                'noroot'     => true,
                                'login'      => $this->user,
                                'password'   => $this->password,
                                'trace'      => true,
                                'exceptions' => false
                            ));
    }

    /**
     * disassemble the soapfault object to get more detailed
     * error information into $errorCode and $errorText
     * @param   soapfault  $phoneBookID
     */
    private function getErrorData ($result)
    {
        $this->errorCode = isset($result->detail->UPnPError->errorCode) ? $result->detail->UPnPError->errorCode : $result->faultcode; 
        $this->errorText = isset($result->detail->UPnPError->errorDescription) ? $result->detail->UPnPError->errorDescription : $result->faultstring;
    }

    /**
     * delivers a list of phonebooks implemented on the FRITZ!Box 
     * requires a client of location 'x_contact' and service 'X_AVM-DE_OnTel:1'
     * @return  string  list of phonebook index like '0,1,2,3' or
     *                  402 (Invalid arguments Any)
     *                  820 (Internal Error)
     */
    public function getPhonebookList ()
    {
        $result = $this->client->GetPhonebookList();
        if (is_soap_fault($result)) {
            $errorData = $this->getErrorData($result);
            error_log(sprintf("Error: %s (%s)! Could not access to phonebooks on FRITZ!Box", $this->errorCode, $this->errorText));
            return;
        }
        return $result;
    }

    /**
     * delivers the content of a designated phonebook
     * requires a client of location 'x_contact' and service 'X_AVM-DE_OnTel:1'
     * @param    integer      $phoneBookID
     * @return   XML          phonebook or
     *                        402 (Invalid arguments)
     *                        713 (Invalid array index)
     *                        820 (Internal Error)
     * 
     * The following URL parameters are also supported but not coded yet:
     * Parameter name    Type          Remarks
     * ---------------------------------------------------------------------------------------
     * pbid              number        Phonebook ID
     * max               number        maximum number of entries in call list, default 999
     * sid               hex-string    Session ID for authentication
     * timestamp         number        value from timestamp tag, to get the phonebook content
     *                                 only if last modification was made after this timestamp
     * tr064sid          string        Session ID for authentication (obsolete)
     */
    public function getPhonebook ($phoneBookID = 0)
    {
        $result = $this->client->GetPhonebook(new \SoapParam($phoneBookID, 'NewPhonebookID'));
        if (is_soap_fault($result)) {
            $errorData = $this->getErrorData($result);
            error_log(sprintf("Error: %s (%s)! Could not get the phonebook %s", $this->errorCode, $this->errorText, $phoneBookID));
            return;
        }
        $phonebook = simplexml_load_file($result['NewPhonebookURL']);
        $phonebook->asXML();
        return $phonebook;
    }
    
    /**
     * add an new entry in the designated phonebook
     * requires a client of location 'x_contact' and service 'X_AVM-DE_OnTel:1'
     * @param   string   $name
     * @param   integer  $phoneBookID
     * @return           null or
     *                   402 (Invalid arguments)
     *                   820 (Internal Error)
     */
    public function addPhonebook ($name, $phoneBookID = null)
    {   
        $result = $this->client->AddPhonebook(
                    new \SoapParam($name, 'NewPhonebookName'),
                    new \SoapParam($phoneBookID, 'NewPhonebookExtraID')
                    );
        if (is_soap_fault($result)) {
            $errorData = $this->getErrorData($result);
            error_log(sprintf("Error: %s (%s)! Could not add the new phonebook %s", $this->errorCode, $this->errorText, $name));
            return;
        }
        return $result;
    }

    /**
     * deletes a designated phonebook
     * requires a client of location 'x_contact' and service 'X_AVM-DE_OnTel:1'
     * @param    integer      $phoneBookID
     * @return                null or
     *                        402 (Invalid arguments)
     *                        713 (Invalid array index)
     *                        820 (Internal Error)
     */
    public function delPhonebook ($phoneBookID)
    {
        $result = $this->client->DeletePhonebook(new \SoapParam($phoneBookID, 'NewPhonebookID'));
        if (is_soap_fault($result)) {
            $errorData = $this->getErrorData($result);
            error_log(sprintf("Error: %s (%s)! Could not delete the phonebook with index %s", $this->errorCode, $this->errorText, $phoneBookID));
            return;
        }
        return $result;
    }

    /**
     * add an new entry in the designated phonebook
     * requires a client of location 'x_contact' and service 'X_AVM-DE_OnTel:1'
     * @param    integer      $phoneBookID
     * @return                null or
     *                        402 (Invalid arguments)
     *                        600 (Argument invalid)
     *                        713 (Invalid array index)
     *                        820 (Internal Error)
     */
    public function setPhonebookEntry ($entry, $phoneBookID = 0)
    {   
        $result = $this->client->SetPhonebookEntry(
                    new \SoapParam($phoneBookID, 'NewPhonebookID'),
                    new \SoapParam($entry, 'NewPhonebookEntryData'),
                    new \SoapParam(null, 'NewPhonebookEntryID')                           // add new entry
                    );
        if (is_soap_fault($result)) {
            $errorData = $this->getErrorData($result);
            error_log(sprintf("Error: %s (%s)! Could not add the new entry to the phonebook with index %s", $this->errorCode, $this->errorText, $phoneBookID));
            return;
        }
        return $result;
    }
}
?>