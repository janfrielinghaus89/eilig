<?php
// config.inc.php einbinden
require_once('../includes/config.inc.php');

/** Create Entry */
$app->post('/', function ($request, $response, $args) {
// Request-Daten abrufen
    $data = $request->getParsedBody();
    $nachricht = $data['nachricht'];
    $ablauf = $data['ablauf'];

// Logger und MeekroDB Container abrufen
    $logger = $this->get('logger');
    $db = $this->get('db');

// Schleife für die Erstellung eines einzigartigen Identifiers
    $identifier = null;
    while (true) {
        $tempIdentifier = $this->get('identGenerator');

// Prüfen ob Identifier bereits in DB vorhanden
        $count = $db->count($_ENV['DB_TABLE'], [$_ENV['DB_IDENT'] => $tempIdentifier]);

        if ($count === 0) {
// Eindeutiger Identifier liegt vor
            $identifier = $tempIdentifier;
            break;
        }
    }

// Logging
    $logger->info('Anlegen: ' . $identifier . "\nNachricht: " . $nachricht . "\nAblauf: " . $ablauf);

// Nachricht in die Datenbank schreiben
    $result = $db->insert($_ENV['DB_TABLE'], [
        $_ENV['DB_IDENT'] => $identifier,
        $_ENV['DB_MSG']   => $nachricht,
        $_ENV['DB_DATE']  => $ablauf
    ]);

// Lade Base64 Encoder
    $base64Enc = $this->get('base64Enc');
    $encryptedIdentifier = $base64Enc($identifier);

    if ($result) {
// Erfolgreich
        $logger->info('Nachricht erfolgreich eingefügt', ['identifier' => $identifier]);

// Weiterleitung auf eine Link-Seite
// Lade die HTML-Vorlage und ersetze Platzhalter durch Daten
        $template = file_get_contents('success.html');

// Formatiere Datum und Uhrzeit in deutsches Format
        $dateTime = new DateTime($ablauf);
        $format = 'd.m.Y, H:i \U\h\r';
        $formatiertesDatum = $dateTime->format($format);

// Ersetze Platzhalter
        $template = str_replace('{{identifier}}', $encryptedIdentifier, $template);
        $template = str_replace('{{nachricht}}', $nachricht, $template);
        $template = str_replace('{{ablauf}}', $formatiertesDatum, $template);

// Gib die HTML-Seite aus
        echo $template;
    } else {
// Fehler beim Einfügen
        $logger->error('Fehler beim Einfügen der Nachricht', ['identifier' => $identifier]);
    }
});

/** Remove Entry */
$app->post('/remove', function ($request, $response, $args) {
    $identifier = $request->getParsedBody()['identifier'];

    // Logger und MeekroDB Container abrufen
    $logger = $this->get('logger');
    $db = $this->get('db');

    $logger->info('Löschen des Eintrags mit Identifier: {identifier}', ['identifier' => $identifier]);

    if (empty($identifier)) {
        // Ungültige Eingabe
        $logger->error('Ungültige Eingabe für den Identifier: {identifier}', ['identifier' => $identifier]);
        $response->getBody()->write('Ungültige Eingabe für den Identifier');
        return $response->withStatus(400); // HTTP-Statuscode für ungültige Anforderung
    } else {
        // Gültige Eingabe, Löschung durchführen
        DB::query("DELETE FROM %b WHERE %b = %s", $_ENV['DB_TABLE'], $_ENV['DB_IDENT'], $identifier);

        // Lade die HTML-Vorlage und ersetze Platzhalter durch Daten
        $template = file_get_contents('deleted.html');

        // Ersetze den Platzhalter in der Vorlage mit den Daten
        $template = str_replace('{{identifier}}', $identifier, $template);

        // Gib die HTML-Seite aus
        echo $template;
    }
});

#BaseCall
$app->get('/', function ($request, $response) {
   {
        $template = file_get_contents('addentry.html');

        $template = str_replace('$$apiKey$$', $_ENV['API_KEY'], $template);

        echo $template;
        return;
    }
});

#Liste
$app->post('/list', function ($request, $response) {

    // Logger und MeekroDB Container abrufen
    $logger = $this->get('logger');
    $db = $this->get('db');

    $logger->info('Abfrage der gesamten Datenbank');

    // Abrufen der Nachricht mit dem Identifier
    $results = DB::query("SELECT * FROM {$_ENV['DB_TABLE']}");

    // Überprüfen, ob Ergebnisse vorhanden sind
    if ($results) {
        $tableContent = '';
        foreach ($results as $result) {
            $identifier = $result[$_ENV['DB_IDENT']];
            $tableContent .= '<tr>';
            $tableContent .= '<td>' . $identifier . '</td>';
            $tableContent .= '<td>' . $result[$_ENV['DB_MSG']] . '</td>';
            $tableContent .= '<td>' . $result[$_ENV['DB_DATE']] . '</td>';
            $tableContent .= '<td>';
            $tableContent .= '<form method="post" action="remove">';
            $tableContent .= '<input type="hidden" name="identifier" value="' . $identifier . '">';
            $tableContent .= '<button type="submit">Löschen</button>';
            $tableContent .= '</form>';
            $tableContent .= '</td>';
            $tableContent .= '</tr>';
        }

        // Lade die HTML-Vorlage und ersetze Platzhalter durch Daten
        $template = file_get_contents('dbsummary.html');

        // Ersetze den Platzhalter in der Vorlage mit den Daten
        $template = str_replace('{{table_content}}', $tableContent, $template);

        // Gib die HTML-Seite aus
        echo $template;
    }
});

$app->get('/{identifier}', function ($request, $response, $args) {
    // Abruf des encrypted Identifiers aus der URL
    $encryptedIdentifier = $args['identifier'];

    // Logger und MeekroDB Container abrufen
    $logger = $this->get('logger');
    $db = $this->get('db');

    // Entschlüsseln des Identifiers
    $base64Dec = $this->get('base64Dec');
    $identifier = $base64Dec($encryptedIdentifier);

    $logger->info('Anzeigen - Identifier: {identifier}', ['identifier' => $identifier]);

    // Abrufen der Nachricht mit dem Identifier
    $results = $db->query("SELECT {$_ENV['DB_MSG']}, {$_ENV['DB_DATE']} FROM {$_ENV['DB_TABLE']} WHERE {$_ENV['DB_IDENT']}=%s", $identifier);

    // Loggen des Abrufs
    $logger->info('Abruf von /anzeigen/{identifier}', ['identifier' => $identifier]);

    // Überprüfen, ob Ergebnisse vorhanden sind
    if ($results) {
        foreach ($results as $row) {
            // Daten in JSON umwandeln
            $data = [
                $_ENV['DB_IDENT'] => $identifier,
                $_ENV['DB_MSG'] => $row['nachricht'],
                $_ENV['DB_DATE'] => date('Y-m-d H:i:s', strtotime($row['ablauf']))
            ];

            // Lade die HTML-Vorlage und ersetze Platzhalter durch Daten
            $template = file_get_contents('result.html');

            // Ersetze Platzhalter in der Vorlage mit den Daten aus dem JSON-Objekt
            $template = str_replace('{{identifier}}', $data['identifier'], $template);
            $template = str_replace('{{nachricht}}', $data['nachricht'], $template);
            $template = str_replace('{{ablauf}}', $data['ablauf'], $template);

            // Gib die HTML-Seite aus
            echo $template;

            // Löschen der angezeigten Nachricht
            $db->query("DELETE FROM {$_ENV['DB_TABLE']} WHERE {$_ENV['DB_IDENT']}=%s", $identifier);

        }
    } else {
        // Wenn keine Ergebnisse gefunden wurden, eine entsprechende Nachricht ausgeben

        // Wenn keine Ergebnisse gefunden wurden, eine entsprechende Nachricht ausgeben

        // Lade die HTML-Vorlage und ersetze Platzhalter durch Daten
        $template = file_get_contents('noresult.html');

        // Gib die HTML-Seite aus
        echo $template;
    }
});

$app->run();
