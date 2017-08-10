<?php
/*
 * Mail object
 *
 * A class for sending formatted mails through a smtp server as far as supported.
 *
 * Author           Julian Schoenbaechler
 * Copyright        (c) 2017 University of the Arts, Zurich
 * Included since   v0.0.1
 * Repository       https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */
namespace SaveYourLanguage\Login;

// Include configuration
require_once dirname(__FILE__).'/../Config.php';

// Include PHPMailer package (https://github.com/PHPMailer/PHPMailer)
require_once dirname(__FILE__).'/PHPMailer/class.phpmailer.php';
require_once dirname(__FILE__).'/PHPMailer/class.smtp.php';

use SaveYourLanguage\Config;
use PHPMailer;


class Mail
{
    // TODO: set to false for production usage
    const NON_PRODUCTION = true;
    
    protected $mailer;
    protected $savedContent;
    
    public function __construct()
    {
        if (Mail::NON_PRODUCTION)
            return;
        
        $this->mailer = new PHPMailer();
        $this->mailer->isSMTP();
        $this->mailer->SMTPDebug = 0; // For production
        //$this->mailer->Debugoutput = 'html';
        $this->mailer->Host = Config::SMTP_HOST;
        $this->mailer->Port = Config::SMTP_PORT;
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = Config::SMTP_LOGIN;
        $this->mailer->Password = Config::SMTP_PASSWORD;
        $this->mailer->setFrom('info@saveyourlanguage.com', 'SaveYourLanguage AutoMailer');
    }
    
    // Add a recipient
    public function addRecipient($email, $name)
    {
        // Check arguments
        if (!is_string($email)) {
			trigger_error("[Login] 'addRecipient' expected Argument 0 to be String", E_USER_WARNING);
		}
        if (!is_string($name)) {
			trigger_error("[Login] 'addRecipient' expected Argument 1 to be String", E_USER_WARNING);
		}
        
        if (Mail::NON_PRODUCTION)
            return;
        
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            $this->mailer->addAddress($email, $name);
    }
    
    // Set a subject
    public function setSubject($subject)
    {
        // Check arguments
        if (!is_string($subject)) {
			trigger_error("[Login] 'setSubject' expected Argument 0 to be String", E_USER_WARNING);
		}
        
        if (Mail::NON_PRODUCTION)
            return;
        
        $subject = htmlspecialchars(trim($subject));
        
        if (strlen($subject) > 3)
            $this->mailer->Subject = $subject;
    }
    
    // Set HTML message content
    // FILTER DATA -> PREVENT XSS!
    public function setContent($htmlContent)
    {
        // Check arguments
        if (!is_string($htmlContent)) {
			trigger_error("[Login] 'setContent' expected Argument 0 to be String", E_USER_WARNING);
		}
        
        if (Mail::NON_PRODUCTION) {
            $this->savedContent = $htmlContent;
            return;
        }
        
        $this->mailer->msgHTML($htmlContent);
    }
    
    // Send email
    // Return tru on success, otherwise an error message
    public function send()
    {
        if (Mail::NON_PRODUCTION)
            return;
        
        if (!$this->mailer->send())
            return $this->mailer->ErrorInfo;
        else
            return true;
    }
    
    // Get saved message when Mail object in non-production environment
    public function getNonProductionContent()
    {
        return $this->savedContent !== null ? $this->savedContent : '';
    }
}
