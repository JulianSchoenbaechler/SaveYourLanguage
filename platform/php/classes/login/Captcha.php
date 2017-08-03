<?php
/*
 * Captcha class
 *
 * A class for captcha verification functions.
 *
 * Author           Julian Schoenbaechler
 * Copyright        (c) 2017 University of the Arts, Zurich
 * Included since   v0.0.1
 * Repository       https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */
namespace SaveYourLanguage\Login;

// Include configuration
require_once dirname(__FILE__).'/Session.php';


class Captcha
{
    // Properties
    protected $name;
    protected $session;
    protected $currentCaptcha;
    protected $randomString = 'ABCDEFGHJKLMNPQRSTUVWXYZ123456789';
    
    // Constructor
    public function __construct($name)
    {
        // Name this captcha verification (also used in session)
        $this->name = trim($name);
        
        $this->session = new Session('SaveYourLanguage');
        $this->session->startSecureSession();
        $this->currentCaptcha = isset($_SESSION['syl']['captcha_'.$this->name]) == true ? $_SESSION['syl']['captcha_'.$this->name] : null;
        $this->session->closeSession();
    }
    
    // Verify a previously generated captcha code
    public function verifyCaptcha($captchaCode)
    {
        $captchaCode = trim($captchaCode);
        
        // Captcha does not exist?
        if ($this->currentCaptcha === null)
            return false;
        
        // Captcha code too short
        if (strlen($captchaCode) < 3)
            return false;
        
        // Start session
        $this->session->startSecureSession();
        
        // Captcha expired
        if ((int)$_SESSION['syl']['captcha_'.$this->name.'_expire'] < time())
            return false;
        
        // Check captcha code
        if (password_verify($captchaCode, $_SESSION['syl']['captcha_'.$this->name])) {
            
            $this->session->closeSession();
            return true;
        
        } else {
            
            $this->session->closeSession();
            return false;
        
        }
    }
    
    // Generate a new captcha code with a specified length
    public function generateCaptcha($length = 5)
    {
        $captchaCode = '';
        
        // Generate random captcha code
        for ($i = 0; $i < $length; $i++) {
            
            $captchaCode .= $this->randomString[mt_rand(0, strlen($this->randomString) - 1)];
            
        }
        
        // Start session
        $this->session->startSecureSession();
        
        // Write session
        $_SESSION['syl']['captcha_'.$this->name.'_expire'] = time() + 200;
        $_SESSION['syl']['captcha_'.$this->name] = $captchaCode;
        
        // Close session
        $this->session->closeSession();
        
        return $captchaCode;
    }
    
    // Creates a captcha image out of a given code
    // Returns an image resource identifier on success, false on errors
    public function createCaptchaImage($code, $x = 300, $y = 100)
    {
        $code = trim($code);
        
        // Code too short
        if (strlen($code) < 3)
            return false;
        
        // Create image
        $background = imagecreatefromjpeg(dirname(__FILE__).'/../../../img/captcha-background.jpg');
        $image =  imagecreatetruecolor($x, $y);
        $angle = mt_rand(-10, 10);
        $textColor = imagecolorallocate($image, 180, 180, 180);
        
        // Background
        imagesettile($image, $background);
        imagefilledrectangle($image, 0, 0, $x - 1, $y - 1, IMG_COLOR_TILED);
        
        // Text
        $textBBox = $this->calculateTextBox(
            $y * 0.4,
            $angle,
            dirname(__FILE__).'/../../../font/quick-end-jerk.ttf',
            $code
        );
        
        imagettftext(
            $image,
            $y * 0.4,
            $angle,
            $textBBox['left'] + (($x - $textBBox['width']) / 2),
            $textBBox['top'] + (($y - $textBBox['height']) / 2),
            $textColor,
            dirname(__FILE__).'/../../../font/quick-end-jerk.ttf',
            $code
        );
        
        // Lines
        for ($i = mt_rand(4, 20); $i > 0; $i--) {
            
            $lineColor = imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imageline($image, mt_rand(0, $x), mt_rand(0, $y), mt_rand(0, $x), mt_rand(0, $y), $lineColor);
            
        }
        
        return $image;
    }
    
    // Returning the *exact* bounding box for text placement in image resource
    protected function calculateTextBox($fontSize, $fontAngle, $fontFile, $text)
    {
        /*
         * The function returns an associative array with these keys:
         * left, top:  coordinates you will pass to imagettftext
         * width, height: dimension of the image you have to create
        */
        $rect = imagettfbbox($fontSize, $fontAngle, $fontFile, $text);
        $minX = min(array($rect[0], $rect[2], $rect[4], $rect[6]));
        $maxX = max(array($rect[0], $rect[2], $rect[4], $rect[6]));
        $minY = min(array($rect[1], $rect[3], $rect[5], $rect[7]));
        $maxY = max(array($rect[1], $rect[3], $rect[5], $rect[7]));
       
        return array(
            'left'   => abs($minX) - 1,
            'top'    => abs($minY) - 1,
            'width'  => $maxX - $minX,
            'height' => $maxY - $minY,
            'box'    => $rect
        );
    }
}
