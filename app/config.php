<?php

/**
 * MiniDesk - Configuration
 *
 * ⚠️ BUG #1 : Ce fichier est utilisé pour initialiser la connexion PDO.
 *       Il contient au moins une mauvaise pratique de sécurité à identifier et corriger.
 */

define('DB_HOST', getenv('DB_HOST') ?: 'db');
define('DB_NAME', getenv('DB_NAME') ?: 'minidesk');
define('DB_USER', getenv('DB_USER') ?: 'minidesk_user');

define('DB_PASS', getenv('DB_PASS') ?: '');

define('APP_ENV', getenv('APP_ENV') ?: 'production');

/**
 * Retourne une connexion PDO partagée (singleton simple).
 */
function get_db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    return $pdo;
}
