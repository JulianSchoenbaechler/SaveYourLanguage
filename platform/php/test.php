<?php
/**
 * Save Your Language PHP engine configuration
 *
 * A class providing constants and static configuration variables.
 *
 * @author      Julian Schoenbaechler
 * @copyright   (c) 2017 University of the Arts, Zurich
 * @since       v0.0.1
 * @link        https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */

    namespace SaveYourLanguage;

    // Error reporting
	error_reporting(1);
	ini_set('display_errors', E_ALL);

    // Include library files
    require_once 'classes/db/DatabaseController.php';
    //require_once 'classes/login/Session.php';
    //require_once 'classes/login/Login.php';
    //require_once 'classes/login/Crypt.php';
    //require_once 'classes/login/Captcha.php';
    //require_once 'classes/syl/Starfield.php';
    require_once 'classes/syl/TranscriptionHandling.php';

    use SaveYourLanguage\Database\DatabaseController;
    //use SaveYourLanguage\Login\Session;
    //use SaveYourLanguage\Login\Login;
    //use SaveYourLanguage\Login\Crypt;
    //use SaveYourLanguage\Login\Captcha;
    //use SaveYourLanguage\Playfield\Starfield;
    use SaveYourLanguage\Transcriptions\TranscriptionHandling;
    /*
    $output = 0;

    $session = new Session('mySession');
    $session->startSecureSession();

    if (!isset($_SESSION['test']))
        $_SESSION['test'] = 82;
    else
        $output = $_SESSION['test'];

    //$session->destroySession();

    echo $output;
    */

    //echo Crypt::generateCryptoKey();
    /*
    $key = base64_encode('akey');
    $password = 'This is my password';
    $encrypted = Crypt::encryptBlowfish($password, $key);
    $decrypted = Crypt::decryptBlowfish($encrypted, base64_encode('akey'));

    echo $key.'<br />';
    echo $password.'<br />';
    echo base64_decode($encrypted).'<br />';
    echo $decrypted.'<br />';
    echo $password === $decrypted ? 'true' : 'false';
    */

    //Login::registerUser('julius', 'securepassword123', 'julian.sch@vtxnet.ch', false, 'Holzchopf', 'Irgendwo im Nirgendwo 23z', '+3385836723678');
    //$login = Login::loginUser('marcello', 'securepassword123'); echo $login;
    //$login = Login::isUserLoggedIn();
    //Login::logoutUser();

    /*
    $captcha = new Captcha('myCaptcha');
    $image = $captcha->createCaptchaImage($captcha->generateCaptcha(), mt_rand(200, 400), mt_rand(50, 150));

    header('Content-type: image/png');
    imagepng($image);
    imagedestroy($image);
    */
    //Starfield::generateNew(1000, 1000, 196);
    /*
    echo '<pre>';
    Starfield::userSaveStarfield(1);
    echo '</pre>';
    */
    /* Mail...
    const SMTP_HOST                 = 'login-124.hoststar.ch';
    const SMTP_PORT                 = 587;
    const SMTP_LOGIN                = 'info@saveyourlanguage.com';
    const SMTP_PASSWORD             = 'ryp-e(d?KDtfcO-6S3)6XQ7?%';
    */
    //echo 'finished';



    echo '<pre>';

    $link = DatabaseController::connect();
    $dc = new DatabaseController($link);

    for ($i = 1; $i <= 400; $i++)
        $dc->insertRow('snippets', array('id' => $i, 'path' => 'a/path/to/file.mp3', 'count' => 0, 'done' => 0));

    echo '</pre>';
    exit();
