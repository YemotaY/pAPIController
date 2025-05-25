<?php

// Aktualisiert die API-Metadaten und protokolliert den Verlauf
function updateApiMetadata($api, $duration, $isSuccess)
{
    // Pfad zur Metadaten-Datei
    $metadataFile = __DIR__ . '/configs/api_metadata.json';
    // Öffnet die Datei zum Lesen/Schreiben, erstellt sie falls nicht vorhanden
    $fp = fopen($metadataFile, 'c+');

    if (!$fp) {
        // Loggt einen Fehler, falls die Datei nicht geöffnet werden kann
        SingletonLog::getInstance()->log("Fehler beim Öffnen der Metadaten-Datei: $metadataFile");
        return;
    }

    // Versucht, die Datei exklusiv zu sperren
    if (flock($fp, LOCK_EX)) {
        $metadata = [];
        $filesize = filesize($metadataFile);
        if ($filesize > 0) {
            rewind($fp);
            $content = fread($fp, $filesize);
            // Dekodiert den JSON-Inhalt oder initialisiert ein leeres Array
            $metadata = json_decode($content, true) ?: [];
        }

        $found = false;
        // Durchläuft alle Einträge, um die passende API zu finden
        foreach ($metadata as &$entry) {
            if ($entry['method'] === $api['method'] && $entry['endpoint'] === $api['endpoint']) {
                // Aktualisiert die Zähler und Zeiten für die gefundene API
                $entry['request_count'] += 1;
                $entry['total_response_time'] += $duration;
                $entry['average_response_time'] = $entry['total_response_time'] / $entry['request_count'];
                $entry['last_called'] = date('c');
                if ($isSuccess) {
                    $entry['success_count'] += 1;
                } else {
                    $entry['error_count'] += 1;
                }
                $found = true;
                break;
            }
        }

        // Falls die API noch nicht existiert, wird ein neuer Eintrag erstellt
        if (!$found) {
            $metadata[] = [
                'method' => $api['method'],
                'endpoint' => $api['endpoint'],
                'request_count' => 1,
                'success_count' => $isSuccess ? 1 : 0,
                'error_count' => $isSuccess ? 0 : 1,
                'total_response_time' => $duration,
                'average_response_time' => $duration,
                'last_called' => date('c'),
            ];
        }
        // Protokolliert einen historischen Eintrag
        $historyEntry = [
            'timestamp' => date('c'),
            'method' => $api['method'],
            'endpoint' => $api['endpoint'],
            'duration' => $duration,
            'success' => $isSuccess
        ];

        // Pfad zur History-Logdatei
        $historyLogPath = __DIR__ . '/configs/api_history.log';
        // Fügt den Eintrag als neue Zeile hinzu
        file_put_contents($historyLogPath, json_encode($historyEntry) . PHP_EOL, FILE_APPEND);
        // Überschreibt die Metadaten-Datei mit den neuen Daten
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($metadata, JSON_PRETTY_PRINT));
        // Gibt die Sperre wieder frei
        flock($fp, LOCK_UN);
    } else {
        // Loggt einen Fehler, falls die Datei nicht gesperrt werden kann
        SingletonLog::getInstance()->log("Konnte die Metadaten-Datei nicht sperren.");
    }

    // Schließt die Datei
    fclose($fp);
}

