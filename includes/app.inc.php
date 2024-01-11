<?php

// Config und Autoload einbinden
require_once('../includes/config.inc.php');

////////////////////////////
/// Slim
////////////////////////////

// Erstellen einer Slim-Instanz
$app = new \Slim\App();

////////////////////////////
/// Allgemeine Container
////////////////////////////

// Bereitstellen von Containern
$container = $app->getContainer();

// Monolog-Container
$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler('../logs/app.log');
    $logger->pushHandler($file_handler);
    return $logger;
};

// MeekroDB Container
$container['db'] = function ($c) {

    // DB-Daten aus .env laden
    DB::$user = $_ENV['DB_USER'];
    DB::$password = $_ENV['DB_PASSWD'];
    DB::$dbName = $_ENV['DB_NAME'];

    return new \MeekroDB();
};

////////////////////////////
/// Base64 Container
////////////////////////////

// Base 64 Encode mit URL-freundlicher Kodierung
$container['base64Enc'] = function ($c) {
    $encryptionKey = $_ENV['BASE64_CODE'];

    return function ($identifier) use ($encryptionKey) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($identifier ^ $encryptionKey));
    };
};

// Base 64 Decode mit URL-freundlicher Kodierung
$container['base64Dec'] = function ($c) {
    $encryptionKey = $_ENV['BASE64_CODE'];

    return function ($encryptedIdentifier) use ($encryptionKey) {
        // Füge ggf. fehlende Padding-Zeichen hinzu
        $padding = strlen($encryptedIdentifier) % 4;
        if ($padding !== 0) {
            $encryptedIdentifier .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(str_replace(['-', '_'], ['+', '/'], $encryptedIdentifier)) ^ $encryptionKey;
    };
};

////////////////////////////
/// Anlegen Container
////////////////////////////

$container['identGenerator'] = function ($c) {
    // Lade alle möglichen Zeichen für den Identifier
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    $length = 48;

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
};