<?php
/*
 * Save Your Language PHP engine configuration
 *
 * A class providing constants and static configuration variables.
 *
 * Author           Julian Schoenbaechler
 * Copyright        (c) 2017 University of the Arts, Zurich
 * Included since   v0.0.1
 * Repository       https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */
namespace SaveYourLanguage;

class Config
{
    // MySQL DB
    const DB_MYSQL_USERNAME         = 'root';
    const DB_MYSQL_PASSWORD         = 'root';
    const DB_MYSQL_HOST             = 'localhost';
    const DB_MYSQL_NAME             = 'SaveYourLanguage';
    const DB_MYSQL_PORT             = null;
    
    // User session
    const DEFAULT_ROLE              = 'member';
    const SECURE_CONNECTION         = false;        // For development only!!!
}
