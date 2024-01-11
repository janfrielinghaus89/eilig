<?php

// Request und Response
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Autoload
require '../vendor/autoload.php';

// Requirements laden
require_once('app.inc.php');

// .env laden
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();
