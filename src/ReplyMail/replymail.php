<?php

namespace Andig\ReplyMail;

/* class replymail delivers a simple function based on PHPMailer
 *
 * Author: BlackSenator
 * https://github.com/BlackSenator
 *
 * This script is an extension for carddav2fb from andig
 * Dependency: PHPMailer is additional instaled via composer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class replymail

{
    private $mail;


    public function __construct ($account)
    {              
        date_default_timezone_set('Etc/UTC');
                
        $this->mail = new PHPMailer;                                    // create a new PHPMailer instance
        $this->mail->isSMTP();                                          // tell PHPMailer to use SMTP
        $this->mail->SMTPDebug  = $account['debug'];
        $this->mail->Host       = $account['url'];                      // set the hostname of the mail server
        $this->mail->Port       = $account['port'];                     // set the SMTP port number - likely to be 25, 465 or 587
        $this->mail->SMTPSecure = $account['secure'];
        $this->mail->SMTPAuth   = true;                                 // whether to use SMTP authentication
        $this->mail->Username   = $account['user'];                     // username to use for SMTP authentication
        $this->mail->Password   = $account['password'];                 // password to use for SMTP authentication
        $this->mail->setFrom($account['user'], 'carddav2fb');           // set who the message is to be sent fromly-to address
        $this->mail->addAddress($account['receiver']);                  // set who the message is to be sent to
    }

    public function sendReply ($phonebook, $attachment, $label)
    {
        $this->mail->clearAttachments();                                // initialize
        $this->mail->Subject = 'Newer contact was found in phonebook: ' . $phonebook;  //Set the subject line
        $this->mail->Body = 'Add this contact to your CardDAV server:';
        $this->mail->addStringAttachment($attachment, $label, 'quoted-printable', 'text/x-vcard');

        if (!$this->mail->send()) {                                     // send the message, check for errors
            echo 'Mailer Error: ' . $mail->ErrorInfo;
            return false;
        }
        else
        {
            return true;
        }
    }
}
?>