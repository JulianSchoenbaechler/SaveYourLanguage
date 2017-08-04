<?php
/**
 * Captcha image
 *
 * Generates a captcha image as response.
 * Get arguments in request:
 * name     = The name for the session variable
 * width    = The width of the captcha image in px
 * height   = the height of the captcha image in px
 *
 * @author      Julian Schoenbaechler
 * @copyright   (c) 2017 University of the Arts, Zurich
 * @since       v0.0.1
 * @link        https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */
namespace SaveYourLanguage;

// Disable error reporting
error_reporting(0);
ini_set('display_errors', 0);

// Include library files
require_once 'classes/login/Captcha.php';

use SaveYourLanguage\Login\Captcha;

$name = isset($_GET['name']) ? htmlspecialchars(trim($_GET['name']), ENT_QUOTES, 'UTF-8') : 'main';
$x = isset($_GET['width']) ? intval(trim($_GET['width'])) : 300;
$y = isset($_GET['height']) ? intval(trim($_GET['height'])) : 100;

$captcha = new Captcha($name);
$image = $captcha->createCaptchaImage($captcha->generateCaptcha(), $x, $y);

// Modify header
header('Content-type: image/png');
imagepng($image);
imagedestroy($image);

exit();
