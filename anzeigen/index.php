<?php

    // Config einbinden
    require_once('../includes/config.inc.php');

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

                /* Comment entfernen für JSON Anzeige
                // JSON-Header setzen
                header('Content-Type: application/json');

                // JSON-Objekt an den Benutzer senden
                echo json_encode($data);
                */

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

            // Lade die HTML-Vorlage und ersetze Platzhalter durch Daten
            $template = file_get_contents('noresult.html');

            // Gib die HTML-Seite aus
            echo $template;
        }
    });

    $app->run();