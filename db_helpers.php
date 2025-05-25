<?php

require_once 'logs/singletonLog.php';

$referrer = $_SERVER['HTTP_REFERER'] ?? 'Direkter Aufruf';
// Logger-Initialisierung
#SingletonLog::getInstance()->log("db_helpers.php : Gestartet von " . $referrer);

// Stellt eine Singleton-Datenbankverbindung her und gibt sie zurück
function getDbConnection()
{
    static $conn;
    if (!$conn) {
        try {
            $configFile = 'configs/db_config.json';
            // Prüft, ob die Konfigurationsdatei existiert
            if (!file_exists($configFile)) {
                throw new Exception("Database config file not found");
            }
            #SingletonLog::getInstance()->log("db_helpers.php : Lese Datenbank-Konfigurationsdatei");

            $config = json_decode(file_get_contents($configFile), true);

            // Überprüft, ob das JSON korrekt dekodiert wurde
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON in config file: " . json_last_error_msg());
            }
            #SingletonLog::getInstance()->log("db_helpers.php : JSON-Konfiguration erfolgreich geladen");

            // Prüft, ob die Datenbank-Konfiguration vorhanden ist
            if (!isset($config['database'])) {
                throw new Exception("Missing database configuration");
            }

            $db = $config['database'];
            $requiredKeys = ['Host', 'Port', 'DB', 'User', 'Password'];
            foreach ($requiredKeys as $key) {
                if (!isset($db[$key])) {
                    throw new Exception("Missing required database key: $key");
                }
            }
            #SingletonLog::getInstance()->log("db_helpers.php : Alle erforderlichen Datenbank-Keys vorhanden");

            // Erstellt den DSN-String
            $dsn = "pgsql:host={$db['Host']};port={$db['Port']};dbname={$db['DB']}";

            // Erstellt die Verbindung
            $conn = new PDO($dsn, $db['User'], $db['Password']);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            #SingletonLog::getInstance()->log("db_helpers.php : Datenbankverbindung wurde aufgebaut");
        } catch (Exception $e) {
            die("<div class='alert alert-danger'>Connection error: " . $e->getMessage() . "</div>");
        }
    }
    #SingletonLog::getInstance()->log("db_helpers.php : Datenbankverbindung wird zurückgegeben");
    return $conn;
}

// Validiert die übergebenen Daten anhand der Tabellenkonfiguration
function validateData($data, $tableName)
{
    #SingletonLog::getInstance()->log("db_helpers.php : Datenvalidierung gestartet für Tabelle $tableName");
    $config = json_decode(file_get_contents('configs/db_config.json'), true);
    $tableConfig = $config['tables'][$tableName]['columns'];
    $errors = [];

    foreach ($tableConfig as $column => $rules) {
        // Prüft, ob ein erforderliches Feld fehlt
        if ($rules['required'] && !isset($data[$column])) {
            $errors[] = "$column is required";
            continue;
        }

        if (isset($data[$column])) {
            $value = $data[$column];
            switch ($rules['type']) {
                case 'integer':
                    if (!is_numeric($value)) $errors[] = "$column must be a number";
                    if (isset($rules['min']) && $value < $rules['min']) $errors[] = "$column must be at least {$rules['min']}";
                    if (isset($rules['max']) && $value > $rules['max']) $errors[] = "$column cannot exceed {$rules['max']}";
                    break;
                case 'varchar(255)':
                    if (strlen($value) > 255) $errors[] = "$column cannot exceed 255 characters";
                    break;
            }
        }
    }
    #SingletonLog::getInstance()->log("db_helpers.php : Datenvalidierung abgeschlossen mit Fehlern: " . json_encode($errors));
    return $errors;
}

// Führt die gewünschte Datenbankoperation (GET, POST, PATCH, DELETE) anhand der API-Konfiguration aus
function handleDatabaseOperation($apiConfig, $requestData, $pathParams)
{
    #SingletonLog::getInstance()->log("db_helpers.php : handleDatabaseOperation gestartet");
    $conn = getDbConnection();
    $method = $apiConfig['method'];
    $table = $apiConfig['table'];
    $query = $apiConfig['query'];

    #SingletonLog::getInstance()->log("db_helpers.php : API-Konfiguration geladen: " . json_encode($apiConfig));
    #SingletonLog::getInstance()->log("db_helpers.php : pathParams: " . json_encode($pathParams));
    #SingletonLog::getInstance()->log("db_helpers.php : requestData: " . json_encode($requestData));
    // Parameter aus Request zuordnen
    $params = [];
    foreach ($apiConfig['param_map'] as $param => $source) {
        $parts = explode('.', $source, 2);
        $sourceType = $parts[0];
        $sourceKey = $parts[1] ?? $param;
        #SingletonLog::getInstance()->log("db_helpers.php : Mapping-Parameter $param aus $sourceType.$sourceKey");
        switch ($sourceType) {
            case 'path':
                $params[$param] = $pathParams[$sourceKey] ?? null; // Direkter Zugriff
                break;
            case 'query':
                $params[$param] = $_GET[$sourceKey] ?? null;
                break;
            case 'body':
                $params[$param] = $requestData[$sourceKey] ?? null;
                break;
        }
    }
    #SingletonLog::getInstance()->log("db_helpers.php : Zuordnung der Parameter abgeschlossen: " . json_encode($params));

    try {
        $stmt = $conn->prepare($query);
        #SingletonLog::getInstance()->log("db_helpers.php : Statement vorbereitet mit Query: $query");
        #SingletonLog::getInstance()->log("db_helpers.php : Statement-Parameter: " . json_encode($params));
        // Validierung und Ausführung je nach Methode
        switch ($method) {
            case 'GET':
                #SingletonLog::getInstance()->log("db_helpers.php : GET-Request mit Parametern: " . json_encode($params));
                $stmt->execute($params);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC); // Ergebnis speichern
                #SingletonLog::getInstance()->log("db_helpers.php : GET-Ergebnis: " . json_encode($result));
                return $result;
            case 'POST':
                // Prüft, ob alle erforderlichen Felder vorhanden sind
                $requiredFields = getTableRequiredFields($table);
                foreach ($requiredFields as $field) {
                    if (!isset($params[$field])) {
                        throw new Exception("Missing required field: $field");
                    }
                }
                $stmt->execute($params);
                #SingletonLog::getInstance()->log("db_helpers.php : POST erfolgreich, neue ID: " . $conn->lastInsertId());
                return ['id' => $conn->lastInsertId()];

            case 'PUT':
                // PUT wird aktuell nicht unterstützt
                #SingletonLog::getInstance()->log("db_helpers.php : PUT-Request nicht implementiert");
                return null;
            case 'PATCH':
                // Stellt sicher, dass ein WHERE-Statement vorhanden ist
                if (stripos($query, 'WHERE') === false) {
                    throw new Exception("UPDATE query requires WHERE clause");
                }
                $stmt->execute($params);
                #SingletonLog::getInstance()->log("db_helpers.php : PATCH erfolgreich, betroffene Zeilen: " . $stmt->rowCount());
                return ['affected_rows' => $stmt->rowCount()];

            case 'DELETE':
                // Stellt sicher, dass ein WHERE-Statement vorhanden ist
                if (stripos($query, 'WHERE') === false) {
                    throw new Exception("DELETE query requires WHERE clause");
                }
                $stmt->execute($params);
                #SingletonLog::getInstance()->log("db_helpers.php : DELETE erfolgreich, betroffene Zeilen: " . $stmt->rowCount());
                return ['affected_rows' => $stmt->rowCount()];

            default:
                throw new Exception("Unsupported method: $method");
        }
    } catch (PDOException $e) {
        #SingletonLog::getInstance()->log("db_helpers.php : Datenbankfehler: " . $e->getMessage());
        http_response_code(400);
        return ['error' => $e->getMessage()];
    }
}

/*
function handleDatabaseOperation($apiConfig, $requestData, $pathMatches) {
    try {
        $conn = getDbConnection();
        $params = [];

        // Map parameters from different sources
        foreach ($apiConfig['param_map'] as $param => $source) {
            list($sourceType, $sourceKey) = explode('.', $source);
            
            switch ($sourceType) {
                case 'path':
                    $params[$param] = $pathMatches[array_search("{".$sourceKey."}", explode('/', $apiConfig['endpoint']))] ?? null;
                    break;
                case 'query':
                    $params[$param] = $_GET[$sourceKey] ?? null;
                    break;
                case 'body':
                    $params[$param] = $requestData[$sourceKey] ?? null;
                    break;
            }
        }

        // Prepare and execute query
        $stmt = $conn->prepare($apiConfig['query']);
        $stmt->execute($params);

        // Return appropriate response
        switch ($apiConfig['method']) {
            case 'GET':
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            case 'POST':
                return ['id' => $conn->lastInsertId()];
            default:
                return ['affected_rows' => $stmt->rowCount()];
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        throw new Exception("Database operation failed");
    }
}
 */

// Gibt die erforderlichen Felder für eine Tabelle anhand der Konfiguration zurück
function getTableRequiredFields($tableName)
{
    #SingletonLog::getInstance()->log("db_helpers.php : getTableRequiredFields für $tableName");
    $dbConfig = json_decode(file_get_contents('configs/db_config.json'), true);
    return $dbConfig['tables'][$tableName]['required'] ?? [];
}
