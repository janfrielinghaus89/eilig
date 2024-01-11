<?php
// Config einbinden
require_once('../includes/config.inc.php');

$app->get('/cleanup', function ($request, $response, array $args) {
    // Config einbinden
    require_once('../includes/config.inc.php');

    // Logger und MeekroDB Container abrufen
    $logger = $this->get('logger');
    $db = $this->get('db');

    // Aktuelles Datum und Uhrzeit
    $currentDateTime = date('Y-m-d H:i:s');

    // SQL-Abfrage, um Datensätze mit abgelaufenem Ablaufdatum zu löschen
    $sql = "DELETE FROM {$_ENV['DB_TABLE']} WHERE {$_ENV['DB_DATE']} < '{$currentDateTime}'";

    // Ausführen der SQL-Abfrage
    DB::query($sql);

    // Log-Eintrag erstellen
    $logger->info('Datensätze mit abgelaufenem Ablaufdatum wurden gelöscht.');

    // Antwort senden (optional)
    return $response->withJson(['message' => 'Cleanup abgeschlossen']);
});

$app->run();