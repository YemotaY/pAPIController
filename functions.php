<?php
require_once 'db_helpers.php';
require_once 'logs/singletonLog.php';

// Diese Funktion übernimmt die Benutzerregistrierung.
// Sie prüft die erforderlichen Felder, schreibt den Benutzer in die Datenbank und gibt das Ergebnis zurück.
function handleRegistration($params)
{
    $logger = SingletonLog::getInstance();
    #$logger->log("Starting user registration", ['params' => $params]);

    try {
        // Prüft, ob alle Pflichtfelder ausgefüllt sind
        $required = ['name', 'email', 'age'];
        $missing = array_filter($required, fn($f) => empty($params[$f]));

        if (!empty($missing)) {
            // Falls Felder fehlen, wird ein Fehler geloggt und eine Exception geworfen
            #$logger->log("Validation failed - missing fields", ['missing' => $missing]);
            throw new Exception("Fehlende Felder: " . implode(', ', $missing));
        }

        #$logger->log("Validation passed - all required fields present");

        // Stellt eine Verbindung zur Datenbank her
        $conn = getDbConnection();
        #$logger->log("Database connection established", [
        #    'db_host' => $conn->getAttribute(PDO::ATTR_DRIVER_NAME),
        #    'db_name' => $conn->getAttribute(PDO::ATTR_CONNECTION_STATUS)
        #]);

        // Bereitet das SQL-Statement für die Benutzeranlage vor
        $sql = "INSERT INTO users (name, email, age) VALUES (:name, :email, :age)";
        $stmt = $conn->prepare($sql);

        #$logger->log("Executing SQL statement", [
        #    'sql' => $sql,
        #    'params' => [
        #        'name' => $params['name'],
        #        'email' => $params['email'],
        #        'age' => (int)$params['age']
        #    ]
        #]);

        // Führt das SQL-Statement aus
        $stmt->execute([
            ':name' => $params['name'],
            ':email' => $params['email'],
            ':age' => (int)$params['age']
        ]);

        // Holt die ID des neu angelegten Benutzers
        $userId = $conn->lastInsertId();
        #$logger->log("User created successfully", ['user_id' => $userId]);

        // Gibt das Erfolgsergebnis zurück
        return [
            'status' => 'success',
            'user_id' => $userId
        ];
    } catch (PDOException $e) {
        // Fehlerbehandlung bei Datenbankfehlern
        #$logger->log("Database error during registration", [
        #    'error' => $e->getMessage(),
        #    'code' => $e->getCode(),
        #    'trace' => $e->getTraceAsString()
        #]);
        throw new Exception("Registrierung fehlgeschlagen: " . $e->getMessage());
    } catch (Exception $e) {
        // Fehlerbehandlung bei sonstigen Fehlern
        #$logger->log("Registration process error", [
        #    'error' => $e->getMessage(),
        #    'trace' => $e->getTraceAsString()
        #]);
        throw $e;
    }
}

// Diese Funktion erzeugt Statistiken für einen API-Endpunkt.
// Sie wertet das Logfile aus und berechnet z.B. Aufrufzahlen, Erfolgs-/Fehlerraten und Antwortzeiten.
function get_statistics($params)
{
    $logger = SingletonLog::getInstance();
    #$logger->log("Generating statistics", ['params' => $params]);
    try {
        // Parameter-Validierung
        $endpoint = $params['endpoint'] ?? null;
        $method = $params['method'] ?? null;
        $period = $params['period'] ?? 'all';

        if (!$endpoint || !$method) {
            // Fehlende Pflichtparameter werden geloggt und führen zu einer Exception
            #$logger->log("Missing required parameters", [
            #    'received' => $params,
            #    'required' => ['endpoint', 'method']
            #]);
            throw new Exception('Endpoint and method parameters are required.');
        }

        #$logger->log("Processing statistics request", [
        #    'endpoint' => $endpoint,
        #    'method' => $method,
        #    'period' => $period
        #]);

        // Zeitbereich für die Statistik berechnen
        $startTimestamp = 0;
        $periodMap = [
            'hour' => '-1 hour',
            'day' => '-1 day',
            'week' => '-1 week',
            'all' => 0
        ];

        if (!array_key_exists($period, $periodMap)) {
            #$logger->log("Invalid period specified", ['period' => $period]);
            throw new Exception("Invalid period specified: $period");
        }

        if ($period !== 'all') {
            $startTimestamp = strtotime($periodMap[$period]);
            #$logger->log("Time range calculated", [
            #    'period' => $period,
            #    'start_timestamp' => $startTimestamp,
            #    'start_date' => date('Y-m-d H:i:s', $startTimestamp)
            #]);
        }

        // Initialisiert die Statistikwerte
        $stats = [
            'request_count' => 0,
            'success_count' => 0,
            'error_count' => 0,
            'total_response_time' => 0,
            'average_response_time' => 0,
            'last_called' => null,
            'min_response_time' => null,
            'max_response_time' => null,
            'response_times' => []
        ];
        // Initialize 6 bins for 4-hour blocks
        $hourly_bins = array_fill(0, 6, 0);
        // Beispielhafte Stundenstatistik (kann angepasst werden)
        $stats['hourly_traffic'] = [
            '09:00' => 5,
            '10:00' => 17,
            '11:00' => 12
        ];
        $historyLogPath = __DIR__ . '/configs/api_history.log';
        #$logger->log("Processing history log", ['log_path' => $historyLogPath]);

        // Prüft, ob das Logfile existiert
        if (!file_exists($historyLogPath)) {
            #$logger->log("History log file not found", ['path' => $historyLogPath]);
            return $stats;
        }

        // Öffnet das Logfile zum Lesen
        $handle = fopen($historyLogPath, 'r');
        #$logger->log("Opened history log file", ['size' => filesize($historyLogPath)]);

        // Liest das Logfile zeilenweise aus
        while (($line = fgets($handle)) !== false) {
            $entry = json_decode(trim($line), true);
            if (!$entry) {
                // Überspringt ungültige Logzeilen
                #$logger->log("Skipping invalid log entry", ['raw_line' => $line]);
                continue;
            }
            $entryTime = strtotime($entry['timestamp']);
            if ($entryTime < $startTimestamp) {
                // Überspringt Einträge außerhalb des Zeitbereichs
                #$logger->log("Skipping entry outside time range", [
                #    'entry_time' => $entry['timestamp'],
                #    'start_time' => date('Y-m-d H:i:s', $startTimestamp)
                #]);
                continue;
            }
            // Prüft, ob der Eintrag zum gewünschten Endpoint und zur Methode passt
            if ($entry['endpoint'] === $endpoint && $entry['method'] === $method) {
                // Fix: If duration is > 60, treat as ms and convert to seconds
                $duration = $entry['duration'];
                if ($duration > 60) {
                    $duration = $duration / 1000;
                }
                $stats['request_count']++;
                $stats['total_response_time'] += $duration;
                $stats['response_times'][] = $duration;
                if ($stats['min_response_time'] === null || $duration < $stats['min_response_time']) {
                    $stats['min_response_time'] = $duration;
                }
                if ($stats['max_response_time'] === null || $duration > $stats['max_response_time']) {
                    $stats['max_response_time'] = $duration;
                }
                $entry['success'] ? $stats['success_count']++ : $stats['error_count']++;
                if (!$stats['last_called'] || $entryTime > strtotime($stats['last_called'])) {
                    $stats['last_called'] = $entry['timestamp'];
                }
                // --- Hourly traffic binning ---
                $hour = (int)date('G', $entryTime); // 0-23
                $bin = intdiv($hour, 4); // 0-5
                $hourly_bins[$bin]++;

                #$logger->log("Processed log entry", [
                #    'entry' => $entry,
                #    'current_stats' => $stats
                #]);
            }
        }
        fclose($handle);
        // Durchschnittliche Antwortzeit berechnen
        if ($stats['request_count'] > 0) {
            // Wenn die Dauer offensichtlich zu groß ist, wurde sie vermutlich in ms statt s gespeichert
            $avg = $stats['total_response_time'] / $stats['request_count'];
            if ($avg > 1000) { // z.B. 80000ms = 80s
                $avg = $avg / 1000;
                $stats['total_response_time'] = $stats['total_response_time'] / 1000;
                $stats['min_response_time'] = $stats['min_response_time'] / 1000;
                $stats['max_response_time'] = $stats['max_response_time'] / 1000;
                $stats['response_times'] = array_map(function($v){ return $v / 1000; }, $stats['response_times']);
            }
            $stats['average_response_time'] = $avg;
            #$logger->log("Calculated average response time", [
            #    'total_time' => $stats['total_response_time'],
            #    'request_count' => $stats['request_count'],
            #    'average' => $stats['average_response_time']
            #]);
        }
        // Set hourly_traffic as array for frontend (6 bins)
        $stats['hourly_traffic'] = $hourly_bins;

        #$logger->log("Final statistics calculated", ['stats' => $stats]);
        return $stats;
    } catch (Exception $e) {
        // Fehlerbehandlung bei Statistik-Berechnung
        #$logger->log("Statistics generation error", [
        #    'error' => $e->getMessage(),
        #    'trace' => $e->getTraceAsString()
        #]);
        throw $e;
    }
}
