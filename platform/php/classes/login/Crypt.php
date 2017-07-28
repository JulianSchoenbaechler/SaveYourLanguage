<?php
/*
 * Crypt class - everything hashing and cryptography related
 *
 * A class providing functions for a secure sensitive data encryption / decryption.
 *
 * Author           Julian Schoenbaechler
 * Copyright        (c) 2017 University of the Arts, Zurich
 * Included since   v0.0.1
 * Repository       https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */
namespace SaveYourLanguage\Login;


class Crypt
{
    // Encrypt data using AES 256 - CBC mode
    // Encrypted data will be base64 encoded
    public static function encryptAES256($data, $key)
    {
        $encryptionKey = base64_decode($key);
        
        // Initializing vector
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $encryptionKey, 0, $iv);
        
        return base64_encode($encrypted.'::'.$iv);
    }
    
    // Decrypt data using AES 256 - CBC mode
    // Data must contain an initializing vector and must be base64 encoded
    function decryptAES256($data, $key)
    {
        $encryptionKey = base64_decode($key);
        
        // Split the encrypted data from the IV
        $cryptStrings =  explode('::', base64_decode($data), 2);
        
        return openssl_decrypt($cryptStrings[0], 'AES-256-CBC', $encryptionKey, 0, $cryptStrings[1]);
    }
    
    // Generate AES 256 key
    // Key will be base64 encoded
    public static function generateAES256Key()
    {
        return base64_encode(openssl_random_pseudo_bytes(32));
    }
    
    // Encrypt data using Blowfish algorithm - CBC mode
    // Encrypted data will be base64 encoded
    public static function encryptBlowfish($data, $key)
    {
        $encryptionKey = base64_decode($key);
        
        // Initializing vector
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('BF-CBC'));
        
        $encrypted = openssl_encrypt($data, 'BF-CBC', $encryptionKey, 0, $iv);
        
        return base64_encode($encrypted.'::'.$iv);
    }
    
    // Decrypt data using Blowfish algorithm - CBC mode
    // Data must contain an initializing vector and must be base64 encoded
    function decryptBlowfish($data, $key)
    {
        $encryptionKey = base64_decode($key);
        
        // Split the encrypted data from the IV
        $cryptStrings =  explode('::', base64_decode($data), 2);
        
        return openssl_decrypt($cryptStrings[0], 'BF-CBC', $encryptionKey, 0, $cryptStrings[1]);
    }
}
